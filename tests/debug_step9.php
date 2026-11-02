<?php
require_once __DIR__ . '/../data/database.php';
$baseUrl = 'http://localhost';
$suffix = bin2hex(random_bytes(4));
$custCookieJar = __DIR__ . "/cookie_cust_{$suffix}.txt";

function httpReq(string $url, string $method = 'GET', array $postData = [], ?string $cookieJar = null, array $headers = []): array {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
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

// 1. Get index
$p1 = httpReq("{$baseUrl}/index.php", 'GET', [], $custCookieJar);
$csrf = extractCsrf($p1['body']);

// 2. Register
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

// 3. Check what session says
$dumpScript = "<?php
require_once 'c:/xampp/htdocs/data/database.php';
require_once 'c:/xampp/htdocs/data/session.php';
require_once 'c:/xampp/htdocs/admin/includes/auth.php';
echo json_encode(\$_SESSION);
";
file_put_contents(__DIR__ . '/dump_sess.php', $dumpScript);

// Read PHPSESSID from cookieJar
$lines = file($custCookieJar);
$sessId = '';
foreach ($lines as $line) {
    if (str_contains($line, 'PHPSESSID')) {
        $parts = preg_split('/\s+/', trim($line));
        $sessId = end($parts);
    }
}
echo "SessId: $sessId\n";

$rDump = httpReq("{$baseUrl}/tests/dump_sess.php", 'GET', [], null, ['Cookie: PHPSESSID=' . $sessId]);
echo "Session in web request: " . $rDump['body'] . "\n";

// Cleanup
$pdo->prepare("DELETE FROM kullanicilar WHERE eposta = ?")->execute([$custEmail]);
@unlink($custCookieJar);
@unlink(__DIR__ . '/dump_sess.php');
