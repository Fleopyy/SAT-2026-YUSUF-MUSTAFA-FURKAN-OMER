<?php
require_once __DIR__ . '/../data/database.php';

$baseUrl = 'http://localhost';
$ch = curl_init("{$baseUrl}/index.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/debug_cookie.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/debug_cookie.txt');
$home = curl_exec($ch);
preg_match('/name=["\']csrf_token["\']\s+value=["\']([^"\']+)["\']/', $home, $m);
$csrf = $m[1] ?? '';

$email = 'debug_cust_' . time() . '@bitiras.test';
$postData = [
    'action' => 'customer_register',
    'csrf_token' => $csrf,
    'ad_soyad' => 'Debug Müşteri',
    'telefon' => '0544' . random_int(1000000, 9999999),
    'eposta' => $email,
    'sehir' => 'Ankara',
    'sifre' => 'Password123!',
    'sifre_tekrar' => 'Password123!',
    'kvkk_onay' => '1'
];

curl_setopt($ch, CURLOPT_URL, "{$baseUrl}/login.php");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_HEADER, true);
$regRes = curl_exec($ch);

echo "--- REGISTRATION RESPONSE ---\n";
echo substr($regRes, 0, 1000) . "\n\n";

// Now check if customer was created in DB
$stmt = $pdo->prepare("SELECT * FROM kullanicilar WHERE eposta = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
echo "User in DB: " . ($user ? "FOUND (ID: {$user['id']})" : "NOT FOUND") . "\n";

// Access /musteri/hesabim.php
curl_setopt($ch, CURLOPT_URL, "{$baseUrl}/musteri/hesabim.php");
curl_setopt($ch, CURLOPT_POST, false);
curl_setopt($ch, CURLOPT_HTTPGET, true);
$accRes = curl_exec($ch);
echo "--- HESABIM RESPONSE (Code: " . curl_getinfo($ch, CURLINFO_HTTP_CODE) . ") ---\n";
echo substr($accRes, 0, 800) . "\n";

// Cleanup
$pdo->prepare("DELETE FROM kullanicilar WHERE eposta = ?")->execute([$email]);
@unlink(__DIR__ . '/debug_cookie.txt');

