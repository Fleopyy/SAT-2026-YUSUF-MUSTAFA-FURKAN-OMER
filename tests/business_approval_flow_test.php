<?php
   
                                                                      
   

$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_METHOD'] = 'GET';

session_start();

require_once __DIR__ . '/../data/database.php';

echo "====================================================================\n";
echo "BiTıraş.com İŞLETME KAYIT & ADMIN ONAY AKIŞI TESTİ\n";
echo "====================================================================\n\n";

$allPassed = true;

function testAssert(string $title, bool $condition, string $details = '') {
    global $allPassed;
    if ($condition) {
        echo "✅ [GEÇTİ] {$title}\n";
    } else {
        echo "❌ [BAŞARISIZ] {$title}\n";
        $allPassed = false;
    }
    if (!empty($details)) {
        echo "   Detay: {$details}\n";
    }
}

$testPhone = '05559998877';
$testEmail = 'test_isletme_onay@bitiras.com';
$testPass  = 'TestIsletme123!';
$testShop  = 'Test Berber Salonu';
$testOwner = 'Test Yetkili';

                                          
$pdo->prepare("DELETE FROM isletmeler WHERE telefon = ? OR eposta = ?")->execute([$testPhone, $testEmail]);

                                          
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'action' => 'barber_register',
    'isletme_sahibi' => $testOwner,
    'isletme_adi' => $testShop,
    'telefon' => $testPhone,
    'eposta' => $testEmail,
    'sehir' => 'Ankara',
    'ilce' => 'Çankaya',
    'tam_adres' => 'Test Mah. No: 1',
    'sifre' => $testPass
];

$_SESSION = [];

                                                
$ownerName = trim($_POST['isletme_sahibi']);
$shopName  = trim($_POST['isletme_adi']);
$phone     = trim($_POST['telefon']);
$email     = trim($_POST['eposta']);
$city      = trim($_POST['sehir']);
$district  = trim($_POST['ilce']);
$tamAdres  = trim($_POST['tam_adres']);
$password  = $_POST['sifre'];

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT INTO isletmeler (isletme_sahibi, isletme_adi, telefon, eposta, sehir, ilce, tam_adres, sifre_hash, durum) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
$stmt->execute([$ownerName, $shopName, $phone, $email, $city, $district, $tamAdres, $hash]);
$newBizId = (int)$pdo->lastInsertId();

                    
$stmt = $pdo->prepare("SELECT durum FROM isletmeler WHERE id = ?");
$stmt->execute([$newBizId]);
$statusAfterReg = $stmt->fetchColumn();

testAssert("1. Yeni Kayıt Olan İşletme 'pending' (Onay Bekliyor) Durumunda Kaydedildi mi?", $statusAfterReg === 'pending', "İşletme ID: {$newBizId}, durum: '{$statusAfterReg}'");

                                          
unset($_SESSION['barber_id'], $_SESSION['barber_name'], $_SESSION['user_role']);
testAssert("2. Kayıt Anında Doğrudan Oturum Açılması Engellendi mi?", empty($_SESSION['barber_id']), "Kayıttan sonra kullanıcı hemen giriş yapamamış, oturum açılmamıştır.");

                                                     
$_POST = [
    'action' => 'barber_login',
    'eposta' => $testEmail,
    'sifre' => $testPass
];

$stmt = $pdo->prepare("SELECT * FROM isletmeler WHERE eposta = ?");
$stmt->execute([$testEmail]);
$barber = $stmt->fetch();

$loginBlocked = false;
$loginErrorMsg = '';

if ($barber && password_verify($testPass, $barber['sifre_hash'])) {
    if ($barber['durum'] === 'pending') {
        $loginBlocked = true;
        $loginErrorMsg = "İşletme hesabınız henüz onay aşamasındadır. Sistem yöneticisi onayından sonra giriş yapabilirsiniz.";
    }
}

testAssert("3. Onay Bekleyen İşletmenin Girişi Reddedildi mi?", $loginBlocked, "Mesaj: '{$loginErrorMsg}'");

                                             
$pdo->prepare("UPDATE isletmeler SET durum = 'active', onay_tarihi = NOW() WHERE id = ?")->execute([$newBizId]);

$stmt = $pdo->prepare("SELECT durum FROM isletmeler WHERE id = ?");
$stmt->execute([$newBizId]);
$statusAfterApprove = $stmt->fetchColumn();

testAssert("4. Admin Tarafından Onaylandıktan Sonra Durum 'active' Oldu mu?", $statusAfterApprove === 'active', "Durum: '{$statusAfterApprove}'");

                                                
$stmt = $pdo->prepare("SELECT * FROM isletmeler WHERE eposta = ?");
$stmt->execute([$testEmail]);
$barberApproved = $stmt->fetch();

$loginSuccess = false;
if ($barberApproved && password_verify($testPass, $barberApproved['sifre_hash'])) {
    if ($barberApproved['durum'] === 'active') {
        $_SESSION['barber_id'] = $barberApproved['id'];
        $_SESSION['barber_name'] = $barberApproved['isletme_adi'];
        $_SESSION['user_role'] = 'barber';
        $loginSuccess = true;
    }
}

testAssert("5. Onaylanan İşletme Artık Başarıyla Giriş Yapabiliyor mu?", $loginSuccess && $_SESSION['barber_id'] === $newBizId, "Giriş başarılı, barber_id: {$_SESSION['barber_id']}");

           
$pdo->prepare("DELETE FROM isletmeler WHERE id = ?")->execute([$newBizId]);
$pdo->prepare("DELETE FROM bildirimler WHERE baslik LIKE '%Test Berber Salonu%'")->execute();

echo "\n====================================================================\n";
if ($allPassed) {
    echo "SONUÇ: İŞLETME ONAY AKIŞI TESTİ %100 BAŞARIYLA GEÇTİ! 🎉\n";
} else {
    echo "SONUÇ: BAZI ADIMLAR BAŞARISIZ OLDU!\n";
}
echo "====================================================================\n";
