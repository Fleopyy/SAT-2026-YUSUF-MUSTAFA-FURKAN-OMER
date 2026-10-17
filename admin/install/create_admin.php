<?php
   
                                                                                
   
             
                                                                   
                                                                      
   

require_once __DIR__ . '/../includes/auth.php';

if (php_sapi_name() !== 'cli') {
    die("Bu script yalnızca komut satırından (CLI) çalıştırılabilir.\n");
}

echo "====================================================\n";
echo "  BiTıraş.com — Güvenli Admin Hesabı Oluşturucu\n";
echo "====================================================\n\n";

$email = $argv[1] ?? '';
$password = $argv[2] ?? '';
$name = $argv[3] ?? '';

if (empty($email)) {
    echo "E-posta adresi: ";
    $email = trim(fgets(STDIN));
}

if (empty($password)) {
    echo "Şifre (en az 8 karakter): ";
    $password = trim(fgets(STDIN));
}

if (empty($name)) {
    echo "Ad Soyad: ";
    $name = trim(fgets(STDIN));
    if (empty($name)) {
        $name = 'Süper Admin';
    }
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("HATA: Geçersiz e-posta formatı.\n");
}

if (strlen($password) < 8) {
    die("HATA: Şifre en az 8 karakter olmalıdır.\n");
}

                            
$stmt = $pdo->prepare("SELECT id FROM yoneticiler WHERE eposta = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    die("HATA: Bu e-posta adresine ait bir yönetici hesabı zaten mevcut.\n");
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT INTO yoneticiler (ad_soyad, eposta, sifre_hash, rol, aktif) VALUES (?, ?, ?, 'admin', 1)");
$stmt->execute([$name, $email, $hash]);
$adminId = (int)$pdo->lastInsertId();

logAdminAction('cli_admin_create', 'yoneticiler', $adminId, "CLI üzerinden admin hesabı oluşturuldu: {$email}");

echo "\n[BAŞARILI] Admin hesabı başarıyla oluşturuldu!\n";
echo "  ID:       {$adminId}\n";
echo "  Ad Soyad: {$name}\n";
echo "  E-posta:  {$email}\n";
echo "  Rol:      Süper Admin\n\n";
echo "Giriş yapmak için: http://localhost/admin/login.php\n";
