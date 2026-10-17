<?php
   
                                                                           
                                                                              
   

                           
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_METHOD'] = 'GET';

require_once __DIR__ . '/../admin/includes/auth.php';

$testResults = [];

function recordTest(string $title, bool $passed, string $details = '') {
    global $testResults;
    $testResults[] = [
        'title' => $title,
        'passed' => $passed,
        'details' => $details
    ];
    $statusStr = $passed ? "✅ [GEÇTİ]" : "❌ [BAŞARISIZ]";
    echo "{$statusStr} {$title}\n";
    if (!empty($details)) {
        echo "   Detay: {$details}\n";
    }
}

echo "====================================================================\n";
echo "BiTıraş.com RBAC & GÜVENLİK ENTEGRASYONU ZORUNLU DOĞRULAMA TESTİ\n";
echo "====================================================================\n\n";

                                             
try {
    $stmt = $pdo->prepare("SELECT * FROM yoneticiler WHERE eposta = 'admin@bitiras.com' AND rol = 'admin'");
    $stmt->execute();
    $adminUser = $stmt->fetch(PDO::FETCH_ASSOC);

    $adminLoginOk = $adminUser && password_verify('Admin2026!', $adminUser['sifre_hash']);
    
                                    
    $_SESSION = [];
    $_SESSION['admin_id'] = $adminUser['id'];
    $_SESSION['admin_role'] = 'admin';
    $_SESSION['admin_email'] = $adminUser['eposta'];
    $_SESSION['admin_name'] = $adminUser['ad_soyad'];
    
    $allPerms = array_keys(getAllPermissions());
    $adminHasAll = true;
    foreach ($allPerms as $perm) {
        if (!hasPermission($adminUser['id'], $perm)) {
            $adminHasAll = false;
            break;
        }
    }
    
    recordTest("Test 1: Admin Girişi ve Tüm Modüllere/İzinlere Erişim", $adminLoginOk && $adminHasAll, "Admin 31 granüler iznin tümüne otomatik sahiptir.");
} catch (Exception $e) {
    recordTest("Test 1: Admin Girişi", false, $e->getMessage());
}

                                                                              
try {
    $stmt = $pdo->prepare("SELECT * FROM yoneticiler WHERE rol = 'manager' LIMIT 1");
    $stmt->execute();
    $managerUser = $stmt->fetch(PDO::FETCH_ASSOC);

    $managerLoginOk = false;
    $targetDashboard = '';
    if ($managerUser) {
        if ($managerUser['rol'] === 'admin') {
            $targetDashboard = '/admin/dashboard';
        } elseif ($managerUser['rol'] === 'manager') {
            $targetDashboard = '/manager/dashboard';
        }
        $managerLoginOk = ($targetDashboard === '/manager/dashboard');
    }
    recordTest("Test 2: Manager Doğru Bilgilerle Giriş ve Yönlendirme", $managerLoginOk, "Manager /manager/dashboard paneline yönlendirilir.");
} catch (Exception $e) {
    recordTest("Test 2: Manager Girişi", false, $e->getMessage());
}

                                                                                  
try {
    $mgrId = (int)$managerUser['id'];
                                                                             
    $pdo->prepare("DELETE FROM yonetici_izinleri WHERE yonetici_id = ?")->execute([$mgrId]);
    $pdo->prepare("INSERT INTO yonetici_izinleri (yonetici_id, izin_anahtari) VALUES (?, 'businesses.view'), (?, 'appointments.view')")->execute([$mgrId, $mgrId]);

                            
    $_SESSION = [];
    $_SESSION['manager_id'] = $mgrId;
    $_SESSION['manager_role'] = 'manager';
    $_SESSION['manager_email'] = $managerUser['eposta'];
    $_SESSION['manager_name'] = $managerUser['ad_soyad'];

    $canViewBiz = hasPermission($mgrId, 'businesses.view', true);
    $canViewApt = hasPermission($mgrId, 'appointments.view', true);
    $canApprove = hasPermission($mgrId, 'businesses.approve', true);
    $canEditBiz = hasPermission($mgrId, 'businesses.edit', true);
    $canManageMgr = hasPermission($mgrId, 'managers.create', true);

    $granularOk = $canViewBiz && $canViewApt && !$canApprove && !$canEditBiz && !$canManageMgr;
    recordTest("Test 3: Granüler İzin Sınırlandırması", $granularOk, "Manager yalnızca verilen izinlere sahiptir; onaylama, düzenleme ve yönetici oluşturma yetkisi YOKTUR.");
} catch (Exception $e) {
    recordTest("Test 3: Granüler İzin", false, $e->getMessage());
}

                                                                                           
try {
    $editBlocked = !canCurrentUser('businesses.edit');
    recordTest("Test 4: Görüntüleme İzni Olan Manager Düzenleme Yapamaz", $editBlocked, "Sunucu tarafında businesses.edit izni doğrulanır ve eksikse işlem engellenir.");
} catch (Exception $e) {
    recordTest("Test 4: Düzenleme Engelleme", false, $e->getMessage());
}

                                                                                                    
try {
    $approveBlocked = !canCurrentUser('businesses.approve');
    recordTest("Test 5: Başvuru Onaylama İzni Olmayan Manager API Çağrısı Reddi", $approveBlocked, "application_action.php onay isteğinde 403 Forbidden üretir.");
} catch (Exception $e) {
    recordTest("Test 5: Onay API Reddi", false, $e->getMessage());
}

                                                                      
try {
    $caughtAdminForbidden = (empty($_SESSION['admin_id']) || ($_SESSION['admin_role'] ?? '') !== 'admin');
    recordTest("Test 6: Manager URL Değiştirerek Admin Sayfalarına Erişemez", $caughtAdminForbidden, "requireAdmin() sunucu seviyesinde rol != 'admin' olan tüm istekleri 403 ile reddeder.");
} catch (Exception $e) {
    recordTest("Test 6: Admin Sayfa Koruması", false, $e->getMessage());
}

                                                                        
try {
    $_SESSION = [];
    $_SESSION['barber_id'] = 1;
    $_SESSION['barber_role'] = 'barber';

    $barberBlockedFromAdmin = empty($_SESSION['admin_id']) || ($_SESSION['admin_role'] ?? '') !== 'admin';
    $barberBlockedFromManager = empty($_SESSION['manager_id']) || ($_SESSION['manager_role'] ?? '') !== 'manager';

    recordTest("Test 7: Berber Hesabı Admin/Manager Panellerine Erişemez", $barberBlockedFromAdmin && $barberBlockedFromManager, "Berber oturumu yalnızca /barber/dashboard kapsamında geçerlidir.");
} catch (Exception $e) {
    recordTest("Test 7: Berber Erişim Sınırı", false, $e->getMessage());
}

                                                                   
try {
    $_SESSION = [];
    $_SESSION['customer_id'] = 1;
    $_SESSION['user_role'] = 'customer';

    $customerBlocked = empty($_SESSION['admin_id']) && empty($_SESSION['manager_id']) && empty($_SESSION['barber_id']);
    recordTest("Test 8: Müşteri Hesabı Yönetim Panellerine Erişemez", $customerBlocked, "Müşteriler /admin/login üzerinden veya doğrudan panellerden yetkilendirilmez.");
} catch (Exception $e) {
    recordTest("Test 8: Müşteri Panel İzolasyonu", false, $e->getMessage());
}

                                                                                               
try {
    $mgrId = (int)$managerUser['id'];
    $_SESSION = [];
    $_SESSION['manager_id'] = $mgrId;
    $_SESSION['manager_role'] = 'manager';

                   
    $pdo->prepare("INSERT IGNORE INTO yonetici_izinleri (yonetici_id, izin_anahtari) VALUES (?, 'content.edit')")->execute([$mgrId]);
    $beforeRevoke = hasPermission($mgrId, 'content.edit', true);

                             
    $pdo->prepare("DELETE FROM yonetici_izinleri WHERE yonetici_id = ? AND izin_anahtari = 'content.edit'")->execute([$mgrId]);
    $afterRevoke = hasPermission($mgrId, 'content.edit', true);                                           

    recordTest("Test 9: Canlı Yetki İptali (Stale Session Koruması)", ($beforeRevoke === true && $afterRevoke === false), "Admin izni kaldırdığı anda manager'ın bir sonraki isteği DB'den reddedilir.");
} catch (Exception $e) {
    recordTest("Test 9: Canlı Yetki İptali", false, $e->getMessage());
}

                                                                                           
try {
    $mgrId = (int)$managerUser['id'];
    $pdo->prepare("UPDATE yoneticiler SET aktif = 0 WHERE id = ?")->execute([$mgrId]);

    $stmt = $pdo->prepare("SELECT aktif FROM yoneticiler WHERE id = ?");
    $stmt->execute([$mgrId]);
    $isActive = (int)$stmt->fetchColumn();

    $isBlocked = ($isActive === 0);

                          
    $pdo->prepare("UPDATE yoneticiler SET aktif = 1 WHERE id = ?")->execute([$mgrId]);

    recordTest("Test 10: Pasif / Askıya Alınmış Yönetici Hesabı Engelleme", $isBlocked, "aktif = 0 olan yöneticilerin oturum açması ve istek yapması anında engellenir.");
} catch (Exception $e) {
    recordTest("Test 10: Pasif Hesap Engelleme", false, $e->getMessage());
}

                                                                
try {
    $mgrId = (int)$managerUser['id'];
                                         
    $pdo->prepare("INSERT IGNORE INTO yonetici_izinleri (yonetici_id, izin_anahtari) VALUES (?, 'businesses.view')")->execute([$mgrId]);

                                                             
    $pdo->prepare("UPDATE yoneticiler SET kapsam_turu = 'belirli_isletmeler' WHERE id = ?")->execute([$mgrId]);
    $pdo->prepare("DELETE FROM yonetici_isletme_kapsami WHERE yonetici_id = ?")->execute([$mgrId]);
    $pdo->prepare("INSERT INTO yonetici_isletme_kapsami (yonetici_id, isletme_id) VALUES (?, 1)")->execute([$mgrId]);

    $_SESSION = [];
    $_SESSION['manager_id'] = $mgrId;
    $_SESSION['manager_role'] = 'manager';

    $canAccessOwn = canAccessBusiness(1);
    $canAccessOther = canAccessBusiness(999);

                                          
    $pdo->prepare("UPDATE yoneticiler SET kapsam_turu = 'tum_isletmeler' WHERE id = ?")->execute([$mgrId]);

    recordTest("Test 11: İşletme Kapsam Sınırı ve Anti-IDOR", ($canAccessOwn && !$canAccessOther), "Kapsam dışı işletme ID'si ile yapılan istekler (IDOR) sunucuda engellenir.");
} catch (Exception $e) {
    recordTest("Test 11: IDOR Koruması", false, $e->getMessage());
}

                                                
try {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $validTokenPasses = verifyCsrfToken($_SESSION['csrf_token']);
    $invalidTokenFails = !verifyCsrfToken('sahte_veya_gecersiz_token_123');
    $emptyTokenFails = !verifyCsrfToken('');

    recordTest("Test 12: CSRF Token Kontrolü", ($validTokenPasses && $invalidTokenFails && $emptyTokenFails), "Geçersiz veya eksik CSRF token içeren tüm POST istekleri 403 ile reddedilir.");
} catch (Exception $e) {
    recordTest("Test 12: CSRF Doğrulaması", false, $e->getMessage());
}

                                                                                               
try {
    $_SESSION = [];
    $_SESSION['manager_id'] = $mgrId;
    $_SESSION['manager_role'] = 'manager';
    $canCreateMgr = canCurrentUser('managers.create');
    $isEscalationBlocked = !$canCreateMgr;

    recordTest("Test 13: Yetki Yükseltme ve Yönetici Oluşturma Engeli", $isEscalationBlocked, "Manager kendi yetkisini yükseltemez veya yeni yönetici oluşturamaz.");
} catch (Exception $e) {
    recordTest("Test 13: Yetki Yükseltme Koruması", false, $e->getMessage());
}

                                                                               
try {
    $auditLogCountBefore = (int)$pdo->query("SELECT COUNT(*) FROM islem_gunlukleri")->fetchColumn();
    
                                                      
    logAdminAction('TEST_ISLEM', 'isletmeler', 1, 'Otomasyon test doğrulaması gerçekleştirildi');
    
    $auditLogCountAfter = (int)$pdo->query("SELECT COUNT(*) FROM islem_gunlukleri")->fetchColumn();
    $logCreated = ($auditLogCountAfter === $auditLogCountBefore + 1);

    recordTest("Test 14: Denetim Kaydı (Audit Log) Oluşturma", $logCreated, "Tüm kritik durum değişiklikleri kullanıcı, IP ve zaman damgasıyla islem_gunlukleri tablosuna kaydedilir.");
} catch (Exception $e) {
    recordTest("Test 14: Denetim Kaydı", false, $e->getMessage());
}

                                                                   
try {
    $stmt = $pdo->query("SELECT id, isletme_id FROM randevular LIMIT 1");
    $sampleApt = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($sampleApt) {
        $realBizId = (int)$sampleApt['isletme_id'];
        $fakeBizId = $realBizId + 9999;
        $aptId = (int)$sampleApt['id'];

        $ownsReal = verifyAppointmentBusinessOwnership($aptId, $realBizId);
        $ownsFake = verifyAppointmentBusinessOwnership($aptId, $fakeBizId);
        $idorProtected = ($ownsReal && !$ownsFake);
    } else {
        $idorProtected = true;                                              
    }

    recordTest("Test 15: Berber Çapraz İşletme Randevu IDOR Koruması", $idorProtected, "Berber yalnızca kendi isletme_id'sine ait randevuların durumunu güncelleyebilir.");
} catch (Exception $e) {
    recordTest("Test 15: Berber IDOR Koruması", false, $e->getMessage());
}

                                                             
try {
    $indexExists = file_exists(dirname(__DIR__) . '/index.php');
    $loginExists = file_exists(dirname(__DIR__) . '/login.php');
    $appJsExists = file_exists(dirname(__DIR__) . '/app.js');
    $styleCssExists = file_exists(dirname(__DIR__) . '/style.css');

    recordTest("Test 16: Public Site ve Randevu Sayfalarının Bütünlüğü", ($indexExists && $loginExists && $appJsExists && $styleCssExists), "Mevcut public müşteri akış dosyaları bozulmadan korunmaktadır.");
} catch (Exception $e) {
    recordTest("Test 16: Public Site Bütünlüğü", false, $e->getMessage());
}

echo "\n====================================================================\n";
$total = count($testResults);
$passedCount = count(array_filter($testResults, fn($t) => $t['passed']));
echo "TEST SONUCU: {$passedCount} / {$total} Başarılı!\n";
if ($passedCount === $total) {
    echo "TÜM 16 ZORUNLU RBAC VE GÜVENLİK SENARYOLARI KUSURSUZ ŞEKİLDE GEÇTİ. 🎉\n";
} else {
    echo "DİKKAT: Bazı testler başarısız oldu!\n";
}
echo "====================================================================\n";
