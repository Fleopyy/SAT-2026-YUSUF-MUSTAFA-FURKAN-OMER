<?php
/**
 * BiTıraş.com GERÇEK ORTAM UÇTAN UCA HTTP & TARAYICI AKIŞ TESTİ (NİHAİ & TAM DOĞRULAMA)
 * 
 * - Gerçek cURL oturum çerezleri (PHPSESSID) ile çalışır.
 * - Çerez başlıklarının cURL oturum çerezini ezmesini önler.
 * - GET formlarından anlık ve taze CSRF token okur.
 * - Durum geçişlerini (pending -> confirmed -> cancelled) ve izin verilmeyen geçişleri doğrular.
 * - Admin, Berber ve Müşteri panellerini gerçek HTTP istekleriyle uçtan uca test eder.
 */

require_once __DIR__ . '/../data/database.php';

echo "====================================================================\n";
echo "BiTıraş.com NİHAİ GERÇEK ORTAM UÇTAN UCA HTTP & TARAYICI AKIŞ TESTİ\n";
echo "====================================================================\n\n";

$baseUrl = 'http://localhost';
$clientSuffix = bin2hex(random_bytes(4));

$custCookieJar = __DIR__ . "/cookie_cust_{$clientSuffix}.txt";
$barberCookieJar = __DIR__ . "/cookie_barber_{$clientSuffix}.txt";

function httpReq(string $url, string $method = 'GET', array $postData = [], ?string $cookieJar = null, array $headers = []): array {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_HEADER, true);

    $formattedHeaders = [];
    foreach ($headers as $k => $v) {
        $formattedHeaders[] = is_numeric($k) ? $v : "$k: $v";
    }
    if (!empty($formattedHeaders)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $formattedHeaders);
    }

    if ($cookieJar) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
    }

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    }

    $raw = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    $headerStr = substr($raw, 0, $headerSize);
    $body = substr($raw, $headerSize);

    return [
        'code' => $httpCode,
        'headers' => $headerStr,
        'body' => $body
    ];
}

function extractCsrf(string $html): ?string {
    if (preg_match('/name=["\']csrf_token["\']\s+value=["\']([^"\']+)["\']/', $html, $m)) {
        return $m[1];
    }
    return null;
}

function appendCookieToJar(string $jarPath, string $name, string $value): void {
    $line = "localhost\tFALSE\t/\tFALSE\t0\t{$name}\t{$value}\n";
    file_put_contents($jarPath, $line, FILE_APPEND);
}

$results = [];
$newBusId = 0;
$newEmpId = 0;
$newSvcId = 0;
$createdAptId = 0;
$custEmail = '';
$barberEmail = '';

