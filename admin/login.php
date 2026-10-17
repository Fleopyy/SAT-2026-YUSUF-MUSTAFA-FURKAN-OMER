<?php
   
                                               
                                                                          
   

require_once __DIR__ . '/includes/auth.php';

                                                                    
if (getAuthenticatedAdmin()) {
    header('Location: /admin/dashboard');
    exit;
}
if (getAuthenticatedManager()) {
    header('Location: /manager/dashboard');
    exit;
}
if (getAuthenticatedBarber()) {
    header('Location: /barber/dashboard');
    exit;
}

$error = '';
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                         
    if (!verifyCsrfToken()) {
        $error = 'Güvenlik doğrulaması başarısız oldu (CSRF). Lütfen sayfayı yenileyip tekrar deneyin.';
    } elseif (!checkLoginRateLimit($ip)) {
                                         
        $error = 'Çok fazla başarısız giriş denemesi. Güvenliğiniz için lütfen 15 dakika sonra tekrar deneyin.';
    } else {
        $loginInput = trim($_POST['eposta'] ?? '');
        $password = $_POST['sifre'] ?? '';

        if (empty($loginInput) || empty($password)) {
            $error = 'Lütfen e-posta ve şifre alanlarını eksiksiz doldurun.';
        } else {
                                                                              
            $stmt = $pdo->prepare("SELECT * FROM yoneticiler WHERE eposta = ?");
            $stmt->execute([$loginInput]);
            $adminUser = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($adminUser && password_verify($password, $adminUser['sifre_hash'])) {
                if (!$adminUser['aktif']) {
                    recordLoginAttempt($ip, $loginInput, false);
                    $error = 'Hesabınız devre dışı bırakılmıştır. Lütfen sistem yöneticisi ile iletişime geçin.';
                } else {
                                               
                    session_regenerate_id(true);

                                                             
                    unset($_SESSION['admin_id'], $_SESSION['admin_role'], $_SESSION['admin_name']);
                    unset($_SESSION['manager_id'], $_SESSION['manager_role'], $_SESSION['manager_name']);
                    unset($_SESSION['barber_id'], $_SESSION['barber_role'], $_SESSION['barber_name']);

                                                    
                    $pdo->prepare("UPDATE yoneticiler SET son_giris = NOW() WHERE id = ?")->execute([$adminUser['id']]);
                    recordLoginAttempt($ip, $loginInput, true);

                    if ($adminUser['rol'] === 'admin') {
                        $_SESSION['admin_id'] = $adminUser['id'];
                        $_SESSION['admin_role'] = 'admin';
                        $_SESSION['admin_name'] = $adminUser['ad_soyad'];
                        logAdminAction('admin_login', 'yoneticiler', $adminUser['id'], 'Süper Admin paneline giriş yapıldı');
                        header('Location: /admin/dashboard');
                        exit;
                    } elseif ($adminUser['rol'] === 'manager') {
                        $_SESSION['manager_id'] = $adminUser['id'];
                        $_SESSION['manager_role'] = 'manager';
                        $_SESSION['manager_name'] = $adminUser['ad_soyad'];
                        logAdminAction('manager_login', 'yoneticiler', $adminUser['id'], 'Yönetici (Manager) paneline giriş yapıldı');
                        header('Location: /manager/dashboard');
                        exit;
                    }
                }
            } else {
                                                                               
                $stmt = $pdo->prepare("SELECT * FROM isletmeler WHERE eposta = ? OR telefon = ?");
                $stmt->execute([$loginInput, $loginInput]);
                $barber = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($barber && (password_verify($password, $barber['sifre_hash']) || $password === $barber['sifre_hash'])) {
                    if ($barber['durum'] === 'pending') {
                        recordLoginAttempt($ip, $loginInput, false);
                        $error = 'İşletme başvurunuz henüz onay aşamasındadır. İncelendikten sonra SMS/e-posta ile bilgilendirileceksiniz.';
                    } elseif ($barber['durum'] === 'rejected') {
                        recordLoginAttempt($ip, $loginInput, false);
                        $error = 'İşletme başvurunuz onaylanmamıştır.' . (!empty($barber['red_gerekce']) ? ' Gerekçe: ' . e($barber['red_gerekce']) : '');
                    } elseif ($barber['durum'] === 'suspended') {
                        recordLoginAttempt($ip, $loginInput, false);
                        $error = 'İşletme hesabınız askıya alınmıştır. Lütfen destek ekibi ile iletişime geçin.';
                    } else {
                                               
                        session_regenerate_id(true);
                        unset($_SESSION['admin_id'], $_SESSION['admin_role'], $_SESSION['admin_name']);
                        unset($_SESSION['manager_id'], $_SESSION['manager_role'], $_SESSION['manager_name']);

                        $_SESSION['barber_id'] = $barber['id'];
                        $_SESSION['barber_role'] = 'barber';
                        $_SESSION['barber_name'] = $barber['isletme_adi'];
                        recordLoginAttempt($ip, $loginInput, true);

                        header('Location: /barber/dashboard');
                        exit;
                    }
                } else {
                                                                                
                    $stmt = $pdo->prepare("SELECT id FROM kullanicilar WHERE eposta = ?");
                    $stmt->execute([$loginInput]);
                    if ($stmt->fetch()) {
                        recordLoginAttempt($ip, $loginInput, false);
                        $error = 'Müşteri hesapları yönetim panellerine erişemez. Lütfen ana sayfa üzerinden müşteri girişi yapın.';
                    } else {
                                                         
                        recordLoginAttempt($ip, $loginInput, false);
                        $error = 'Geçersiz e-posta adresi veya şifre girdiniz.';
                    }
                }
            }
        }
    }
}

