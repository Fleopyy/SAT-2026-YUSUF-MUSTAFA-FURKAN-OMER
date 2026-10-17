<?php
require_once dirname(__DIR__) . '/admin/includes/auth.php';

if (getAuthenticatedBarber()) {
    header('Location: /isletme/');
    exit;
}

$error = '';
$loginInput = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $loginInput = trim(is_string($_POST['login'] ?? null) ? $_POST['login'] : '');
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

    if (!verifyCsrfToken()) {
        $error = 'Güvenlik doğrulaması başarısız oldu. Sayfayı yenileyip tekrar deneyin.';
    } elseif (!checkLoginRateLimit($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', 5, 15)) {
        $error = 'Çok fazla başarısız giriş denemesi. Lütfen 15 dakika sonra tekrar deneyin.';
    } elseif ($loginInput === '' || $password === '') {
        $error = 'E-posta/telefon ve şifre alanlarını doldurun.';
    } else {
        $stmt = $pdo->prepare('SELECT id, isletme_adi, sifre_hash, durum FROM isletmeler WHERE eposta = ? OR telefon = ? LIMIT 1');
        $stmt->execute([$loginInput, $loginInput]);
        $business = $stmt->fetch(PDO::FETCH_ASSOC);
        $passwordMatches = $business
            && (password_verify($password, $business['sifre_hash']) || hash_equals($business['sifre_hash'], $password));

        if (!$passwordMatches) {
            recordLoginAttempt($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', $loginInput, false);
            $error = 'E-posta/telefon veya şifre hatalı.';
        } elseif ($business['durum'] !== 'active') {
            recordLoginAttempt($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', $loginInput, false);
            $error = match ($business['durum']) {
                'pending' => 'İşletme başvurunuz henüz onaylanmadı.',
                'suspended' => 'İşletme hesabınız askıya alınmış. Destek ekibiyle iletişime geçin.',
                'rejected' => 'İşletme başvurunuz onaylanmadı.',
                default => 'İşletme hesabınız şu anda giriş yapamıyor.',
            };
        } else {
            session_regenerate_id(true);
            unset(
                $_SESSION['admin_id'], $_SESSION['admin_role'], $_SESSION['admin_name'],
                $_SESSION['manager_id'], $_SESSION['manager_role'], $_SESSION['manager_name'],
                $_SESSION['user_id'], $_SESSION['user_name']
            );
            $_SESSION['barber_id'] = (int)$business['id'];
            $_SESSION['barber_role'] = 'barber';
            $_SESSION['barber_name'] = $business['isletme_adi'];
            $_SESSION['user_role'] = 'barber';
            recordLoginAttempt($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', $loginInput, true);
            header('Location: /isletme/');
            exit;
        }
    }
}

$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>İşletme Girişi — BiTıraş</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        body { min-height: 100vh; margin: 0; display: grid; place-items: center; padding: 24px; background: #F1F5F9; color: #0F172A; font-family: 'Plus Jakarta Sans', sans-serif; }
        .login-card { width: min(100%, 440px); padding: 36px; border: 1px solid #E2E8F0; border-radius: 20px; background: #fff; box-shadow: 0 18px 45px rgba(15,23,42,.09); }
        .brand { display: flex; align-items: center; gap: 12px; margin-bottom: 28px; }
        .brand-icon { display: grid; width: 46px; height: 46px; place-items: center; border-radius: 13px; background: #0F172A; color: #E5A955; }
        .brand strong { display: block; font-size: 18px; }
        .brand small { color: #64748B; font-size: 11px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
        h1 { margin: 0 0 8px; font-size: 24px; }
        .intro { margin: 0 0 24px; color: #64748B; font-size: 14px; line-height: 1.6; }
        label { display: block; margin: 16px 0 7px; font-size: 13px; font-weight: 700; }
        input { width: 100%; padding: 12px 14px; border: 1px solid #CBD5E1; border-radius: 9px; font: inherit; }
        input:focus { outline: 3px solid rgba(194,136,64,.18); border-color: #C28840; }
        button { width: 100%; margin-top: 22px; padding: 13px; border: 0; border-radius: 9px; background: #C28840; color: #fff; font: inherit; font-weight: 800; cursor: pointer; }
        button:hover { background: #A66E2C; }
        .error { margin: 0 0 16px; padding: 12px 14px; border: 1px solid #FECACA; border-radius: 9px; background: #FEF2F2; color: #991B1B; font-size: 13px; }
        .back { display: block; margin-top: 20px; color: #64748B; text-align: center; text-decoration: none; font-size: 13px; font-weight: 700; }
        .back:hover { color: #A66E2C; }
        @media (max-width: 480px) { .login-card { padding: 26px 22px; } }
    </style>
</head>
<body>
    <main class="login-card">
        <div class="brand">
            <div class="brand-icon"><i class="fa-solid fa-scissors" aria-hidden="true"></i></div>
            <div><strong>BiTıraş<span style="color:#C28840">.com</span></strong><small>İşletme Paneli</small></div>
        </div>
        <h1>İşletme hesabınıza giriş yapın</h1>
        <p class="intro">Randevularınızı ve işletme bilgilerinizi kendi yönetim alanınızdan yönetin.</p>
        <?php if ($error !== ''): ?>
            <div class="error" role="alert"><?= e($error) ?></div>
        <?php endif; ?>
        <form method="POST" action="/isletme/giris.php">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <label for="login">E-posta veya telefon</label>
            <input id="login" name="login" type="text" autocomplete="username" required value="<?= e($loginInput) ?>">
            <label for="password">Şifre</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>
            <button type="submit">İşletme Paneline Giriş Yap</button>
        </form>
        <a class="back" href="/">Ana sayfaya dön</a>
    </main>
</body>
</html>
