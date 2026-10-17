<?php
require_once dirname(__DIR__) . '/admin/includes/auth.php';

$customerId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT);
if (($_SESSION['user_role'] ?? '') !== 'customer' || !$customerId) {
    header('Location: /');
    exit;
}

$customerStatement = $pdo->prepare('SELECT id, ad_soyad, telefon, eposta FROM kullanicilar WHERE id = ?');
$customerStatement->execute([$customerId]);
$customer = $customerStatement->fetch(PDO::FETCH_ASSOC);
if (!$customer) {
    unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_role']);
    header('Location: /');
    exit;
}

function customerEscape($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function customerRequirePost(): void {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        http_response_code(405);
        exit('Bu işlem için POST isteği gereklidir.');
    }
    if (!verifyCsrfToken()) {
        http_response_code(403);
        exit('Güvenlik doğrulaması başarısız oldu. Sayfayı yenileyin.');
    }
}

function customerFlash(string $type, string $message): void {
    $_SESSION['customer_flash'] = ['type' => $type, 'message' => $message];
}

function customerCreateAppointmentReview(PDO $pdo, int $customerId, int $appointmentId, int $rating, string $comment): void {
    $comment = trim($comment);
    if ($appointmentId < 1 || $rating < 1 || $rating > 5 || $comment === '' || mb_strlen($comment, 'UTF-8') > 2000) {
        throw new RuntimeException('Randevu için 1-5 puan ve 1-2000 karakter arasında yorum girin.');
    }
    $stmt = $pdo->prepare("INSERT INTO degerlendirmeler (randevu_id, degerlendiren_turu, degerlendiren_id, puan, yorum)
                           SELECT r.id, 'customer', ?, ?, ?
                           FROM randevular r
                           WHERE r.id = ? AND r.kullanici_id = ? AND r.durum = 'completed'");
    $stmt->execute([$customerId, $rating, $comment, $appointmentId, $customerId]);
    if ($stmt->rowCount() !== 1) {
        throw new RuntimeException('Yalnızca tamamlanmış kendi randevularını değerlendirebilirsin.');
    }
}

function customerUploadPhoto(array $file, int $customerId): string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'] ?? '')) {
        throw new RuntimeException('Profil fotoğrafı yüklenemedi.');
    }
    if (($file['size'] ?? 0) < 1 || $file['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('Profil fotoğrafı en fazla 5 MB olabilir.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($extensions[$mime]) || @getimagesize($file['tmp_name']) === false) {
        throw new RuntimeException('Yalnızca geçerli JPG, PNG veya WEBP fotoğraf yükleyebilirsiniz.');
    }
    $directory = dirname(__DIR__) . '/uploads/musteri/' . $customerId;
    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        throw new RuntimeException('Profil fotoğrafı klasörü oluşturulamadı.');
    }
    $path = $directory . '/' . bin2hex(random_bytes(20)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($file['tmp_name'], $path)) {
        throw new RuntimeException('Profil fotoğrafı diske kaydedilemedi.');
    }
    return 'uploads/musteri/' . $customerId . '/' . basename($path);
}

$customerProfileStatement = $pdo->prepare('SELECT fotograf_yolu FROM kullanici_portal_profilleri WHERE kullanici_id = ?');
$customerProfileStatement->execute([$customerId]);
$customerPhotoPath = $customerProfileStatement->fetchColumn() ?: null;
$customerFlash = $_SESSION['customer_flash'] ?? null;
unset($_SESSION['customer_flash']);
