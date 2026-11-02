<?php
/**
 * BiTıraş.com — Arama Sonucu Bulunamadığında Bilgilendirme Mesajı Test Paketi
 */

require_once __DIR__ . '/../data/database.php';

function httpGet(string $url): array {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $body = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['status' => $status, 'body' => $body];
}

echo "====================================================================\n";
echo " BiTıraş.com ARAMA SONUCU BOŞ DURUM MESAJI TESTLERİ\n";
echo "====================================================================\n\n";

$passCount = 0;
$totalTests = 5;

// TEST 1: İşletme bulunmayan bir şehir seçildiğinde bilgilendirme mesajı gösterilmeli
$res1 = httpGet('http://localhost/index.php?ara=1&sehir=Hakkari');
$hasEmptyTitle1 = str_contains($res1['body'], 'Aradığınız şehirde kayıtlı berber bulunamadı.');
$hasEmptySub1 = str_contains($res1['body'], "Siz de işletmenizi BiTıraş.com'a ekleyerek müşterilerinize ulaşabilirsiniz.");
$hasRegisterBtn1 = str_contains($res1['body'], 'İşletmenizi Kaydedin');
$hasCards1 = str_contains($res1['body'], 'class="card h-100 border rounded-4 overflow-hidden card-hover shadow-sm"');

$test1Pass = ($res1['status'] === 200) && $hasEmptyTitle1 && $hasEmptySub1 && $hasRegisterBtn1 && !$hasCards1;
echo sprintf("[%s] Test 1: İşletme bulunmayan şehir (Hakkari)\n      -> Başlık: %s, Alt Metin: %s, Buton: %s, Kartlar Gizli: %s\n",
    $test1Pass ? 'PASS' : 'FAIL',
    $hasEmptyTitle1 ? 'VAR' : 'YOK',
    $hasEmptySub1 ? 'VAR' : 'YOK',
    $hasRegisterBtn1 ? 'VAR' : 'YOK',
    !$hasCards1 ? 'EVET' : 'HAYIR'
);
if ($test1Pass) $passCount++;

// TEST 2: İşletme bulunmayan bir ilçe seçildiğinde mesaj gösterilmeli
$res2 = httpGet('http://localhost/index.php?ara=1&sehir=%C4%B0stanbul&ilce=BulunmayanIlceXYZ');
$hasEmptyTitle2 = str_contains($res2['body'], 'Aradığınız şehirde kayıtlı berber bulunamadı.');
$hasEmptySub2 = str_contains($res2['body'], "Siz de işletmenizi BiTıraş.com'a ekleyerek müşterilerinize ulaşabilirsiniz.");
$hasRegisterBtn2 = str_contains($res2['body'], 'İşletmenizi Kaydedin');
$hasCards2 = str_contains($res2['body'], 'class="card h-100 border rounded-4 overflow-hidden card-hover shadow-sm"');

$test2Pass = ($res2['status'] === 200) && $hasEmptyTitle2 && $hasEmptySub2 && $hasRegisterBtn2 && !$hasCards2;
echo sprintf("[%s] Test 2: İşletme bulunmayan ilçe (İstanbul - BulunmayanIlceXYZ)\n      -> Başlık: %s, Alt Metin: %s, Buton: %s, Kartlar Gizli: %s\n",
    $test2Pass ? 'PASS' : 'FAIL',
    $hasEmptyTitle2 ? 'VAR' : 'YOK',
    $hasEmptySub2 ? 'VAR' : 'YOK',
    $hasRegisterBtn2 ? 'VAR' : 'YOK',
    !$hasCards2 ? 'EVET' : 'HAYIR'
);
if ($test2Pass) $passCount++;

// TEST 3: Sonuç bulunan bir şehir seçildiğinde mevcut işletme kartları aynen gösterilmeli
$res3 = httpGet('http://localhost/index.php?ara=1&sehir=%C4%B0stanbul&ilce=%C3%87ekmek%C3%B6y');
$hasEmptyTitle3 = str_contains($res3['body'], 'Aradığınız şehirde kayıtlı berber bulunamadı.');
$hasCards3 = str_contains($res3['body'], 'Yılmaz Berber Salonu');

$test3Pass = ($res3['status'] === 200) && !$hasEmptyTitle3 && $hasCards3;
echo sprintf("[%s] Test 3: Sonuç bulunan şehir/ilçe (İstanbul - Çekmeköy)\n      -> İşletme Kartı: %s, Boş Mesaj Gizli: %s\n",
    $test3Pass ? 'PASS' : 'FAIL',
    $hasCards3 ? 'VAR (Yılmaz Berber Salonu)' : 'YOK',
    !$hasEmptyTitle3 ? 'EVET' : 'HAYIR'
);
if ($test3Pass) $passCount++;

// TEST 4: İşletmenizi Kaydedin butonu doğru işletme kayıt sayfasına yönlendirmeli
$hasCorrectUrl = str_contains($res1['body'], 'href="/index.php?register=barber"');
$hasModalTrigger = str_contains($res1['body'], "openAuthModal('register', 'barber')");

// index.php?register=barber adresine gidildiğinde modal açılış scriptinin çalıştığını teyit et
$res4 = httpGet('http://localhost/index.php?register=barber');
$hasAutoOpenScript = str_contains($res4['body'], "openAuthModal('register', 'barber')");

$test4Pass = $hasCorrectUrl && $hasModalTrigger && $hasAutoOpenScript;
echo sprintf("[%s] Test 4: İşletmenizi Kaydedin Yönlendirmesi\n      -> URL: /index.php?register=barber (%s), JS modal tetikleyici: %s, Otomatik Açılış Scripti: %s\n",
    $test4Pass ? 'PASS' : 'FAIL',
    $hasCorrectUrl ? 'DOĞRU' : 'YANLIŞ',
    $hasModalTrigger ? 'VAR' : 'YOK',
    $hasAutoOpenScript ? 'VAR' : 'YOK'
);
if ($test4Pass) $passCount++;

// TEST 5: Mobil görünüm kontrolü
$indexSource = file_get_contents(__DIR__ . '/../index.php');
$hasMobileClasses = str_contains($indexSource, 'max-width: 520px;')
    && str_contains($indexSource, 'text-center')
    && str_contains($indexSource, 'py-5 px-3 rounded-4')
    && str_contains($indexSource, 'btn-lg');

$test5Pass = $hasMobileClasses;
echo sprintf("[%s] Test 5: Mobil ve Masaüstü Ergonomi\n      -> Responsive Container: %s, Dokunma Alanı (btn-lg): %s, Ortalama ve Taşma Koruması: %s\n",
    $test5Pass ? 'PASS' : 'FAIL',
    $hasMobileClasses ? 'UYUMLU' : 'EKSİK',
    str_contains($indexSource, 'btn-lg') ? 'VAR' : 'YOK',
    str_contains($indexSource, 'max-width: 520px;') ? 'VAR' : 'YOK'
);
if ($test5Pass) $passCount++;

echo "\n====================================================================\n";
echo sprintf("TEST SONUCU: %d / %d BAŞARILI (PASS)\n", $passCount, $totalTests);
echo "====================================================================\n";

if ($passCount === $totalTests) {
    exit(0);
} else {
    exit(1);
}

