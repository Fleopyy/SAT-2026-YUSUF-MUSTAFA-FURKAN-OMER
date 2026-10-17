<?php
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_METHOD'] = 'GET';

session_start();
$_SESSION['admin_id'] = 1;
$_SESSION['admin_role'] = 'admin';
$_SESSION['admin_name'] = 'Süper Admin';

ob_start();
include __DIR__ . '/../admin/dashboard.php';
$output = ob_get_clean();

echo "Rendered dashboard successfully!\n";
echo "Output length: " . strlen($output) . " bytes\n";
echo "Contains sidebar brand: " . (strpos($output, 'BiTıraş') !== false ? "YES" : "NO") . "\n";
echo "Contains Genel Bakış: " . (strpos($output, 'Genel Bakış') !== false ? "YES" : "NO") . "\n";
echo "Contains Yönetici Hesapları: " . (strpos($output, 'Yönetici Hesapları') !== false ? "YES" : "NO") . "\n";
