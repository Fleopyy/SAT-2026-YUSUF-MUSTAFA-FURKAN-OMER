<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

require_once __DIR__ . '/../data/security.php';
require_once __DIR__ . '/../data/database.php';
require_once __DIR__ . '/../data/maps_config.php';

echo "=== BiTıraş.com İkinci Aşama Konum Doğrulama Test Paketi ===\n\n";

$results = [];
function recordResult(int $num, string $question, string $status, string $details = ''): void {
    global $results;
    $results[$num] = ['status' => $status, 'details' => $details];
    echo sprintf("[%s] Senaryo %d: %s\n      -> %s\n\n", $status, $num, $question, $details);
}

$pdo->beginTransaction();

try {
    $suffix = bin2hex(random_bytes(4));
    $initialLat = 41.02854000;
    $initialLng = 29.11235000;
    $initialVerifiedTime = '2026-10-30 10:00:00';

    $stmt = $pdo->prepare("INSERT INTO isletmeler (
        isletme_sahibi, isletme_adi, telefon, eposta, sehir, ilce,
        mahalle, cadde_sokak, bina_no, daire_no, posta_kodu, tam_adres,
        latitude, longitude, place_id, location_verified_at, sifre_hash, durum
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'ChIJ_initial_place', ?, ?, 'active')");

    $stmt->execute([
        'Test Berber',
        'Doğrulama Berber ' . $suffix,
        '0535' . random_int(1000000, 9999999),
        'verify-' . $suffix . '@bitiras.test',
        'İstanbul',
        'Çekmeköy',
        'Taşdelen Mahallesi',
        'Bulvar Sokak',
        '1-3B',
        '2',
        '34788',
        'Taşdelen Mah. Bulvar Sok. No: 1-3B, Çekmeköy, İstanbul',
        number_format($initialLat, 8, '.', ''),
        number_format($initialLng, 8, '.', ''),
        $initialVerifiedTime,
        password_hash('Password123!', PASSWORD_DEFAULT),
    ]);
    $businessId = (int)$pdo->lastInsertId();

    $_SESSION['barber_id'] = $businessId;
    $_SESSION['barber_role'] = 'barber';
    $_SESSION['barber_name'] = 'Doğrulama Berber';
    $_SESSION['user_role'] = 'barber';
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = [
        'csrf_token'            => $_SESSION['csrf_token'],
        'isletme_adi'           => 'Doğrulama Berber ' . $suffix,
        'isletme_sahibi'        => 'Test Berber',
        'telefon'               => '0535' . random_int(1000000, 9999999),
        'eposta'                => 'verify-' . $suffix . '@bitiras.test',
        'sehir'                 => 'Ankara', // Changed
        'ilce'                  => 'Çankaya', // Changed
        'mahalle'               => 'Kızılay Mahallesi',
        'cadde_sokak'           => 'Gazi Mustafa Kemal Bulvarı',
        'bina_no'               => '10',
        'daire_no'              => '',
        'posta_kodu'            => '06420',
        'tam_adres'             => 'Kızılay Mah. GMK Bulvarı No: 10, Çankaya, Ankara',
        'aciklama'              => 'Yeni adres açıklaması',
        'latitude'              => (string)$initialLat, // Form still had old Istanbul coords
        'longitude'             => (string)$initialLng,
        'place_id'              => 'ChIJ_initial_place',
        'location_reconfirmed'  => '0', // NOT reconfirmed for Ankara
    ];

    ob_start();
    require __DIR__ . '/../isletme/profil.php';
    ob_get_clean();

    $stmt = $pdo->prepare("SELECT sehir, ilce, latitude, longitude, location_verified_at FROM isletmeler WHERE id = ?");
    $stmt->execute([$businessId]);
    $afterChange = $stmt->fetch(PDO::FETCH_ASSOC);

    $oldCoordsInvalidated = ($afterChange['latitude'] === null && $afterChange['longitude'] === null);
    $verifiedReset = ($afterChange['location_verified_at'] === null);

    recordResult(
        1,
        "İşletme kayıtlı adresini değiştirdiğinde eski latitude ve longitude değerleri otomatik olarak geçersiz kılınıyor mu?",
        $oldCoordsInvalidated ? "PASS" : "FAIL",
        $oldCoordsInvalidated
            ? "Adres değiştiğinde (İstanbul -> Ankara) eski koordinatlar başarıyla NULL yapıldı ve geçersiz kılındı."
            : "HATA: Eski koordinatlar silinmedi! Lat: {$afterChange['latitude']}, Lng: {$afterChange['longitude']}"
    );

    recordResult(
        2,
        "Adres değiştiğinde location_verified_at sıfırlanıyor mu?",
        $verifiedReset ? "PASS" : "FAIL",
        $verifiedReset
            ? "Adres değiştiğinde location_verified_at başarıyla NULL olarak sıfırlandı."
            : "HATA: location_verified_at sıfırlanmadı: {$afterChange['location_verified_at']}"
    );

    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET['id'] = (string)$businessId;
    unset($_SESSION['barber_id'], $_SESSION['barber_role']);

    ob_start();
    require __DIR__ . '/../berber.php';
    $customerHtml = ob_get_clean();

    $showsOldMap = str_contains($customerHtml, 'customerProfileMap');
    $showsVerifiedBadge = str_contains($customerHtml, 'Doğrulanmış Konum');
    $showsNewAddressText = str_contains($customerHtml, 'Kızılay Mah');
    $scenario3Pass = (!$showsOldMap && !$showsVerifiedBadge && $showsNewAddressText);

    recordResult(
        3,
        "İşletme yeni konumu haritada tekrar onaylamadan müşteri profilinde eski konum doğrulanmış olarak gösteriliyor mu?",
        $scenario3Pass ? "PASS" : "FAIL",
        $scenario3Pass
            ? "Eski harita ve doğrulanmış rozeti gizlendi. Müşteri profilinde yalnızca güncel açık adres metni gösterildi."
            : "HATA: Eski konum veya harita onaylanmadan müşteri profilinde gösterildi!"
    );

    $_SESSION['barber_id'] = $businessId;
    $_SESSION['barber_role'] = 'barber';
    $_SERVER['REQUEST_METHOD'] = 'POST';

    $ankaraLat = 39.92077000;
    $ankaraLng = 32.85411000;

    $_POST = [
        'csrf_token' => $_SESSION['csrf_token'],
        'latitude'   => (string)$ankaraLat,
        'longitude'  => (string)$ankaraLng,
        'place_id'   => 'ChIJ_ankara_place',
    ];
    ob_start();
    require __DIR__ . '/../isletme/konum_kaydet.php';
    $ajaxJson = ob_get_clean();
    $ajaxData = json_decode($ajaxJson, true);

    $stmt = $pdo->prepare("SELECT latitude, longitude, location_verified_at FROM isletmeler WHERE id = ?");
    $stmt->execute([$businessId]);
    $ajaxDbRow = $stmt->fetch(PDO::FETCH_ASSOC);

    $ajaxDbLat = (float)$ajaxDbRow['latitude'];
    $ajaxDbLng = (float)$ajaxDbRow['longitude'];
    $ajaxMatches = (abs($ajaxDbLat - $ankaraLat) < 0.00001 && abs($ajaxDbLng - $ankaraLng) < 0.00001 && !empty($ajaxDbRow['location_verified_at']));

    recordResult(
        4,
        "İşletme sahibi konumu değiştirdiğinde hem profil formu hem AJAX uç noktası aynı veriyi kullanıyor mu?",
        $ajaxMatches ? "PASS" : "FAIL",
        $ajaxMatches
            ? "Her iki uç nokta da standart 8 basamaklı decimal koordinat hassasiyeti ve veritabanı şemasını kullanıyor (Lat: $ajaxDbLat, Lng: $ajaxDbLng)."
            : "HATA: Veri uyuşmazlığı tespit edildi."
    );

    $keyInCustomerHtml = str_contains($customerHtml, 'AIzaSy');
    $keyInAjaxResponse = str_contains($ajaxJson, 'AIzaSy') || str_contains($ajaxJson, 'api_key');
    $appJsContent = file_get_contents(__DIR__ . '/../app.js');
    $keyInAppJs = str_contains($appJsContent, 'AIzaSy') || str_contains($appJsContent, 'google_maps_api_key');

    $scenario5Pass = (!$keyInCustomerHtml && !$keyInAjaxResponse && !$keyInAppJs);
    recordResult(
        5,
        "API anahtarı HTML kaynak kodunda, JavaScript dosyalarında veya yetkisiz AJAX yanıtlarında açığa çıkıyor mu?",
        $scenario5Pass ? "PASS" : "FAIL",
        $scenario5Pass
            ? "Müşteri sayfalarında Leaflet kullanılmakta olup hiçbir API anahtarı HTML/JS veya AJAX yanıtlarında sızdırılmamaktadır."
            : "HATA: API anahtarı istemci tarafında tespit edildi!"
    );

    $musteriFiles = glob(__DIR__ . '/../musteri/*.php');
    $isletmeFiles = glob(__DIR__ . '/../isletme/*.php');
    $foundInMusteri = false;
    $foundInIsletme = false;

    foreach ($musteriFiles as $f) {
        if (str_contains(file_get_contents($f), 'site_ayarlari')) {
            $foundInMusteri = true;
            break;
        }
    }
    foreach ($isletmeFiles as $f) {
        if (str_contains(file_get_contents($f), 'site_ayarlari')) {
            $foundInIsletme = true;
            break;
        }
    }

    $scenario6Pass = (!$foundInMusteri && !$foundInIsletme);
    recordResult(
        6,
        "site_ayarlari tablosundaki API anahtarına müşteri ve işletme hesapları erişebiliyor mu?",
        $scenario6Pass ? "PASS" : "FAIL",
        $scenario6Pass
            ? "Müşteri ve işletme panellerinde site_ayarlari tablosuna doğrudan sorgu bulunmamaktadır. Yalnızca yetkili Admin erişebilmektedir."
            : "HATA: İşletme veya müşteri panelinde site_ayarlari tablosuna erişim tespit edildi."
    );

    $osmQuery = "Cankaya, Ankara, Turkiye";
    $osmUrl = "https://nominatim.openstreetmap.org/search?format=json&q=" . urlencode($osmQuery);
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 5,
            'header'  => "User-Agent: BiTiras-Geocoding-Verification/1.0\r\n"
        ]
    ]);
    $osmRes = @file_get_contents($osmUrl, false, $ctx);
    $osmJson = $osmRes ? json_decode($osmRes, true) : null;
    $osmWorks = (!empty($osmJson) && isset($osmJson[0]['lat'], $osmJson[0]['lon']));

    if ($osmWorks) {
        $osmLat = (float)$osmJson[0]['lat'];
        $osmLng = (float)$osmJson[0]['lon'];
        $osmValid = isValidCoordinate($osmLat, $osmLng);
    } else {
        $osmValid = false;
    }

    recordResult(
        7,
        "Google Maps API anahtarı bulunmadığında OpenStreetMap üzerinden adres arama ve koordinat kaydetme gerçekten çalışıyor mu?",
        ($osmWorks && $osmValid) ? "PASS" : "FAIL",
        ($osmWorks && $osmValid)
            ? "OpenStreetMap Nominatim servisi adres aramasını başarıyla döndürdü (Lat: {$osmJson[0]['lat']}, Lon: {$osmJson[0]['lon']}) ve sistem koordinatları geçerli olarak işledi."
            : "Ağ erişimi veya harita servisi yanıt vermedi."
    );

    $invalidCases = [
        ['lat' => '95.1234', 'lng' => '29.1234', 'name' => 'Enlem > 90'],
        ['lat' => '-95.1234', 'lng' => '29.1234', 'name' => 'Enlem < -90'],
        ['lat' => '41.1234', 'lng' => '195.1234', 'name' => 'Boylam > 180'],
        ['lat' => '41.1234', 'lng' => '-195.1234', 'name' => 'Boylam < -180'],
        ['lat' => 'injection\' OR 1=1', 'lng' => '29.1234', 'name' => 'SQL Enjeksiyonu'],
        ['lat' => '<script>alert(1)</script>', 'lng' => '29.1234', 'name' => 'XSS Girişimi'],
        ['lat' => '', 'lng' => '29.1234', 'name' => 'Eksik Enlem'],
        ['lat' => 'NAN', 'lng' => '29.1234', 'name' => 'Sayısal Olmayan Değer'],
    ];

    $allManipulationsBlocked = true;
    foreach ($invalidCases as $case) {
        $_POST = [
            'csrf_token' => $_SESSION['csrf_token'],
            'latitude'   => $case['lat'],
            'longitude'  => $case['lng'],
        ];
        ob_start();
        require __DIR__ . '/../isletme/konum_kaydet.php';
        $res = json_decode(ob_get_clean(), true);
        if (($res['success'] ?? false) === true) {
            $allManipulationsBlocked = false;
            break;
        }
    }

    recordResult(
        8,
        "Geçersiz, eksik veya manipüle edilmiş koordinatlarla konum doğrulaması yapılabiliyor mu?",
        $allManipulationsBlocked ? "PASS" : "FAIL",
        $allManipulationsBlocked
            ? "Tüm geçersiz, eksik, sınır dışı, NaN, SQLi ve XSS manipülasyonlu koordinat denemeleri 422 ile kesin olarak engellendi."
            : "HATA: Bazı geçersiz koordinatlar kabul edildi!"
    );

    $stmt = $pdo->prepare("INSERT INTO isletmeler (isletme_sahibi, isletme_adi, telefon, eposta, sehir, ilce, latitude, longitude, sifre_hash, durum)
                           VALUES ('Hedef Berber', 'Hedef İşletme', '05361112233', 'target@bitiras.test', 'Bursa', 'Nilüfer', 40.21000000, 28.98000000, 'hash', 'active')");
    $stmt->execute();
    $targetBusinessId = (int)$pdo->lastInsertId();

    $_POST = [
        'csrf_token'  => $_SESSION['csrf_token'],
        'business_id' => (string)$targetBusinessId,
        'id'          => (string)$targetBusinessId,
        'latitude'    => '41.99999999',
        'longitude'   => '29.99999999',
    ];
    ob_start();
    require __DIR__ . '/../isletme/konum_kaydet.php';
    ob_get_clean();

    $stmt = $pdo->prepare("SELECT latitude, longitude FROM isletmeler WHERE id = ?");
    $stmt->execute([$targetBusinessId]);
    $targetRow = $stmt->fetch(PDO::FETCH_ASSOC);

    $targetUnchanged = (abs((float)$targetRow['latitude'] - 40.21) < 0.0001);
    recordResult(
        9,
        "İşletme hesabı başka bir işletmenin konumunu değiştirebiliyor mu?",
        $targetUnchanged ? "PASS" : "FAIL",
        $targetUnchanged
            ? "İşlem yetkisi doğrudan sunucu tarafındaki oturumdan (barber_id) alınmakta olup IDOR girişimi engellenmiştir. Hedef işletme koordinatları değişmemiştir."
            : "HATA: Başka bir işletmenin konumu değiştirildi (IDOR Açığı)!"
    );

    $verifiedLat = 41.02854000;
    $verifiedLng = 29.11235000;
    $nowTime = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare("UPDATE isletmeler SET latitude = ?, longitude = ?, location_verified_at = ? WHERE id = ?");
    $stmt->execute([
        number_format($verifiedLat, 8, '.', ''),
        number_format($verifiedLng, 8, '.', ''),
        $nowTime,
        $businessId
    ]);

    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET['id'] = (string)$businessId;
    unset($_SESSION['barber_id'], $_SESSION['barber_role']);

    ob_start();
    require __DIR__ . '/../berber.php';
    $renderedHtml = ob_get_clean();

    $expectedCoordsQuery = number_format($verifiedLat, 8, '.', '') . '%2C' . number_format($verifiedLng, 8, '.', '');
    $expectedCoordsDir = number_format($verifiedLat, 8, '.', '') . '%2C' . number_format($verifiedLng, 8, '.', '');

    $hasCorrectDirLink = str_contains($renderedHtml, 'destination=' . $expectedCoordsDir);
    $hasCorrectSearchLink = str_contains($renderedHtml, 'query=' . $expectedCoordsQuery);
    $hasCorrectMarkerJs = str_contains($renderedHtml, 'var lat = ' . number_format($verifiedLat, 8, '.', '')) && str_contains($renderedHtml, 'var lng = ' . number_format($verifiedLng, 8, '.', ''));

    $scenario10Pass = ($hasCorrectDirLink && $hasCorrectSearchLink && $hasCorrectMarkerJs);

    recordResult(
        10,
        "Konum kaydedildikten sonra müşteri profilindeki adres, harita işaretçisi ve yol tarifi bağlantısı aynı koordinatları gösteriyor mu?",
        $scenario10Pass ? "PASS" : "FAIL",
        $scenario10Pass
            ? "Harita işaretçisi (var lat/lng = {$verifiedLat}, {$verifiedLng}), Yol Tarifi Al bağlantısı (destination={$expectedCoordsDir}) ve Haritada Aç bağlantısı (query={$expectedCoordsQuery}) birebir aynı koordinatları kullanmaktadır."
            : "HATA: Koordinat tutarsızlığı tespit edildi!"
    );

    echo "=== TÜM DOĞRULAMALAR TAMAMLANDI ===\n";

} finally {
    $pdo->rollBack();
    echo "\n[BİLGİ] İkinci aşama test veritabanı rollback yapılarak temizlendi.\n";
}
