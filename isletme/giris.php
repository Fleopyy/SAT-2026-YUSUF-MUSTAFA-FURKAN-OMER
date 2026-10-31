<?php
require_once __DIR__ . '/../data/privacy.php';
require_once dirname(__DIR__) . '/admin/includes/auth.php';
require_once dirname(__DIR__) . '/data/two_factor_service.php';

if (getAuthenticatedBarber()) {
    header('Location: /isletme/');
    exit;
}

$error = '';
$loginInput = '';
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

if (($_GET['step'] ?? '') === 'cancel') {
    unset($_SESSION['2fa_pending']);
    header('Location: /isletme/giris.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'resend_2fa') {
    if (!verifyCsrfToken()) {
        http_response_code(403);
        exit('CSRF dogrulamasi basarisiz.');
    }
    $pending = $_SESSION['2fa_pending'] ?? null;
    if ($pending && !empty($pending['user_id']) && ($pending['expires'] ?? 0) > time() && ($pending['attempts'] ?? 0) < 8 && ($pending['resends'] ?? 0) < 3) {
        $now = time();
        $lastResend = (int)($pending['last_resend'] ?? (($pending['expires'] ?? 300) - 300));
        if (($now - $lastResend) < 60) {
            $_SESSION['2fa_resend_flash'] = 'Lütfen yeni kod istemeden önce 60 saniye bekleyin.';
        } else {
            $_SESSION['2fa_pending']['last_resend'] = $now;
            $_SESSION['2fa_pending']['resends'] = (int)($pending['resends'] ?? 0) + 1;
            $resendOk = sendBarber2faEmail((int)$pending['user_id'], $pending['email'], $pending['name']);
            if (!$resendOk) {
                $_SESSION['2fa_resend_flash'] = 'Kod gönderilemedi veya gönderim sınırına ulaşıldı. Lütfen tekrar deneyin.';
            } else {
                $_SESSION['2fa_resend_flash'] = 'Doğrulama kodu e-posta adresinize tekrar gönderildi.';
            }
        }
    } else {
        $_SESSION['2fa_resend_flash'] = 'Doğrulama oturumu bulunamadı. Lütfen giriş ekranından tekrar deneyin.';
    }
    header('Location: /isletme/giris.php?step=2fa');
    exit;
}

$resendFlash = $_SESSION['2fa_resend_flash'] ?? '';
unset($_SESSION['2fa_resend_flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? 'login';

    if ($postAction === 'verify_2fa') {
        if (!verifyCsrfToken()) {
            $error = 'Güvenlik doğrulaması başarısız oldu (CSRF). Sayfayı yenileyip tekrar deneyin.';
        } elseif (!checkLoginRateLimit($ip, 5, 15)) {
            $error = 'Çok fazla başarısız deneme. Lütfen 15 dakika sonra tekrar deneyin.';
        } else {
            $pending = $_SESSION['2fa_pending'] ?? null;
            if (!$pending || ($pending['expires'] ?? 0) < time() || ($pending['attempts'] ?? 0) >= 8 || ($pending['type'] ?? '') !== 'barber') {
                unset($_SESSION['2fa_pending']);
                $error = 'Doğrulama süresi doldu. Lütfen tekrar giriş yapın.';
            } else {
                $challengeStmt = $pdo->prepare("SELECT sifre_hash FROM isletmeler WHERE id = ? AND durum = 'active'");
                $challengeStmt->execute([$pending['user_id']]);
                $currentHash = $challengeStmt->fetchColumn();

                if (!is_string($currentHash) || !hash_equals($pending['hash'], $currentHash)) {
                    unset($_SESSION['2fa_pending']);
                    header('Location: /isletme/giris.php');
                    exit;
                }

                $_SESSION['2fa_pending']['attempts'] = (int)($pending['attempts'] ?? 0) + 1;
                $code = trim($_POST['two_factor_code'] ?? '');
                $vRes = verifyBarber2faCode((int)$pending['user_id'], $code);

                if ($vRes['success']) {
                    registerTrustedDevice('isletme', (int)$pending['user_id'], 30);
                    sendLoginSecurityAlert('isletme', (int)$pending['user_id'], $pending['email'], $pending['name']);
                    renewAuthenticatedSession('barber', $pending['hash']);
                    unset($_SESSION['admin_id'], $_SESSION['admin_role'], $_SESSION['admin_name']);
                    unset($_SESSION['manager_id'], $_SESSION['manager_role'], $_SESSION['manager_name']);
                    unset($_SESSION['user_id'], $_SESSION['user_name']);

                    $_SESSION['user_role'] = 'barber';
                    $_SESSION['barber_id'] = $pending['user_id'];
                    $_SESSION['barber_role'] = 'barber';
                    $_SESSION['barber_name'] = $pending['name'];

                    $pdo->prepare("UPDATE isletmeler SET son_giris = NOW() WHERE id = ?")->execute([$pending['user_id']]);
                    recordLoginAttempt($ip, $pending['email'], true);

                    unset($_SESSION['2fa_pending']);
                    header('Location: /isletme/');
                    exit;
                } else {
                    $error = $vRes['message'];
                }
            }
        }
    } else {
        $loginInput = trim(is_string($_POST['login'] ?? null) ? $_POST['login'] : '');
        $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

        if (!verifyCsrfToken()) {
            $error = 'Güvenlik doğrulaması başarısız oldu. Sayfayı yenileyip tekrar deneyin.';
        } elseif (!checkLoginRateLimit($ip, 5, 15)) {
            $error = 'Çok fazla başarısız giriş denemesi. Lütfen 15 dakika sonra tekrar deneyin.';
        } elseif ($loginInput === '' || $password === '') {
            recordLoginAttempt($ip, $loginInput, false);
            $error = 'E-posta/telefon ve şifre alanlarını doldurun.';
        } else {
            $stmt = $pdo->prepare('SELECT * FROM isletmeler WHERE eposta = ? OR telefon = ? LIMIT 1');
            $stmt->execute([$loginInput, $loginInput]);
            $business = $stmt->fetch(PDO::FETCH_ASSOC);
            $passwordMatches = $business
                && password_verify($password, $business['sifre_hash']);

            if (!$passwordMatches) {
                recordLoginAttempt($ip, $loginInput, false);
                $error = 'E-posta/telefon veya şifre hatalı.';
            } elseif ($business['durum'] !== 'active') {
                recordLoginAttempt($ip, $loginInput, false);
                $error = match ($business['durum']) {
                    'pending' => 'İşletme başvurunuz henüz onaylanmadı.',
                    'suspended' => 'İşletme hesabınız askıya alınmış. Destek ekibiyle iletişime geçin.',
                    'rejected' => 'İşletme başvurunuz onaylanmadı.',
                    default => 'İşletme hesabınız şu anda giriş yapamıyor.',
                };
            } else {
                completeBarberPasswordLogin($business, $loginInput, '/isletme/');
            }
        }
    }
}

$is2faStep = (($_GET['step'] ?? '') === '2fa');
$pending = $_SESSION['2fa_pending'] ?? null;
if ($is2faStep && (!$pending || ($pending['expires'] ?? 0) < time() || ($pending['type'] ?? '') !== 'barber')) {
    unset($_SESSION['2fa_pending']);
    $is2faStep = false;
}

$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= $is2faStep ? 'Güvenlik Doğrulaması' : 'İşletme Girişi' ?> — BiTıraş</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { min-height: 100vh; margin: 0; display: grid; place-items: center; padding: 24px; background: #F1F5F9; color: #0F172A; font-family: 'Plus Jakarta Sans', sans-serif; }
        .login-card { width: min(100%, 460px); padding: 36px; border: 1px solid #E2E8F0; border-radius: 20px; background: #fff; box-shadow: 0 18px 45px rgba(15,23,42,.09); }
        .brand { display: flex; align-items: center; gap: 12px; margin-bottom: 24px; }
        .brand-icon { display: grid; width: 46px; height: 46px; place-items: center; border-radius: 13px; background: #0F172A; color: #E5A955; font-size: 20px; }
        .brand strong { display: block; font-size: 18px; }
        .brand small { color: #64748B; font-size: 11px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
        h1 { margin: 0 0 8px; font-size: 22px; font-weight: 800; color: #0F172A; }
        .intro { margin: 0 0 22px; color: #64748B; font-size: 13.5px; line-height: 1.55; }
        label { display: block; margin: 16px 0 7px; font-size: 13px; font-weight: 700; color: #334155; }
        input[type="text"], input[type="password"] { width: 100%; padding: 12px 14px; border: 1.5px solid #CBD5E1; border-radius: 10px; font: inherit; font-size: 14px; transition: border-color .15s; }
        input:focus { outline: 3px solid rgba(194,136,64,.18); border-color: #C28840; }
        .btn-primary-action { width: 100%; margin-top: 20px; padding: 13px; border: 0; border-radius: 10px; background: #C28840; color: #fff; font: inherit; font-weight: 800; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 15px; transition: background .15s; }
        .btn-primary-action:hover { background: #A66E2C; }
        .error-box { margin: 0 0 16px; padding: 12px 15px; border: 1px solid #FECACA; border-radius: 10px; background: #FEF2F2; color: #991B1B; font-size: 13px; display: flex; align-items: flex-start; gap: 10px; }
        .flash-box { margin: 0 0 16px; padding: 12px 15px; border: 1px solid #A7F3D0; border-radius: 10px; background: #ECFDF5; color: #065F46; font-size: 13px; display: flex; align-items: center; gap: 10px; }
        .code-input { letter-spacing: 8px; font-size: 24px; text-align: center; font-weight: 800; padding: 12px; }
        .actions-row { display: flex; justify-content: space-between; align-items: center; margin-top: 22px; font-size: 13px; }
        .btn-resend { background: none; border: 0; color: #C28840; font-weight: 700; cursor: pointer; padding: 0; display: inline-flex; align-items: center; gap: 6px; font-family: inherit; font-size: 13px; }
        .btn-resend:hover { color: #A66E2C; text-decoration: underline; }
        .back-link { color: #64748B; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
        .back-link:hover { color: #0F172A; }
        .info-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 6px; background: #F1F5F9; color: #475569; font-size: 12px; font-weight: 600; margin-bottom: 14px; }
        @media (max-width: 480px) { .login-card { padding: 26px 20px; } }
    </style>
</head>
<body>
    <main class="login-card">
        <div class="brand">
            <div class="brand-icon"><i class="fa-solid fa-scissors" aria-hidden="true"></i></div>
            <div><strong>BiTıraş<span style="color:#C28840">.com</span></strong><small>İşletme Paneli</small></div>
        </div>

        <?php if ($is2faStep): ?>
            <!-- 2FA DOĞRULAMA ADIMI -->
            <div class="info-badge">
                <i class="fa-solid fa-shield-halved" style="color: #C28840;"></i> İki Faktörlü Doğrulama
            </div>

            <h1>Güvenlik Doğrulaması</h1>
            <p class="intro">
                İşletme hesabınızın güvenliği için (<?= htmlspecialchars($pending['reason'] ?? 'Güvenlik doğrulaması', ENT_QUOTES, 'UTF-8') ?>),
                <strong><?= htmlspecialchars($pending['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong> adresinize gönderilen 6 haneli doğrulama kodunu girin.
            </p>

            <?php if ($error !== ''): ?>
                <div class="error-box" role="alert">
                    <i class="fa-solid fa-circle-exclamation" style="margin-top: 2px;"></i>
                    <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            <?php endif; ?>

            <?php if (!empty($resendFlash)): ?>
                <div class="flash-box" role="status">
                    <i class="fa-solid fa-circle-check"></i>
                    <div><?= htmlspecialchars($resendFlash, ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" action="/isletme/giris.php?step=2fa" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="verify_2fa">

                <label for="twoFactorCodeInput">6 Haneli Doğrulama Kodu</label>
                <input type="text" id="twoFactorCodeInput" name="two_factor_code" required autofocus autocomplete="one-time-code" inputmode="numeric" maxlength="6" class="code-input" placeholder="••••••">
                <div style="font-size:11px;color:#94A3B8;margin-top:6px;text-align:center;">
                    Kodunuz 5 dakika boyunca geçerlidir.
                </div>

                <button type="submit" class="btn-primary-action">
                    <i class="fa-solid fa-shield-check"></i> Doğrula ve İşletme Paneline Gir
                </button>
            </form>

            <div class="actions-row">
                <form method="POST" action="/isletme/giris.php?step=2fa" style="margin:0;">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit" name="action" value="resend_2fa" class="btn-resend">
                        <i class="fa-solid fa-rotate-right"></i> Yeni Kod Gönder
                    </button>
                </form>

                <a href="/isletme/giris.php?step=cancel" class="back-link">
                    <i class="fa-solid fa-arrow-left"></i> Giriş Ekranına Dön
                </a>
            </div>

        <?php else: ?>
            <!-- NORMAL İŞLETME GİRİŞ ADIMI -->
            <h1>İşletme hesabınıza giriş yapın</h1>
            <p class="intro">Randevularınızı ve işletme bilgilerinizi kendi yönetim alanınızdan yönetin.</p>

            <?php if ($error !== ''): ?>
                <div class="error-box" role="alert">
                    <i class="fa-solid fa-circle-exclamation" style="margin-top: 2px;"></i>
                    <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" action="/isletme/giris.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="login">

                <label for="login">E-posta veya telefon</label>
                <input id="login" name="login" type="text" autocomplete="username" required value="<?= htmlspecialchars($loginInput, ENT_QUOTES, 'UTF-8') ?>">

                <label for="password">Şifre</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required>

                <button type="submit" class="btn-primary-action">
                    <i class="fa-solid fa-arrow-right-to-bracket"></i> İşletme Paneline Giriş Yap
                </button>
            </form>

            <div style="text-align: center; margin-top: 22px;">
                <a class="back-link" href="/">
                    <i class="fa-solid fa-arrow-left"></i> Ana sayfaya dön
                </a>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
