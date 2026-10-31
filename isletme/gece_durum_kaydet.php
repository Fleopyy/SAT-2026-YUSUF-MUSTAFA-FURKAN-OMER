<?php
require_once __DIR__ . '/_common.php';

header('Content-Type: application/json; charset=UTF-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Yalnızca POST istekleri kabul edilir.']);
    exit;
}

if (!verifyCsrfToken()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Güvenlik doğrulaması (CSRF) başarısız oldu. Lütfen sayfayı yenileyin.']);
    exit;
}

$geceAcik = (isset($_POST['gece_acik']) && ((string)$_POST['gece_acik'] === '1' || $_POST['gece_acik'] === true || $_POST['gece_acik'] === 'true')) ? 1 : 0;

try {
    $stmt = $pdo->prepare('UPDATE isletmeler SET gece_acik = ? WHERE id = ?');
    $stmt->execute([$geceAcik, $businessId]);

    echo json_encode([
        'success' => true,
        'gece_acik' => $geceAcik,
        'message' => $geceAcik ? 'İşletmeniz Gece Açık olarak ayarlandı.' : 'Gece Açık özelliği kapatıldı.'
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Veritabanı güncellenirken hata oluştu: ' . $e->getMessage()]);
}

