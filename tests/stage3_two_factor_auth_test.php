<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

require_once __DIR__ . '/../data/database.php';
require_once __DIR__ . '/../data/two_factor_service.php';

$checks = 0;
function check3(bool $ok, string $label): void {
    global $checks;
    if (!$ok) {
        throw new RuntimeException("Stage 3 Assertion Failed: " . $label);
    }
    $checks++;
}

echo "=== Aşama 3: Admin ve İşletme 2FA & Risk Bazlı Cihaz Güvenliği Testi ===\n";

$secret = generateTotpSecret(16);
check3(strlen($secret) === 16, 'TOTP secret generated with 16 Base32 characters');
check3(preg_match('/^[A-Z2-7]+$/', $secret) === 1, 'TOTP secret contains valid Base32 alphabet');

$totpCode = calculateTotpCode($secret);
check3(strlen($totpCode) === 6 && ctype_digit($totpCode), 'TOTP code is 6-digit numeric string');
check3(verifyTotpCode($secret, $totpCode), 'TOTP code verified successfully');
check3(!verifyTotpCode($secret, '000000'), 'invalid TOTP code rejected');

$recovery = generateRecoveryCodes(8);
check3(count($recovery['plain']) === 8, '8 recovery codes generated');
check3(count($recovery['hashed']) === 8, '8 hashed recovery codes generated');
check3(preg_match('/^[A-F0-9]{4}-[A-F0-9]{4}$/', $recovery['plain'][0]) === 1, 'recovery code format XXXX-XXXX');

$testAdminEmail = 'test_admin_2fa_' . time() . '@example.invalid';
$testAdminPass = 'AdminGuvenliSifre123!';
$adminHash = password_hash($testAdminPass, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("INSERT INTO yoneticiler (ad_soyad, eposta, sifre_hash, rol, aktif, totp_gizli_anahtar, kurtarma_kodlari_hash) 
                       VALUES ('Test Süper Admin', ?, ?, 'admin', 1, ?, ?)");
$stmt->execute([$testAdminEmail, $adminHash, $secret, json_encode($recovery['hashed'])]);
$adminId = (int)$pdo->lastInsertId();
check3($adminId > 0, 'test admin created with TOTP and recovery codes');

$vTotp = verifyAdmin2faCode($adminId, $totpCode);
check3($vTotp['success'] === true, 'admin verified via TOTP');

$firstCode = $recovery['plain'][0];
$vRecovery1 = verifyAdmin2faCode($adminId, $firstCode);
check3($vRecovery1['success'] === true, 'admin verified via recovery code');

$vRecovery2 = verifyAdmin2faCode($adminId, $firstCode);
check3($vRecovery2['success'] === false, 'used recovery code cannot be reused (consumed)');

sendAdmin2faEmail($adminId, $testAdminEmail, 'Test Admin');
$adminRow = $pdo->query("SELECT eposta_2fa_kod_hash, eposta_2fa_son_tarih FROM yoneticiler WHERE id = $adminId")->fetch(PDO::FETCH_ASSOC);
check3(!empty($adminRow['eposta_2fa_kod_hash']), 'admin email 2FA code hash saved in DB');
check3(strtotime($adminRow['eposta_2fa_son_tarih']) > time(), 'admin email 2FA code has valid future expiration');

$testBarberEmail = 'test_barber_2fa_' . time() . '@example.invalid';
$testBarberPhone = '555' . substr(strval(time()), -7);
$barberPass = 'BarberSifre123!';
$barberHash = password_hash($barberPass, PASSWORD_DEFAULT);

$stmtB = $pdo->prepare("INSERT INTO isletmeler (isletme_sahibi, isletme_adi, telefon, eposta, sehir, ilce, sifre_hash, durum, son_giris) 
                        VALUES ('Berber Sahibi', 'Test Berber Salonu', ?, ?, 'İstanbul', 'Kadıköy', ?, 'active', NULL)");
$stmtB->execute([$testBarberPhone, $testBarberEmail, $barberHash]);
$barberId = (int)$pdo->lastInsertId();
check3($barberId > 0, 'test barber created');

$barberRow = $pdo->query("SELECT * FROM isletmeler WHERE id = $barberId")->fetch(PDO::FETCH_ASSOC);
$riskNew = evaluateBarberLoginRisk($barberRow);
check3($riskNew['requires_2fa'] === true, 'untrusted new device triggers 2FA');

sendBarber2faEmail($barberId, $testBarberEmail, 'Test Berber Salonu');
$barberAfterEmail = $pdo->query("SELECT eposta_2fa_kod_hash FROM isletmeler WHERE id = $barberId")->fetch(PDO::FETCH_ASSOC);
check3(!empty($barberAfterEmail['eposta_2fa_kod_hash']), 'barber 2FA email code hash saved');

$deviceToken = registerTrustedDevice('isletme', $barberId, 30);
check3(strlen($deviceToken) === 64, 'trusted device token generated (64 hex)');
check3(isTrustedDevice('isletme', $barberId) === true, 'registered device is trusted');

$pdo->prepare("UPDATE isletmeler SET son_giris = NOW() WHERE id = ?")->execute([$barberId]);
$barberActive = $pdo->query("SELECT * FROM isletmeler WHERE id = $barberId")->fetch(PDO::FETCH_ASSOC);
$riskTrusted = evaluateBarberLoginRisk($barberActive);
check3($riskTrusted['requires_2fa'] === false, 'trusted device bypasses 2FA');

$pdo->prepare("UPDATE isletmeler SET son_giris = DATE_SUB(NOW(), INTERVAL 15 DAY) WHERE id = ?")->execute([$barberId]);
$barberOld = $pdo->query("SELECT * FROM isletmeler WHERE id = $barberId")->fetch(PDO::FETCH_ASSOC);
$riskOld = evaluateBarberLoginRisk($barberOld);
check3($riskOld['requires_2fa'] === true && str_contains($riskOld['reason'], '14'), 'inactive account for 14+ days triggers 2FA');

$pdo->prepare("DELETE FROM yoneticiler WHERE id = ?")->execute([$adminId]);
$pdo->prepare("DELETE FROM isletmeler WHERE id = ?")->execute([$barberId]);
$pdo->prepare("DELETE FROM guvenilir_cihazlar WHERE kullanici_id IN (?, ?)")->execute([$adminId, $barberId]);

echo "Aşama 3 2FA ve Risk Bazlı Güvenlik Testi: $checks kontrol başarıyla geçti.\n";
