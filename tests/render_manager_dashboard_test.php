<?php
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_METHOD'] = 'GET';

require_once __DIR__ . '/../admin/includes/auth.php';

                 
$stmt = $pdo->prepare("SELECT * FROM yoneticiler WHERE rol = 'manager' LIMIT 1");
$stmt->execute();
$mgr = $stmt->fetch(PDO::FETCH_ASSOC);

$_SESSION = [];
$_SESSION['manager_id'] = $mgr['id'];
$_SESSION['manager_role'] = 'manager';
$_SESSION['manager_name'] = $mgr['ad_soyad'];
$_SESSION['manager_email'] = $mgr['eposta'];

ob_start();
include __DIR__ . '/../manager/dashboard.php';
$output = ob_get_clean();

echo "Rendered manager dashboard successfully!\n";
echo "Output length: " . strlen($output) . " bytes\n";
echo "Contains sidebar brand: " . (strpos($output, 'BiTıraş') !== false ? "YES" : "NO") . "\n";
echo "Contains Yetkili Personel: " . (strpos($output, 'Yetkili Personel') !== false ? "YES" : "NO") . "\n";
echo "Does NOT contain Yönetici Hesapları: " . (strpos($output, 'Yönetici Hesapları') === false ? "YES" : "NO") . "\n";
