<?php
require_once dirname(__DIR__) . '/admin/includes/auth.php';
require_once __DIR__ . '/_booking.php';

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
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'] ?? '')) {
        throw new RuntimeException('Fotoğraf yüklenemedi.');
    }
    if (($file['size'] ?? 0) < 1 || $file['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('Fotoğraf en fazla 5 MB olabilir.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($extensions[$mime]) || @getimagesize($file['tmp_name']) === false) {
        throw new RuntimeException('Yalnızca geçerli JPG, PNG veya WEBP fotoğraf yükleyebilirsiniz.');
    }

    $directory = dirname(__DIR__) . '/uploads/isletme/' . $businessId . '/' . $folder;
    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        throw new RuntimeException('Fotoğraf klasörü oluşturulamadı.');
    }

    $filename = bin2hex(random_bytes(20)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $filename)) {
        throw new RuntimeException('Fotoğraf diske kaydedilemedi.');
    }

    return 'uploads/isletme/' . $businessId . '/' . $folder . '/' . $filename;
}

function businessDeleteOwnedFile(string $relativePath, int $businessId): void {
    $baseDirectory = realpath(dirname(__DIR__) . '/uploads/isletme/' . $businessId);
    $candidate = realpath(dirname(__DIR__) . '/' . ltrim($relativePath, '/\\'));
    if ($baseDirectory === false || $candidate === false || !is_file($candidate)) {
        return;
    }
    $prefix = $baseDirectory . DIRECTORY_SEPARATOR;
    if (strncmp($candidate, $prefix, strlen($prefix)) === 0) {
        if (!unlink($candidate)) {
            throw new RuntimeException('İşletmeye ait fotoğraf dosyası silinemedi.');
        }
    }
}

function businessValidDate(string $date): bool {
    $parsed = DateTime::createFromFormat('!Y-m-d', $date);
    return $parsed !== false && $parsed->format('Y-m-d') === $date;
}
