<?php
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_METHOD'] = 'POST';

require_once __DIR__ . '/../admin/includes/auth.php';
require_once __DIR__ . '/../isletme/_booking.php';

$pdo->beginTransaction();

try {
    $suffix = bin2hex(random_bytes(4));
    $stmt = $pdo->prepare("INSERT INTO isletmeler (isletme_sahibi, isletme_adi, telefon, eposta, sehir, ilce, sifre_hash, durum)
                           VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");
    $stmt->execute([
        'Booking Test Owner',
        'Booking Test Business ' . $suffix,
        '0555' . random_int(1000000, 9999999),
        'booking-' . $suffix . '@example.test',
        'Ankara',
        'Çankaya',
        password_hash('test-password', PASSWORD_DEFAULT),
    ]);
    $businessId = (int)$pdo->lastInsertId();

    $stmt = $pdo->prepare('INSERT INTO kullanicilar (ad_soyad, telefon, eposta, sifre_hash) VALUES (?, ?, ?, ?)');
    $stmt->execute([
        'Booking Test Customer',
        '0544' . random_int(1000000, 9999999),
        'booking-customer-' . $suffix . '@example.test',
        password_hash('test-password', PASSWORD_DEFAULT),
    ]);
    $customerId = (int)$pdo->lastInsertId();

    $stmt = $pdo->prepare('INSERT INTO hizmetler (isletme_id, hizmet_adi, fiyat, sure_dakika) VALUES (?, ?, 300, 30)');
    $stmt->execute([$businessId, 'Booking Test Service']);
    $serviceId = (int)$pdo->lastInsertId();

    $photoInsert = $pdo->prepare('INSERT INTO isletme_fotograflari (isletme_id, fotograf_yolu) VALUES (?, ?)');
    $photoInsert->execute([$businessId, 'uploads/isletme/' . $businessId . '/profile-test.jpg']);
    $profilePhotoId = (int)$pdo->lastInsertId();
    $photoInsert->execute([$businessId, 'uploads/isletme/' . $businessId . '/cover-test.jpg']);
    $coverPhotoId = (int)$pdo->lastInsertId();
    $photoInsert->execute([$businessId, 'uploads/isletme/' . $businessId . '/gallery-test.jpg']);
    $galleryPhotoId = (int)$pdo->lastInsertId();
    $pdo->prepare('INSERT INTO isletme_panel_medya (isletme_id, profil_fotograf_id, kapak_fotograf_id) VALUES (?, ?, ?)')
        ->execute([$businessId, $profilePhotoId, $coverPhotoId]);
    $photoOrder = $pdo->prepare('INSERT INTO isletme_fotograf_siralama (fotograf_id, isletme_id, sira) VALUES (?, ?, ?)');
    foreach ([$profilePhotoId, $coverPhotoId, $galleryPhotoId] as $position => $photoId) {
        $photoOrder->execute([$photoId, $businessId, $position]);
    }

    $stmt = $pdo->prepare('INSERT INTO calisanlar (isletme_id, ad_soyad) VALUES (?, ?)');
    $stmt->execute([$businessId, 'Booking Test Employee']);
    $employeeId = (int)$pdo->lastInsertId();

    $stmt = $pdo->prepare('INSERT INTO hizmet_calisanlari (hizmet_id, calisan_id) VALUES (?, ?)');
    $stmt->execute([$serviceId, $employeeId]);

    $appointmentDate = date('Y-m-d', strtotime('+3 days'));
    $weekday = (int)date('N', strtotime($appointmentDate));
    $stmt = $pdo->prepare('INSERT INTO isletme_calisma_araliklari (isletme_id, haftanin_gunu, baslangic, bitis) VALUES (?, ?, ?, ?)');
    $stmt->execute([$businessId, $weekday, '09:00:00', '18:00:00']);
    $stmt = $pdo->prepare('INSERT INTO calisan_calisma_araliklari (calisan_id, haftanin_gunu, baslangic, bitis) VALUES (?, ?, ?, ?)');
    $stmt->execute([$employeeId, $weekday, '09:00:00', '18:00:00']);

    $_SESSION['user_role'] = 'customer';
    $_SESSION['user_id'] = $customerId;
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_POST['csrf_token'] = $_SESSION['csrf_token'];
    $csrfAccepted = verifyCsrfToken();

    $_GET['id'] = $businessId;
    ob_start();
    include __DIR__ . '/../berber.php';
    $profileOutput = ob_get_clean();
    $bookingFormRenders = strpos($profileOutput, 'action="/randevu_olustur.php"') !== false;
    $profileUsesSessionCsrf = strpos($profileOutput, 'value="' . $_SESSION['csrf_token'] . '"') !== false;
    $businessOwnerIsSelectable = strpos($profileOutput, 'İşletme sahibi — Booking Test Owner') !== false;
    $ownerAppearsInStaffList = strpos($profileOutput, '<small class="d-block text-muted">İşletme sahibi</small>') !== false;
    $ownerHoursAreDisplayed = strpos($profileOutput, '<h2 class="h5"><i class="fa-regular fa-clock me-2"></i>Çalışma saatleri</h2>') !== false
        && strpos($profileOutput, '09:00–18:00') !== false;
    $bookingIsInRightColumn = strpos($profileOutput, 'booking-sticky') !== false
        && strpos($profileOutput, 'id="bookingTitle">Hizmet seçin') !== false;
    $bookingStartsAlongsideProfile = strpos($profileOutput, 'class="business-profile-layout"')
            < strpos($profileOutput, 'style="height:280px;background:#0F172A;position:relative"')
        && strpos($profileOutput, 'position:sticky; top:16px') !== false;
    $bookingWizardIncludesTimeGrid = strpos($profileOutput, 'booking-time-grid') !== false
        && strpos($profileOutput, 'booking-date-list') !== false;

    $ownerSlots = businessAvailableSlots($pdo, $businessId, 0, $serviceId, $appointmentDate);
    $ownerSlotValues = array_column($ownerSlots, 'time');
    $ownerSlotAvailability = array_column($ownerSlots, 'available', 'time');
    $ownerSlotsUseBusinessHours = in_array('09:00', $ownerSlotValues, true)
        && isset($ownerSlotAvailability['09:00'])
        && !in_array('18:00', $ownerSlotValues, true);
    $employeeSlots = businessAvailableSlots($pdo, $businessId, $employeeId, $serviceId, $appointmentDate);
    $employeeSlotAvailability = array_column($employeeSlots, 'available', 'time');
    $employeeSlotsRespectEmployeeHours = isset($employeeSlotAvailability['09:00'])
        && !isset($employeeSlotAvailability['08:30']);
    $galleryStart = strpos($profileOutput, '<h2 class="h5">Fotoğraf galerisi</h2>');
    $galleryEnd = $galleryStart === false ? false : strpos($profileOutput, '</section>', $galleryStart);
    $galleryMarkup = $galleryStart !== false && $galleryEnd !== false
        ? substr($profileOutput, $galleryStart, $galleryEnd - $galleryStart)
        : '';
    $featuredPhotosExcludedFromGallery = strpos($galleryMarkup, 'gallery-test.jpg') !== false
        && strpos($galleryMarkup, 'profile-test.jpg') === false
        && strpos($galleryMarkup, 'cover-test.jpg') === false;

    $start = $appointmentDate . ' 10:00';
    [$available, $end] = businessCanBook($pdo, $businessId, $employeeId, $serviceId, $start);
    $validSlotAccepted = $available && $end === $appointmentDate . ' 10:30:00';

    [$outsideHoursAvailable] = businessCanBook($pdo, $businessId, $employeeId, $serviceId, $appointmentDate . ' 18:00');

    $stmt = $pdo->prepare("INSERT INTO randevular (kullanici_id, isletme_id, calisan_id, hizmet_id, randevu_zamani, durum)
                           VALUES (?, ?, ?, ?, ?, 'pending')");
    $stmt->execute([$customerId, $businessId, $employeeId, $serviceId, $appointmentDate . ' 10:00:00']);
    [$overlapAvailable] = businessCanBook($pdo, $businessId, $employeeId, $serviceId, $start);
    $busyEmployeeSlots = businessAvailableSlots($pdo, $businessId, $employeeId, $serviceId, $appointmentDate);
    $busyEmployeeAvailability = array_column($busyEmployeeSlots, 'available', 'time');
    $overlappingDisplayedAsUnavailable = isset($busyEmployeeAvailability['10:00']) && !$busyEmployeeAvailability['10:00'];

    $triggerRejectedOverlap = false;
    try {
        $stmt->execute([$customerId, $businessId, $employeeId, $serviceId, $appointmentDate . ' 10:00:00']);
    } catch (PDOException $e) {
        $triggerRejectedOverlap = $e->getCode() === '45000';
    }

    $ownerStart = $appointmentDate . ' 11:00';
    [$ownerAvailable, $ownerEnd] = businessCanBook($pdo, $businessId, 0, $serviceId, $ownerStart);
    $ownerCanBook = $ownerAvailable && $ownerEnd === $appointmentDate . ' 11:30:00';
    $ownerAppointment = $pdo->prepare("INSERT INTO randevular (kullanici_id, isletme_id, calisan_id, hizmet_id, randevu_zamani, durum)
                                       VALUES (?, ?, NULL, ?, ?, 'pending')");
    $ownerAppointment->execute([$customerId, $businessId, $serviceId, $appointmentDate . ' 11:00:00']);
    [$ownerOverlapAvailable] = businessCanBook($pdo, $businessId, 0, $serviceId, $ownerStart);
    $ownerTriggerRejectedOverlap = false;
    try {
        $ownerAppointment->execute([$customerId, $businessId, $serviceId, $appointmentDate . ' 11:00:00']);
    } catch (PDOException $e) {
        $ownerTriggerRejectedOverlap = $e->getCode() === '45000';
    }

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM isletme_bildirimleri WHERE isletme_id = ? AND tur = "appointment"');
    $stmt->execute([$businessId]);
    $appointmentNotificationCreated = (int)$stmt->fetchColumn() > 0;

    $pdo->prepare('DELETE FROM isletme_calisma_araliklari WHERE isletme_id = ?')->execute([$businessId]);
    ob_start();
    include __DIR__ . '/../berber.php';
    $profileWithoutOwnerHours = ob_get_clean();
    $unconfiguredOwnerIsHidden = strpos($profileWithoutOwnerHours, 'Çalışma saatleri</h2>') === false
        && strpos($profileWithoutOwnerHours, 'value="0">İşletme sahibi') === false;

    $checks = [
        'Customer CSRF token is accepted' => $csrfAccepted,
        'Public profile renders customer booking form' => $bookingFormRenders,
        'Booking form uses session CSRF token' => $profileUsesSessionCsrf,
        'Business owner appears as a selectable service provider' => $businessOwnerIsSelectable,
        'Business owner appears in staff list' => $ownerAppearsInStaffList,
        'Configured owner hours are displayed' => $ownerHoursAreDisplayed,
        'Booking form is positioned in right column' => $bookingIsInRightColumn,
        'Sticky booking panel starts alongside business profile' => $bookingStartsAlongsideProfile,
        'Booking wizard includes date and selectable time slots' => $bookingWizardIncludesTimeGrid,
        'Owner time slots use business hours' => $ownerSlotsUseBusinessHours,
        'Employee time slots use employee schedule' => $employeeSlotsRespectEmployeeHours,
        'Profile and cover photos are excluded from public gallery' => $featuredPhotosExcludedFromGallery,
        'Owner and hours are hidden when no schedule is configured' => $unconfiguredOwnerIsHidden,
        'Valid slot is available with calculated end time' => $validSlotAccepted,
        'Slot outside business hours is rejected' => !$outsideHoursAvailable,
        'Existing appointment blocks overlapping slot' => !$overlapAvailable,
        'Existing appointment is disabled in slot picker' => $overlappingDisplayedAsUnavailable,
        'Database trigger rejects direct overlapping booking' => $triggerRejectedOverlap,
        'Business owner can be booked during business hours' => $ownerCanBook,
        'Existing owner appointment blocks overlapping slot' => !$ownerOverlapAvailable,
        'Database trigger rejects overlapping owner booking' => $ownerTriggerRejectedOverlap,
        'New appointment creates a business notification' => $appointmentNotificationCreated,
    ];
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
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}
