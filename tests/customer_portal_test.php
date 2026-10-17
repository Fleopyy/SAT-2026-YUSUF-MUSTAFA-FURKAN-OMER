<?php
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_METHOD'] = 'GET';
require_once __DIR__ . '/../data/database.php';

$businessId = 0;
$customerId = 0;
$serviceId = 0;
$pastAppointmentId = 0;
$activeAppointmentId = 0;

$pdo->beginTransaction();

try {
    $suffix = bin2hex(random_bytes(4));
    $stmt = $pdo->prepare("INSERT INTO isletmeler (isletme_sahibi, isletme_adi, telefon, eposta, sehir, ilce, sifre_hash, durum)
                           VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");
    $stmt->execute([
        'Customer Portal Owner',
        'Customer Portal Business ' . $suffix,
        '0555' . random_int(1000000, 9999999),
        'customer-portal-' . $suffix . '@example.test',
        'Ankara',
        'Çankaya',
        password_hash('test-password', PASSWORD_DEFAULT),
    ]);
    $businessId = (int)$pdo->lastInsertId();

    $stmt = $pdo->prepare("INSERT INTO isletmeler (isletme_sahibi, isletme_adi, telefon, eposta, sehir, ilce, sifre_hash, durum)
                           VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");
    $stmt->execute([
        'Customer Portal Other Owner',
        'Customer Portal Istanbul Location ' . $suffix,
        '0556' . random_int(1000000, 9999999),
        'customer-portal-other-business-' . $suffix . '@example.test',
        'istanbul',
        'beşiktaş',
        password_hash('test-password', PASSWORD_DEFAULT),
    ]);

    $stmt = $pdo->prepare('INSERT INTO kullanicilar (ad_soyad, telefon, eposta, sifre_hash) VALUES (?, ?, ?, ?)');
    $stmt->execute([
        'Customer Portal Customer',
        '0544' . random_int(1000000, 9999999),
        'customer-portal-user-' . $suffix . '@example.test',
        password_hash('test-password', PASSWORD_DEFAULT),
    ]);
    $customerId = (int)$pdo->lastInsertId();
    $stmt->execute([
        'Other Portal Customer',
        '0533' . random_int(1000000, 9999999),
        'customer-portal-other-' . $suffix . '@example.test',
        password_hash('test-password', PASSWORD_DEFAULT),
    ]);
    $otherCustomerId = (int)$pdo->lastInsertId();

    $stmt = $pdo->prepare('INSERT INTO hizmetler (isletme_id, hizmet_adi, fiyat, sure_dakika) VALUES (?, ?, 300, 30)');
    $stmt->execute([$businessId, 'Customer Portal Service']);
    $serviceId = (int)$pdo->lastInsertId();

    $stmt = $pdo->prepare("INSERT INTO randevular (kullanici_id, isletme_id, hizmet_id, randevu_zamani, durum)
                           VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 2 DAY), 'confirmed')");
    $stmt->execute([$customerId, $businessId, $serviceId]);
    $activeAppointmentId = (int)$pdo->lastInsertId();

    $stmt = $pdo->prepare("INSERT INTO randevular (kullanici_id, isletme_id, hizmet_id, randevu_zamani, durum)
                           VALUES (?, ?, ?, DATE_SUB(NOW(), INTERVAL 2 DAY), 'completed')");
    $stmt->execute([$customerId, $businessId, $serviceId]);
    $pastAppointmentId = (int)$pdo->lastInsertId();

    $stmt = $pdo->prepare("INSERT INTO randevular (kullanici_id, isletme_id, hizmet_id, randevu_zamani, durum)
                           VALUES (?, ?, ?, DATE_SUB(NOW(), INTERVAL 3 DAY), 'completed')");
    $stmt->execute([$otherCustomerId, $businessId, $serviceId]);
    $otherAppointmentId = (int)$pdo->lastInsertId();

    $stmt = $pdo->prepare("INSERT INTO degerlendirmeler (randevu_id, degerlendiren_turu, degerlendiren_id, puan, yorum)
                           VALUES (?, 'business', ?, 5, 'Customer portal test review')");
    $stmt->execute([$pastAppointmentId, $businessId]);
    $stmt = $pdo->prepare("INSERT INTO degerlendirmeler (randevu_id, degerlendiren_turu, degerlendiren_id, puan, yorum)
                           VALUES (?, 'business', ?, 4, 'Other customer private review')");
    $stmt->execute([$otherAppointmentId, $businessId]);
    $pdo->prepare('INSERT INTO kullanici_portal_profilleri (kullanici_id, fotograf_yolu) VALUES (?, ?)')
        ->execute([$customerId, 'uploads/musteri/' . $customerId . '/test-profile.jpg']);

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION = [
        'user_id' => $customerId,
        'user_name' => 'Customer Portal Customer',
        'user_role' => 'customer',
        'csrf_token' => bin2hex(random_bytes(32)),
    ];

    ob_start();
    include __DIR__ . '/../musteri/randevularim.php';
    $appointmentsOutput = ob_get_clean();
    $appointmentsChecks = [
        'Customer appointments page shows active appointment' => strpos($appointmentsOutput, 'Aktif randevular') !== false
            && strpos($appointmentsOutput, 'Onaylandı') !== false,
        'Customer appointments page shows appointment history' => strpos($appointmentsOutput, 'Geçmiş randevular') !== false
            && strpos($appointmentsOutput, 'Tamamlandı') !== false,
        'Completed appointment offers review form' => strpos($appointmentsOutput, 'name="action" value="review"') !== false
            && strpos($appointmentsOutput, 'name="comment"') !== false,
        'Customer appointments page excludes other customers' => strpos($appointmentsOutput, 'Other customer private review') === false,
        'Customer appointment page stays out of admin UI' => strpos($appointmentsOutput, 'admin-panel') === false,
    ];

    customerCreateAppointmentReview($pdo, $customerId, $pastAppointmentId, 5, 'My customer review');
    $reviewStatement = $pdo->prepare("SELECT puan, yorum FROM degerlendirmeler
                                      WHERE randevu_id = ? AND degerlendiren_turu = 'customer' AND degerlendiren_id = ?");
    $reviewStatement->execute([$pastAppointmentId, $customerId]);
    $savedCustomerReview = $reviewStatement->fetch(PDO::FETCH_ASSOC);

    $duplicateReviewRejected = false;
    try {
        customerCreateAppointmentReview($pdo, $customerId, $pastAppointmentId, 4, 'Duplicate review');
    } catch (PDOException $e) {
        $duplicateReviewRejected = $e->getCode() === '23000';
    }

    $otherCustomerAppointmentRejected = false;
    try {
        customerCreateAppointmentReview($pdo, $customerId, $otherAppointmentId, 5, 'Unauthorized review');
    } catch (RuntimeException $e) {
        $otherCustomerAppointmentRejected = true;
    }

    $activeAppointmentReviewRejected = false;
    try {
        customerCreateAppointmentReview($pdo, $customerId, $activeAppointmentId, 5, 'Too early');
    } catch (RuntimeException $e) {
        $activeAppointmentReviewRejected = true;
    }

    ob_start();
    include __DIR__ . '/../musteri/randevularim.php';
    $reviewedAppointmentsOutput = ob_get_clean();
    $reviewChecks = [
        'Customer review is saved with its rating and text' => $savedCustomerReview
            && (int)$savedCustomerReview['puan'] === 5
            && $savedCustomerReview['yorum'] === 'My customer review',
        'Customer can submit only one review per appointment' => $duplicateReviewRejected,
        'Customer cannot review another customers appointment' => $otherCustomerAppointmentRejected,
        'Customer cannot review an appointment before completion' => $activeAppointmentReviewRejected,
        'Saved review is shown instead of the form' => strpos($reviewedAppointmentsOutput, 'My customer review') !== false
            && strpos($reviewedAppointmentsOutput, 'name="action" value="review"') === false,
    ];

    ob_start();
    include __DIR__ . '/../musteri/hesabim.php';
    $accountOutput = ob_get_clean();
    $accountChecks = [
        'Account page includes profile photo upload' => strpos($accountOutput, 'name="action" value="photo"') !== false,
        'Account page includes password change form' => strpos($accountOutput, 'name="action" value="password"') !== false,
        'Account page shows business-written review' => strpos($accountOutput, 'Customer portal test review') !== false,
        'Account page excludes other customers reviews' => strpos($accountOutput, 'Other customer private review') === false,
        'Business reviews are grouped in account page' => strpos($accountOutput, 'İşletmelerin değerlendirmeleri') !== false,
        'Account page stays out of admin UI' => strpos($accountOutput, 'admin-panel') === false,
    ];

    ob_start();
    include __DIR__ . '/../index.php';
    $homepageOutput = ob_get_clean();
    $turkeyDistrictData = json_decode(
        file_get_contents(__DIR__ . '/../data/turkey_districts.json'),
        true,
        512,
        JSON_THROW_ON_ERROR
    );
    $districtCount = array_sum(array_map('count', $turkeyDistrictData['districts']));
    $homepageChecks = [
        'Homepage menu contains customer destinations' => strpos($homepageOutput, 'href="/musteri/randevularim.php"') !== false
            && strpos($homepageOutput, 'href="/musteri/hesabim.php"') !== false,
        'Homepage account menu uses customer profile photo' => strpos($homepageOutput, 'account-menu-avatar')
            !== false && strpos($homepageOutput, 'test-profile.jpg') !== false,
        'Homepage search uses custom searchable option menus' => strpos($homepageOutput, 'id="searchCityOptions"') !== false
            && strpos($homepageOutput, 'id="searchDistrictOptions"') !== false
            && strpos($homepageOutput, 'id="businessServiceOptions"') !== false
            && strpos($homepageOutput, 'method="GET" action="/index.php#barbers"') !== false,
        'Homepage service filter contains services only and white label' => strpos($homepageOutput, '> Hizmetler') !== false
            && strpos($homepageOutput, 'Salon veya') === false
            && strpos($homepageOutput, 'Customer Portal Service') !== false,
        'Homepage search includes all provinces and districts' => count($turkeyDistrictData['districts']) === 81
            && $districtCount >= 970
            && in_array('Çandır', $turkeyDistrictData['districts']['Yozgat'], true),
        'District suggestions are available from the selected province' => strpos($homepageOutput, 'Yozgat') !== false
            && strpos($homepageOutput, 'Çandır') !== false,
        'Search panel uses a white background' => strpos(file_get_contents(__DIR__ . '/../style.css'), '.hero-search-form') !== false
            && strpos(file_get_contents(__DIR__ . '/../style.css'), 'background: #fff;') !== false,
        'Homepage search removes helper text under inputs' => strpos($homepageOutput, 'Yazmaya başlayın veya şehir listesinden seçin.') === false
            && strpos($homepageOutput, 'İlçe adı yazın veya mevcut seçeneklerden seçin.') === false
            && strpos($homepageOutput, 'İşletme veya hizmet seçin; yazarak da arayabilirsiniz.') === false,
    ];

    $_GET = ['ara' => '1', 'sehir' => 'Ankara', 'ilce' => 'Çankaya', 'hizmet' => 'Customer Portal Service'];
    ob_start();
    include __DIR__ . '/../index.php';
    $locationSearchOutput = ob_get_clean();
    $searchChecks = [
        'Location search displays matching active business offering selected service' => strpos($locationSearchOutput, '<h3 class="fs-5 fw-extrabold mb-1">Customer Portal Business ' . $suffix . '</h3>') !== false,
        'Location search excludes businesses in another district' => strpos($locationSearchOutput, '<h3 class="fs-5 fw-extrabold mb-1">Customer Portal Istanbul Location ' . $suffix . '</h3>') === false,
        'Location search renders its results heading' => strpos($locationSearchOutput, 'Arama Sonuçları') !== false,
    ];
    $_GET = ['ara' => '1', 'sehir' => 'İstanbul', 'ilce' => 'Beşiktaş'];
    ob_start();
    include __DIR__ . '/../index.php';
    $normalizedLocationSearchOutput = ob_get_clean();
    $searchChecks['Location search matches legacy lowercase city and district values'] =
        strpos($normalizedLocationSearchOutput, '<h3 class="fs-5 fw-extrabold mb-1">Customer Portal Istanbul Location ' . $suffix . '</h3>') !== false;
    $_GET = ['ara' => '1', 'sehir' => 'Yozgat'];
    ob_start();
    include __DIR__ . '/../index.php';
    $emptyLocationSearchOutput = ob_get_clean();
    $searchChecks['Typing a city without listed businesses shows a helpful empty state'] =
        strpos($emptyLocationSearchOutput, 'Bu konumda eşleşen berber bulunamadı') !== false;
    $_GET = [];

    $checks = array_merge($appointmentsChecks, $reviewChecks, $accountChecks, $homepageChecks, $searchChecks);
    $allPassed = !in_array(false, $checks, true);
    foreach ($checks as $label => $passed) {
        echo ($passed ? '[PASS] ' : '[FAIL] ') . $label . PHP_EOL;
    }
    $_SESSION = [];
    $pdo->rollBack();
    exit($allPassed ? 0 : 1);
} catch (Throwable $e) {
    if (ob_get_level() > 0) {
        ob_end_clean();
    }
    $_SESSION = [];
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}
