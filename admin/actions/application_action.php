<?php
   
                                                                               
   

require_once __DIR__ . '/../includes/auth.php';

$admin = requireAuth();
$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
          || (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($isAjax) {
        jsonResponse(['success' => false, 'message' => 'Geçersiz istek metodu.'], 405);
    }
    header('Location: /admin/pages/applications.php');
    exit;
}

                  
if (!verifyCsrfToken()) {
    if ($isAjax) {
        jsonResponse(['success' => false, 'message' => 'Güvenlik doğrulaması başarısız oldu (CSRF). Lütfen sayfayı yenileyin.'], 403);
    }
    $_SESSION['flash_error'] = 'Güvenlik doğrulaması başarısız oldu. Lütfen sayfayı yenileyip tekrar deneyin.';
    header('Location: /admin/pages/applications.php');
    exit;
}

$action = $_POST['action'] ?? '';
$businessId = (int)($_POST['business_id'] ?? $_POST['id'] ?? 0);

if (!$businessId) {
    if ($isAjax) {
        jsonResponse(['success' => false, 'message' => 'Geçersiz işletme seçimi.'], 400);
    }
    $_SESSION['flash_error'] = 'Geçersiz işletme seçimi.';
    header('Location: /admin/pages/applications.php');
    exit;
}

                  
$stmt = $pdo->prepare("SELECT * FROM isletmeler WHERE id = ?");
$stmt->execute([$businessId]);
$business = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$business) {
    if ($isAjax) {
        jsonResponse(['success' => false, 'message' => 'İşletme kaydı bulunamadı.'], 404);
    }
    $_SESSION['flash_error'] = 'İşletme kaydı bulunamadı.';
    header('Location: /admin/pages/applications.php');
    exit;
}

switch ($action) {
    case 'approve':
        if (!isCurrentUserAdmin() && !canCurrentUser('businesses.approve') && !canCurrentUser('applications.approve')) {
            if ($isAjax) jsonResponse(['success' => false, 'message' => 'Bu işlem için yetkiniz yok (businesses.approve).'], 403);
            $_SESSION['flash_error'] = 'Başvuru onaylama yetkiniz bulunmuyor.';
            header('Location: /admin/pages/applications.php');
            exit;
        }

        $stmt = $pdo->prepare("UPDATE isletmeler SET durum = 'active', onaylayan_admin_id = ?, onay_tarihi = NOW(), red_gerekce = NULL, red_tarihi = NULL WHERE id = ?");
        $stmt->execute([$admin['id'], $businessId]);

        logAdminAction('business_approve', 'isletmeler', $businessId, "İşletme onaylandı: {$business['isletme_adi']}");
        createNotification('İşletme Onaylandı', "{$business['isletme_adi']} başlıklı işletme başvurusu onaylandı ve yayına alındı.", 'success', "/admin/pages/businesses.php?id={$businessId}");

                                          
        $emailSubject = "Tebrikler! BiTıraş.com İşletme Başvurunuz Onaylandı";
        $emailBody = "Merhaba {$business['isletme_sahibi']},<br><br><strong>{$business['isletme_adi']}</strong> isimli işletmeniz için yaptığınız başvuru incelenmiş ve onaylanmıştır. Artık BiTıraş.com üzerinden randevu almaya başlayabilirsiniz.<br><br>Giriş yapmak için: <a href='http://localhost/admin/login.php'>Giriş Yap</a><br><br>İyi çalışmalar dileriz,<br>BiTıraş.com Ekibi";
        queueEmail($business['eposta'], $business['isletme_sahibi'], $emailSubject, $emailBody);

        $msg = "'{$business['isletme_adi']}' başvurusu başarıyla onaylandı ve aktifleştirildi.";
        if ($isAjax) jsonResponse(['success' => true, 'message' => $msg]);
        $_SESSION['flash_success'] = $msg;
        break;

    case 'reject':
        if (!isCurrentUserAdmin() && !canCurrentUser('businesses.reject') && !canCurrentUser('applications.reject')) {
            if ($isAjax) jsonResponse(['success' => false, 'message' => 'Bu işlem için yetkiniz yok (businesses.reject).'], 403);
            $_SESSION['flash_error'] = 'Başvuru reddetme yetkiniz bulunmuyor.';
            header('Location: /admin/pages/applications.php');
            exit;
        }

        $reason = trim($_POST['red_gerekce'] ?? '');
        if (empty($reason)) {
            $reason = 'Belirtilmedi.';
        }

        $stmt = $pdo->prepare("UPDATE isletmeler SET durum = 'rejected', red_gerekce = ?, red_tarihi = NOW() WHERE id = ?");
        $stmt->execute([$reason, $businessId]);

        logAdminAction('business_reject', 'isletmeler', $businessId, "İşletme reddedildi: {$business['isletme_adi']}. Gerekçe: {$reason}");
        createNotification('İşletme Başvurusu Reddedildi', "{$business['isletme_adi']} başvurusu reddedildi. Gerekçe: {$reason}", 'warning');

                                          
        $emailSubject = "BiTıraş.com İşletme Başvurusu Hakkında";
        $emailBody = "Merhaba {$business['isletme_sahibi']},<br><br><strong>{$business['isletme_adi']}</strong> isimli işletmeniz için yaptığınız başvuru değerlendirilmiş ancak şu gerekçeyle onaylanamamıştır:<br><br><em>{$reason}</em><br><br>Gerekli düzenlemeleri yaparak tekrar başvuruda bulunabilirsiniz.<br><br>BiTıraş.com Ekibi";
        queueEmail($business['eposta'], $business['isletme_sahibi'], $emailSubject, $emailBody);

        $msg = "'{$business['isletme_adi']}' başvurusu reddedildi.";
        if ($isAjax) jsonResponse(['success' => true, 'message' => $msg]);
        $_SESSION['flash_success'] = $msg;
        break;

    case 'suspend':
        if (!isCurrentUserAdmin() && !canCurrentUser('businesses.suspend')) {
            if ($isAjax) jsonResponse(['success' => false, 'message' => 'Bu işlem için yetkiniz yok.'], 403);
            $_SESSION['flash_error'] = 'İşletme askıya alma yetkiniz bulunmuyor.';
            header('Location: /admin/pages/applications.php');
            exit;
        }

        $stmt = $pdo->prepare("UPDATE isletmeler SET durum = 'suspended' WHERE id = ?");
        $stmt->execute([$businessId]);

        logAdminAction('business_suspend', 'isletmeler', $businessId, "İşletme askıya alındı: {$business['isletme_adi']}");

        $msg = "'{$business['isletme_adi']}' işletmesi askıya alındı.";
        if ($isAjax) jsonResponse(['success' => true, 'message' => $msg]);
        $_SESSION['flash_success'] = $msg;
        break;

    default:
        if ($isAjax) jsonResponse(['success' => false, 'message' => 'Geçersiz işlem.'], 400);
        $_SESSION['flash_error'] = 'Geçersiz işlem parametresi.';
}

header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/admin/pages/applications.php'));
exit;
