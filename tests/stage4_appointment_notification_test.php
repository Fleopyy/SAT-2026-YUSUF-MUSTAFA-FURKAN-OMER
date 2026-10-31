<?php

declare(strict_types=1);

date_default_timezone_set('Europe/Istanbul');

require_once __DIR__ . '/../data/database.php';
require_once __DIR__ . '/../data/mail_config.php';
require_once __DIR__ . '/../data/mail_service.php';
require_once __DIR__ . '/../data/appointment_notification_service.php';

$passed = 0;
$failed = 0;

function assertCondition(bool $cond, string $desc): void {
    global $passed, $failed;
    if ($cond) {
        $passed++;
        echo "  [PASS] {$desc}\n";
    } else {
        $failed++;
        echo "  [FAIL] {$desc}\n";
    }
}

echo "=== AŞAMA 4: RANDEVU BİLDİRİM VE HATIRLATMA TESTLERİ ===\n\n";

$testEmailCust = 'test_cust_' . time() . '@example.com';
$testEmailBiz = 'test_biz_' . time() . '@example.com';

$testPhoneCust = '0555' . substr((string)time(), -7);
$stmt = $pdo->prepare("INSERT INTO kullanicilar (ad_soyad, eposta, telefon, sehir, sifre_hash, eposta_dogrulandi) VALUES (?, ?, ?, 'İstanbul', 'dummy_hash', 1)");
$stmt->execute(['Test Randevu Müşterisi', $testEmailCust, $testPhoneCust]);
$custId = (int)$pdo->lastInsertId();

$testPhoneBiz = '0554' . substr((string)time(), -7);
$stmt = $pdo->prepare("INSERT INTO isletmeler (isletme_adi, isletme_sahibi, eposta, telefon, sehir, ilce, tam_adres) VALUES (?, ?, ?, ?, 'İstanbul', 'Kadıköy', 'Moda Cad. No: 12')");
$stmt->execute(['Test Randevu Berber', 'Berber Ahmet', $testEmailBiz, $testPhoneBiz]);
$bizId = (int)$pdo->lastInsertId();

$stmt = $pdo->prepare("INSERT INTO hizmetler (isletme_id, hizmet_adi, sure_dakika, fiyat) VALUES (?, 'Saç Kesimi', 30, 250.00)");
$stmt->execute([$bizId]);
$serviceId = (int)$pdo->lastInsertId();

$randevuZamani = date('Y-m-d H:i:s', strtotime('+24 hours + 10 minutes'));
$stmt = $pdo->prepare("INSERT INTO randevular (kullanici_id, isletme_id, hizmet_id, randevu_zamani, durum) VALUES (?, ?, ?, ?, 'pending')");
$stmt->execute([$custId, $bizId, $serviceId, $randevuZamani]);
$aptId = (int)$pdo->lastInsertId();

$stmt = $pdo->prepare("UPDATE randevu_ek_bilgileri SET musteri_notu = 'Özel test randevusu' WHERE randevu_id = ?");
$stmt->execute([$aptId]);

echo "--- 1. Randevu Detayları ve Yardımcı Fonksiyonlar ---\n";
$details = getAppointmentFullDetails($pdo, $aptId);
assertCondition($details !== null, 'getAppointmentFullDetails randevu kaydını getirdi');
assertCondition(($details['musteri_adi'] ?? '') === 'Test Randevu Müşterisi', 'Müşteri adı eşleşti');
assertCondition(($details['isletme_adi'] ?? '') === 'Test Randevu Berber', 'İşletme adı eşleşti');
assertCondition(($details['hizmet_adi'] ?? '') === 'Saç Kesimi', 'Hizmet adı eşleşti');

echo "\n--- 2. Bildirim Tekilleştirme Mekanizması ---\n";
assertCondition(!isAppointmentNotificationSent($pdo, $aptId, 'degisiklik'), 'Henüz kaydedilmemiş bildirim false döndü');
$recResult1 = recordAppointmentNotification($pdo, $aptId, 'degisiklik', $testEmailCust);
assertCondition($recResult1 === true, 'İlk bildirim kaydı başarıyla eklendi');
assertCondition(isAppointmentNotificationSent($pdo, $aptId, 'degisiklik'), 'Kayıt sonrası isAppointmentNotificationSent true döndü');

$recResult2 = recordAppointmentNotification($pdo, $aptId, 'degisiklik', $testEmailCust);
assertCondition($recResult2 === false, 'Aynı randevu ve bildirim türü ikinci kez eklenemedi (DB uk_randevu_bildirim)');

echo "\n--- 3. Randevu Oluşturma Bildirimleri ---\n";
notifyAppointmentCreated($aptId);
$stmtQ = $pdo->prepare("SELECT COUNT(*) FROM eposta_kuyrugu WHERE tur = 'randevu_olusturuldu' AND alici_eposta = ?");
$stmtQ->execute([$testEmailCust]);
$custCreatedCount = (int)$stmtQ->fetchColumn();
assertCondition($custCreatedCount >= 1, 'Müşteriye randevu talebi e-postası kuyruğa eklendi');

$stmtQ = $pdo->prepare("SELECT COUNT(*) FROM eposta_kuyrugu WHERE tur = 'yeni_randevu_isletme' AND alici_eposta = ?");
$stmtQ->execute([$testEmailBiz]);
$bizCreatedCount = (int)$stmtQ->fetchColumn();
assertCondition($bizCreatedCount >= 1, 'İşletmeye yeni randevu bildirimi e-postası kuyruğa eklendi');

