<?php
require_once __DIR__ . '/_common.php';
$currentBusinessPage = 'ratings';
$pageTitle = 'Müşteri Değerlendirmeleri';
$errors = [];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    businessRequirePost();
    try {
        $appointmentId = filter_var($_POST['appointment_id'] ?? null, FILTER_VALIDATE_INT);
        $rating = filter_var($_POST['rating'] ?? null, FILTER_VALIDATE_INT);
        $comment = trim(is_string($_POST['comment'] ?? null) ? $_POST['comment'] : '');
        if (!$appointmentId || $rating === false || $rating < 1 || $rating > 5 || mb_strlen($comment, 'UTF-8') > 2000) {
            throw new RuntimeException('Randevu, 1-5 puan ve en fazla 2000 karakter yorum girin.');
        }
        $stmt = $pdo->prepare("SELECT r.kullanici_id FROM randevular r
                               WHERE r.id = ? AND r.isletme_id = ? AND r.durum = 'completed'");
        $stmt->execute([$appointmentId, $businessId]);
        $customerId = $stmt->fetchColumn();
        if (!$customerId) {
            throw new RuntimeException('Yalnızca bu işletmenin tamamlanmış randevusu değerlendirilebilir.');
        }
        $stmt = $pdo->prepare("INSERT INTO degerlendirmeler (randevu_id, degerlendiren_turu, degerlendiren_id, puan, yorum)
                               VALUES (?, 'business', ?, ?, ?)");
        $stmt->execute([$appointmentId, $businessId, $rating, $comment !== '' ? $comment : null]);
        $stmt = $pdo->prepare('INSERT INTO kullanici_bildirimleri (kullanici_id, baslik, mesaj, tur, link) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([(int)$customerId, 'İşletme sizi değerlendirdi', $business['isletme_adi'] . ' tamamlanan randevunuz için değerlendirme yazdı.', 'business_review', '/musteri/hesabim.php']);
        businessFlash('success', 'Müşteri değerlendirmesi kaydedildi ve müşteriye bildirim gönderildi.');
        businessRedirect('degerlendirmeler.php');
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            $errors[] = 'Bu randevu için daha önce değerlendirme yapılmış.';
        } else {
            error_log('Business customer rating failed: ' . $e->getMessage());
            $errors[] = 'Değerlendirme kaydedilemedi.';
        }
    } catch (Throwable $e) {
        $errors[] = $e instanceof RuntimeException ? $e->getMessage() : 'Değerlendirme kaydedilemedi.';
    }
}

$stmt = $pdo->prepare("SELECT r.id, r.randevu_zamani, k.ad_soyad AS musteri_adi, h.hizmet_adi,
                              d.puan AS business_rating, d.yorum AS business_comment, d.olusturulma_tarihi AS rated_at
                       FROM randevular r
                       INNER JOIN kullanicilar k ON k.id = r.kullanici_id
                       LEFT JOIN hizmetler h ON h.id = r.hizmet_id
                       LEFT JOIN degerlendirmeler d ON d.randevu_id = r.id AND d.degerlendiren_turu = 'business' AND d.degerlendiren_id = r.isletme_id
                       WHERE r.isletme_id = ? AND r.durum = 'completed'
                       ORDER BY r.randevu_zamani DESC LIMIT 200");
$stmt->execute([$businessId]);
$completedAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
include __DIR__ . '/_header.php';
?>
<?php foreach ($errors as $error): ?><div class="business-notice error"><?= businessEscape($error) ?></div><?php endforeach; ?>
<section class="admin-panel">
    <div class="panel-header"><h2><i class="fa-solid fa-comment-dots"></i><span>Tamamlanan randevular</span></h2><span class="status-badge"><?= count($completedAppointments) ?> kayıt</span></div>
    <div class="panel-body">
        <?php if (!$completedAppointments): ?><div class="empty-state"><i class="fa-regular fa-calendar-check"></i><h4>Tamamlanmış randevu yok</h4><p>Müşterileri yalnızca tamamlanan randevular üzerinden değerlendirebilirsiniz.</p></div>
        <?php else: ?><div style="overflow-x:auto;"><table class="admin-table"><thead><tr><th>Müşteri</th><th>Randevu</th><th>Hizmet</th><th>Değerlendirme</th></tr></thead><tbody>
            <?php foreach ($completedAppointments as $appointment): ?><tr>
                <td><?= businessEscape($appointment['musteri_adi']) ?></td><td><?= date('d.m.Y H:i',strtotime($appointment['randevu_zamani'])) ?></td><td><?= businessEscape($appointment['hizmet_adi'] ?? '—') ?></td>
                <td><?php if ($appointment['business_rating'] !== null): ?><span class="status-badge active"><?= (int)$appointment['business_rating'] ?>/5</span><small style="display:block;"><?= businessEscape($appointment['business_comment'] ?? '') ?></small><?php else: ?>
                    <details><summary class="btn-admin outline sm">Müşteriyi değerlendir</summary><form method="POST" class="admin-form" style="min-width:260px;padding:12px;">
                        <input type="hidden" name="csrf_token" value="<?= businessEscape(generateCsrfToken()) ?>"><input type="hidden" name="appointment_id" value="<?= (int)$appointment['id'] ?>">
                        <div class="form-group"><label>Puan</label><select name="rating" required><option value="">Seçin</option><?php for($i=5;$i>=1;$i--): ?><option value="<?= $i ?>"><?= $i ?> / 5</option><?php endfor; ?></select></div>
                        <div class="form-group"><label>Yorum (isteğe bağlı)</label><textarea name="comment" maxlength="2000"></textarea></div>
                        <button class="btn-admin primary sm">Değerlendirmeyi kaydet</button>
                    </form></details>
                <?php endif; ?></td>
            </tr><?php endforeach; ?>
        </tbody></table></div><?php endif; ?>
    </div>
</section>
<?php include __DIR__ . '/_footer.php'; ?>
