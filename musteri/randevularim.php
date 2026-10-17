<?php
require_once __DIR__ . '/_common.php';
$customerActivePage = 'appointments';
$customerPageTitle = 'Randevularım';
$errors = [];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    customerRequirePost();
    try {
        if (($_POST['action'] ?? '') !== 'review') {
            throw new RuntimeException('İşlem tanınmadı.');
        }
        $appointmentId = filter_var($_POST['appointment_id'] ?? null, FILTER_VALIDATE_INT);
        $rating = filter_var($_POST['rating'] ?? null, FILTER_VALIDATE_INT);
        $comment = is_string($_POST['comment'] ?? null) ? $_POST['comment'] : '';
        if ($appointmentId === false || $rating === false) {
            throw new RuntimeException('Randevu ve puan bilgisi geçersiz.');
        }
        customerCreateAppointmentReview($pdo, $customerId, $appointmentId, $rating, $comment);
        customerFlash('success', 'Yorumun işletme profiline eklendi.');
        header('Location: /musteri/randevularim.php');
        exit;
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            $errors[] = 'Bu randevu için daha önce yorum yazılmış.';
        } else {
            error_log('Customer appointment review failed: ' . $e->getMessage());
            $errors[] = 'Yorumun kaydedilemedi. Lütfen tekrar dene.';
        }
    } catch (Throwable $e) {
        $errors[] = $e instanceof RuntimeException ? $e->getMessage() : 'Yorumun kaydedilemedi. Lütfen tekrar dene.';
    }
}