$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yönetici Girişi — BiTıraş.com</title>
                                                                         
    <meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #0B0F19 0%, #111827 50%, #0F172A 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            color: #F8FAFC;
        }
        .login-card-wrapper {
            display: flex;
            max-width: 980px;
            width: 100%;
            border-radius: 24px;
            overflow: hidden;
            background: #0F172A;
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.6);
        }
        .login-hero {
            flex: 1.1;
            background: radial-gradient(circle at top right, rgba(194,136,64,0.18), transparent 70%), #0A0F1D;
            padding: 56px 48px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            border-right: 1px solid rgba(255, 255, 255, 0.06);
        }
        .brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 12px;
        }
        .brand-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: linear-gradient(135deg, #C28840, #935E23);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 20px;
            box-shadow: 0 8px 20px rgba(194,136,64,0.3);
        }
        .brand-title {
            font-size: 20px;
            font-weight: 800;
            color: #fff;
            letter-spacing: -0.5px;
        }
        .brand-title span { color: #E5A955; }
        .hero-body h2 {
            font-size: 28px;
            font-weight: 800;
            line-height: 1.25;
            color: #F8FAFC;
            margin-bottom: 14px;
            letter-spacing: -0.5px;
        }
        .hero-body h2 span {
            background: linear-gradient(135deg, #E5A955, #C28840);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .hero-body p {
            font-size: 14px;
            line-height: 1.6;
            color: #94A3B8;
            margin-bottom: 28px;
        }
        .security-highlights {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .security-item {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 13px;
            color: #CBD5E1;
        }
        .security-item i {
            color: #E5A955;
            font-size: 14px;
        }
        .login-form-area {
            flex: 1;
            padding: 56px 48px;
            background: #111827;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .form-header {
            margin-bottom: 32px;
        }
        .form-header h3 {
            font-size: 22px;
            font-weight: 800;
            color: #F8FAFC;
            margin-bottom: 6px;
        }
        .form-header p {
            font-size: 13px;
            color: #64748B;
        }
        .alert-error {
            background: rgba(220, 38, 38, 0.12);
            border: 1px solid rgba(220, 38, 38, 0.3);
            color: #FCA5A5;
            padding: 14px 16px;
            border-radius: 12px;
            font-size: 13px;
            margin-bottom: 24px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            line-height: 1.4;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: #94A3B8;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .input-wrap {
            position: relative;
        }
        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748B;
            font-size: 15px;
        }
        .form-input {
            width: 100%;
            background: #0B0F19;
            border: 1px solid #1E293B;
            border-radius: 12px;
            padding: 14px 16px 14px 46px;
            color: #F8FAFC;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.2s ease;
        }
        .form-input:focus {
            outline: none;
            border-color: #C28840;
            box-shadow: 0 0 0 3px rgba(194, 136, 64, 0.15);
        }
        .btn-submit {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #C28840 0%, #A66E2C 100%);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.25s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 28px;
            box-shadow: 0 6px 18px rgba(194, 136, 64, 0.25);
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 24px rgba(194, 136, 64, 0.35);
        }
        .form-footer-notice {
            margin-top: 24px;
            text-align: center;
            font-size: 11px;
            color: #475569;
            line-height: 1.5;
        }
        @media (max-width: 820px) {
            .login-card-wrapper { flex-direction: column; }
            .login-hero { padding: 36px 28px; }
            .login-form-area { padding: 36px 28px; }
        }
    </style>
</head>
<body>

<div class="login-card-wrapper">
                             
    <div class="login-hero">
        <div class="brand-badge">
            <div class="brand-icon">
                <i class="fa-solid fa-scissors" style="transform: rotate(90deg);"></i>
            </div>
            <div class="brand-title">BiTıraş<span>.com</span></div>
        </div>

        <div class="hero-body">
            <h2>Yönetim & Denetim <span>Merkezi</span></h2>
            <p>Berber onayları, randevu takibi, personel yetkileri ve sistem ayarlarını yönetmek için güvenli yönetici girişi yapın.</p>
            
            <div class="security-highlights">
                <div class="security-item">
                    <i class="fa-solid fa-shield-check"></i>
                    <span>Uçtan uca CSRF ve brute-force hız koruması</span>
                </div>
                <div class="security-item">
                    <i class="fa-solid fa-lock"></i>
                    <span>Yalnızca yetkili Süper Admin hesaplarına açıktır</span>
                </div>
                <div class="security-item">
                    <i class="fa-solid fa-fingerprint"></i>
                    <span>Tüm işlemler denetim günlüğünde kayıt altına alınır</span>
                </div>
            </div>
        </div>

        <div style="font-size: 11px; color: #475569;">
            © <?= date('Y') ?> BiTıraş.com Yönetim Paneli — Tüm Hakları Saklıdır.
        </div>
    </div>

                              
    <div class="login-form-area">
        <div class="form-header">
            <h3>Admin Girişi</h3>
            <p>Yönetici paneline erişmek için bilgilerinizi girin</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert-error">
                <i class="fa-solid fa-circle-exclamation" style="margin-top: 2px;"></i>
                <div><?= e($error) ?></div>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['logged_out'])): ?>
            <div style="background: rgba(16, 185, 129, 0.12); border: 1px solid rgba(16, 185, 129, 0.3); color: #6EE7B7; padding: 12px 16px; border-radius: 12px; font-size: 13px; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-circle-check"></i>
                <span>Admin oturumu başarıyla kapatıldı.</span>
            </div>
        <?php endif; ?>

        <form method="POST" action="/admin/login.php" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

            <div class="form-group">
                <label class="form-label">Yönetici E-Posta</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-envelope input-icon"></i>
                    <input type="email" name="eposta" required autofocus class="form-input" placeholder="admin@bitiras.com" value="<?= e($_POST['eposta'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Şifre</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-lock input-icon"></i>
                    <input type="password" name="sifre" required class="form-input" placeholder="••••••••">
                </div>
            </div>

            <button type="submit" class="btn-submit">
                <i class="fa-solid fa-right-to-bracket"></i>
                <span>Güvenli Giriş Yap</span>
            </button>
        </form>

        <div class="form-footer-notice">
            Bu ekran yalnızca BiTıraş.com sistem yöneticilerine tahsis edilmiştir. Müşteri ve işletme girişleri bu panelden kabul edilmemektedir.
        </div>
    </div>
</div>

</body>
</html>
