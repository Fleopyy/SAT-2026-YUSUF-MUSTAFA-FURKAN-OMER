<?php
require_once __DIR__ . '/_common.php';
require_once dirname(__DIR__) . '/data/maps_config.php';

header('Content-Type: application/json; charset=UTF-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Yalnızca POST istekleri kabul edilir.']);
    return;
}

if (!verifyCsrfToken()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Güvenlik doğrulaması (CSRF) başarısız oldu. Lütfen sayfayı yenileyin.']);
    return;
}

$rawLat = $_POST['latitude'] ?? null;
$rawLng = $_POST['longitude'] ?? null;
$placeId = is_string($_POST['place_id'] ?? null) ? trim($_POST['place_id']) : null;
if ($placeId !== null) {
    $placeId = mb_substr($placeId, 0, 255, 'UTF-8');
}

if ($rawLat === null || $rawLng === null || $rawLat === '' || $rawLng === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Lütfen harita üzerinde geçerli bir konum belirleyin.']);
    return;
}

if (!is_numeric($rawLat) || !is_numeric($rawLng)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Koordinat formatı sayısal olmalıdır.']);
    return;
}

$lat = (float)$rawLat;
$lng = (float)$rawLng;

if (!isValidCoordinate($lat, $lng)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Geçersiz koordinat değerleri. Enlem -90 ile 90, boylam -180 ile 180 arasında olmalıdır.']);
    return;
}

try {
    $now = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare('UPDATE isletmeler SET latitude = ?, longitude = ?, place_id = ?, location_verified_at = ? WHERE id = ?');
    $stmt->execute([
        number_format($lat, 8, '.', ''),
        number_format($lng, 8, '.', ''),
        $placeId ?: null,
        $now,
        $businessId
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'İşletme konumunuz başarıyla kaydedildi.',
        'latitude' => $lat,
        'longitude' => $lng,
        'location_verified_at' => date('d.m.Y H:i', strtotime($now)),
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Konum kaydedilirken bir veritabanı hatası oluştu.']);
}