$stmt = $pdo->prepare("SELECT r.id, r.randevu_zamani, r.durum, i.id AS isletme_id, i.isletme_adi,
                              i.sehir, i.ilce, h.hizmet_adi, h.sure_dakika, h.fiyat,
                              c.ad_soyad AS calisan_adi, i.isletme_sahibi,
                              x.bitis_zamani, x.fiyat_snapshot, x.musteri_notu,
                              d.puan AS customer_rating, d.yorum AS customer_review
                       FROM randevular r
                       INNER JOIN isletmeler i ON i.id = r.isletme_id
                       LEFT JOIN hizmetler h ON h.id = r.hizmet_id
                       LEFT JOIN calisanlar c ON c.id = r.calisan_id
                       LEFT JOIN randevu_ek_bilgileri x ON x.randevu_id = r.id
                       LEFT JOIN degerlendirmeler d ON d.randevu_id = r.id
                            AND d.degerlendiren_turu = 'customer' AND d.degerlendiren_id = r.kullanici_id
                       WHERE r.kullanici_id = ?
                       ORDER BY r.randevu_zamani DESC");
$stmt->execute([$customerId]);
$allAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
$activeAppointments = [];
$previousAppointments = [];
foreach ($allAppointments as $appointment) {
    $endTime = $appointment['bitis_zamani'] ?: date(
        'Y-m-d H:i:s',
        strtotime($appointment['randevu_zamani']) + ((int)($appointment['sure_dakika'] ?: 30) * 60)
    );
    if (in_array($appointment['durum'], ['pending', 'confirmed'], true) && strtotime($endTime) >= time()) {
        $activeAppointments[] = $appointment;
    } else {
        $previousAppointments[] = $appointment;
    }
}

include __DIR__ . '/_header.php';
?>
<section class="mb-4">
    <h1 class="customer-title">Randevularım</h1>
    <p class="customer-subtitle">Yaklaşan randevuların ve geçmiş ziyaretlerin burada.</p>
</section>

<?php foreach ($errors as $error): ?><div class="customer-notice error" role="alert"><?= customerEscape($error) ?></div><?php endforeach; ?>

<section class="customer-card p-4 p-md-5 mb-4">
    <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
        <h2 class="h5 fw-bold mb-0">Aktif randevular</h2>
        <span class="customer-muted small"><?= count($activeAppointments) ?> randevu</span>
    </div>
    <?php if (!$activeAppointments): ?>
        <p class="customer-muted mb-0">Yaklaşan veya yanıt bekleyen bir randevun yok.</p>
    <?php else: ?>
        <?php foreach ($activeAppointments as $appointment):
            $endTime = $appointment['bitis_zamani'] ?: date(
                'Y-m-d H:i:s',
                strtotime($appointment['randevu_zamani']) + ((int)($appointment['sure_dakika'] ?: 30) * 60)
            );
            $statusLabels = ['pending' => 'Onay bekliyor', 'confirmed' => 'Onaylandı'];
        ?>
            <article class="border rounded-4 p-3 p-md-4 mb-3">
                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                    <div>
                        <h3 class="h6 fw-bold mb-1"><?= customerEscape($appointment['isletme_adi']) ?></h3>
                        <div class="customer-muted small"><?= customerEscape($appointment['sehir'] . ' · ' . $appointment['ilce']) ?></div>
                    </div>
                    <span class="badge rounded-pill <?= $appointment['durum'] === 'confirmed' ? 'text-bg-success' : 'text-bg-warning' ?>"><?= customerEscape($statusLabels[$appointment['durum']]) ?></span>
                </div>
                <div class="row g-3 mt-2 small">
                    <div class="col-sm-6"><span class="customer-muted">Hizmet</span><div class="fw-semibold"><?= customerEscape($appointment['hizmet_adi'] ?? 'Hizmet') ?></div></div>
                    <div class="col-sm-6"><span class="customer-muted">Personel</span><div class="fw-semibold"><?= customerEscape($appointment['calisan_adi'] ?? ('İşletme sahibi — ' . $appointment['isletme_sahibi'])) ?></div></div>
                    <div class="col-sm-6"><span class="customer-muted">Tarih ve saat</span><div class="fw-semibold"><?= date('d.m.Y', strtotime($appointment['randevu_zamani'])) ?> · <?= date('H:i', strtotime($appointment['randevu_zamani'])) ?>–<?= date('H:i', strtotime($endTime)) ?></div></div>
                    <div class="col-sm-6"><span class="customer-muted">Ücret</span><div class="fw-semibold"><?= number_format((float)($appointment['fiyat_snapshot'] ?? $appointment['fiyat'] ?? 0), 2, ',', '.') ?> ₺</div></div>
                </div>
                <?php if ($appointment['musteri_notu']): ?><p class="customer-muted small mb-0 mt-3">Notun: <?= customerEscape($appointment['musteri_notu']) ?></p><?php endif; ?>
                <a class="btn btn-sm btn-outline-secondary mt-3" href="/berber.php?id=<?= (int)$appointment['isletme_id'] ?>">İşletme profili</a>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

<section class="customer-card p-4 p-md-5">
    <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
        <h2 class="h5 fw-bold mb-0">Geçmiş randevular</h2>
        <span class="customer-muted small"><?= count($previousAppointments) ?> randevu</span>
    </div>
    <?php if (!$previousAppointments): ?>
        <p class="customer-muted mb-0">Henüz geçmiş randevun yok.</p>
    <?php else: ?>
        <?php foreach ($previousAppointments as $appointment): ?>
            <article class="border-bottom py-3">
                <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
                    <div>
                        <strong><?= customerEscape($appointment['isletme_adi']) ?></strong>
                        <div class="customer-muted small"><?= customerEscape($appointment['hizmet_adi'] ?? 'Hizmet') ?> · <?= date('d.m.Y H:i', strtotime($appointment['randevu_zamani'])) ?></div>
                    </div>
                    <span class="small"><?= customerEscape(['completed' => 'Tamamlandı', 'cancelled' => 'İptal edildi', 'rejected' => 'Reddedildi', 'pending' => 'Süresi geçti', 'confirmed' => 'Süresi geçti'][$appointment['durum']] ?? $appointment['durum']) ?></span>
                </div>
                <?php if ($appointment['durum'] === 'completed'): ?>
                    <?php if ($appointment['customer_rating'] !== null): ?>
                        <div class="mt-3">
                            <div class="text-nowrap" style="color:#B9813E" aria-label="<?= (int)$appointment['customer_rating'] ?> / 5 yıldız"><?= str_repeat('★', (int)$appointment['customer_rating']) ?><?= str_repeat('☆', 5 - (int)$appointment['customer_rating']) ?></div>
                            <p class="mb-0 mt-1"><?= customerEscape($appointment['customer_review']) ?></p>
                        </div>
                    <?php else: ?>
                        <form method="POST" class="mt-3">
                            <input type="hidden" name="csrf_token" value="<?= customerEscape(generateCsrfToken()) ?>">
                            <input type="hidden" name="action" value="review">
                            <input type="hidden" name="appointment_id" value="<?= (int)$appointment['id'] ?>">
                            <div class="row g-2 align-items-end">
                                <div class="col-sm-3">
                                    <label class="form-label small fw-semibold" for="rating-<?= (int)$appointment['id'] ?>">Puanın</label>
                                    <select class="form-select" id="rating-<?= (int)$appointment['id'] ?>" name="rating" required>
                                        <option value="">Puan seç</option>
                                        <?php for ($rating = 5; $rating >= 1; $rating--): ?><option value="<?= $rating ?>"><?= $rating ?> / 5</option><?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-sm-9">
                                    <label class="form-label small fw-semibold" for="comment-<?= (int)$appointment['id'] ?>">İşletme hakkında yorumun</label>
                                    <textarea class="form-control" id="comment-<?= (int)$appointment['id'] ?>" name="comment" rows="2" maxlength="2000" required placeholder="Deneyimini paylaş..."></textarea>
                                </div>
                                <div class="col-12"><button class="customer-button" type="submit">Yorumu gönder</button></div>
                            </div>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
<?php include __DIR__ . '/_footer.php'; ?>
