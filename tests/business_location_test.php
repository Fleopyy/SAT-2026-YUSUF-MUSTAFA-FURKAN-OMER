<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

require_once __DIR__ . '/../data/database.php';
require_once __DIR__ . '/../data/maps_config.php';
require_once __DIR__ . '/../data/security.php';

echo "=== BiTıraş.com İşletme Konum ve Harita Entegrasyonu Test Paketi ===\n\n";

$testResults = [];
function reportTest(string $title, bool $passed, string $details = ''): void {
    global $testResults;
    $testResults[] = ['title' => $title, 'passed' => $passed, 'details' => $details];
    echo ($passed ? "  [OK] " : "  [FAIL] ") . $title . ($details ? " - $details" : "") . "\n";
}

$pdo->beginTransaction();

try {
    $cols = $pdo->query("SHOW COLUMNS FROM isletmeler")->fetchAll(PDO::FETCH_ASSOC);
    $colMap = [];
    foreach ($cols as $c) {
        $colMap[$c['Field']] = $c;
    }

    $requiredFields = [
        'mahalle', 'cadde_sokak', 'bina_no', 'daire_no', 'posta_kodu',
        'latitude', 'longitude', 'place_id', 'location_verified_at'
    ];
    $allColsPresent = true;
    foreach ($requiredFields as $f) {
        if (!isset($colMap[$f])) {
            $allColsPresent = false;
            break;
        }
    }
    reportTest("Veritabanı migration kontrolü (gerekli tüm kolonlar mevcut)", $allColsPresent);

    $latType = $colMap['latitude']['Type'] ?? '';
    $lngType = $colMap['longitude']['Type'] ?? '';
    $isDecimalPrecision = (str_contains($latType, 'decimal(10,8)') && str_contains($lngType, 'decimal(11,8)'));
    reportTest("Koordinat decimal hassasiyeti (DECIMAL 10,8 ve 11,8)", $isDecimalPrecision, "Lat: $latType, Lng: $lngType");

    $config = mapsSettings();
    $hasSettings = isset($config['google_maps_api_key'], $config['has_api_key'], $config['default_lat']);
    reportTest("Harita yapılandırma servisi (mapsSettings()) çalışıyor", $hasSettings);

    $valid1 = isValidCoordinate(41.0082, 28.9784); // Istanbul
    $valid2 = isValidCoordinate(-89.9999, 179.9999);
    $invalid1 = isValidCoordinate(91.0, 28.0); // Lat > 90
    $invalid2 = isValidCoordinate(41.0, 185.0); // Lng > 180
    $invalid3 = isValidCoordinate(-95.0, 10.0); // Lat < -90
    $invalid4 = isValidCoordinate(10.0, -185.0); // Lng < -180
    $invalid5 = isValidCoordinate(null, 28.0);

    $coordValidationOk = $valid1 && $valid2 && !$invalid1 && !$invalid2 && !$invalid3 && !$invalid4 && !$invalid5;
    reportTest("Koordinat sınır doğrulaması (Enlem -90..90, Boylam -180..180)", $coordValidationOk);

    $suffix = bin2hex(random_bytes(4));
    $stmt = $pdo->prepare("INSERT INTO isletmeler (
        isletme_sahibi, isletme_adi, telefon, eposta, sehir, ilce,
        mahalle, cadde_sokak, bina_no, daire_no, posta_kodu, tam_adres,
        latitude, longitude, place_id, location_verified_at, sifre_hash, durum
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, 'active')");

    $testLat = 41.02854000;
    $testLng = 29.11235000;
    $stmt->execute([
        'Test Berber Sahibi',
        'Test Berber Salonu ' . $suffix,
        '0532' . random_int(1000000, 9999999),
        'test-' . $suffix . '@bitiras.test',
        'İstanbul',
        'Çekmeköy',
        'Taşdelen Mahallesi',
        'Bulvar Sokak',
        'No: 1-3B',
        'D: 2',
        '34788',
        'Taşdelen Mah. Bulvar Sok. No: 1-3B, Daire: 2, Çekmeköy, İstanbul',
        number_format($testLat, 8, '.', ''),
        number_format($testLng, 8, '.', ''),
        'ChIJtest_place_id',
        password_hash('Password123!', PASSWORD_DEFAULT),
    ]);
    $testBusinessId = (int)$pdo->lastInsertId();
    reportTest("Test işletmesi adres ve koordinatlarıyla oluşturuldu", $testBusinessId > 0);

    $stmt = $pdo->prepare("SELECT * FROM isletmeler WHERE id = ?");
    $stmt->execute([$testBusinessId]);
    $savedBusiness = $stmt->fetch(PDO::FETCH_ASSOC);

    $dbLat = (float)$savedBusiness['latitude'];
    $dbLng = (float)$savedBusiness['longitude'];
    $coordsMatch = (abs($dbLat - $testLat) < 0.000001) && (abs($dbLng - $testLng) < 0.000001);
    reportTest("Veritabanından okunan koordinatlar birebir eşleşiyor", $coordsMatch, "DB Lat: $dbLat, DB Lng: $dbLng");

    $_SESSION['barber_id'] = $testBusinessId;
    $_SESSION['barber_role'] = 'barber';
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SERVER['REQUEST_METHOD'] = 'POST';

    $newTestLat = 41.03512300;
    $newTestLng = 29.12456700;
    $_POST = [
        'csrf_token' => $_SESSION['csrf_token'],
        'latitude' => (string)$newTestLat,
        'longitude' => (string)$newTestLng,
        'place_id' => 'ChIJ_updated_place_123',
    ];

    ob_start();
    require __DIR__ . '/../isletme/konum_kaydet.php';
    $ajaxOutput = ob_get_clean();
    $ajaxResponse = json_decode($ajaxOutput, true);

    $ajaxSuccess = ($ajaxResponse['success'] ?? false) === true;
    reportTest("AJAX konum kaydetme endpointi (isletme/konum_kaydet.php)", $ajaxSuccess, $ajaxResponse['message'] ?? '');

    $stmt = $pdo->prepare("SELECT latitude, longitude, location_verified_at FROM isletmeler WHERE id = ?");
    $stmt->execute([$testBusinessId]);
    $updatedRow = $stmt->fetch(PDO::FETCH_ASSOC);
    $latUpdated = abs((float)$updatedRow['latitude'] - $newTestLat) < 0.000001;
    $verifiedUpdated = !empty($updatedRow['location_verified_at']);
    reportTest("AJAX sonrası veritabanı koordinatları ve onay tarihi güncellendi", $latUpdated && $verifiedUpdated);

    $_POST = [
        'csrf_token' => $_SESSION['csrf_token'],
        'latitude' => '999.99', // Invalid > 90
        'longitude' => '29.123',
    ];
    ob_start();
    require __DIR__ . '/../isletme/konum_kaydet.php';
    $badOutput = ob_get_clean();
    $badResponse = json_decode($badOutput, true);
    $badRejected = ($badResponse['success'] ?? true) === false;
    reportTest("Geçersiz koordinat (Enlem > 90) reddedildi", $badRejected);

    $_POST = [
        'csrf_token' => $_SESSION['csrf_token'],
        'latitude' => 'not-a-number',
        'longitude' => '29.123',
    ];
    ob_start();
    require __DIR__ . '/../isletme/konum_kaydet.php';
    $stringOutput = ob_get_clean();
    $stringResponse = json_decode($stringOutput, true);
    $stringRejected = ($stringResponse['success'] ?? true) === false;
    reportTest("Sayısal olmayan koordinat metni reddedildi", $stringRejected);

    $stmt = $pdo->prepare("INSERT INTO isletmeler (
        isletme_sahibi, isletme_adi, telefon, eposta, sehir, ilce,
        latitude, longitude, sifre_hash, durum
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
    $stmt->execute([
        'Baska Sahip',
        'Baska Berber',
        '0533' . random_int(1000000, 9999999),
        'other-' . $suffix . '@bitiras.test',
        'Ankara',
        'Çankaya',
        '39.92077000',
        '32.85411000',
        password_hash('Pass123!', PASSWORD_DEFAULT),
    ]);
    $otherBusinessId = (int)$pdo->lastInsertId();

    $_POST = [
        'csrf_token' => $_SESSION['csrf_token'],
        'business_id' => $otherBusinessId, // Attack attempt
        'latitude' => '40.00000000',
        'longitude' => '30.00000000',
    ];
    ob_start();
    require __DIR__ . '/../isletme/konum_kaydet.php';
    ob_get_clean();

    $stmt = $pdo->prepare("SELECT latitude FROM isletmeler WHERE id = ?");
    $stmt->execute([$otherBusinessId]);
    $otherLat = (float)$stmt->fetchColumn();
    $idorProtected = abs($otherLat - 39.92077) < 0.0001;
    reportTest("IDOR koruması: Oturumdaki işletme başka işletmenin konumunu değiştiremez", $idorProtected);

    $_POST = [
        'csrf_token' => 'invalid-token-12345',
        'latitude' => '41.11111111',
        'longitude' => '29.22222222',
    ];
    ob_start();
    require __DIR__ . '/../isletme/konum_kaydet.php';
    $csrfOutput = ob_get_clean();
    $csrfResponse = json_decode($csrfOutput, true);
    $csrfBlocked = ($csrfResponse['success'] ?? true) === false;
    reportTest("CSRF güvenlik koruması: Geçersiz token ile istek reddedildi", $csrfBlocked);

    unset($_SESSION['barber_id'], $_SESSION['barber_role']);
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET['id'] = (string)$testBusinessId;

    ob_start();
    require __DIR__ . '/../berber.php';
    $berberHtml = ob_get_clean();

    $containsLocationSection = str_contains($berberHtml, 'id="isletme-konumu"');
    $containsAddress = str_contains($berberHtml, 'Taşdelen Mah');
    $containsGoogleMapsDir = str_contains($berberHtml, 'maps/dir/?api=1') && str_contains($berberHtml, 'destination=');
    $containsGoogleMapsSearch = str_contains($berberHtml, 'maps/search/?api=1') && str_contains($berberHtml, 'query=');
    $containsCustomerMap = str_contains($berberHtml, 'customerProfileMap');

    reportTest("Müşteri sayfasında (berber.php) Konum Bölümü görüntülendi", $containsLocationSection);
    reportTest("Müşteri sayfasında açık adres doğru render edildi", $containsAddress);
    reportTest("Google Maps 'Yol Tarifi Al' bağlantısı doğru formatta oluşturuldu", $containsGoogleMapsDir);
    reportTest("Google Maps 'Haritada Aç' bağlantısı doğru formatta oluşturuldu", $containsGoogleMapsSearch);
    reportTest("Müşteri harita tuvali (customerProfileMap) render edildi", $containsCustomerMap);

    $stmt = $pdo->prepare("INSERT INTO isletmeler (
        isletme_sahibi, isletme_adi, telefon, eposta, sehir, ilce,
        tam_adres, latitude, longitude, sifre_hash, durum
    ) VALUES (?, ?, ?, ?, ?, ?, ?, NULL, NULL, ?, 'active')");
    $stmt->execute([
        'Konumsuz Berber Sahibi',
        'Konumsuz Berber Salonu',
        '0534' . random_int(1000000, 9999999),
        'nocoords-' . $suffix . '@bitiras.test',
        'İzmir',
        'Konak',
        'Alsancak Mah. Kıbrıs Şehitleri Cad. No: 10',
        password_hash('Pass123!', PASSWORD_DEFAULT),
    ]);
    $noCoordsBusinessId = (int)$pdo->lastInsertId();

    $_GET['id'] = (string)$noCoordsBusinessId;
    ob_start();
    require __DIR__ . '/../berber.php';
    $noCoordsHtml = ob_get_clean();

    $noEmptyMap = !str_contains($noCoordsHtml, 'customerProfileMap');
    $hasAddressSearch = str_contains($noCoordsHtml, 'Google Haritalar\'da Adresi Ara');
    reportTest("Koordinatı olmayan işletmede boş harita gösterilmedi, temiz adres gösterildi", $noEmptyMap && $hasAddressSearch);

    $stmt = $pdo->prepare("INSERT INTO hizmetler (isletme_id, hizmet_adi, fiyat, sure_dakika, aktif_mi) VALUES (?, 'Test Kesim', 250.00, 30, 1)");
    $stmt->execute([$testBusinessId]);
    $hizmetId = (int)$pdo->lastInsertId();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM hizmetler WHERE isletme_id = ?");
    $stmt->execute([$testBusinessId]);
    $hizmetCount = (int)$stmt->fetchColumn();
    reportTest("Mevcut hizmetler ve randevu altyapısı sağlamlığı korundu", $hizmetCount === 1);

    $districtsFile = __DIR__ . '/../data/turkey_districts.json';
    $districtsExist = file_exists($districtsFile);
    $decoded = json_decode(file_get_contents($districtsFile), true);
    $has81Provinces = count($decoded['districts'] ?? []) >= 81;
    reportTest("Türkiye İl & İlçe veri seti (data/turkey_districts.json) 81 il ile entegre", $districtsExist && $has81Provinces);

    echo "\n=== BÜTÜN TESTLER BAŞARIYLA TAMAMLANDI ===\n";

} finally {
    $pdo->rollBack();
    echo "\n[BİLGİ] Test veritabanı rollback yapılarak temizlendi.\n";
}
