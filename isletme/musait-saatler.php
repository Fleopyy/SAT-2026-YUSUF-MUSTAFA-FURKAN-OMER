<?php
require_once __DIR__ . '/../data/database.php';
require_once __DIR__ . '/_booking.php';

header('Content-Type: application/json; charset=utf-8');

$businessId = filter_var($_GET['business_id'] ?? null, FILTER_VALIDATE_INT);
$employeeId = filter_var($_GET['employee_id'] ?? null, FILTER_VALIDATE_INT);
$serviceId = filter_var($_GET['service_id'] ?? null, FILTER_VALIDATE_INT);
$date = is_string($_GET['date'] ?? null) ? $_GET['date'] : '';

if (!$businessId || $employeeId === false || $employeeId === null || $employeeId < 0 || !$serviceId) {
    http_response_code(422);
    echo json_encode(['error' => 'Randevu bilgileri geçersiz.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id FROM isletmeler WHERE id = ? AND durum = 'active'");
    $stmt->execute([$businessId]);
    if (!$stmt->fetchColumn()) {
        http_response_code(404);
        echo json_encode(['error' => 'İşletme bulunamadı.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $pdo->prepare('SELECT id FROM hizmetler WHERE id = ? AND isletme_id = ? AND aktif_mi = 1');
    $stmt->execute([$serviceId, $businessId]);
    if (!$stmt->fetchColumn()) {
        http_response_code(422);
        echo json_encode(['error' => 'Hizmet seçimi geçersiz.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($employeeId > 0) {
        $stmt = $pdo->prepare('SELECT id FROM calisanlar WHERE id = ? AND isletme_id = ? AND aktif_mi = 1');
        $stmt->execute([$employeeId, $businessId]);
        if (!$stmt->fetchColumn()) {
            http_response_code(422);
            echo json_encode(['error' => 'Personel seçimi geçersiz.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    $slots = businessAvailableSlots($pdo, $businessId, $employeeId, $serviceId, $date);
    echo json_encode(['slots' => $slots], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('Public appointment availability failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Saatler yüklenemedi. Lütfen tekrar deneyin.'], JSON_UNESCAPED_UNICODE);
}
