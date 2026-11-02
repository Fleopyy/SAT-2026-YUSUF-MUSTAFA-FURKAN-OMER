<?php
$baseUrl = 'http://localhost';
$suffix = bin2hex(random_bytes(4));
$custCookieJar = __DIR__ . "/cookie_cust_{$suffix}.txt";

require_once __DIR__ . '/../data/database.php';

function httpReq(string $url, string $method = 'GET', array $postData = [], ?string $cookieJar = null, array $headers = []): array {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // Don't follow location!
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_HEADER, true);
    if ($cookieJar) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
    }
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    }
    $raw = curl_exec($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    return ['code' => curl_getinfo($ch, CURLINFO_HTTP_CODE), 'headers' => substr($raw, 0, $headerSize), 'body' => substr($raw, $headerSize)];
}

function extractCsrf(string $html): ?string {
    if (preg_match('/name=["\']csrf_token["\']\s+value=["\']([^"\']+)["\']/', $html, $m)) {
        return $m[1];
    }
    return null;
}

$p1 = httpReq("{$baseUrl}/index.php", 'GET', [], $custCookieJar);
$csrf = extractCsrf($p1['body']);

$custEmail = "dbg_cust_{$suffix}@bitiras.test";
$validCustPost = [
    'action' => 'customer_register',
    'csrf_token' => $csrf,
    'ad_soyad' => 'Debug Cust',
    'telefon' => '0544' . random_int(1000000, 9999999),
    'eposta' => $custEmail,
    'sehir' => 'Ankara',
    'sifre' => 'Pass123!456',
    'sifre_tekrar' => 'Pass123!456',
    'kvkk_onay' => '1'
];
$rReg = httpReq("{$baseUrl}/login.php", 'POST', $validCustPost, $custCookieJar);
echo "login.php response headers:\n" . $rReg['headers'] . "\n";

$pdo->prepare("DELETE FROM kullanicilar WHERE eposta = ?")->execute([$custEmail]);
@unlink($custCookieJar);
