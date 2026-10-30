<?php
require_once __DIR__ . '/_common.php';
$customerActivePage = 'account';
$customerPageTitle = 'Hesabım';
$errors = [];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    customerRequirePost();
    $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
    try {
        if ($action === 'photo') {
            $newPhotoPath = customerUploadPhoto($_FILES['photo'] ?? [], $customerId);
            try {
                $pdo->prepare('INSERT INTO kullanici_portal_profilleri (kullanici_id, fotograf_yolu) VALUES (?, ?)
                               ON DUPLICATE KEY UPDATE fotograf_yolu = VALUES(fotograf_yolu)')
                    ->execute([$customerId, $newPhotoPath]);
            } catch (Throwable $e) {
                $newPhotoAbsolute = dirname(__DIR__) . '/' . $newPhotoPath;
                if (is_file($newPhotoAbsolute) && !unlink($newPhotoAbsolute)) {
                    error_log('Unable to remove uncommitted customer photo: ' . $newPhotoAbsolute);
                }
                throw $e;
            }
            if ($customerPhotoPath) {
                $oldPhotoAbsolute = realpath(dirname(__DIR__) . '/' . ltrim($customerPhotoPath, '/\\'));
                $customerUploadRoot = realpath(dirname(__DIR__) . '/uploads/musteri/' . $customerId);
                if ($oldPhotoAbsolute && $customerUploadRoot
                    && strncmp($oldPhotoAbsolute, $customerUploadRoot . DIRECTORY_SEPARATOR, strlen($customerUploadRoot . DIRECTORY_SEPARATOR)) === 0
                    && is_file($oldPhotoAbsolute) && !unlink($oldPhotoAbsolute)) {
                    error_log('Unable to remove replaced customer photo: ' . $oldPhotoAbsolute);
                }
            }
            customerFlash('success', 'Profil fotoğrafın güncellendi.');
        } elseif ($action === 'password') {
            $currentPassword = is_string($_POST['current_password'] ?? null) ? $_POST['current_password'] : '';
            $newPassword = is_string($_POST['new_password'] ?? null) ? $_POST['new_password'] : '';
            $confirmPassword = is_string($_POST['confirm_password'] ?? null) ? $_POST['confirm_password'] : '';
            $stmt = $pdo->prepare('SELECT sifre_hash FROM kullanicilar WHERE id = ?');
            $stmt->execute([$customerId]);
            $passwordHash = $stmt->fetchColumn();
            if (!$passwordHash || !password_verify($currentPassword, $passwordHash)) {
                throw new RuntimeException('Mevcut şifren doğru değil.');
            }
            if (!validNewPassword($newPassword)) {
                throw new RuntimeException('Yeni şifre 10-72 bayt olmalı.');
            }
            if (!hash_equals($newPassword, $confirmPassword)) {
                throw new RuntimeException('Yeni şifre ve tekrarı aynı değil.');
            }
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $pdo->prepare('UPDATE kullanicilar SET sifre_hash = ? WHERE id = ?')
                ->execute([$newHash, $customerId]);
            bindPasswordSession('user', $newHash);
            session_regenerate_id(true);

            // Güvenlik bildirimi e-postası gönder
            sendPasswordChangedNotification($customerId, $customer['eposta'], $customer['ad_soyad']);

            customerFlash('success', 'Şifren güncellendi.');
        } elseif ($action === 'request_code' || $action === 'resend_code') {
            sendCustomerVerificationEmail($customerId, $customer['eposta'], $customer['ad_soyad']);
            $_SESSION['musteri_kod_gonderildi'] = true;
            customerFlash('info', '6 haneli doğrulama kodunuz ' . htmlspecialchars($customer['eposta']) . ' adresinize gönderildi.');
        } elseif ($action === 'verify_code') {
            $code = is_string($_POST['verification_code'] ?? null) ? trim($_POST['verification_code']) : '';
            $result = verifyCustomerEmailCode($customerId, $code);
            if ($result['success']) {
                unset($_SESSION['musteri_kod_gonderildi']);
                customerFlash('success', $result['message']);
            } else {
                throw new RuntimeException($result['message']);
            }
        } else {
            throw new RuntimeException('İşlem tanınmadı.');
        }
        header('Location: /musteri/hesabim.php');
        exit;
    } catch (Throwable $e) {
        error_log('Customer account update failed: ' . $e->getMessage());
        $errors[] = ($e instanceof RuntimeException && !($e instanceof PDOException)) ? $e->getMessage() : 'Hesap bilgisi güncellenemedi.';
    }
}

