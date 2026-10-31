<?php
require_once __DIR__ . '/data/privacy.php';
require_once __DIR__ . '/data/security.php';
require_once __DIR__ . '/data/customer_auth_service.php';

$message = '';
$messageType = '';
$token = trim($_GET['token'] ?? ($_POST['token'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'request_reset') {
        $email = trim($_POST['eposta'] ?? '');
        requestPasswordReset($email);
        $message = 'Talebiniz alindi. Hesap uygunsa sifre sifirlama iletisi gonderimi denenecektir. Teslimat garanti edilmez; gerekirse daha sonra tekrar deneyin.';
        $messageType = 'info';
    } elseif ($action === 'update_password') {
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($newPassword) || empty($confirmPassword)) {
            $message = 'Lütfen tüm şifre alanlarını doldurun.';
            $messageType = 'danger';
        } elseif ($newPassword !== $confirmPassword) {
            $message = 'Girdiğiniz şifreler birbiriyle eşleşmiyor.';
            $messageType = 'danger';
        } elseif (!validNewPassword($newPassword)) {
            $message = 'Yeni şifreniz en az 10, en fazla 72 karakter uzunluğunda olmalıdır.';
            $messageType = 'danger';
        } else {
            $res = resetPasswordWithToken($token, $newPassword);
            $message = $res['message'];
            $messageType = $res['success'] ? 'success' : 'danger';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Şifre Sıfırlama — BİTİRAS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php if (hasFunctionalConsent()): ?><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"><?php endif; ?>
    <link rel="stylesheet" href="/style.css">
    <style>
        body { background: #0F172A; min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', sans-serif; color: #1E293B; }
        .auth-card { background: #FFFFFF; border-radius: 16px; box-shadow: 0 20px 45px rgba(0,0,0,0.4); max-width: 480px; width: 100%; overflow: hidden; border: 1px solid rgba(194, 136, 64, 0.2); }
        .auth-header { background: #0F172A; padding: 28px 24px; text-align: center; border-bottom: 3px solid #C28840; }
        .auth-body { padding: 32px 28px; }
        .brand-logo { color: #F8FAFC; font-size: 26px; font-weight: 800; letter-spacing: 2px; text-decoration: none; }
        .btn-gold { background: #C28840; color: #fff; font-weight: 700; border: none; padding: 12px 20px; border-radius: 10px; transition: all 0.2s; }
        .btn-gold:hover { background: #a67232; color: #fff; }
    </style>
</head>
<body>
<div class="auth-card m-3">
    <div class="auth-header">
        <a href="/" class="brand-logo">BİTİRAS</a>
        <div style="font-size: 12px; color: #94A3B8; text-transform: uppercase; letter-spacing: 1px; margin-top: 4px;">Güvenli Şifre Yenileme</div>
    </div>
    <div class="auth-body">
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $messageType ?> d-flex align-items-center gap-2 mb-4" role="alert">
                <i class="fa-solid <?= $messageType === 'success' ? 'fa-circle-check' : ($messageType === 'info' ? 'fa-circle-info' : 'fa-circle-exclamation') ?>"></i>
                <div><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        <?php endif; ?>

        <?php if ($messageType === 'success'): ?>
            <div class="text-center py-2">
                <p class="text-muted mb-4">Yeni şifreniz başarıyla kaydedildi. Artık sisteme giriş yapabilirsiniz.</p>
                <a href="/" class="btn btn-gold w-100"><i class="fa-solid fa-right-to-bracket me-2"></i>Giriş Yap</a>
            </div>
        <?php elseif (!empty($token) && $messageType !== 'info'): ?>
            <h4 class="fw-bold mb-2 text-dark">Yeni Şifrenizi Belirleyin</h4>
            <p class="text-muted small mb-4">Hesabınız için en az 10 karakter uzunluğunda güçlü bir şifre oluşturun.</p>

            <form method="POST" action="sifre_sifirla.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="update_password">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">

                <div class="mb-3">
                    <label class="form-label small fw-bold text-uppercase text-muted">Yeni Şifre</label>
                    <input type="password" name="new_password" class="form-control form-control-lg fs-6" minlength="10" maxlength="72" required placeholder="••••••••••" autocomplete="new-password">
                    <div class="form-text">En az 10 karakter uzunluğunda olmalıdır.</div>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold text-uppercase text-muted">Yeni Şifre (Tekrar)</label>
                    <input type="password" name="confirm_password" class="form-control form-control-lg fs-6" minlength="10" maxlength="72" required placeholder="••••••••••" autocomplete="new-password">
                </div>

                <button type="submit" class="btn btn-gold w-100 py-3">
                    <i class="fa-solid fa-key me-2"></i>Şifreyi Güncelle
                </button>
            </form>
        <?php else: ?>
            <h4 class="fw-bold mb-2 text-dark">Şifrenizi mi Unuttunuz?</h4>
            <p class="text-muted small mb-4">Kayıtlı e-posta adresinizi girin; size şifrenizi sıfırlamanız için güvenli bir bağlantı gönderelim.</p>

            <form method="POST" action="sifre_sifirla.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="request_reset">

                <div class="mb-4">
                    <label class="form-label small fw-bold text-uppercase text-muted">E-Posta Adresiniz</label>
                    <input type="email" name="eposta" class="form-control form-control-lg fs-6" required placeholder="ornek@alanadi.com">
                </div>

                <button type="submit" class="btn btn-gold w-100 py-3">
                    <i class="fa-solid fa-paper-plane me-2"></i>Şifre Sıfırlama Bağlantısı Gönder
                </button>
            </form>

            <div class="text-center mt-4">
                <a href="/" class="text-muted small text-decoration-none">
                    <i class="fa-solid fa-arrow-left me-1"></i> Giriş Ekranına Dön
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
