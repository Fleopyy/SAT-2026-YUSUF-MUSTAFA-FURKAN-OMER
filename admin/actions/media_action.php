<?php
   
                                                             
   

require_once __DIR__ . '/../includes/auth.php';

$admin = requirePermission('media.manage');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/pages/media.php');
    exit;
}

if (!verifyCsrfToken()) {
    $_SESSION['flash_error'] = 'Güvenlik doğrulaması başarısız oldu (CSRF).';
    header('Location: /admin/pages/media.php');
    exit;
}

$action = $_POST['action'] ?? '';
$photoId = (int)($_POST['photo_id'] ?? 0);

if ($action === 'delete' && $photoId > 0) {
    $stmt = $pdo->prepare("SELECT f.*, i.isletme_adi FROM isletme_fotograflari f LEFT JOIN isletmeler i ON f.isletme_id = i.id WHERE f.id = ?");
    $stmt->execute([$photoId]);
    $photo = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($photo) {
                                              
        $filePath = dirname(__DIR__, 2) . '/' . ltrim($photo['fotograf_yolu'], '/');
        if (file_exists($filePath) && is_file($filePath)) {
            @unlink($filePath);
        }

        $pdo->prepare("DELETE FROM isletme_fotograflari WHERE id = ?")->execute([$photoId]);
        logAdminAction('media_delete', 'isletme_fotograflari', $photoId, "İşletme fotoğrafı silindi: {$photo['isletme_adi']} ({$photo['fotograf_yolu']})");
        $_SESSION['flash_success'] = "Fotoğraf başarıyla silindi.";
    } else {
        $_SESSION['flash_error'] = "Fotoğraf kaydı bulunamadı.";
    }
}

header('Location: /admin/pages/media.php');
exit;
