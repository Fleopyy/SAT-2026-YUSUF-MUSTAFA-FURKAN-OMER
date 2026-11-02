<?php
require_once __DIR__ . '/data/database.php';
require_once __DIR__ . '/admin/includes/auth.php';
require_once __DIR__ . '/isletme/_booking.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    exit('Bu işlem için POST isteği gereklidir.');
}
if (!verifyCsrfToken()) {
    http_response_code(403);
    exit('Geçersiz istek.');
}
if (($_SESSION['user_role'] ?? '') !== 'customer' || empty($_SESSION['user_id'])) {
    $_SESSION['mesaj'] = 'Randevu almak için müşteri hesabınızla giriş yapın.';
    $_SESSION['mesaj_turu'] = 'warning';
    header('Location: /');
    exit;
}

$requestedBusinessId = filter_var($_POST['business_id'] ?? null, FILTER_VALIDATE_INT);
$employeeId = filter_var($_POST['employee_id'] ?? null, FILTER_VALIDATE_INT);
$serviceId = filter_var($_POST['service_id'] ?? null, FILTER_VALIDATE_INT);
$rawServiceIds = $_POST['service_ids'] ?? null;
$serviceIds = [];
if (is_array($rawServiceIds)) {
    $serviceIds = array_values(array_unique(array_filter(array_map('intval', $rawServiceIds))));
} elseif (is_string($rawServiceIds) && trim($rawServiceIds) !== '') {
    $serviceIds = array_values(array_unique(array_filter(array_map('intval', explode(',', $rawServiceIds)))));
}
if (empty($serviceIds) && $serviceId) {
    $serviceIds = [$serviceId];
}

$startInput = is_string($_POST['start'] ?? null) ? $_POST['start'] : '';
$start = preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $startInput) ? str_replace('T', ' ', $startInput) : '';
$note = trim(is_string($_POST['note'] ?? null) ? $_POST['note'] : '');
if (!$requestedBusinessId || $employeeId === false || $employeeId === null || empty($serviceIds) || mb_strlen($note, 'UTF-8') > 1000) {
    http_response_code(422);
    exit('Randevu bilgileri geçersiz.');
}

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("SELECT id FROM isletmeler WHERE id = ? AND durum = 'active' FOR UPDATE");
    $stmt->execute([$requestedBusinessId]);
    if (!$stmt->fetchColumn()) {
        throw new RuntimeException('Bu işletme şu anda randevu kabul etmiyor.');
    }
    if ($employeeId > 0) {
        $stmt = $pdo->prepare('SELECT id FROM calisanlar WHERE id = ? AND isletme_id = ? AND aktif_mi = 1 FOR UPDATE');
        $stmt->execute([$employeeId, $requestedBusinessId]);
        if (!$stmt->fetchColumn()) {
            throw new RuntimeException('Seçilen çalışan bu işletmede aktif değil.');
        }
    }

    $placeholders = implode(',', array_fill(0, count($serviceIds), '?'));
    $stmt = $pdo->prepare("SELECT id, hizmet_adi, sure_dakika, fiyat FROM hizmetler WHERE id IN ($placeholders) AND isletme_id = ? AND aktif_mi = 1");
    $stmt->execute(array_merge($serviceIds, [$requestedBusinessId]));
    $servicesList = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($servicesList) !== count($serviceIds)) {
        throw new RuntimeException('Seçilen hizmetlerden biri veya birkaçı geçerli değil.');
    }

    $totalPrice = 0.0;
    $serviceNames = [];
    foreach ($servicesList as $svc) {
        $totalPrice += (float)$svc['fiyat'];
        $serviceNames[] = $svc['hizmet_adi'];
    }
    $primaryServiceId = $serviceIds[0];

    [$available, $result] = businessCanBook($pdo, $requestedBusinessId, $employeeId, $serviceIds, $start);
    if (!$available) {
        throw new RuntimeException($result);
    }
    $end = $result;

    $stmt = $pdo->prepare('INSERT INTO randevular (kullanici_id, isletme_id, calisan_id, hizmet_id, randevu_zamani, durum) VALUES (?, ?, ?, ?, ?, "pending")');
    $stmt->execute([(int)$_SESSION['user_id'], $requestedBusinessId, $employeeId > 0 ? $employeeId : null, $primaryServiceId, $start . ':00']);
    $appointmentId = (int)$pdo->lastInsertId();

    $servicesNote = count($serviceNames) > 1 ? ('Seçilen Hizmetler: ' . implode(' + ', $serviceNames)) : '';
    $finalNote = $servicesNote !== '' ? ($note !== '' ? ($servicesNote . "\nNot: " . $note) : $servicesNote) : ($note !== '' ? $note : null);

    $stmt = $pdo->prepare('UPDATE randevu_ek_bilgileri SET bitis_zamani = ?, musteri_notu = ?, fiyat_snapshot = ? WHERE randevu_id = ?');
    $stmt->execute([$end, $finalNote, number_format($totalPrice, 2, '.', ''), $appointmentId]);
    $pdo->commit();

    // Otomatik E-Posta Bildirimi: Musteri ve Isletmeye Bildirim
    try {
        require_once __DIR__ . '/data/appointment_notification_service.php';
        notifyAppointmentCreated($appointmentId);
    } catch (Throwable $mailEx) {
        error_log('Appointment creation notification failed: ' . $mailEx->getMessage());
    }
    $_SESSION['mesaj'] = 'Randevu isteğiniz işletmeye iletildi.';
    $_SESSION['mesaj_turu'] = 'success';
    header('Location: /berber.php?id=' . $requestedBusinessId);
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if (!($e instanceof RuntimeException) && $e->getCode() === '45000') {
        $message = 'Bu saat çalışan için artık müsait değil. Lütfen başka bir saat seçin.';
    } else {
        error_log('Customer booking failed: ' . $e->getMessage());
        $message = ($e instanceof RuntimeException && !($e instanceof PDOException)) ? $e->getMessage() : 'Randevu oluşturulamadı. Lütfen başka bir saat seçin.';
    }
    $_SESSION['mesaj'] = $message;
    $_SESSION['mesaj_turu'] = 'warning';
    header('Location: /berber.php?id=' . $requestedBusinessId);
    exit;
}