$stmt = $pdo->prepare("SELECT i.isletme_adi, d.puan, d.yorum, d.olusturulma_tarihi,
                              h.hizmet_adi, r.randevu_zamani
                       FROM degerlendirmeler d
                       INNER JOIN randevular r ON r.id = d.randevu_id
                       INNER JOIN isletmeler i ON i.id = r.isletme_id
                       LEFT JOIN hizmetler h ON h.id = r.hizmet_id
                       WHERE d.degerlendiren_turu = 'business' AND r.kullanici_id = ?
                         AND d.degerlendiren_id = r.isletme_id
                       ORDER BY d.olusturulma_tarihi DESC");
$stmt->execute([$customerId]);
$businessReviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/_header.php';
?>
<section class="mb-4">
    <h1 class="customer-title">Hesabım</h1>
    <p class="customer-subtitle">Profil bilgilerin ve işletmelerden aldığın değerlendirmeler.</p>
</section>

<?php foreach ($errors as $error): ?><div class="customer-notice error"><?= customerEscape($error) ?></div><?php endforeach; ?>

<?php if (empty($customer['eposta_dogrulandi'])): 
    $showCodeInput = !empty($_SESSION['musteri_kod_gonderildi']) || isset($_GET['kod_gir']);
?>
<section class="customer-card p-4 mb-4" style="border: 2px solid #F59E0B; background: #FFFDF5; border-radius: 12px;">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <?php if ($showCodeInput): ?>
                    <span class="badge bg-primary text-white"><i class="fa-solid fa-envelope-open-text me-1"></i> Kod Gönderildi</span>
                    <h2 class="h6 fw-bold mb-0 text-dark">Doğrulama Kodunu Girin</h2>
                <?php else: ?>
                    <span class="badge bg-warning text-dark"><i class="fa-solid fa-triangle-exclamation me-1"></i> Doğrulanmamış E-Posta</span>
                    <h2 class="h6 fw-bold mb-0 text-dark">Hesabınızı Doğrulayın</h2>
                <?php endif; ?>
            </div>
            <p class="customer-muted small mb-0">
                <?php if ($showCodeInput): ?>
                    <strong><?= customerEscape($customer['eposta']) ?></strong> adresinize gönderilen 6 haneli doğrulama kodunu girerek onaylayın.
                <?php else: ?>
                    Randevu onayları ve bildirimleri eksiksiz alabilmeniz için e-posta adresinizi doğrulamanız gerekmektedir.
                <?php endif; ?>
            </p>
        </div>
        <div class="d-flex flex-column gap-2" style="min-width: 280px;">
            <?php if ($showCodeInput): ?>
                <form method="POST" class="d-flex gap-2">
                    <input type="hidden" name="csrf_token" value="<?= customerEscape(generateCsrfToken()) ?>">
                    <input type="hidden" name="action" value="verify_code">
                    <input type="text" name="verification_code" maxlength="6" pattern="[0-9]{6}" required autofocus placeholder="6 Haneli Kod" class="form-control text-center fw-bold" style="letter-spacing: 4px; font-size: 16px;">
                    <button class="customer-button text-nowrap" type="submit"><i class="fa-solid fa-check me-1"></i> Kodu Onayla</button>
                </form>
                <form method="POST" class="text-end">
                    <input type="hidden" name="csrf_token" value="<?= customerEscape(generateCsrfToken()) ?>">
                    <input type="hidden" name="action" value="resend_code">
                    <button type="submit" class="btn btn-link text-decoration-none p-0 text-muted small" style="font-size: 12px;">
                        <i class="fa-solid fa-rotate-right me-1"></i> Kodu almadınız mı? Tekrar Gönder
                    </button>
                </form>
            <?php else: ?>
                <div class="d-flex flex-column align-items-md-end gap-1">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= customerEscape(generateCsrfToken()) ?>">
                        <input type="hidden" name="action" value="request_code">
                        <button class="customer-button text-nowrap py-2 px-4 fs-6" type="submit">
                            <i class="fa-solid fa-envelope-circle-check me-2"></i>Doğrulama Kodu Al
                        </button>
                    </form>
                    <a href="?kod_gir=1" class="text-muted small text-decoration-none mt-1" style="font-size: 12px;">
                        <i class="fa-solid fa-key me-1"></i>Zaten kodum var
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="customer-card p-4 p-md-5 mb-4">
    <div class="d-flex align-items-center gap-3 mb-4">
        <?php if ($customerPhotoPath): ?>
            <img src="/<?= customerEscape($customerPhotoPath) ?>" alt="Profil fotoğrafı" style="width:76px;height:76px;object-fit:cover;border-radius:50%">
        <?php else: ?>
            <div class="d-flex align-items-center justify-content-center rounded-circle" style="width:76px;height:76px;background:#F1F5F9;color:#64748B;font-size:30px"><i class="fa-solid fa-user"></i></div>
        <?php endif; ?>
        <div>
            <div class="d-flex align-items-center gap-2">
                <h2 class="h5 fw-bold mb-1"><?= customerEscape($customer['ad_soyad']) ?></h2>
                <?php if (!empty($customer['eposta_dogrulandi'])): ?>
                    <span class="badge rounded-pill bg-warning-subtle text-warning border border-warning px-2 py-1 small" style="font-size:11px;">
                        <i class="fa-solid fa-shield-check me-1"></i>Doğrulanmış Hesap
                    </span>
                <?php endif; ?>
            </div>
            <div class="customer-muted small"><?= customerEscape($customer['eposta']) ?> · <?= customerEscape($customer['telefon']) ?></div>
        </div>
    </div>
    <form method="POST" enctype="multipart/form-data" class="row g-3 align-items-end">
        <input type="hidden" name="csrf_token" value="<?= customerEscape(generateCsrfToken()) ?>">
        <input type="hidden" name="action" value="photo">
        <div class="col-md-8"><label for="customerPhoto" class="form-label fw-semibold">Profil fotoğrafı</label><input class="form-control" type="file" name="photo" id="customerPhoto" accept="image/jpeg,image/png,image/webp" required><div class="form-text">JPG, PNG veya WEBP; en fazla 5 MB.</div></div>
        <div class="col-md-4"><button class="customer-button w-100" type="submit">Fotoğrafı kaydet</button></div>
    </form>
</section>

<section class="customer-card p-4 p-md-5 mb-4">
    <h2 class="h5 fw-bold mb-3">Şifre değiştir</h2>
    <form method="POST" class="row g-3">
        <input type="hidden" name="csrf_token" value="<?= customerEscape(generateCsrfToken()) ?>">
        <input type="hidden" name="action" value="password">
        <div class="col-md-4"><label class="form-label" for="currentPassword">Mevcut şifre</label><input class="form-control" type="password" name="current_password" id="currentPassword" autocomplete="current-password" required></div>
        <div class="col-md-4"><label class="form-label" for="newPassword">Yeni şifre</label><input class="form-control" type="password" name="new_password" id="newPassword" minlength="8" autocomplete="new-password" required></div>
        <div class="col-md-4"><label class="form-label" for="confirmPassword">Yeni şifre (tekrar)</label><input class="form-control" type="password" name="confirm_password" id="confirmPassword" minlength="8" autocomplete="new-password" required></div>
        <div class="col-12"><button class="customer-button" type="submit">Şifreyi güncelle</button></div>
    </form>
</section>

<section class="customer-card p-4 p-md-5">
    <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
        <h2 class="h5 fw-bold mb-0">İşletmelerin değerlendirmeleri</h2>
        <span class="customer-muted small"><?= count($businessReviews) ?> değerlendirme</span>
    </div>
    <?php if (!$businessReviews): ?>
        <p class="customer-muted mb-0">İşletmeler henüz senin hakkında değerlendirme yazmamış.</p>
    <?php else: foreach ($businessReviews as $review): ?>
        <article class="border-bottom py-3">
            <div class="d-flex justify-content-between align-items-start gap-3">
                <div><strong><?= customerEscape($review['isletme_adi']) ?></strong><div class="customer-muted small"><?= customerEscape($review['hizmet_adi'] ?? 'Hizmet') ?> · <?= date('d.m.Y', strtotime($review['randevu_zamani'])) ?></div></div>
                <span class="text-nowrap" style="color:#B9813E"><?= str_repeat('★', (int)$review['puan']) ?><?= str_repeat('☆', 5 - (int)$review['puan']) ?></span>
            </div>
            <?php if ($review['yorum']): ?><p class="mb-1 mt-2"><?= customerEscape($review['yorum']) ?></p><?php endif; ?>
            <small class="customer-muted"><?= date('d.m.Y', strtotime($review['olusturulma_tarihi'])) ?></small>
        </article>
    <?php endforeach; endif; ?>
</section>
<?php include __DIR__ . '/_footer.php'; ?>
