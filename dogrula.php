<?php
require_once __DIR__ . '/data/privacy.php';
require_once __DIR__ . '/data/security.php';
require_once __DIR__ . '/data/customer_auth_service.php';

$message = '';
$messageType = '';
$token = trim($_GET['token'] ?? '');

if (!empty($token)) {
    $result = verifyCustomerEmailToken($token);
    $message = $result['message'];
    $messageType = $result['success'] ? 'success' : 'danger';
    if ($result['success'] && isset($_SESSION['user_id'])) {
        $_SESSION['customer_flash'] = ['type' => 'success', 'message' => $result['message']];
        header('Location: /musteri/hesabim.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $email = trim($_POST['eposta'] ?? '');
    $code = trim($_POST['kod'] ?? '');

    if (!empty($email) && !empty($code)) {
        $stmt = $pdo->prepare("SELECT id FROM kullanicilar WHERE LOWER(eposta) = ?");
        $stmt->execute([strtolower($email)]);
        $uid = $stmt->fetchColumn();

        if ($uid) {
            $result = verifyCustomerEmailCode((int)$uid, $code);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'danger';
            if ($result['success'] && isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] === (int)$uid) {
                $_SESSION['customer_flash'] = ['type' => 'success', 'message' => $result['message']];
                header('Location: /musteri/hesabim.php');
                exit;
            }
        } else {
            $message = 'Girdiğiniz e-posta veya doğrulama kodu hatalı.';
            $messageType = 'danger';
        }
    } else {
        $message = 'Lütfen tüm alanları doldurun.';
        $messageType = 'warning';
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Posta Doğrulama — BİTİRAS</title>
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
        <div style="font-size: 12px; color: #94A3B8; text-transform: uppercase; letter-spacing: 1px; margin-top: 4px;">Hesap Doğrulama Merkezi</div>
    </div>
    <div class="auth-body">
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $messageType ?> d-flex align-items-center gap-2 mb-4" role="alert">
                <i class="fa-solid <?= $messageType === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
                <div><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        <?php endif; ?>

        <?php if ($messageType === 'success'): ?>
            <div class="text-center py-2">
                <p class="text-muted mb-4">Hesabınız onaylandı. Şimdi güvenle giriş yapabilir veya profilinize dönebilirsiniz.</p>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="/musteri/hesabim.php" class="btn btn-gold w-100"><i class="fa-solid fa-user me-2"></i>Hesabıma Git</a>
                <?php else: ?>
                    <a href="/" class="btn btn-gold w-100"><i class="fa-solid fa-right-to-bracket me-2"></i>Giriş Yap</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <h4 class="fw-bold mb-2 text-dark">E-Posta Doğrulama Kodu</h4>
            <p class="text-muted small mb-4">Kayıt sırasında e-posta adresinize gönderilen 6 haneli kodu aşağıya girerek hesabınızı doğrulayabilirsiniz.</p>

            <form method="POST" action="dogrula.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                
                <div class="mb-3">
                    <label class="form-label small fw-bold text-uppercase text-muted">E-Posta Adresiniz</label>
                    <input type="email" name="eposta" class="form-control" required placeholder="ornek@alanadi.com" value="<?= htmlspecialchars($_POST['eposta'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold text-uppercase text-muted">6 Haneli Doğrulama Kodu</label>
                    <input type="text" name="kod" class="form-control form-control-lg text-center fw-bold letter-spacing-2" maxlength="6" pattern="[0-9]{6}" required placeholder="••••••" style="letter-spacing: 6px; font-size: 24px;">
                </div>

                <button type="submit" class="btn btn-gold w-100 py-3">
                    <i class="fa-solid fa-check-double me-2"></i>Kodu Doğrula ve Onayla
                </button>
            </form>

            <div class="text-center mt-4">
                <a href="/" class="text-muted small text-decoration-none">
                    <i class="fa-solid fa-arrow-left me-1"></i> Ana Sayfaya Dön
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