echo "\n--- 4. Randevu Durum Değişikliği Bildirimleri ---\n";
notifyAppointmentStatusChanged($aptId, 'confirmed', 'pending');
$stmtQ = $pdo->prepare("SELECT COUNT(*) FROM eposta_kuyrugu WHERE tur = 'randevu_onay' AND alici_eposta = ?");
$stmtQ->execute([$testEmailCust]);
assertCondition((int)$stmtQ->fetchColumn() >= 1, 'Randevu onaylandığında müşteriye bildirim kuyruğa eklendi');

notifyAppointmentStatusChanged($aptId, 'cancelled', 'confirmed');
$stmtQ = $pdo->prepare("SELECT COUNT(*) FROM eposta_kuyrugu WHERE tur = 'randevu_iptal' AND alici_eposta = ?");
$stmtQ->execute([$testEmailCust]);
assertCondition((int)$stmtQ->fetchColumn() >= 1, 'Randevu iptal edildiğinde müşteriye bildirim kuyruğa eklendi');
assertCondition(isAppointmentNotificationSent($pdo, $aptId, 'iptal'), 'İptal bildirimi tekilleştirme tablosuna işlendi');

$qCountBefore = (int)$pdo->query("SELECT COUNT(*) FROM eposta_kuyrugu")->fetchColumn();
notifyAppointmentStatusChanged($aptId, 'cancelled', 'cancelled');
$qCountAfter = (int)$pdo->query("SELECT COUNT(*) FROM eposta_kuyrugu")->fetchColumn();
assertCondition($qCountBefore === $qCountAfter, 'Durum değişmediğinde e-posta kuyruğuna fazladan kayıt atılmadı (noop)');

echo "\n--- 5. Otomatik Hatırlatma Taraması (processAppointmentReminders) ---\n";
$target24 = date('Y-m-d H:i:s', strtotime('+24 hours'));
$stmt = $pdo->prepare("UPDATE randevular SET randevu_zamani = ?, durum = 'confirmed' WHERE id = ?");
$stmt->execute([$target24, $aptId]);

$remResults1 = processAppointmentReminders();
assertCondition(($remResults1['reminders_24h'] ?? 0) >= 1, '24 saat hatırlatması başarıyla algılandı ve kuyruğa eklendi');
assertCondition(isAppointmentNotificationSent($pdo, $aptId, 'hatirlatma_24h'), '24 saat hatırlatması randevu_bildirim_kayitlari tablosuna işlendi');

$remResults2 = processAppointmentReminders();
$stmtR24 = $pdo->prepare("SELECT COUNT(*) FROM randevu_bildirim_kayitlari WHERE randevu_id = ? AND bildirim_turu = 'hatirlatma_24h'");
$stmtR24->execute([$aptId]);
assertCondition((int)$stmtR24->fetchColumn() === 1, '24 saat hatırlatması tekilleştirildi, veritabanında tam olarak 1 kayıt var');

$target2 = date('Y-m-d H:i:s', strtotime('+90 minutes'));
$stmt = $pdo->prepare("UPDATE randevular SET randevu_zamani = ? WHERE id = ?");
$stmt->execute([$target2, $aptId]);

$remResults3 = processAppointmentReminders();
assertCondition(($remResults3['reminders_2h'] ?? 0) >= 1, '2 saat müşteri hatırlatması başarıyla algılandı ve kuyruğa eklendi');
assertCondition(($remResults3['business_reminders'] ?? 0) >= 1, 'İşletme yaklaşan randevu bildirimi başarıyla algılandı ve kuyruğa eklendi');

$remResults4 = processAppointmentReminders();
$stmtR2h = $pdo->prepare("SELECT COUNT(*) FROM randevu_bildirim_kayitlari WHERE randevu_id = ? AND bildirim_turu = 'hatirlatma_2h'");
$stmtR2h->execute([$aptId]);
assertCondition((int)$stmtR2h->fetchColumn() === 1, '2 saat hatırlatması tekilleştirildi, veritabanında tam olarak 1 kayıt var');

$stmtRbiz = $pdo->prepare("SELECT COUNT(*) FROM randevu_bildirim_kayitlari WHERE randevu_id = ? AND bildirim_turu = 'isletme_yaklasan'");
$stmtRbiz->execute([$aptId]);
assertCondition((int)$stmtRbiz->fetchColumn() === 1, 'İşletme hatırlatması tekilleştirildi, veritabanında tam olarak 1 kayıt var');

echo "\n--- 6. Temizlik (Cleanup) ---\n";
$pdo->prepare("DELETE FROM randevu_bildirim_kayitlari WHERE randevu_id = ?")->execute([$aptId]);
$pdo->prepare("DELETE FROM eposta_kuyrugu WHERE alici_eposta IN (?, ?)")->execute([$testEmailCust, $testEmailBiz]);
$pdo->prepare("DELETE FROM randevu_ek_bilgileri WHERE randevu_id = ?")->execute([$aptId]);
$pdo->prepare("DELETE FROM randevular WHERE id = ?")->execute([$aptId]);
$pdo->prepare("DELETE FROM hizmetler WHERE id = ?")->execute([$serviceId]);
$pdo->prepare("DELETE FROM isletmeler WHERE id = ?")->execute([$bizId]);
$pdo->prepare("DELETE FROM kullanicilar WHERE id = ?")->execute([$custId]);

assertCondition(true, 'Test verileri başarıyla temizlendi');

echo "\n====================================================\n";
echo "AŞAMA 4 TEST SONUÇLARI: {$passed} BAŞARILI, {$failed} BAŞARISIZ\n";
echo "====================================================\n";

if ($failed > 0) {
    exit(1);
}
exit(0);
