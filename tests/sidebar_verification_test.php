<?php
   
                                                      
   

$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_METHOD'] = 'GET';

require_once __DIR__ . '/../admin/includes/auth.php';

echo "====================================================================\n";
echo "BiTıraş.com SIDEBAR & MENÜ FONKSİYONU DOĞRULAMA TESTİ\n";
echo "====================================================================\n\n";

$allPassed = true;

function testAssert(string $title, bool $condition, string $details = '') {
    global $allPassed;
    if ($condition) {
        echo "✅ [GEÇTİ] {$title}\n";
    } else {
        echo "❌ [BAŞARISIZ] {$title}\n";
        $allPassed = false;
    }
    if (!empty($details)) {
        echo "   Detay: {$details}\n";
    }
}

                            
testAssert("1. getAdminSidebarItems Fonksiyonu Tanımlı mı?", function_exists('getAdminSidebarItems'), "auth.php ve sidebar.php üzerinden erişilebilir.");

                                                             
$_SESSION = [];
$emptyItems = getAdminSidebarItems();
testAssert("2. Oturumsuz / Yetkisiz Kullanıcı Güvenli Varsayılan", is_array($emptyItems) && empty($emptyItems), "Oturum yokken sıfır menü döndürüldü.");

                             
$_SESSION = [];
$_SESSION['admin_id'] = 1;
$_SESSION['admin_role'] = 'admin';
$_SESSION['admin_name'] = 'Süper Admin';

$adminItems = getAdminSidebarItems();
$adminKeys = array_column($adminItems, 'key');
$expectedAdminKeys = ['dashboard', 'applications', 'businesses', 'appointments', 'customers', 'media', 'content', 'managers', 'logs'];
$hasAllAdminKeys = empty(array_diff($expectedAdminKeys, $adminKeys));
$dashboardUrlAdmin = $adminItems[0]['url'] ?? '';

testAssert("3. Admin Tüm Menüleri Görebiliyor mu?", $hasAllAdminKeys && $dashboardUrlAdmin === '/admin/dashboard', "Admin 9 menünün tümünü görüyor ve dashboard linki /admin/dashboard.");

                                            
                         
$stmt = $pdo->prepare("SELECT id FROM yoneticiler WHERE rol = 'manager' LIMIT 1");
$stmt->execute();
$mgrId = (int)$stmt->fetchColumn();

                                                                
$pdo->prepare("DELETE FROM yonetici_izinleri WHERE yonetici_id = ?")->execute([$mgrId]);
$pdo->prepare("INSERT INTO yonetici_izinleri (yonetici_id, izin_anahtari) VALUES (?, 'businesses.view'), (?, 'appointments.view')")->execute([$mgrId, $mgrId]);

$_SESSION = [];
$_SESSION['manager_id'] = $mgrId;
$_SESSION['manager_role'] = 'manager';
$_SESSION['manager_name'] = 'Test Manager';

$managerItems = getAdminSidebarItems();
$managerKeys = array_column($managerItems, 'key');

$hasDashboard = in_array('dashboard', $managerKeys);
$hasApplications = in_array('applications', $managerKeys);
$hasBusinesses = in_array('businesses', $managerKeys);
$hasAppointments = in_array('appointments', $managerKeys);

                          
$hasCustomers = in_array('customers', $managerKeys);
$hasManagers = in_array('managers', $managerKeys);
$hasLogs = in_array('logs', $managerKeys);
$hasContent = in_array('content', $managerKeys);

$managerPermOk = $hasDashboard && $hasApplications && $hasBusinesses && $hasAppointments &&
                 !$hasCustomers && !$hasManagers && !$hasLogs && !$hasContent;

$dashboardUrlManager = $managerItems[0]['url'] ?? '';

testAssert("4. Manager Yalnızca İzinli Menüleri Görebiliyor mu?", $managerPermOk && $dashboardUrlManager === '/manager/dashboard', "Yalnızca izin verilen modüller listelendi, 'managers' ve izinsiz modüller gizlendi.");

                                                                                
$pdo->prepare("INSERT IGNORE INTO yonetici_izinleri (yonetici_id, izin_anahtari) VALUES (?, 'managers.view')")->execute([$mgrId]);
$managerItemsWithMgrPerm = getAdminSidebarItems();
$managerKeysWithMgrPerm = array_column($managerItemsWithMgrPerm, 'key');
$strictlyBlocksManagersMenu = !in_array('managers', $managerKeysWithMgrPerm);

testAssert("5. Manager'a managers.view İzni Verilse Dahi Menü Gösterilmez (Admin-Only Güvencesi)", $strictlyBlocksManagersMenu, "Yönetici Hesapları menüsü kesinlikle sadece Süper Admin'e açıktır.");

           
$pdo->prepare("DELETE FROM yonetici_izinleri WHERE yonetici_id = ? AND izin_anahtari = 'managers.view'")->execute([$mgrId]);

                                                          
$_SESSION = [];
$_SESSION['admin_id'] = 1;
$_SESSION['admin_role'] = 'admin';
$_SESSION['admin_name'] = 'Süper Admin';
$currentPage = 'dashboard';

ob_start();
include __DIR__ . '/../admin/includes/sidebar.php';
$sidebarHtml = ob_get_clean();

$renderedCleanly = !empty($sidebarHtml) && strpos($sidebarHtml, 'BiTıraş') !== false && strpos($sidebarHtml, 'fa-gauge') !== false;

testAssert("6. sidebar.php Bileşeni Hatasız ve Temiz Render Edildi mi?", $renderedCleanly, "HTML hatasız üretildi, Fatal error veya undefined function kalmadı.");

echo "\n====================================================================\n";
if ($allPassed) {
    echo "SONUÇ: TÜM TESTLER BAŞARIYLA GEÇTİ! 🎉\n";
} else {
    echo "SONUÇ: BAZI TESTLER BAŞARISIZ OLDU!\n";
}
echo "====================================================================\n";
