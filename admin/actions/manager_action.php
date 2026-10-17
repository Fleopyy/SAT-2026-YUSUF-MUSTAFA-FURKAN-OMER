<?php
   
                                                             
                                                            
   

require_once __DIR__ . '/../includes/auth.php';

                                                            
$admin = requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/pages/managers.php');
    exit;
}

if (!verifyCsrfToken()) {
    $_SESSION['flash_error'] = 'Güvenlik doğrulaması başarısız oldu (CSRF).';
    header('Location: /admin/pages/managers.php');
    exit;
}

$action = $_POST['action'] ?? '';
$managerId = (int)($_POST['manager_id'] ?? 0);
$allPermKeys = array_keys(getAllPermissions());

switch ($action) {
    case 'create':
        $name = trim($_POST['ad_soyad'] ?? '');
        $email = trim($_POST['eposta'] ?? '');
        $password = $_POST['sifre'] ?? '';
        $role = $_POST['rol'] ?? 'manager';
        $kapsamTuru = ($_POST['kapsam_turu'] ?? 'tum_isletmeler') === 'belirli_isletmeler' ? 'belirli_isletmeler' : 'tum_isletmeler';
        $permissions = $_POST['permissions'] ?? [];
        $scopedBusinessIds = $_POST['scoped_businesses'] ?? [];

        if (empty($name) || empty($email) || empty($password)) {
            $_SESSION['flash_error'] = 'Lütfen Ad Soyad, E-posta ve Şifre alanlarını eksiksiz doldurun.';
            header('Location: /admin/pages/managers.php?action=create');
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_error'] = 'Geçerli bir e-posta adresi girin.';
            header('Location: /admin/pages/managers.php?action=create');
            exit;
        }

        if (strlen($password) < 8) {
            $_SESSION['flash_error'] = 'Şifre en az 8 karakter uzunluğunda olmalıdır.';
            header('Location: /admin/pages/managers.php?action=create');
            exit;
        }

                                           
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM yoneticiler WHERE eposta = ?");
        $stmt->execute([$email]);
        if ((int)$stmt->fetchColumn() > 0) {
            $_SESSION['flash_error'] = 'Bu e-posta adresiyle kayıtlı bir yönetici hesabı zaten mevcut.';
            header('Location: /admin/pages/managers.php?action=create');
            exit;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $rolVal = ($role === 'admin') ? 'admin' : 'manager';

        $stmt = $pdo->prepare("INSERT INTO yoneticiler (ad_soyad, eposta, sifre_hash, rol, kapsam_turu, yetki_versiyonu, aktif, olusturan_admin_id) VALUES (?, ?, ?, ?, ?, 1, 1, ?)");
        $stmt->execute([$name, $email, $hash, $rolVal, $kapsamTuru, $admin['id']]);
        $newManagerId = (int)$pdo->lastInsertId();

                                                 
        if ($rolVal === 'manager') {
            if (!empty($permissions) && is_array($permissions)) {
                $stmtPerm = $pdo->prepare("INSERT INTO yonetici_izinleri (yonetici_id, izin_anahtari) VALUES (?, ?)");
                foreach ($permissions as $p) {
                    if (in_array($p, $allPermKeys)) {
                        $stmtPerm->execute([$newManagerId, $p]);
                    }
                }
            }

                                                                
            if ($kapsamTuru === 'belirli_isletmeler' && !empty($scopedBusinessIds) && is_array($scopedBusinessIds)) {
                $stmtScope = $pdo->prepare("INSERT INTO yonetici_isletme_kapsami (yonetici_id, isletme_id) VALUES (?, ?)");
                foreach ($scopedBusinessIds as $bId) {
                    $bId = (int)$bId;
                    if ($bId > 0) {
                        $stmtScope->execute([$newManagerId, $bId]);
                    }
                }
            }
        }

        logAdminAction('manager_create', 'yoneticiler', $newManagerId, "Yeni yönetici oluşturuldu: {$name} ({$rolVal})");
        $_SESSION['flash_success'] = "'{$name}' adlı yeni yönetici hesabı başarıyla oluşturuldu.";
        break;

    case 'edit':
        if (!$managerId) {
            $_SESSION['flash_error'] = 'Geçersiz yönetici ID.';
            header('Location: /admin/pages/managers.php');
            exit;
        }

        $name = trim($_POST['ad_soyad'] ?? '');
        $email = trim($_POST['eposta'] ?? '');
        $password = $_POST['sifre'] ?? '';
        $role = $_POST['rol'] ?? 'manager';
        $active = isset($_POST['aktif']) ? 1 : 0;
        $kapsamTuru = ($_POST['kapsam_turu'] ?? 'tum_isletmeler') === 'belirli_isletmeler' ? 'belirli_isletmeler' : 'tum_isletmeler';
        $permissions = $_POST['permissions'] ?? [];
        $scopedBusinessIds = $_POST['scoped_businesses'] ?? [];

        if (empty($name) || empty($email)) {
            $_SESSION['flash_error'] = 'Ad Soyad ve E-posta alanları boş bırakılamaz.';
            header('Location: /admin/pages/managers.php?edit=' . $managerId);
            exit;
        }

                                           
        if ($managerId === (int)$admin['id'] && $active === 0) {
            $_SESSION['flash_error'] = 'Kendi yönetici hesabınızı devre dışı bırakamazsınız.';
            header('Location: /admin/pages/managers.php?edit=' . $managerId);
            exit;
        }

                                 
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM yoneticiler WHERE eposta = ? AND id != ?");
        $stmt->execute([$email, $managerId]);
        if ((int)$stmt->fetchColumn() > 0) {
            $_SESSION['flash_error'] = 'Bu e-posta adresi başka bir yönetici tarafından kullanılıyor.';
            header('Location: /admin/pages/managers.php?edit=' . $managerId);
            exit;
        }

        $rolVal = ($role === 'admin') ? 'admin' : 'manager';

                                    
        if (!empty($password)) {
            if (strlen($password) < 8) {
                $_SESSION['flash_error'] = 'Şifre en az 8 karakter olmalıdır.';
                header('Location: /admin/pages/managers.php?edit=' . $managerId);
                exit;
            }
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE yoneticiler SET ad_soyad = ?, eposta = ?, sifre_hash = ?, rol = ?, kapsam_turu = ?, yetki_versiyonu = yetki_versiyonu + 1, aktif = ? WHERE id = ?");
            $stmt->execute([$name, $email, $hash, $rolVal, $kapsamTuru, $active, $managerId]);
        } else {
            $stmt = $pdo->prepare("UPDATE yoneticiler SET ad_soyad = ?, eposta = ?, rol = ?, kapsam_turu = ?, yetki_versiyonu = yetki_versiyonu + 1, aktif = ? WHERE id = ?");
            $stmt->execute([$name, $email, $rolVal, $kapsamTuru, $active, $managerId]);
        }

                              
        $pdo->prepare("DELETE FROM yonetici_izinleri WHERE yonetici_id = ?")->execute([$managerId]);
        if ($rolVal === 'manager' && !empty($permissions) && is_array($permissions)) {
            $stmtPerm = $pdo->prepare("INSERT INTO yonetici_izinleri (yonetici_id, izin_anahtari) VALUES (?, ?)");
            foreach ($permissions as $p) {
                if (in_array($p, $allPermKeys)) {
                    $stmtPerm->execute([$managerId, $p]);
                }
            }
        }

                             
        $pdo->prepare("DELETE FROM yonetici_isletme_kapsami WHERE yonetici_id = ?")->execute([$managerId]);
        if ($rolVal === 'manager' && $kapsamTuru === 'belirli_isletmeler' && !empty($scopedBusinessIds) && is_array($scopedBusinessIds)) {
            $stmtScope = $pdo->prepare("INSERT INTO yonetici_isletme_kapsami (yonetici_id, isletme_id) VALUES (?, ?)");
            foreach ($scopedBusinessIds as $bId) {
                $bId = (int)$bId;
                if ($bId > 0) {
                    $stmtScope->execute([$managerId, $bId]);
                }
            }
        }

        logAdminAction('manager_update', 'yoneticiler', $managerId, "Yönetici yetkileri ve kapsamı güncellendi: {$name}");
        $_SESSION['flash_success'] = "'{$name}' yöneticisinin bilgileri, yetkileri ve işletme kapsamı güncellendi.";
        break;

    case 'toggle_active':
        if (!$managerId) {
            $_SESSION['flash_error'] = 'Geçersiz yönetici ID.';
            header('Location: /admin/pages/managers.php');
            exit;
        }

        if ($managerId === (int)$admin['id']) {
            $_SESSION['flash_error'] = 'Kendi yönetici hesabınızı devre dışı bırakamazsınız.';
            header('Location: /admin/pages/managers.php');
            exit;
        }

        $stmt = $pdo->prepare("SELECT ad_soyad, aktif FROM yoneticiler WHERE id = ?");
        $stmt->execute([$managerId]);
        $target = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($target) {
            $newActive = $target['aktif'] ? 0 : 1;
            $pdo->prepare("UPDATE yoneticiler SET aktif = ?, yetki_versiyonu = yetki_versiyonu + 1 WHERE id = ?")->execute([$newActive, $managerId]);
            $actionText = $newActive ? 'etkinleştirildi' : 'devre dışı bırakıldı';
            logAdminAction('manager_toggle_status', 'yoneticiler', $managerId, "Yönetici hesabı {$actionText}: {$target['ad_soyad']}");
            $_SESSION['flash_success'] = "'{$target['ad_soyad']}' hesabı {$actionText}.";
        }
        break;

    case 'delete':
        if (!$managerId) {
            $_SESSION['flash_error'] = 'Geçersiz yönetici ID.';
            header('Location: /admin/pages/managers.php');
            exit;
        }

        if ($managerId === (int)$admin['id']) {
            $_SESSION['flash_error'] = 'Kendi admin hesabınızı silemezsiniz.';
            header('Location: /admin/pages/managers.php');
            exit;
        }

        $stmt = $pdo->prepare("SELECT ad_soyad FROM yoneticiler WHERE id = ?");
        $stmt->execute([$managerId]);
        $targetName = $stmt->fetchColumn() ?: 'Yönetici';

                                             
        $pdo->prepare("DELETE FROM yonetici_izinleri WHERE yonetici_id = ?")->execute([$managerId]);
        $pdo->prepare("DELETE FROM yonetici_isletme_kapsami WHERE yonetici_id = ?")->execute([$managerId]);
        $pdo->prepare("DELETE FROM yoneticiler WHERE id = ?")->execute([$managerId]);

        logAdminAction('manager_delete', 'yoneticiler', $managerId, "Yönetici hesabı silindi: {$targetName}");
        $_SESSION['flash_success'] = "'{$targetName}' hesabı sistemden kalıcı olarak silindi.";
        break;

    default:
        $_SESSION['flash_error'] = 'Bilinmeyen işlem parametresi.';
}

header('Location: /admin/pages/managers.php');
exit;
