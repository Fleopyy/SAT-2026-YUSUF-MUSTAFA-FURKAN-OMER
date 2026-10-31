<?php
require_once dirname(__DIR__) . '/data/image_upload.php';
require_once dirname(__DIR__) . '/admin/includes/auth.php';
require_once dirname(__DIR__) . '/data/customer_auth_service.php';

$customerId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT);
if (($_SESSION['user_role'] ?? '') !== 'customer' || !$customerId) {
    $redirectTarget = $_SERVER['REQUEST_URI'] ?? '/musteri/hesabim.php';
    header('Location: /login.php?redirect=' . urlencode($redirectTarget) . '&role=customer');
    exit;
}

$customerStatement = $pdo->prepare('SELECT id, ad_soyad, telefon, eposta, sehir, eposta_dogrulandi FROM kullanicilar WHERE id = ?');
$customerStatement->execute([$customerId]);
$customer = $customerStatement->fetch(PDO::FETCH_ASSOC);
if (!$customer) {
    unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_role']);
    header('Location: /login.php?role=customer');
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
    if ($customerId < 1) throw new RuntimeException('Geçersiz kullanıcı.');
    return storeUploadImage($file, 5 * 1024 * 1024, 'uploads/musteri/' . $customerId);
}

$customerProfileStatement = $pdo->prepare('SELECT fotograf_yolu FROM kullanici_portal_profilleri WHERE kullanici_id = ?');
$customerProfileStatement->execute([$customerId]);
$customerPhotoPath = $customerProfileStatement->fetchColumn() ?: null;
$customerFlash = $_SESSION['customer_flash'] ?? null;
unset($_SESSION['customer_flash']);
