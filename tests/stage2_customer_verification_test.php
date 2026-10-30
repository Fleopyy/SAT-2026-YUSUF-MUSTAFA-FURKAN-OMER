<?php
// CLI only. BİTİRAS Stage 2: Customer Email Verification & Password Reset Test.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

require_once __DIR__ . '/../data/database.php';
require_once __DIR__ . '/../data/customer_auth_service.php';

$checks = 0;
function check2(bool $ok, string $label): void {
    global $checks;
    if (!$ok) {
        throw new RuntimeException("Stage 2 Assertion Failed: " . $label);
    }
    $checks++;
}

echo "=== Aşama 2: Müşteri Doğrulama ve Hesap Güvenliği Testi ===\n";

// 1. Test kullanıcısı oluştur
$testEmail = 'test_verify_' . time() . '@example.invalid';
$testPass = 'GuvenliSifre123!';
$hash = password_hash($testPass, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("INSERT INTO kullanicilar (ad_soyad, telefon, eposta, sehir, sifre_hash, eposta_dogrulandi) 
                       VALUES ('Test Müşteri', ?, ?, 'İstanbul', ?, 0)");
$testPhone = '555' . substr(strval(time()), -7);
$stmt->execute([$testPhone, $testEmail, $hash]);
$userId = (int)$pdo->lastInsertId();

check2($userId > 0, 'test customer created with eposta_dogrulandi = 0');

// 2. Doğrulama kodu gönderme testi
$code = (string)random_int(100000, 999999);
$token = bin2hex(random_bytes(32));
$combinedHash = hash('sha256', $code) . ':' . hash('sha256', $token);

$pdo->prepare("UPDATE kullanicilar 
               SET eposta_dogrulama_kodu_hash = ?,
                   eposta_dogrulama_son_tarih = DATE_ADD(NOW(), INTERVAL 15 MINUTE),
                   eposta_dogrulama_deneme = 0
               WHERE id = ?")
    ->execute([$combinedHash, $userId]);

$userRecord = $pdo->query("SELECT * FROM kullanicilar WHERE id = $userId")->fetch(PDO::FETCH_ASSOC);
check2(!empty($userRecord['eposta_dogrulama_kodu_hash']), 'verification hash saved');
check2(!str_contains($userRecord['eposta_dogrulama_kodu_hash'], $code), 'code is NEVER stored in plaintext');

// 3. Hatalı kod denemesi
$badRes = verifyCustomerEmailCode($userId, '000000');
check2($badRes['success'] === false, 'incorrect code rejected');

$userRecordAfterFail = $pdo->query("SELECT eposta_dogrulama_deneme FROM kullanicilar WHERE id = $userId")->fetch(PDO::FETCH_ASSOC);
check2((int)$userRecordAfterFail['eposta_dogrulama_deneme'] === 1, 'failed attempt incremented');

// 4. Doğru kod ile hesap doğrulama
$goodRes = verifyCustomerEmailCode($userId, $code);
check2($goodRes['success'] === true, 'correct code verified');

$userVerified = $pdo->query("SELECT eposta_dogrulandi, eposta_dogrulama_kodu_hash FROM kullanicilar WHERE id = $userId")->fetch(PDO::FETCH_ASSOC);
check2((int)$userVerified['eposta_dogrulandi'] === 1, 'account marked as verified (eposta_dogrulandi = 1)');
check2($userVerified['eposta_dogrulama_kodu_hash'] === null, 'verification code cleared after success');

// 5. Şifre sıfırlama talebi (Hesap varlığını ifşa etmeme kontrolü)
$resetNonExistent = requestPasswordReset('non_existent_' . time() . '@example.invalid');
check2($resetNonExistent === true, 'non-existent email returns generic true (no enumeration)');

$resetExistent = requestPasswordReset($testEmail);
check2($resetExistent === true, 'existent email returns generic true');

$userWithReset = $pdo->query("SELECT sifre_sifirlama_token_hash FROM kullanicilar WHERE id = $userId")->fetch(PDO::FETCH_ASSOC);
check2(!empty($userWithReset['sifre_sifirlama_token_hash']), 'reset token hash generated for existing user');

// 6. Token ile yeni şifre belirleme
$fakeToken = bin2hex(random_bytes(32));
$badTokenRes = resetPasswordWithToken($fakeToken, 'YeniSifre12345!');
check2($badTokenRes['success'] === false, 'invalid reset token rejected');

$weakPassRes = resetPasswordWithToken($fakeToken, 'short');
check2($weakPassRes['success'] === false && str_contains($weakPassRes['message'], 'en az 10'), 'short password rejected');

// 7. Dosya ve Arayüz Bütünlüğü
$hesabimCode = file_get_contents(__DIR__ . '/../musteri/hesabim.php');
check2(str_contains($hesabimCode, 'verify_code'), 'hesabim.php handles verify_code');
check2(str_contains($hesabimCode, 'resend_code'), 'hesabim.php handles resend_code');
check2(str_contains($hesabimCode, 'Hesabınızı Doğrulayın'), 'hesabim.php shows verification banner when unverified');
check2(str_contains($hesabimCode, 'Doğrulanmış'), 'hesabim.php shows verified badge when verified');

$loginCode = file_get_contents(__DIR__ . '/../login.php');
check2(str_contains($loginCode, 'sendCustomerVerificationEmail'), 'login.php sends verification on registration');
check2(str_contains($loginCode, '/sifre_sifirla.php'), 'login.php links to sifre_sifirla.php');

// Temizlik
$pdo->prepare("DELETE FROM kullanicilar WHERE id = ?")->execute([$userId]);

echo "Aşama 2 Güvenlik ve Doğrulama Testi: $checks kontrol başarıyla geçti.\n";
