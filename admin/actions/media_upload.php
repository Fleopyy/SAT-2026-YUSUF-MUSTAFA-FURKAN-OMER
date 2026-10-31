<?php
require_once __DIR__ . '/../includes/auth.php';
require_once dirname(__DIR__, 2) . '/data/image_upload.php';
require_once dirname(__DIR__, 2) . '/data/owned_media.php';

$admin = requirePermission('business_photos.upload');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/pages/media.php');
    exit;
}

if (!verifyCsrfToken()) {
    $_SESSION['flash_error'] = 'Güvenlik doğrulaması başarısız oldu (CSRF).';
    header('Location: /admin/pages/media.php');
    exit;
}

$businessId = (int)($_POST['business_id'] ?? 0);
if ($businessId < 1) {
    $_SESSION['flash_error'] = 'Geçersiz işletme ID.';
    header('Location: /admin/pages/media.php');
    exit;
}

if (!canAccessBusiness($businessId)) {
    http_response_code(403);
    exit('Forbidden');
}

if (!isset($_FILES['photos']) || !is_array($_FILES['photos']['error'] ?? null)
    || !is_array($_FILES['photos']['tmp_name'] ?? null) || !is_array($_FILES['photos']['name'] ?? null)) {
    $_SESSION['flash_error'] = 'Yüklemek için fotoğraf seçilmedi.';
    header('Location: /admin/pages/media.php');
    exit;
}

$uploadedCount = 0;
$errors = [];

foreach ($_FILES['photos']['error'] as $idx => $error) {
    if ($error !== UPLOAD_ERR_OK) {
        $errors[] = "Fotoğraf #{$idx} yüklenirken bir hata oluştu (kod {$error}).";
        continue;
    }
    $relativePath = null;
    try {
        $relativePath = storeUploadImage([
            'error' => $error,
            'tmp_name' => $_FILES['photos']['tmp_name'][$idx] ?? null,
            'name' => $_FILES['photos']['name'][$idx] ?? null,
        ], 2 * 1024 * 1024, 'uploads/isletme/' . $businessId . '/gallery');
        $stmt = $pdo->prepare("INSERT INTO isletme_fotograflari (isletme_id, fotograf_yolu, olusturulma_tarihi) VALUES (?, ?, NOW())");
        $stmt->execute([$businessId, $relativePath]);
        $uploadedCount++;
    } catch (Throwable $e) {
        $errors[] = "Fotograf #{$idx} yuklenemedi veya kaydedilemedi.";
        if ($relativePath !== null) {
            try { deleteOwnedMedia($relativePath, $businessId); }
            catch (Throwable $cleanupError) { error_log('Media upload cleanup failed for business ' . $businessId); }
        }
    }
}

if ($uploadedCount > 0) {
    $_SESSION['flash_success'] = "{$uploadedCount} fotoğraf başarıyla yüklendi.";
}
if (!empty($errors)) {
    $_SESSION['flash_error'] = implode(' ', $errors);
}

logAdminAction('media_upload', 'isletme_fotograflari', $businessId, "İşletme ID {$businessId} için {$uploadedCount} fotoğraf yüklendi.");

header('Location: /admin/pages/media.php');
exit;
?>