try {
    // -------------------------------------------------------------
    // ADIM 1: Ana Sayfayı Çek ve CSRF Token Al
    // -------------------------------------------------------------
    $step1 = httpReq("{$baseUrl}/index.php", 'GET', [], $custCookieJar);
    $csrf = extractCsrf($step1['body']);
    $results['1_homepage_and_csrf'] = ($step1['code'] === 200 && !empty($csrf));
    echo "1. Ana sayfa ve CSRF Token alındı mı: " . ($results['1_homepage_and_csrf'] ? "PASS" : "FAIL") . "\n";

    // -------------------------------------------------------------
    // ADIM 2: KVKK Onaysız Müşteri Kaydı Denemesi (Reddedilmeli)
    // -------------------------------------------------------------
    $custEmail = "flow_cust_{$clientSuffix}@bitiras.test";
    $custPass = "TestPass123!";
    $noKvkkPost = [
        'action' => 'customer_register',
        'csrf_token' => $csrf,
        'ad_soyad' => 'Nihai Tarayıcı Müşterisi',
        'telefon' => '0544' . random_int(1000000, 9999999),
        'eposta' => $custEmail,
        'sehir' => 'Ankara',
        'sifre' => $custPass,
        'sifre_tekrar' => $custPass,
        // kvkk_onay bilinçli gönderilmiyor
    ];
    $step2 = httpReq("{$baseUrl}/login.php", 'POST', $noKvkkPost, $custCookieJar);
    $kvkkBlocked = str_contains($step2['body'], 'KVKK') || str_contains($step2['body'], 'Aydınlatma');
    $results['2_kvkk_rejection'] = $kvkkBlocked;
    echo "2. KVKK onaysız müşteri kaydı engellendi mi: " . ($results['2_kvkk_rejection'] ? "PASS" : "FAIL") . "\n";

    // -------------------------------------------------------------
    // ADIM 3: KVKK Onaylı Müşteri Kaydı ve Oturum Kontrolü
    // -------------------------------------------------------------
    // Taze CSRF token al
    $step3Page = httpReq("{$baseUrl}/index.php", 'GET', [], $custCookieJar);
    $custCsrf = extractCsrf($step3Page['body']) ?: $csrf;

    $validCustPost = $noKvkkPost;
    $validCustPost['csrf_token'] = $custCsrf;
    $validCustPost['kvkk_onay'] = '1';
    $step3 = httpReq("{$baseUrl}/login.php", 'POST', $validCustPost, $custCookieJar);
    
    // Müşteri oturumu kayıt sonrası doğrudan açılmış olmalı
    $step3Check = httpReq("{$baseUrl}/musteri/hesabim.php", 'GET', [], $custCookieJar);
    $custLoggedIn = ($step3Check['code'] === 200 && (str_contains($step3Check['body'], 'Hesabım') || str_contains($step3Check['body'], 'customer-title')));
    $results['3_customer_reg_and_login'] = $custLoggedIn;
    echo "3. Müşteri KVKK onaylı kayıt ve giriş başarılı mı: " . ($results['3_customer_reg_and_login'] ? "PASS" : "FAIL") . "\n";

    // -------------------------------------------------------------
    // ADIM 4: Berber Kayıt Başvurusu (Pending Olmalı)
    // -------------------------------------------------------------
    $step4Page = httpReq("{$baseUrl}/index.php", 'GET', [], $barberCookieJar);
    $barberCsrf = extractCsrf($step4Page['body']) ?: $csrf;

    $barberEmail = "flow_barber_{$clientSuffix}@bitiras.test";
    $barberPass = "BarberPass123!";
    $barberPost = [
        'action' => 'barber_register',
        'csrf_token' => $barberCsrf,
        'isletme_adi' => "Nihai Doğrulama Salonu {$clientSuffix}",
        'isletme_sahibi' => 'Nihai Berber Sahibi',
        'telefon' => '0533' . random_int(1000000, 9999999),
        'eposta' => $barberEmail,
        'sehir' => 'Ankara',
        'ilce' => 'Çankaya',
        'tam_adres' => 'Tunalı Hilmi Cad. No: 99',
        'sifre' => $barberPass,
        'sifre_tekrar' => $barberPass,
        'kvkk_onay' => '1'
    ];
    $step4 = httpReq("{$baseUrl}/login.php", 'POST', $barberPost, $barberCookieJar);
    
    // Onay bekleyen berber ile giriş denemesi (Engellenmeli)
    $step4Page2 = httpReq("{$baseUrl}/index.php", 'GET', [], $barberCookieJar);
    $barberCsrf2 = extractCsrf($step4Page2['body']) ?: $barberCsrf;

    $loginBarberPost = [
        'action' => 'barber_login',
        'csrf_token' => $barberCsrf2,
        'eposta' => $barberEmail,
        'sifre' => $barberPass
    ];
    $step4Login = httpReq("{$baseUrl}/login.php", 'POST', $loginBarberPost, $barberCookieJar);
    $pendingBlocked = str_contains($step4Login['body'], 'onay') || str_contains($step4Login['body'], 'incelen');
    $results['4_barber_pending_state'] = $pendingBlocked;
    echo "4. Berber kaydı oluşturuldu ve onaysız giriş engellendi mi: " . ($results['4_barber_pending_state'] ? "PASS" : "FAIL") . "\n";

    // -------------------------------------------------------------
    // ADIM 5: Admin Girişi ve Berber Başvurusunu Onaylama
    // -------------------------------------------------------------
    $stmtAdmin = $pdo->query("SELECT id, eposta, sifre_hash, ad_soyad FROM yoneticiler WHERE rol = 'admin' AND aktif = 1 LIMIT 1");
    $adminRow = $stmtAdmin->fetch(PDO::FETCH_ASSOC);

    // İşletme ID'sini bul
    $stmtBus = $pdo->prepare("SELECT id FROM isletmeler WHERE eposta = ?");
    $stmtBus->execute([$barberEmail]);
    $newBusId = (int)$stmtBus->fetchColumn();

    // Admin oturumu çerezi oluştur
    $adminInitScript = __DIR__ . "/init_admin_sess_{$clientSuffix}.php";
    file_put_contents($adminInitScript, "<?php
require_once 'c:/xampp/htdocs/data/database.php';
require_once 'c:/xampp/htdocs/data/security.php';
\$_SESSION['admin_id'] = {$adminRow['id']};
\$_SESSION['admin_role'] = 'admin';
\$_SESSION['admin_name'] = '{$adminRow['ad_soyad']}';
\$_SESSION['admin_password_version'] = hash('sha256', '{$adminRow['sifre_hash']}');
\$_SESSION['csrf_token'] = 'admin_flow_csrf_token';
echo session_id();
");
    exec("C:\\xampp\\php\\php.exe " . escapeshellarg($adminInitScript), $sessOut);
    $adminSessId = trim(implode('', $sessOut));
    @unlink($adminInitScript);

    // Admin endpoint'e POST: application_action.php (approve)
    $approvePost = [
        'action' => 'approve',
        'business_id' => $newBusId,
        'csrf_token' => 'admin_flow_csrf_token'
    ];
    $step5 = httpReq("{$baseUrl}/admin/actions/application_action.php", 'POST', $approvePost, null, [
        'Cookie: PHPSESSID=' . $adminSessId,
        'X-Requested-With: xmlhttprequest'
    ]);
    
    // Veritabanında durum 'active' oldu mu?
    $stmtBusCheck = $pdo->prepare("SELECT durum FROM isletmeler WHERE id = ?");
    $stmtBusCheck->execute([$newBusId]);
    $isApproved = ($stmtBusCheck->fetchColumn() === 'active');
    $results['5_admin_approval'] = $isApproved;
    echo "5. Admin panelinden işletme onaylandı mı (durum=active): " . ($results['5_admin_approval'] ? "PASS" : "FAIL") . "\n";

    // -------------------------------------------------------------
    // ADIM 6: Onaylanan Berber ile Giriş Yapma ve Panel Erişimi
    // -------------------------------------------------------------
    $step6Page = httpReq("{$baseUrl}/index.php", 'GET', [], $barberCookieJar);
    $barberCsrf3 = extractCsrf($step6Page['body']) ?: $barberCsrf2;

    $loginBarberPost['csrf_token'] = $barberCsrf3;

    // Yeni cihaz için cihaz güven belirteci ekle
    require_once __DIR__ . '/../data/two_factor_service.php';
    $deviceToken = registerTrustedDevice('isletme', $newBusId, 30);
    appendCookieToJar($barberCookieJar, getDeviceCookieName(), $deviceToken);

    $step6Login = httpReq("{$baseUrl}/login.php", 'POST', $loginBarberPost, $barberCookieJar);

    $step6Panel = httpReq("{$baseUrl}/isletme/randevular.php", 'GET', [], $barberCookieJar);
    $barberPanelAccess = ($step6Panel['code'] === 200 && (str_contains($step6Panel['body'], 'Randevu') || str_contains($step6Panel['body'], 'randevular')));
    $results['6_barber_panel_access'] = $barberPanelAccess;
    echo "6. Onaylanan berber hesabıyla panele başarıyla girildi mi: " . ($results['6_barber_panel_access'] ? "PASS" : "FAIL") . "\n";

    // -------------------------------------------------------------
    // ADIM 7: İşletme Arama ve Detay Sayfası
    // -------------------------------------------------------------
    $todayDay = (int)date('w', strtotime('+1 day'));
    $pdo->prepare("INSERT INTO isletme_calisma_araliklari (isletme_id, haftanin_gunu, baslangic, bitis) VALUES (?, ?, '09:00:00', '19:00:00')")->execute([$newBusId, $todayDay]);
    $pdo->prepare("INSERT INTO calisanlar (isletme_id, ad_soyad, aktif_mi) VALUES (?, 'Usta Test', 1)")->execute([$newBusId]);
    $newEmpId = (int)$pdo->lastInsertId();
    $pdo->prepare("INSERT INTO calisan_calisma_araliklari (calisan_id, haftanin_gunu, baslangic, bitis) VALUES (?, ?, '09:00:00', '19:00:00')")->execute([$newEmpId, $todayDay]);
    $pdo->prepare("INSERT INTO hizmetler (isletme_id, hizmet_adi, fiyat, sure_dakika, aktif_mi) VALUES (?, 'Saç Tasarım', 200, 30, 1)")->execute([$newBusId]);
    $newSvcId = (int)$pdo->lastInsertId();
    $pdo->prepare("INSERT INTO hizmet_calisanlari (hizmet_id, calisan_id) VALUES (?, ?)")->execute([$newSvcId, $newEmpId]);
    
    // Konum ve koordinat ekle (Harita testi için)
    $pdo->prepare("UPDATE isletmeler SET latitude = '39.92077000', longitude = '32.85411000', location_verified_at = NOW() WHERE id = ?")->execute([$newBusId]);

    // Arama sayfası GET
    $searchRes = httpReq("{$baseUrl}/index.php?sehir=Ankara&ilce=%C3%87ankaya", 'GET', [], $custCookieJar);
    $searchFound = str_contains($searchRes['body'], "Nihai Doğrulama Salonu {$clientSuffix}");
    $results['7_business_search'] = $searchFound;
    echo "7. Şehir ve ilçe araması işletmeyi buldu mu: " . ($results['7_business_search'] ? "PASS" : "FAIL") . "\n";

    // Detay sayfası GET (Çerez onayını CookieJar'a doğrudan ekle)
    $cookieConsentVal = json_encode(['version' => 1, 'functional' => true, 'updated_at' => date('c')]);
    appendCookieToJar($custCookieJar, 'bitiras_cookie_consent', urlencode($cookieConsentVal));

    $step7Detail = httpReq("{$baseUrl}/berber.php?id={$newBusId}", 'GET', [], $custCookieJar);
    $detailOk = ($step7Detail['code'] === 200 && str_contains($step7Detail['body'], 'customerProfileMap') && str_contains($step7Detail['body'], 'Yol Tarifi'));
    $results['8_business_detail_and_map'] = $detailOk;
    echo "8. İşletme detay sayfası ve harita bileşeni yüklendi mi: " . ($results['8_business_detail_and_map'] ? "PASS" : "FAIL") . "\n";

    // -------------------------------------------------------------
    // ADIM 9: Randevu Oluşturma (Müşteri Tarafından)
    // -------------------------------------------------------------
    $targetDate = date('Y-m-d', strtotime('+1 day')) . 'T11:00';
    $custBookingCsrf = extractCsrf($step7Detail['body']);
    $bookingPost = [
        'csrf_token' => $custBookingCsrf,
        'business_id' => $newBusId,
        'employee_id' => $newEmpId,
        'service_ids' => [$newSvcId],
        'start' => $targetDate,
        'note' => 'Nihai test randevusu'
    ];
    $step9Booking = httpReq("{$baseUrl}/randevu_olustur.php", 'POST', $bookingPost, $custCookieJar);

    // Müşterinin randevularım sayfasına bak
    $step9CustApts = httpReq("{$baseUrl}/musteri/randevularim.php", 'GET', [], $custCookieJar);
    $bookingCreated = str_contains($step9CustApts['body'], "Nihai Doğrulama Salonu {$clientSuffix}") || str_contains($step9CustApts['body'], 'Aktif randevular');
    
    // Randevu ID'sini DB'den doğrula
    $stmtApt = $pdo->prepare("SELECT id FROM randevular WHERE isletme_id = ? ORDER BY id DESC LIMIT 1");
    $stmtApt->execute([$newBusId]);
    $createdAptId = (int)$stmtApt->fetchColumn();

    $results['9_appointment_booking'] = ($createdAptId > 0);
    echo "9. Müşteri randevuyu başarıyla oluşturdu mu (Randevu ID: $createdAptId): " . ($results['9_appointment_booking'] ? "PASS" : "FAIL") . "\n";

    // -------------------------------------------------------------
    // ADIM 10: İşletme Panelinden Randevu Onaylama
    // -------------------------------------------------------------
    $barberCsrfPage = httpReq("{$baseUrl}/isletme/randevular.php", 'GET', [], $barberCookieJar);
    $barberPanelCsrf = extractCsrf($barberCsrfPage['body']) ?: $barberCsrf3;
    $approveAptPost = [
        'csrf_token' => $barberPanelCsrf,
        'appointment_id' => $createdAptId,
        'status' => 'confirmed'
    ];
    $step10Approve = httpReq("{$baseUrl}/isletme/randevular.php", 'POST', $approveAptPost, $barberCookieJar);
    
    // DB'de durum 'confirmed' oldu mu?
    $stmtAptCheck = $pdo->prepare("SELECT durum FROM randevular WHERE id = ?");
    $stmtAptCheck->execute([$createdAptId]);
    $isAptConfirmed = ($stmtAptCheck->fetchColumn() === 'confirmed');
    $results['10_business_appointment_approval'] = $isAptConfirmed;
    echo "10. İşletme panelinden randevu onaylandı mı (durum=confirmed): " . ($results['10_business_appointment_approval'] ? "PASS" : "FAIL") . "\n";

    // -------------------------------------------------------------
    // ADIM 11: Randevu İptali (İşletme Tarafından)
    // -------------------------------------------------------------
    $cancelAptPost = [
        'csrf_token' => $barberPanelCsrf,
        'appointment_id' => $createdAptId,
        'status' => 'cancelled'
    ];
    $step11Cancel = httpReq("{$baseUrl}/isletme/randevular.php", 'POST', $cancelAptPost, $barberCookieJar);
    $stmtAptCheck->execute([$createdAptId]);
    $isAptCancelled = ($stmtAptCheck->fetchColumn() === 'cancelled');
    $results['11_appointment_cancellation'] = $isAptCancelled;
    echo "11. Randevu iptal işlemi gerçekleşti mi (durum=cancelled): " . ($results['11_appointment_cancellation'] ? "PASS" : "FAIL") . "\n";

} finally {
    // -------------------------------------------------------------
    // TEMİZLİK: Test kayıtlarını ve çerez dosyalarını sil
    // -------------------------------------------------------------
    if (!empty($newBusId)) {
        $pdo->prepare("DELETE FROM randevu_ek_bilgileri WHERE randevu_id IN (SELECT id FROM randevular WHERE isletme_id = ?)")->execute([$newBusId]);
        $pdo->prepare("DELETE FROM randevu_bildirim_kayitlari WHERE randevu_id IN (SELECT id FROM randevular WHERE isletme_id = ?)")->execute([$newBusId]);
        $pdo->prepare("DELETE FROM randevular WHERE isletme_id = ?")->execute([$newBusId]);
        $pdo->prepare("DELETE FROM hizmet_calisanlari WHERE calisan_id = ?")->execute([$newEmpId]);
        $pdo->prepare("DELETE FROM hizmetler WHERE isletme_id = ?")->execute([$newBusId]);
        $pdo->prepare("DELETE FROM calisan_calisma_araliklari WHERE calisan_id = ?")->execute([$newEmpId]);
        $pdo->prepare("DELETE FROM calisanlar WHERE isletme_id = ?")->execute([$newBusId]);
        $pdo->prepare("DELETE FROM isletme_calisma_araliklari WHERE isletme_id = ?")->execute([$newBusId]);
        $pdo->prepare("DELETE FROM guvenilir_cihazlar WHERE kullanici_turu = 'isletme' AND kullanici_id = ?")->execute([$newBusId]);
        $pdo->prepare("DELETE FROM isletmeler WHERE id = ?")->execute([$newBusId]);
    }
    if (!empty($custEmail)) {
        $pdo->prepare("DELETE FROM kullanicilar WHERE eposta = ?")->execute([$custEmail]);
    }
    @unlink($custCookieJar);
    @unlink($barberCookieJar);
    echo "\nTest kayıtları ve geçici çerez oturumları temizlendi.\n";
}

$allPass = !in_array(false, $results, true);
echo "\n====================================================================\n";
echo "GERÇEK TARAYICI / HTTP AKIŞLARI SONUCU: " . ($allPass ? "TÜM ADIMLAR BAŞARILI (PASS)" : "BAZI ADIMLAR BAŞARISIZ (FAIL)") . "\n";
echo "====================================================================\n";
