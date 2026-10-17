<?php
require_once __DIR__ . '/../data/database.php';

echo "=== BiTıraş.com Kapsamlı Admin Aksiyon Testi Başlıyor ===\n\n";

$passCount = 0;
$failCount = 0;

function assertTest(string $desc, bool $condition): void {
    global $passCount, $failCount;
    if ($condition) {
        echo " [BAŞARILI] $desc\n";
        $passCount++;
    } else {
        echo " [BAŞARISIZ] $desc\n";
        $failCount++;
    }
}

$stmt = $pdo->query("SELECT * FROM yoneticiler WHERE rol = 'admin' AND aktif = 1 LIMIT 1");
$admin = $stmt->fetch(PDO::FETCH_ASSOC);
assertTest("Süper Admin hesabı mevcut", !empty($admin));

if (!$admin) {
    die("Test durduruldu: Admin hesabı bulunamadı.\n");
}

$testEmail = 'test_onay_isletme_' . time() . '@bitiras.com';
$stmt = $pdo->prepare("INSERT INTO isletmeler (isletme_sahibi, isletme_adi, telefon, eposta, sehir, ilce, tam_adres, aciklama, sifre_hash, durum) 
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
$stmt->execute([
    'Test Berber Sahibi',
    'Test Onay Bekleyen Berber Salonu',
    '0555' . rand(1000000, 9999999),
    $testEmail,
    'İstanbul',
    'Kadıköy',
    'Moda Cad. No: 12',
    'Onaylama testi için oluşturulmuş işletme',
    password_hash('test1234', PASSWORD_DEFAULT)
]);
$testBusinessId = (int)$pdo->lastInsertId();
assertTest("Test işletmesi 'pending' durumunda oluşturuldu (ID: $testBusinessId)", $testBusinessId > 0);

$csrfToken = bin2hex(random_bytes(32));

$approveScript = "<?php
\$_SERVER['REQUEST_METHOD'] = 'POST';
\$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
\$_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';
session_start();
\$_SESSION['admin_id'] = {$admin['id']};
\$_SESSION['admin_role'] = 'admin';
\$_SESSION['admin_name'] = '{$admin['ad_soyad']}';
\$_SESSION['csrf_token'] = '{$csrfToken}';
\$_POST = [
    'csrf_token' => '{$csrfToken}',
    'action' => 'approve',
    'business_id' => {$testBusinessId}
];
require 'c:/Users/Pc/Desktop/satgeliştirmeadminpanel/admin/actions/application_action.php';
";

$tmpFile = __DIR__ . '/temp_approve_test.php';
file_put_contents($tmpFile, $approveScript);

exec("C:\\xampp\\php\\php.exe " . escapeshellarg($tmpFile), $outApprove, $retApprove);
@unlink($tmpFile);

$rawApproveOut = implode("\n", $outApprove);
$approveData = json_decode($rawApproveOut, true);

assertTest("application_action.php (approve) hatasız çalıştı (Dönüş kodu: $retApprove)", $retApprove === 0);
assertTest("application_action.php JSON başarılı yanıtı döndü", !empty($approveData['success']) && $approveData['success'] === true);

$stmt = $pdo->prepare("SELECT durum, onaylayan_admin_id, onay_tarihi FROM isletmeler WHERE id = ?");
$stmt->execute([$testBusinessId]);
$updatedBusiness = $stmt->fetch(PDO::FETCH_ASSOC);

assertTest("İşletme durumu 'active' olarak güncellendi", ($updatedBusiness['durum'] ?? '') === 'active');
assertTest("Onaylayan admin ID kaydedildi", (int)($updatedBusiness['onaylayan_admin_id'] ?? 0) === (int)$admin['id']);
assertTest("Onay tarihi güncellendi", !empty($updatedBusiness['onay_tarihi']));

$stmt = $pdo->prepare("SELECT COUNT(*) FROM islem_gunlukleri WHERE islem_turu = 'business_approve' AND hedef_id = ?");
$stmt->execute([$testBusinessId]);
assertTest("İşlem günlüğü (islem_gunlukleri) kaydı oluşturuldu", (int)$stmt->fetchColumn() > 0);

$stmt = $pdo->prepare("SELECT COUNT(*) FROM bildirimler WHERE baslik = 'İşletme Onaylandı' AND link LIKE ?");
$stmt->execute(["%id={$testBusinessId}%"]);
assertTest("Sistem içi bildirim (bildirimler) oluşturuldu", (int)$stmt->fetchColumn() > 0);

$stmt = $pdo->prepare("SELECT COUNT(*) FROM eposta_kuyrugu WHERE alici_eposta = ?");
$stmt->execute([$testEmail]);
assertTest("İşletme sahibine tebrik e-postası (eposta_kuyrugu) eklendi", (int)$stmt->fetchColumn() > 0);

$suspendScript = "<?php
\$_SERVER['REQUEST_METHOD'] = 'POST';
\$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
\$_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';
session_start();
\$_SESSION['admin_id'] = {$admin['id']};
\$_SESSION['admin_role'] = 'admin';
\$_SESSION['csrf_token'] = '{$csrfToken}';
\$_POST = [
    'csrf_token' => '{$csrfToken}',
    'action' => 'suspend',
    'business_id' => {$testBusinessId}
];
require 'c:/Users/Pc/Desktop/satgeliştirmeadminpanel/admin/actions/application_action.php';
";
file_put_contents($tmpFile, $suspendScript);
exec("C:\\xampp\\php\\php.exe " . escapeshellarg($tmpFile), $outSuspend, $retSuspend);
@unlink($tmpFile);

$suspendData = json_decode(implode("\n", $outSuspend), true);
assertTest("application_action.php (suspend) hatasız çalıştı", !empty($suspendData['success']) && $suspendData['success'] === true);

$stmt = $pdo->prepare("SELECT durum FROM isletmeler WHERE id = ?");
$stmt->execute([$testBusinessId]);
assertTest("İşletme durumu 'suspended' oldu", $stmt->fetchColumn() === 'suspended');

$rejectScript = "<?php
\$_SERVER['REQUEST_METHOD'] = 'POST';
\$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
\$_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';
session_start();
\$_SESSION['admin_id'] = {$admin['id']};
\$_SESSION['admin_role'] = 'admin';
\$_SESSION['csrf_token'] = '{$csrfToken}';
\$_POST = [
    'csrf_token' => '{$csrfToken}',
    'action' => 'reject',
    'business_id' => {$testBusinessId},
    'red_gerekce' => 'Eksik işletme belgeleri ve doğrulanamayan telefon numarası.'
];
require 'c:/Users/Pc/Desktop/satgeliştirmeadminpanel/admin/actions/application_action.php';
";
file_put_contents($tmpFile, $rejectScript);
exec("C:\\xampp\\php\\php.exe " . escapeshellarg($tmpFile), $outReject, $retReject);
@unlink($tmpFile);

$rejectData = json_decode(implode("\n", $outReject), true);
assertTest("application_action.php (reject) hatasız çalıştı", !empty($rejectData['success']) && $rejectData['success'] === true);

$stmt = $pdo->prepare("SELECT durum, red_gerekce FROM isletmeler WHERE id = ?");
$stmt->execute([$testBusinessId]);
$rejRow = $stmt->fetch(PDO::FETCH_ASSOC);
assertTest("İşletme durumu 'rejected' oldu", ($rejRow['durum'] ?? '') === 'rejected');
assertTest("Red gerekçesi kaydedildi", !empty($rejRow['red_gerekce']));

$businessEditScript = "<?php
\$_SERVER['REQUEST_METHOD'] = 'POST';
\$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
session_start();
\$_SESSION['admin_id'] = {$admin['id']};
\$_SESSION['admin_role'] = 'admin';
\$_SESSION['csrf_token'] = '{$csrfToken}';
\$_POST = [
    'csrf_token' => '{$csrfToken}',
    'action' => 'edit',
    'business_id' => {$testBusinessId},
    'isletme_adi' => 'Test Güncellenmiş Berber',
    'isletme_sahibi' => 'Test Sahip Güncel',
    'telefon' => '05550000000',
    'eposta' => '{$testEmail}',
    'sehir' => 'Ankara',
    'ilce' => 'Çankaya',
    'tam_adres' => 'Tunalı Hilmi Cad.',
    'aciklama' => 'Güncellenmiş açıklama',
    'durum' => 'active'
];
require 'c:/Users/Pc/Desktop/satgeliştirmeadminpanel/admin/actions/business_action.php';
";
file_put_contents($tmpFile, $businessEditScript);
exec("C:\\xampp\\php\\php.exe " . escapeshellarg($tmpFile), $outBusEdit, $retBusEdit);
@unlink($tmpFile);

assertTest("business_action.php (edit) hatasız çalıştı (Kod: $retBusEdit)", $retBusEdit === 0);

$stmt = $pdo->prepare("SELECT isletme_adi, sehir FROM isletmeler WHERE id = ?");
$stmt->execute([$testBusinessId]);
$busRow = $stmt->fetch(PDO::FETCH_ASSOC);
assertTest("İşletme adı ve şehri başarıyla güncellendi", ($busRow['isletme_adi'] ?? '') === 'Test Güncellenmiş Berber' && ($busRow['sehir'] ?? '') === 'Ankara');

$stmt = $pdo->prepare("INSERT INTO kullanicilar (ad_soyad, telefon, eposta, sifre_hash) VALUES ('Test Müşteri', '05441112233', 'test_must@bitiras.com', 'hash')");
$stmt->execute();
$testUserId = (int)$pdo->lastInsertId();

$stmt = $pdo->prepare("INSERT INTO randevular (kullanici_id, isletme_id, randevu_zamani, durum) VALUES (?, ?, NOW(), 'pending')");
$stmt->execute([$testUserId, $testBusinessId]);
$testAptId = (int)$pdo->lastInsertId();

$aptScript = "<?php
\$_SERVER['REQUEST_METHOD'] = 'POST';
\$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
session_start();
\$_SESSION['admin_id'] = {$admin['id']};
\$_SESSION['admin_role'] = 'admin';
\$_SESSION['csrf_token'] = '{$csrfToken}';
\$_POST = [
    'csrf_token' => '{$csrfToken}',
    'appointment_id' => {$testAptId},
    'status' => 'confirmed'
];
require 'c:/Users/Pc/Desktop/satgeliştirmeadminpanel/admin/actions/appointment_action.php';
";
file_put_contents($tmpFile, $aptScript);
exec("C:\\xampp\\php\\php.exe " . escapeshellarg($tmpFile), $outApt, $retApt);
@unlink($tmpFile);

assertTest("appointment_action.php (status update) hatasız çalıştı (Kod: $retApt)", $retApt === 0);

$stmt = $pdo->prepare("SELECT durum FROM randevular WHERE id = ?");
$stmt->execute([$testAptId]);
assertTest("Randevu durumu 'confirmed' olarak güncellendi", $stmt->fetchColumn() === 'confirmed');

$pdo->prepare("DELETE FROM islem_gunlukleri WHERE hedef_id = ? AND hedef_turu = 'randevular'")->execute([$testAptId]);
$pdo->prepare("DELETE FROM randevular WHERE id = ?")->execute([$testAptId]);
$pdo->prepare("DELETE FROM kullanicilar WHERE id = ?")->execute([$testUserId]);

$settingsScript = "<?php
\$_SERVER['REQUEST_METHOD'] = 'POST';
\$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
session_start();
\$_SESSION['admin_id'] = {$admin['id']};
\$_SESSION['admin_role'] = 'admin';
\$_SESSION['csrf_token'] = '{$csrfToken}';
\$_POST = [
    'csrf_token' => '{$csrfToken}',
    'action' => 'save_settings',
    'site_baslik' => 'BiTıraş.com Test Başlık'
];
require 'c:/Users/Pc/Desktop/satgeliştirmeadminpanel/admin/actions/settings_action.php';
";
file_put_contents($tmpFile, $settingsScript);
exec("C:\\xampp\\php\\php.exe " . escapeshellarg($tmpFile), $outSet, $retSet);
@unlink($tmpFile);

assertTest("settings_action.php (save_settings) hatasız çalıştı", $retSet === 0);

$adminPages = [
    'dashboard.php' => 'admin/dashboard.php',
    'applications.php' => 'admin/pages/applications.php',
    'businesses.php' => 'admin/pages/businesses.php',
    'appointments.php' => 'admin/pages/appointments.php',
    'customers.php' => 'admin/pages/customers.php',
    'media.php' => 'admin/pages/media.php',
    'content.php' => 'admin/pages/content.php',
    'logs.php' => 'admin/pages/logs.php',
    'managers.php' => 'admin/pages/managers.php',
];

echo "\n--- Admin Sayfa Render Testleri ---\n";
foreach ($adminPages as $pageName => $pageRelPath) {
    $renderScript = "<?php
\$_SERVER['REQUEST_METHOD'] = 'GET';
\$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
\$_SERVER['REQUEST_URI'] = '/{$pageRelPath}';
session_start();
\$_SESSION['admin_id'] = {$admin['id']};
\$_SESSION['admin_role'] = 'admin';
\$_SESSION['admin_name'] = '{$admin['ad_soyad']}';
ob_start();
require 'c:/Users/Pc/Desktop/satgeliştirmeadminpanel/{$pageRelPath}';
\$html = ob_get_clean();
if (strpos(\$html, 'Fatal error') !== false || strpos(\$html, 'Parse error') !== false) {
    echo 'FATAL_ERROR_FOUND';
    exit(1);
}
echo 'RENDER_OK';
";
    file_put_contents($tmpFile, $renderScript);
    exec("C:\\xampp\\php\\php.exe " . escapeshellarg($tmpFile), $outPage, $retPage);
    @unlink($tmpFile);

    $pageOut = implode("\n", $outPage);
    assertTest("Sayfa render: {$pageName} (Hatasız ve başarıyla üretildi)", $retPage === 0 && strpos($pageOut, 'RENDER_OK') !== false);
    unset($outPage);
}

$pdo->prepare("DELETE FROM eposta_kuyrugu WHERE alici_eposta = ?")->execute([$testEmail]);
$pdo->prepare("DELETE FROM bildirimler WHERE link LIKE ?")->execute(["%id={$testBusinessId}%"]);
$pdo->prepare("DELETE FROM islem_gunlukleri WHERE hedef_id = ?")->execute([$testBusinessId]);
$pdo->prepare("DELETE FROM isletmeler WHERE id = ?")->execute([$testBusinessId]);
echo "\nTest işletmesi ve ilgili test kayıtları temizlendi.\n";

echo "\n" . str_repeat("=", 50) . "\n";
echo "TEST SONUCU: $passCount Başarılı, $failCount Başarısız\n";
echo str_repeat("=", 50) . "\n";
