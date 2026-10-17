<?php
   
                                                                                                         
   

require_once __DIR__ . '/../includes/auth.php';

$admin = requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/pages/businesses.php');
    exit;
}

if (!verifyCsrfToken()) {
    $_SESSION['flash_error'] = 'Güvenlik doğrulaması başarısız oldu (CSRF). Lütfen tekrar deneyin.';
    header('Location: /admin/pages/businesses.php');
    exit;
}

$action = $_POST['action'] ?? '';
$businessId = (int)($_POST['business_id'] ?? $_POST['id'] ?? 0);

if (!$businessId) {
    $_SESSION['flash_error'] = 'Geçersiz işletme ID.';
    header('Location: /admin/pages/businesses.php');
    exit;
}

                       
$stmt = $pdo->prepare("SELECT * FROM isletmeler WHERE id = ?");
$stmt->execute([$businessId]);
$business = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$business) {
    $_SESSION['flash_error'] = 'İşletme bulunamadı.';
    header('Location: /admin/pages/businesses.php');
    exit;
}

                                               
if (!canAccessBusiness($businessId)) {
    http_response_code(403);
    logAdminAction('idor_attempt_blocked', 'isletmeler', $businessId, "Yetkisiz işletme erişim/düzenleme denemesi engellendi.");
    $_SESSION['flash_error'] = 'Bu işletme üzerinde işlem yapma yetkiniz bulunmamaktadır.';
    header('Location: /admin/pages/businesses.php');
    exit;
}

switch ($action) {
    case 'edit':
        if (!isCurrentUserAdmin() && !canCurrentUser('businesses.edit')) {
            $_SESSION['flash_error'] = 'İşletme düzenleme yetkiniz bulunmuyor.';
            header('Location: /admin/pages/businesses.php?id=' . $businessId);
            exit;
        }

        $isletmeAdi = trim($_POST['isletme_adi'] ?? '');
        $isletmeSahibi = trim($_POST['isletme_sahibi'] ?? '');
        $telefon = trim($_POST['telefon'] ?? '');
        $eposta = trim($_POST['eposta'] ?? '');
        $sehir = trim($_POST['sehir'] ?? '');
        $ilce = trim($_POST['ilce'] ?? '');
        $tamAdres = trim($_POST['tam_adres'] ?? '');
        $aciklama = trim($_POST['aciklama'] ?? '');
        $durum = $_POST['durum'] ?? 'active';

        if (empty($isletmeAdi) || empty($isletmeSahibi) || empty($telefon) || empty($eposta)) {
            $_SESSION['flash_error'] = 'Lütfen tüm zorunlu alanları (İşletme adı, yetkili, telefon, e-posta) doldurun.';
            header('Location: /admin/pages/businesses.php?edit=' . $businessId);
            exit;
        }

                                                                      
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM isletmeler WHERE (telefon = ? OR eposta = ?) AND id != ?");
        $stmt->execute([$telefon, $eposta, $businessId]);
        if ((int)$stmt->fetchColumn() > 0) {
            $_SESSION['flash_error'] = 'Bu telefon numarası veya e-posta adresi başka bir işletme tarafından kullanılıyor.';
            header('Location: /admin/pages/businesses.php?edit=' . $businessId);
            exit;
        }

        if (!in_array($durum, ['pending', 'active', 'suspended', 'rejected'])) {
            $durum = $business['durum'];
        }

        $stmt = $pdo->prepare("UPDATE isletmeler SET 
            isletme_adi = ?, isletme_sahibi = ?, telefon = ?, eposta = ?, 
            sehir = ?, ilce = ?, tam_adres = ?, aciklama = ?, durum = ? 
            WHERE id = ?");
        $stmt->execute([$isletmeAdi, $isletmeSahibi, $telefon, $eposta, $sehir, $ilce, $tamAdres, $aciklama, $durum, $businessId]);

        logAdminAction('business_update', 'isletmeler', $businessId, "İşletme bilgileri güncellendi: {$isletmeAdi}");
        $_SESSION['flash_success'] = "'{$isletmeAdi}' işletme bilgileri başarıyla güncellendi.";
        header('Location: /admin/pages/businesses.php?id=' . $businessId);
        exit;

    case 'toggle_status':
        if (!isCurrentUserAdmin() && !canCurrentUser('businesses.suspend')) {
            $_SESSION['flash_error'] = 'Durum değiştirme yetkiniz bulunmuyor.';
            header('Location: /admin/pages/businesses.php');
            exit;
        }

        $newStatus = ($business['durum'] === 'active') ? 'suspended' : 'active';
        $stmt = $pdo->prepare("UPDATE isletmeler SET durum = ? WHERE id = ?");
        $stmt->execute([$newStatus, $businessId]);

        $statusText = $newStatus === 'active' ? 'aktifleştirildi' : 'askıya alındı';
        logAdminAction('business_status_change', 'isletmeler', $businessId, "İşletme durumu değiştirildi: {$statusText}");
        $_SESSION['flash_success'] = "'{$business['isletme_adi']}' işletmesi {$statusText}.";
        break;

    case 'reset_password':
        if (!isCurrentUserAdmin() && !canCurrentUser('businesses.edit')) {
            $_SESSION['flash_error'] = 'Şifre sıfırlama yetkiniz bulunmuyor.';
            header('Location: /admin/pages/businesses.php');
            exit;
        }

        $newPassword = $_POST['new_password'] ?? '';
        if (empty($newPassword) || strlen($newPassword) < 6) {
            $_SESSION['flash_error'] = 'Yeni şifre en az 6 karakter olmalıdır.';
            header('Location: /admin/pages/businesses.php?id=' . $businessId);
            exit;
        }

        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE isletmeler SET sifre_hash = ? WHERE id = ?");
        $stmt->execute([$hash, $businessId]);

        logAdminAction('business_password_reset', 'isletmeler', $businessId, "İşletme şifresi admin tarafından sıfırlandı: {$business['isletme_adi']}");
        $_SESSION['flash_success'] = "'{$business['isletme_adi']}' işletmesinin şifresi başarıyla güncellendi.";
        break;

    default:
        $_SESSION['flash_error'] = 'Bilinmeyen işlem.';
}

header('Location: /admin/pages/businesses.php');
exit;
