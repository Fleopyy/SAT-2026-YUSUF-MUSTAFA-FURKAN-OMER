<?php
   
                                               
   

require_once __DIR__ . '/../includes/auth.php';

$admin = requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/pages/appointments.php');
    exit;
}

if (!verifyCsrfToken()) {
    $_SESSION['flash_error'] = 'Güvenlik doğrulaması başarısız oldu (CSRF).';
    header('Location: /admin/pages/appointments.php');
    exit;
}

$appointmentId = (int)($_POST['appointment_id'] ?? $_POST['id'] ?? 0);
$newStatus = $_POST['status'] ?? '';

if (!$appointmentId || !in_array($newStatus, ['pending', 'confirmed', 'completed', 'cancelled', 'rejected'])) {
    $_SESSION['flash_error'] = 'Geçersiz parametreler.';
    header('Location: /admin/pages/appointments.php');
    exit;
}

                           
if ($newStatus === 'cancelled') {
    if (!isCurrentUserAdmin() && !canCurrentUser('appointments.cancel') && !canCurrentUser('appointments.edit')) {
        $_SESSION['flash_error'] = 'Randevu iptal etme yetkiniz bulunmuyor (appointments.cancel).';
        header('Location: /admin/pages/appointments.php');
        exit;
    }
} else {
    if (!isCurrentUserAdmin() && !canCurrentUser('appointments.edit')) {
        $_SESSION['flash_error'] = 'Randevu düzenleme/onaylama yetkiniz bulunmuyor (appointments.edit).';
        header('Location: /admin/pages/appointments.php');
        exit;
    }
}

                
$stmt = $pdo->prepare("SELECT r.*, k.ad_soyad AS musteri_adi, i.isletme_adi 
                       FROM randevular r 
                       LEFT JOIN kullanicilar k ON r.kullanici_id = k.id 
                       LEFT JOIN isletmeler i ON r.isletme_id = i.id 
                       WHERE r.id = ?");
$stmt->execute([$appointmentId]);
$apt = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$apt) {
    $_SESSION['flash_error'] = 'Randevu kaydı bulunamadı.';
    header('Location: /admin/pages/appointments.php');
    exit;
}

                                               
if (!canAccessBusiness((int)$apt['isletme_id'])) {
    http_response_code(403);
    logAdminAction('idor_attempt_blocked', 'randevular', $appointmentId, "Yetkisiz randevu durum değiştirme denemesi (Kapsam dışı işletme)");
    $_SESSION['flash_error'] = 'Bu randevunun ait olduğu işletme üzerinde işlem yetkiniz bulunmamaktadır.';
    header('Location: /admin/pages/appointments.php');
    exit;
}

$stmt = $pdo->prepare("UPDATE randevular SET durum = ? WHERE id = ?");
$stmt->execute([$newStatus, $appointmentId]);

logAdminAction('appointment_status_change', 'randevular', $appointmentId, "Randevu durumu '{$newStatus}' olarak güncellendi. (Müşteri: {$apt['musteri_adi']}, İşletme: {$apt['isletme_adi']})");

$statusLabels = [
    'pending' => 'Beklemede',
    'confirmed' => 'Onaylandı',
    'completed' => 'Tamamlandı',
    'cancelled' => 'İptal Edildi',
    'rejected' => 'Reddedildi'
];

$_SESSION['flash_success'] = "Randevu durumu başarıyla '{$statusLabels[$newStatus]}' olarak güncellendi.";
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/admin/pages/appointments.php'));
exit;
