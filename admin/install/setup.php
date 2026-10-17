<?php
   
                                         
                                                      
                                                   
   

require_once __DIR__ . '/../includes/auth.php';

                                      
$adminCount = (int)$pdo->query("SELECT COUNT(*) FROM yoneticiler WHERE rol = 'admin'")->fetchColumn();
if ($adminCount > 0) {
    die('<div style="font-family:Inter,sans-serif;max-width:500px;margin:100px auto;text-align:center;padding:40px;background:#1E293B;color:#fff;border-radius:16px;">
        <h2 style="color:#E5A955;">⚠️ Kurulum Tamamlandı</h2>
        <p>Admin hesabı zaten oluşturulmuş. Bu sayfaya artık erişilemez.</p>
        <a href="/admin/login.php" style="color:#E5A955;">Admin Girişine Git →</a>
    </div>');
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['ad_soyad'] ?? '');
    $email = trim($_POST['eposta'] ?? '');
    $password = $_POST['sifre'] ?? '';
    $passwordConfirm = $_POST['sifre_tekrar'] ?? '';
    
    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Tüm alanları doldurun.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Geçerli bir e-posta adresi girin.';
    } elseif (strlen($password) < 8) {
        $error = 'Şifre en az 8 karakter olmalıdır.';
    } elseif ($password !== $passwordConfirm) {
        $error = 'Şifreler uyuşmuyor.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO yoneticiler (ad_soyad, eposta, sifre_hash, rol) VALUES (?, ?, ?, 'admin')");
        $stmt->execute([$name, $email, $hash]);
        
        logAdminAction('admin_setup', 'yoneticiler', (int)$pdo->lastInsertId(), 'İlk admin hesabı oluşturuldu');
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BiTıraş — Admin Kurulumu</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #0F172A 0%, #1E293B 50%, #0F172A 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .setup-card {
            background: #fff;
            border-radius: 20px;
            padding: 48px;
            max-width: 480px;
            width: 100%;
            box-shadow: 0 25px 50px rgba(0,0,0,0.3);
        }
        .logo { text-align: center; margin-bottom: 32px; }
        .logo-box {
            width: 56px; height: 56px;
            background: #0F172A;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #E5A955;
            font-size: 22px;
            margin-bottom: 12px;
        }
        .logo h1 { font-size: 22px; color: #0F172A; font-weight: 800; }
        .logo h1 span { color: #C28840; }
        .logo p { color: #64748B; font-size: 13px; margin-top: 4px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-size: 12px; font-weight: 700; color: #64748B; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
        input {
            width: 100%; padding: 14px 16px;
            border: 2px solid #E2E8F0;
            border-radius: 12px;
            font-family: inherit;
            font-size: 15px;
            transition: border-color 0.2s;
            outline: none;
        }
        input:focus { border-color: #C28840; }
        .btn {
            width: 100%; padding: 16px;
            background: #C28840;
            color: #fff;
            border: none;
            border-radius: 12px;
            font-family: inherit;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn:hover { background: #A66E2C; }
        .error { background: #FEF2F2; color: #DC2626; padding: 12px; border-radius: 10px; font-size: 14px; margin-bottom: 16px; }
        .success-box { text-align: center; }
        .success-box .check { width: 64px; height: 64px; background: #ECFDF5; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; color: #059669; font-size: 28px; margin-bottom: 16px; }
        .success-box h2 { font-size: 20px; color: #0F172A; margin-bottom: 8px; }
        .success-box p { color: #64748B; font-size: 14px; margin-bottom: 24px; }
        .success-box a { color: #C28840; font-weight: 700; text-decoration: none; }
        .warning { background: #FFFBEB; color: #92400E; padding: 12px; border-radius: 10px; font-size: 13px; margin-bottom: 20px; display: flex; gap: 8px; align-items: flex-start; }
    </style>
</head>
<body>
    <div class="setup-card">
        <div class="logo">
            <div class="logo-box"><i class="fa-solid fa-scissors" style="transform:rotate(90deg)"></i></div>
            <h1>BiTıraş<span>.com</span></h1>
            <p>Admin Hesap Kurulumu</p>
        </div>

        <?php if ($success): ?>
            <div class="success-box">
                <div class="check"><i class="fa-solid fa-check"></i></div>
                <h2>Admin Hesabı Oluşturuldu!</h2>
                <p>Artık admin paneline giriş yapabilirsiniz. Bu kurulum sayfasına artık erişilemeyecektir.</p>
                <a href="/admin/login.php"><i class="fa-solid fa-right-to-bracket"></i> Admin Girişine Git →</a>
            </div>
        <?php else: ?>
            <div class="warning">
                <i class="fa-solid fa-triangle-exclamation" style="margin-top:2px"></i>
                <span>Bu form yalnızca ilk admin hesabını oluşturmak için kullanılır. Oluşturulduktan sonra bu sayfaya erişim otomatik olarak kapanır.</span>
            </div>

            <?php if ($error): ?>
                <div class="error"><i class="fa-solid fa-circle-xmark"></i> <?= e($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="ad_soyad">Ad Soyad</label>
                    <input type="text" id="ad_soyad" name="ad_soyad" required placeholder="Yönetici adı soyadı" value="<?= e($_POST['ad_soyad'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="eposta">E-Posta Adresi</label>
                    <input type="email" id="eposta" name="eposta" required placeholder="admin@bitiras.com" value="<?= e($_POST['eposta'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="sifre">Şifre (Min. 8 Karakter)</label>
                    <input type="password" id="sifre" name="sifre" required minlength="8" placeholder="••••••••">
                </div>
                <div class="form-group">
                    <label for="sifre_tekrar">Şifre Tekrar</label>
                    <input type="password" id="sifre_tekrar" name="sifre_tekrar" required minlength="8" placeholder="••••••••">
                </div>
                <button type="submit" class="btn"><i class="fa-solid fa-shield-halved"></i> Admin Hesabı Oluştur</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
