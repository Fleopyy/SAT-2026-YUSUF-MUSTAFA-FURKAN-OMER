<?php

require_once __DIR__ . '/../includes/auth.php';

require_once dirname(__DIR__, 2) . '/data/owned_media.php';

$admin = requirePermission('business_photos.delete');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/pages/media.php');
    exit;
}

if (!verifyCsrfToken()) {
    $_SESSION['flash_error'] = 'Güvenlik doğrulaması başarısız oldu (CSRF).';
    header('Location: /admin/pages/media.php');
    exit;
}

$action  = $_POST['action'] ?? '';
$photoId = (int)($_POST['photo_id'] ?? 0);

if ($action === 'delete' && $photoId > 0) {
    $stmt = $pdo->prepare("SELECT f.*, i.isletme_adi
                           FROM isletme_fotograflari f
                           LEFT JOIN isletmeler i ON f.isletme_id = i.id
                           WHERE f.id = ?");
    $stmt->execute([$photoId]);
    $photo = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($photo && !currentBusinessScopeAllows((int)$photo['isletme_id'])) {
        http_response_code(403);
        exit('Forbidden');
    }
    if ($photo) {
        try {
            deleteOwnedMedia($photo['fotograf_yolu'], (int)$photo['isletme_id'], $pdo, function () use ($pdo, $photoId, $photo) {

            $pdo->prepare("UPDATE isletme_panel_medya
                           SET profil_fotograf_id = CASE WHEN profil_fotograf_id = ? THEN NULL ELSE profil_fotograf_id END,
                               kapak_fotograf_id  = CASE WHEN kapak_fotograf_id  = ? THEN NULL ELSE kapak_fotograf_id  END
                           WHERE isletme_id = ?")
                ->execute([$photoId, $photoId, $photo['isletme_id']]);

            $pdo->prepare("DELETE FROM isletme_fotograf_siralama WHERE fotograf_id = ?")
                ->execute([$photoId]);

            $delete = $pdo->prepare("DELETE FROM isletme_fotograflari WHERE id = ? AND isletme_id = ? AND fotograf_yolu = ?");
            $delete->execute([$photoId, $photo['isletme_id'], $photo['fotograf_yolu']]);
            if ($delete->rowCount() !== 1) throw new RuntimeException('Fotoğraf kaydı değişti.');

            });

            logAdminAction(
                'media_delete',
                'isletme_fotograflari',
                $photoId,
                "İşletme fotoğrafı silindi: {$photo['isletme_adi']} ({$photo['fotograf_yolu']})"
            );
            $_SESSION['flash_success'] = "Fotoğraf başarıyla silindi.";

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Admin media delete failed: ' . $e->getMessage());
            $_SESSION['flash_error'] = "Fotoğraf silinirken bir hata oluştu.";
        }
    } else {
        $_SESSION['flash_error'] = "Fotoğraf kaydı bulunamadı.";
    }
}

header('Location: /admin/pages/media.php');
exit;
