<?php

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/mail_service.php';

function getAppointmentFullDetails(PDO $pdo, int $appointmentId): ?array {
    $stmt = $pdo->prepare("SELECT r.id, r.kullanici_id, r.isletme_id, r.calisan_id, r.hizmet_id,
                                  r.randevu_zamani, r.durum,
                                  k.ad_soyad AS musteri_adi, k.eposta AS musteri_eposta, k.telefon AS musteri_telefon,
                                  i.isletme_adi, i.isletme_sahibi, i.eposta AS isletme_eposta, i.telefon AS isletme_telefon,
                                  i.sehir, i.ilce, i.tam_adres,
                                  h.hizmet_adi, h.sure_dakika, h.fiyat,
                                  c.ad_soyad AS calisan_adi,
                                  x.bitis_zamani, x.musteri_notu
                           FROM randevular r
                           INNER JOIN kullanicilar k ON k.id = r.kullanici_id
                           INNER JOIN isletmeler i ON i.id = r.isletme_id
                           LEFT JOIN hizmetler h ON h.id = r.hizmet_id
                           LEFT JOIN calisanlar c ON c.id = r.calisan_id
                           LEFT JOIN randevu_ek_bilgileri x ON x.randevu_id = r.id
                           WHERE r.id = ?");
    $stmt->execute([$appointmentId]);
    $apt = $stmt->fetch(PDO::FETCH_ASSOC);
    return $apt ?: null;
}

function isAppointmentNotificationSent(PDO $pdo, int $appointmentId, string $type): bool {
    $stmt = $pdo->prepare("SELECT id FROM randevu_bildirim_kayitlari WHERE randevu_id = ? AND bildirim_turu = ? LIMIT 1");
    $stmt->execute([$appointmentId, $type]);
    return (bool)$stmt->fetchColumn();
}

function recordAppointmentNotification(PDO $pdo, int $appointmentId, string $type, string $recipientEmail): bool {
    try {
        $stmt = $pdo->prepare("INSERT INTO randevu_bildirim_kayitlari (randevu_id, bildirim_turu, alici_eposta, gonderim_zamani)
                               VALUES (?, ?, ?, NOW())");
        return $stmt->execute([$appointmentId, $type, $recipientEmail]);
    } catch (PDOException $e) {
        return false;
    }
}

function notifyAppointmentCreated(int $appointmentId): void {
    global $pdo;
    $apt = getAppointmentFullDetails($pdo, $appointmentId);
    if (!$apt) return;

    $dateStr = date('d.m.Y', strtotime($apt['randevu_zamani']));
    $timeStr = date('H:i', strtotime($apt['randevu_zamani']));
    $staff = !empty($apt['calisan_adi']) ? $apt['calisan_adi'] : ('İşletme Sahibi (' . $apt['isletme_sahibi'] . ')');
    $service = !empty($apt['hizmet_adi']) ? $apt['hizmet_adi'] : 'Standart Hizmet';
    $price = !empty($apt['fiyat']) ? number_format((float)$apt['fiyat'], 2, ',', '.') . ' TL' : 'Belirtilmedi';

    if (!empty($apt['musteri_eposta'])) {
        $custBody = "
        <p>Merhaba <strong>" . htmlspecialchars($apt['musteri_adi'], ENT_QUOTES, 'UTF-8') . "</strong>,</p>
        <p><strong>" . htmlspecialchars($apt['isletme_adi'], ENT_QUOTES, 'UTF-8') . "</strong> için randevu talebiniz başarıyla alınmıştır. İşletme randevunuzu onayladığında ayrıca bilgilendirileceksiniz.</p>
        
        <table role='presentation' width='100%' style='background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; padding: 16px; margin: 20px 0; font-size: 14px;'>
            <tr><td style='padding: 6px 0; color: #64748B;'>İşletme:</td><td style='padding: 6px 0; font-weight: 700; text-align: right;'>" . htmlspecialchars($apt['isletme_adi'], ENT_QUOTES, 'UTF-8') . "</td></tr>
            <tr><td style='padding: 6px 0; color: #64748B;'>Tarih & Saat:</td><td style='padding: 6px 0; font-weight: 700; text-align: right;'>{$dateStr} — {$timeStr}</td></tr>
            <tr><td style='padding: 6px 0; color: #64748B;'>Hizmet:</td><td style='padding: 6px 0; font-weight: 700; text-align: right;'>" . htmlspecialchars($service, ENT_QUOTES, 'UTF-8') . "</td></tr>
            <tr><td style='padding: 6px 0; color: #64748B;'>Personel:</td><td style='padding: 6px 0; font-weight: 700; text-align: right;'>" . htmlspecialchars($staff, ENT_QUOTES, 'UTF-8') . "</td></tr>
            <tr><td style='padding: 6px 0; color: #64748B;'>Ücret:</td><td style='padding: 6px 0; font-weight: 700; text-align: right; color: #C28840;'>{$price}</td></tr>
            <tr><td style='padding: 6px 0; color: #64748B;'>Adres:</td><td style='padding: 6px 0; text-align: right; font-size: 12px;'>" . htmlspecialchars($apt['tam_adres'] ?? ($apt['sehir'] . ' / ' . $apt['ilce']), ENT_QUOTES, 'UTF-8') . "</td></tr>
        </table>
        ";

        $custHtml = renderEmailTemplate(
            'Randevu Talebiniz Alındı',
            $custBody,
            'Randevularımı Görüntüle',
            getSiteBaseUrl() . '/musteri/randevularim.php',
            'Randevunuza gelemeyecekseniz lütfen işletmeyi zor durumda bırakmamak için panelinizden iptal ediniz.'
        );

        enqueueMail(
            $apt['musteri_eposta'],
            $apt['musteri_adi'],
            "BİTİRAS — Randevu Talebiniz Alındı: {$apt['isletme_adi']}",
            $custHtml,
            "apt_created_cust_{$appointmentId}",
            'randevu_olusturuldu'
        );
    }

    if (!empty($apt['isletme_eposta'])) {
        $bizBody = "
        <p>Merhaba <strong>" . htmlspecialchars($apt['isletme_adi'], ENT_QUOTES, 'UTF-8') . "</strong>,</p>
        <p>İşletmeniz için yeni bir müşteri randevusu oluşturuldu. Lütfen işletme panelinizden onaylayınız veya detayları inceleyiniz.</p>
        
        <table role='presentation' width='100%' style='background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; padding: 16px; margin: 20px 0; font-size: 14px;'>
            <tr><td style='padding: 6px 0; color: #64748B;'>Müşteri:</td><td style='padding: 6px 0; font-weight: 700; text-align: right;'>" . htmlspecialchars($apt['musteri_adi'], ENT_QUOTES, 'UTF-8') . " (" . htmlspecialchars($apt['musteri_telefon'], ENT_QUOTES, 'UTF-8') . ")</td></tr>
            <tr><td style='padding: 6px 0; color: #64748B;'>Tarih & Saat:</td><td style='padding: 6px 0; font-weight: 700; text-align: right;'>{$dateStr} — {$timeStr}</td></tr>
            <tr><td style='padding: 6px 0; color: #64748B;'>Hizmet:</td><td style='padding: 6px 0; font-weight: 700; text-align: right;'>" . htmlspecialchars($service, ENT_QUOTES, 'UTF-8') . "</td></tr>
            <tr><td style='padding: 6px 0; color: #64748B;'>Personel:</td><td style='padding: 6px 0; font-weight: 700; text-align: right;'>" . htmlspecialchars($staff, ENT_QUOTES, 'UTF-8') . "</td></tr>
        </table>
        ";

        $bizHtml = renderEmailTemplate(
            'Yeni Randevu Talebi Var',
            $bizBody,
            'İşletme Paneline Git',
            getSiteBaseUrl() . '/isletme/randevular.php',
            'Randevuyu zamanında onaylamak müşteri memnuniyetinizi ve işletme puanınızı artırır.'
        );

        enqueueMail(
            $apt['isletme_eposta'],
            $apt['isletme_adi'],
            "BİTİRAS — Yeni Randevu Talebi: {$apt['musteri_adi']}",
            $bizHtml,
            "apt_created_biz_{$appointmentId}",
            'yeni_randevu_isletme'
        );
    }

    recordAppointmentNotification($pdo, $appointmentId, 'onay', $apt['musteri_eposta']);
}

function notifyAppointmentStatusChanged(int $appointmentId, string $newStatus, string $oldStatus): void {
    if ($newStatus === $oldStatus) return;
    global $pdo;
    $apt = getAppointmentFullDetails($pdo, $appointmentId);
    if (!$apt) return;

    $dateStr = date('d.m.Y', strtotime($apt['randevu_zamani']));
    $timeStr = date('H:i', strtotime($apt['randevu_zamani']));
    $service = !empty($apt['hizmet_adi']) ? $apt['hizmet_adi'] : 'Hizmet';

    if ($newStatus === 'confirmed') {
        $body = "
        <p>Merhaba <strong>" . htmlspecialchars($apt['musteri_adi'], ENT_QUOTES, 'UTF-8') . "</strong>,</p>
        <p>Harika haber! <strong>" . htmlspecialchars($apt['isletme_adi'], ENT_QUOTES, 'UTF-8') . "</strong> randevunuzu onayladı.</p>
        <div style='background: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 8px; padding: 16px; margin: 20px 0; font-size: 14px; color: #065F46;'>
            <strong>Randevu Zamanı:</strong> {$dateStr} günü saat {$timeStr}<br>
            <strong>Hizmet:</strong> " . htmlspecialchars($service, ENT_QUOTES, 'UTF-8') . "<br>
            <strong>Adres:</strong> " . htmlspecialchars($apt['tam_adres'] ?? ($apt['sehir'] . ' / ' . $apt['ilce']), ENT_QUOTES, 'UTF-8') . "
        </div>
        ";
        $html = renderEmailTemplate('Randevunuz Onaylandı!', $body, 'Detayları İncele', getSiteBaseUrl() . '/musteri/randevularim.php');
        enqueueMail($apt['musteri_eposta'], $apt['musteri_adi'], "BİTİRAS — Randevunuz Onaylandı: {$apt['isletme_adi']}", $html, "apt_conf_{$appointmentId}_" . time(), 'randevu_onay');
    } elseif ($newStatus === 'cancelled') {
        $body = "
        <p>Merhaba <strong>" . htmlspecialchars($apt['musteri_adi'], ENT_QUOTES, 'UTF-8') . "</strong>,</p>
        <p><strong>{$dateStr} — {$timeStr}</strong> tarihli randevunuz iptal edilmiştir.</p>
        <div style='background: #FEF2F2; border: 1px solid #FECACA; border-radius: 8px; padding: 16px; margin: 20px 0; font-size: 14px; color: #991B1B;'>
            <strong>İşletme:</strong> " . htmlspecialchars($apt['isletme_adi'], ENT_QUOTES, 'UTF-8') . "<br>
            <strong>Durum:</strong> İptal Edildi
        </div>
        ";
        $html = renderEmailTemplate('Randevu İptal Edildi', $body, 'Yeni Randevu Al', getSiteBaseUrl(), 'Farklı bir tarih veya saat için dilediğiniz zaman yeniden randevu oluşturabilirsiniz.');
        enqueueMail($apt['musteri_eposta'], $apt['musteri_adi'], "BİTİRAS — Randevunuz İptal Edildi", $html, "apt_canc_{$appointmentId}_" . time(), 'randevu_iptal');
        recordAppointmentNotification($pdo, $appointmentId, 'iptal', $apt['musteri_eposta']);
    } elseif ($newStatus === 'rejected') {
        $body = "
        <p>Merhaba <strong>" . htmlspecialchars($apt['musteri_adi'], ENT_QUOTES, 'UTF-8') . "</strong>,</p>
        <p><strong>{$dateStr} — {$timeStr}</strong> tarihindeki randevu talebiniz işletmenin uygun olmaması nedeniyle maalesef onaylanamamıştır.</p>
        ";
        $html = renderEmailTemplate('Randevu Talebiniz Onaylanamadı', $body, 'Başka Saat Seç', getSiteBaseUrl());
        enqueueMail($apt['musteri_eposta'], $apt['musteri_adi'], "BİTİRAS — Randevu Talebi Hakkında", $html, "apt_rej_{$appointmentId}_" . time(), 'randevu_red');
    }
}

function processAppointmentReminders(): array {
    global $pdo;
    $counts = ['reminders_24h' => 0, 'reminders_2h' => 0, 'business_reminders' => 0];

    $stmt24 = $pdo->query("SELECT r.id 
                           FROM randevular r 
                           WHERE r.durum IN ('confirmed', 'pending')
                             AND r.randevu_zamani BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 25 HOUR)
                             AND r.id NOT IN (SELECT randevu_id FROM randevu_bildirim_kayitlari WHERE bildirim_turu = 'hatirlatma_24h')");
    $ids24 = $stmt24->fetchAll(PDO::FETCH_COLUMN) ?: [];

    foreach ($ids24 as $aptId) {
        $apt = getAppointmentFullDetails($pdo, (int)$aptId);
        if (!$apt) continue;

        $dateStr = date('d.m.Y', strtotime($apt['randevu_zamani']));
        $timeStr = date('H:i', strtotime($apt['randevu_zamani']));

        if (!empty($apt['musteri_eposta'])) {
            $body = "
            <p>Merhaba <strong>" . htmlspecialchars($apt['musteri_adi'], ENT_QUOTES, 'UTF-8') . "</strong>,</p>
            <p>Yarın <strong>" . htmlspecialchars($apt['isletme_adi'], ENT_QUOTES, 'UTF-8') . "</strong> işletmesinde randevunuz var!</p>
            <div style='background:#FFFDF5;border:2px solid #FDE68A;border-radius:10px;padding:18px;margin:20px 0;font-size:14px;color:#92400E;'>
                <strong>Randevu Saati:</strong> Yarın ({$dateStr}) saat {$timeStr}<br>
                <strong>Hizmet:</strong> " . htmlspecialchars($apt['hizmet_adi'] ?? 'Randevu', ENT_QUOTES, 'UTF-8') . "<br>
                <strong>Adres:</strong> " . htmlspecialchars($apt['tam_adres'] ?? ($apt['sehir'] . ' / ' . $apt['ilce']), ENT_QUOTES, 'UTF-8') . "
            </div>
            ";
            $html = renderEmailTemplate('Yarın Randevunuz Var! (24 Saat Kaldı)', $body, 'Randevumu İncele', getSiteBaseUrl() . '/musteri/randevularim.php');
            if (queueAppointmentReminder($pdo, (int)$aptId, 'hatirlatma_24h', $apt['musteri_eposta'], $apt['musteri_adi'], "Hatırlatma: Yarın Randevunuz Var — {$apt['isletme_adi']}", $html, "rem24_cust_{$aptId}")) $counts['reminders_24h']++;
        }


    }

    $stmt2 = $pdo->query("SELECT r.id 
                          FROM randevular r 
                          WHERE r.durum IN ('confirmed', 'pending')
                            AND r.randevu_zamani BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 2 HOUR)
                            AND r.id NOT IN (SELECT randevu_id FROM randevu_bildirim_kayitlari WHERE bildirim_turu = 'hatirlatma_2h')");
    $ids2 = $stmt2->fetchAll(PDO::FETCH_COLUMN) ?: [];

    foreach ($ids2 as $aptId) {
        $apt = getAppointmentFullDetails($pdo, (int)$aptId);
        if (!$apt) continue;

        $timeStr = date('H:i', strtotime($apt['randevu_zamani']));

        if (!empty($apt['musteri_eposta'])) {
            $body = "
            <p>Merhaba <strong>" . htmlspecialchars($apt['musteri_adi'], ENT_QUOTES, 'UTF-8') . "</strong>,</p>
            <p><strong>" . htmlspecialchars($apt['isletme_adi'], ENT_QUOTES, 'UTF-8') . "</strong> işletmesindeki randevunuza yaklaşık <strong>2 saat</strong> kaldı!</p>
            <div style='background:#EFF6FF;border:2px solid #BFDBFE;border-radius:10px;padding:18px;margin:20px 0;font-size:14px;color:#1E40AF;'>
                <strong>Randevu Saati:</strong> Bugün saat <strong>{$timeStr}</strong><br>
                <strong>Adres:</strong> " . htmlspecialchars($apt['tam_adres'] ?? ($apt['sehir'] . ' / ' . $apt['ilce']), ENT_QUOTES, 'UTF-8') . "
            </div>
            ";
            $html = renderEmailTemplate('Randevunuza 2 Saat Kaldı!', $body, 'Yol Tarifi ve Bilgiler', getSiteBaseUrl() . '/musteri/randevularim.php');
            if (queueAppointmentReminder($pdo, (int)$aptId, 'hatirlatma_2h', $apt['musteri_eposta'], $apt['musteri_adi'], "Son Hatırlatma: Randevunuza 2 Saat Kaldı!", $html, "rem2_cust_{$aptId}")) $counts['reminders_2h']++;
        }


    }

    $stmtBiz = $pdo->query("SELECT r.id 
                            FROM randevular r 
                            WHERE r.durum IN ('confirmed', 'pending')
                              AND r.randevu_zamani BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 2 HOUR)
                              AND r.id NOT IN (SELECT randevu_id FROM randevu_bildirim_kayitlari WHERE bildirim_turu = 'isletme_yaklasan')");
    $idsBiz = $stmtBiz->fetchAll(PDO::FETCH_COLUMN) ?: [];

    foreach ($idsBiz as $aptId) {
        $apt = getAppointmentFullDetails($pdo, (int)$aptId);
        if (!$apt) continue;

        $timeStr = date('H:i', strtotime($apt['randevu_zamani']));

        if (!empty($apt['isletme_eposta'])) {
            $body = "
            <p>Sayın <strong>" . htmlspecialchars($apt['isletme_adi'], ENT_QUOTES, 'UTF-8') . "</strong>,</p>
            <p>Yaklaşan müşteri randevunuz bulunmaktadır:</p>
            <div style='background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;padding:16px;margin:20px 0;font-size:14px;'>
                <strong>Müşteri:</strong> " . htmlspecialchars($apt['musteri_adi'], ENT_QUOTES, 'UTF-8') . " (" . htmlspecialchars($apt['musteri_telefon'], ENT_QUOTES, 'UTF-8') . ")<br>
                <strong>Saat:</strong> Bugün {$timeStr}<br>
                <strong>Hizmet:</strong> " . htmlspecialchars($apt['hizmet_adi'] ?? 'Randevu', ENT_QUOTES, 'UTF-8') . "
            </div>
            ";
            $html = renderEmailTemplate('Yaklaşan Randevu Bildirimi (İşletme)', $body, 'İşletme Paneline Git', getSiteBaseUrl() . '/isletme/randevular.php');
            if (queueAppointmentReminder($pdo, (int)$aptId, 'isletme_yaklasan', $apt['isletme_eposta'], $apt['isletme_adi'], "Yaklaşan Randevu: {$apt['musteri_adi']} ({$timeStr})", $html, "rem_biz_{$aptId}")) $counts['business_reminders']++;
        }


    }

    return $counts;
}


function queueAppointmentReminder(PDO $pdo, int $id, string $type, string $email, string $name, string $subject, string $html, string $key): bool {
    if ($pdo->inTransaction()) return false;
    try {
        $pdo->beginTransaction();
        $lock = $pdo->prepare('SELECT id FROM randevular WHERE id = ? FOR UPDATE');
        $lock->execute([$id]);
        if (!$lock->fetchColumn() || isAppointmentNotificationSent($pdo, $id, $type)) {
            $pdo->rollBack();
            return false;
        }
        if (enqueueMail($email, $name, $subject, $html, $key, $type) === false || !recordAppointmentNotification($pdo, $id, $type, $email)) {
            $pdo->rollBack();
            return false;
        }
        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Appointment reminder queue unavailable.');
        return false;
    }
}
