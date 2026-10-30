<?php
require_once dirname(__DIR__) . '/data/image_upload.php';
require_once dirname(__DIR__) . '/admin/includes/auth.php';
require_once __DIR__ . '/_booking.php';
require_once dirname(__DIR__) . '/data/owned_media.php';

$business = getAuthenticatedBarber();
if (!$business) {
    header('Location: /isletme/giris.php');
    exit;
}
$businessId = (int)$business['id'];

function businessEscape($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function businessFlash(string $type, string $message): void {
    $_SESSION['business_flash'] = ['type' => $type, 'message' => $message];
}

function businessRedirect(string $page): void {
    header('Location: /isletme/' . $page);
    exit;
}

function businessRequirePost(): void {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        http_response_code(405);
        exit('Bu işlem için POST isteği gereklidir.');
    }
    if (!verifyCsrfToken()) {
        http_response_code(403);
        exit('Güvenlik doğrulaması başarısız oldu. Sayfayı yenileyin.');
    }
}

function businessNotify(int $businessId, string $title, string $message, string $type = 'info', ?string $link = null): void {
    global $pdo;
    $stmt = $pdo->prepare('INSERT INTO isletme_bildirimleri (isletme_id, baslik, mesaj, tur, link) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$businessId, $title, $message, $type, $link]);
}

function businessUploadImage(array $file, int $businessId, string $folder = 'gallery'): string {
    if ($businessId < 1 || !in_array($folder, ['gallery', 'employees'], true)) throw new RuntimeException('Ge?ersiz y?kleme dizini.');
    return storeUploadImage($file, 2 * 1024 * 1024, 'uploads/isletme/' . $businessId . '/' . $folder);
}

function businessDeleteOwnedFile(string $relativePath, int $businessId): void {
    deleteOwnedMedia($relativePath, $businessId);
}

function businessValidDate(string $date): bool {
    $parsed = DateTime::createFromFormat('!Y-m-d', $date);
    return $parsed !== false && $parsed->format('Y-m-d') === $date;
}
