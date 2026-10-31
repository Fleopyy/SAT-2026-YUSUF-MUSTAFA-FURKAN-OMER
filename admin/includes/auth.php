<?php
   
                                                                                                 
                                                                     
   

                                        
require_once dirname(__DIR__, 2) . '/data/security.php';

                                                                                  
if (!headers_sent()) {
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
    header("X-Robots-Tag: noindex, nofollow, noarchive, nosnippet");
}

require_once dirname(__DIR__, 2) . '/data/database.php';
require_once dirname(__DIR__, 2) . '/data/mail_service.php';

                                                                             
                                  
                                                                                

function getAllPermissions(): array {
    return [
                              
        'businesses.view'            => ['label' => 'İşletmeleri Görüntüleme', 'group' => 'businesses'],
        'businesses.view_details'    => ['label' => 'İşletme Detaylarını İnceleme', 'group' => 'businesses'],
        'businesses.approve'         => ['label' => 'İşletme Başvurularını Onaylama', 'group' => 'businesses'],
        'businesses.reject'          => ['label' => 'İşletme Başvurularını Reddetme', 'group' => 'businesses'],
        'businesses.edit'            => ['label' => 'İşletme Bilgilerini Düzenleme', 'group' => 'businesses'],
        'businesses.suspend'         => ['label' => 'İşletmeyi Askıya Alma', 'group' => 'businesses'],
        'businesses.reactivate'      => ['label' => 'İşletmeyi Yeniden Aktifleştirme', 'group' => 'businesses'],
        'businesses.archive'         => ['label' => 'İşletmeyi Arşivleme', 'group' => 'businesses'],
        'businesses.delete'          => ['label' => 'İşletmeyi Silme', 'group' => 'businesses'],

                                
        'business_photos.view'       => ['label' => 'İşletme Fotoğraflarını Görme', 'group' => 'media'],
        'business_photos.upload'     => ['label' => 'İşletme Fotoğrafı Yükleme', 'group' => 'media'],
        'business_photos.delete'     => ['label' => 'İşletme Fotoğrafı Silme', 'group' => 'media'],

                            
        'appointments.view'          => ['label' => 'Randevuları Görüntüleme', 'group' => 'appointments'],
        'appointments.view_details'  => ['label' => 'Randevu Detaylarını İnceleme', 'group' => 'appointments'],
        'appointments.edit'          => ['label' => 'Randevu Düzenleme', 'group' => 'appointments'],
        'appointments.cancel'        => ['label' => 'Randevu İptal Etme', 'group' => 'appointments'],
        'appointments.export'        => ['label' => 'Randevuları Dışa Aktarma (Excel/CSV)', 'group' => 'appointments'],

                                            
        'users.view'                 => ['label' => 'Müşterileri Görüntüleme', 'group' => 'users'],
        'users.edit'                 => ['label' => 'Müşteri Bilgilerini Düzenleme', 'group' => 'users'],
        'users.suspend'              => ['label' => 'Müşteriyi Askıya Alma', 'group' => 'users'],
        'users.archive'              => ['label' => 'Müşteriyi Arşivleme', 'group' => 'users'],
        'users.delete'               => ['label' => 'Müşteriyi Silme', 'group' => 'users'],

                                           
        'reviews.view'               => ['label' => 'Yorumları Görüntüleme', 'group' => 'reviews'],
        'reviews.moderate'           => ['label' => 'Yorumları Onaylama / Gizleme', 'group' => 'reviews'],
        'reviews.delete'             => ['label' => 'Yorumları Silme', 'group' => 'reviews'],

                          
        'content.view'               => ['label' => 'Site İçeriğini Görüntüleme', 'group' => 'content'],
        'content.edit'               => ['label' => 'Site İçeriğini Düzenleme', 'group' => 'content'],
        'content.publish'            => ['label' => 'Site İçeriğini Yayına Alma', 'group' => 'content'],

                               
        'reports.view'               => ['label' => 'Raporları Görüntüleme', 'group' => 'reports'],
        'reports.export'             => ['label' => 'Raporları Dışa Aktarma', 'group' => 'reports'],
        'analytics.view'             => ['label' => 'Analitik İstatistikleri İnceleme', 'group' => 'reports'],

                           
        'settings.view'              => ['label' => 'Sistem ve SMTP Ayarlarını Görme', 'group' => 'settings'],
        'settings.edit'              => ['label' => 'Sistem ve SMTP Ayarlarını Değiştirme', 'group' => 'settings'],

                              
        'audit_logs.view'            => ['label' => 'Denetim Günlüklerini İnceleme', 'group' => 'audit_logs'],
    ];
}

   
                                           
   
function getPermissionGroups(): array {
    return [
        'group_business' => [
            'name' => 'İşletme ve Başvuru Sorumlusu',
            'desc' => 'Tüm işletme başvurularını inceler, onaylar, reddeder ve düzenler.',
            'perms' => [
                'businesses.view', 'businesses.view_details', 'businesses.approve', 'businesses.reject',
                'businesses.edit', 'businesses.suspend', 'businesses.reactivate',
                'business_photos.view', 'business_photos.delete'
            ]
        ],
        'group_appointments' => [
            'name' => 'Randevu ve Operasyon Yöneticisi',
            'desc' => 'Müşteri randevularını, durumlarını ve müşteri geçmişini yönetir.',
            'perms' => [
                'appointments.view', 'appointments.view_details', 'appointments.edit', 'appointments.cancel',
                'appointments.export', 'users.view', 'reviews.view', 'reviews.moderate'
            ]
        ],
        'group_content' => [
            'name' => 'İçerik ve Medya Editörü',
            'desc' => 'Site metinlerini, duyuru bandını, görselleri ve yorumları denetler.',
            'perms' => [
                'content.view', 'content.edit', 'content.publish',
                'business_photos.view', 'business_photos.upload', 'business_photos.delete',
                'reviews.view', 'reviews.moderate'
            ]
        ],
        'group_reports' => [
            'name' => 'Rapor ve Analiz Uzmanı',
            'desc' => 'İstatistikleri, doluluk oranlarını ve analiz raporlarını dışa aktarır.',
            'perms' => [
                'reports.view', 'reports.export', 'analytics.view',
                'businesses.view', 'appointments.view', 'users.view'
            ]
        ],
        'group_support' => [
            'name' => 'Müşteri Destek Temsilcisi',
            'desc' => 'Salt görüntüleme yetkisi ile işletme, randevu ve müşteri bilgilerini inceler.',
            'perms' => [
                'businesses.view', 'businesses.view_details',
                'appointments.view', 'appointments.view_details',
                'users.view', 'reviews.view'
            ]
        ]
    ];
}

   
                                                                                     
   
if (!function_exists('getAdminSidebarItems')) {
    function getAdminSidebarItems(): array {
        $isAdmin = isset($_SESSION['admin_id']) && ($_SESSION['admin_role'] ?? '') === 'admin';
        $isManager = isset($_SESSION['manager_id']) && ($_SESSION['manager_role'] ?? '') === 'manager';

                                                                           
        if (!$isAdmin && !$isManager) {
            return [];
        }

                              
        $catalog = [
            [
                'key' => 'dashboard',
                'label' => 'Genel Bakış',
                'url' => $isAdmin ? '/admin/dashboard' : '/manager/dashboard',
                'icon' => 'fa-solid fa-gauge',
                'permission' => null,
                'admin_only' => false,
                'badge_query' => null,
            ],
            [
                'key' => 'applications',
                'label' => 'İşletme Başvuruları',
                'url' => '/admin/pages/applications.php',
                'icon' => 'fa-solid fa-clock',
                'permission' => 'businesses.view',
                'admin_only' => false,
                'badge_query' => "SELECT COUNT(*) FROM isletmeler WHERE durum = 'pending'",
            ],
            [
                'key' => 'businesses',
                'label' => 'İşletmeler',
                'url' => '/admin/pages/businesses.php',
                'icon' => 'fa-solid fa-store',
                'permission' => 'businesses.view',
                'admin_only' => false,
                'badge_query' => null,
            ],
            [
                'key' => 'appointments',
                'label' => 'Randevular',
                'url' => '/admin/pages/appointments.php',
                'icon' => 'fa-solid fa-calendar-check',
                'permission' => 'appointments.view',
                'admin_only' => false,
                'badge_query' => "SELECT COUNT(*) FROM randevular WHERE DATE(randevu_zamani) = CURDATE()",
            ],
            [
                'key' => 'customers',
                'label' => 'Müşteriler',
                'url' => '/admin/pages/customers.php',
                'icon' => 'fa-solid fa-users',
                'permission' => 'users.view',
                'admin_only' => false,
                'badge_query' => null,
            ],
            [
                'key' => 'media',
                'label' => 'Medya & Fotoğraflar',
                'url' => '/admin/pages/media.php',
                'icon' => 'fa-solid fa-images',
                'permission' => 'business_photos.view',
                'admin_only' => false,
                'badge_query' => null,
            ],
            [
                'key' => 'content',
                'label' => 'İçerik Yönetimi',
                'url' => '/admin/pages/content.php',
                'icon' => 'fa-solid fa-newspaper',
                'permission' => 'settings.view',
                'admin_only' => false,
                'badge_query' => null,
            ],
            [
                'key' => 'managers',
                'label' => 'Yönetici Hesapları',
                'url' => '/admin/pages/managers.php',
                'icon' => 'fa-solid fa-user-shield',
                'permission' => 'managers.view',
                'admin_only' => true,                                      
                'badge_query' => null,
            ],
            [
                'key' => 'logs',
                'label' => 'İşlem Kayıtları',
                'url' => '/admin/pages/logs.php',
                'icon' => 'fa-solid fa-clock-rotate-left',
                'permission' => 'audit_logs.view',
                'admin_only' => false,
                'badge_query' => null,
            ],
        ];

        $items = [];
        foreach ($catalog as $item) {
                                                   
            if ($isAdmin) {
                $items[] = $item;
                continue;
            }

                                                     
            if ($isManager) {
                                                                  
                if (!empty($item['admin_only'])) {
                    continue;
                }

                                                                      
                if (empty($item['permission'])) {
                    $items[] = $item;
                    continue;
                }

                                           
                if (canCurrentUser($item['permission']) || ($item['key'] === 'content' && canCurrentUser('content.view'))) {
                    $items[] = $item;
                }
            }
        }

        return $items;
    }
}

                                                                             
                                                           
                                                                                

   
                                   
   
function getAuthenticatedAdmin(): ?array {
    global $pdo;
    
    if (empty($_SESSION['admin_id']) || ($_SESSION['admin_role'] ?? '') !== 'admin') {
        return null;
    }
    
    $stmt = $pdo->prepare("SELECT id, ad_soyad, eposta, rol, aktif, yetki_versiyonu FROM yoneticiler WHERE id = ? AND aktif = 1 AND rol = 'admin'");
    $stmt->execute([$_SESSION['admin_id']]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin) {
        unset($_SESSION['admin_id'], $_SESSION['admin_role'], $_SESSION['admin_name']);
        return null;
    }
    
    return $admin;
}

   
                              
   
function getAuthenticatedManager(): ?array {
    global $pdo;
    
    if (empty($_SESSION['manager_id']) || ($_SESSION['manager_role'] ?? '') !== 'manager') {
        return null;
    }
    
    $stmt = $pdo->prepare("SELECT id, ad_soyad, eposta, rol, aktif, kapsam_turu, yetki_versiyonu FROM yoneticiler WHERE id = ? AND aktif = 1 AND rol = 'manager'");
    $stmt->execute([$_SESSION['manager_id']]);
    $manager = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$manager) {
        unset($_SESSION['manager_id'], $_SESSION['manager_role'], $_SESSION['manager_name']);
        return null;
    }
    
    return $manager;
}

   
                             
   
function getAuthenticatedBarber(): ?array {
    global $pdo;
    
    if (empty($_SESSION['barber_id']) || ($_SESSION['barber_role'] ?? '') !== 'barber') {
        return null;
    }
    
    $stmt = $pdo->prepare("SELECT id, isletme_sahibi, isletme_adi, telefon, eposta, sehir, ilce, mahalle, cadde_sokak, bina_no, daire_no, posta_kodu, tam_adres, aciklama, latitude, longitude, place_id, location_verified_at, durum FROM isletmeler WHERE id = ? AND durum = 'active'");
    $stmt->execute([$_SESSION['barber_id']]);
    $barber = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$barber) {
        unset($_SESSION['barber_id'], $_SESSION['barber_role'], $_SESSION['barber_name']);
        return null;
    }
    
    return $barber;
}

   
                            
   
function requireAdmin(): array {
    $admin = getAuthenticatedAdmin();
    if (!$admin) {
                                                              
        if (getAuthenticatedManager()) {
            header('Location: /manager/dashboard');
            exit;
        }
        header('Location: /admin/login.php');
        exit;
    }
    return $admin;
}

   
                       
   
function requireManager(): array {
    $manager = getAuthenticatedManager();
    if (!$manager) {
                                                            
        if (getAuthenticatedAdmin()) {
            header('Location: /admin/dashboard');
            exit;
        }
        header('Location: /admin/login.php');
        exit;
    }
    return $manager;
}

   
                                  
   
function requireAdminOrManager(): array {
    $admin = getAuthenticatedAdmin();
    if ($admin) return $admin;

    $manager = getAuthenticatedManager();
    if ($manager) return $manager;

    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
              || (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);
    if ($isAjax) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Lütfen tekrar giriş yapın (Oturum bulunamadı).'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    header('Location: /admin/login.php');
    exit;
}

   
                                                
   
function requireAuth(): array {
    return requireAdminOrManager();
}

   
                                                                    
   
function isCurrentUserAdmin(): bool {
    return isset($_SESSION['admin_id']) && ($_SESSION['admin_role'] ?? '') === 'admin';
}

   
                                                                                             
   
function isCurrentUserManager(): bool {
    return isset($_SESSION['manager_id']) && ($_SESSION['manager_role'] ?? '') === 'manager';
}

   
                      
   
function requireBarber(): array {
    $barber = getAuthenticatedBarber();
    if (!$barber) {
        header('Location: /admin/login.php');
        exit;
    }
    return $barber;
}


                                                                             
                                                           
                                                                                

   
                                                                                                    
   
function hasPermission(int $managerId, string $permission, bool $skipCache = false): bool {
    global $pdo;
    static $permCache = [];

                                                   
    if (($_SESSION['admin_role'] ?? '') === 'admin') {
        return true;
    }

    $cacheKey = $managerId . '_' . $permission;
    if (!$skipCache && isset($permCache[$cacheKey])) {
        return $permCache[$cacheKey];
    }

                                
    $stmt = $pdo->prepare("SELECT rol, aktif FROM yoneticiler WHERE id = ?");
    $stmt->execute([$managerId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !$user['aktif']) {
        $permCache[$cacheKey] = false;
        return false;
    }

    if ($user['rol'] === 'admin') {
        $permCache[$cacheKey] = true;
        return true;
    }

                                        
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM yonetici_izinleri WHERE yonetici_id = ? AND izin_anahtari = ?");
    $stmt->execute([$managerId, $permission]);
    $has = (int)$stmt->fetchColumn() > 0;

                                                                    
    $permCache[$cacheKey] = $has;
    return $has;
}

   
                                                                               
   
function canCurrentUser(string $permission): bool {
    if (isset($_SESSION['admin_id']) && ($_SESSION['admin_role'] ?? '') === 'admin') {
        return true;
    }
    if (isset($_SESSION['manager_id']) && ($_SESSION['manager_role'] ?? '') === 'manager') {
        return hasPermission((int)$_SESSION['manager_id'], $permission);
    }
    return false;
}

   
                                                                           
   
function requirePermission(string $permission): array {
    $user = requireAdminOrManager();

    if ($user['rol'] === 'admin') {
        return $user;
    }

    if (!canCurrentUser($permission)) {
        error_log("forbidden_action_blocked: permission={$permission}; manager=" . (int)($_SESSION['manager_id'] ?? 0));

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                  || (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);

        if ($isAjax) {
            http_response_code(403);
            die(json_encode(['success' => false, 'message' => "Bu işlem için yetkiniz bulunmamaktadır ({$permission})."]));
        }

        http_response_code(403);
        die("<div style='font-family:Inter,sans-serif;max-width:520px;margin:100px auto;padding:40px;background:#0F172A;color:#fff;border-radius:16px;text-align:center;box-shadow:0 20px 50px rgba(0,0,0,0.5);border:1px solid rgba(239,68,68,0.3);'>
            <div style='font-size:44px;color:#EF4444;margin-bottom:14px;'><i class='fa-solid fa-ban'></i></div>
            <h2 style='color:#F8FAFC;margin-bottom:10px;'>Yetkisiz Erişim (403)</h2>
            <p style='color:#94A3B8;font-size:14px;line-height:1.6;margin-bottom:16px;'>Bu sayfaya veya işleme erişim izniniz bulunmamaktadır.</p>
            <div style='background:#1E293B;padding:10px 16px;border-radius:8px;font-size:12px;color:#CBD5E1;display:inline-block;margin-bottom:24px;'>
                Gerekli İzin: <code style='color:#E5A955;font-weight:700;'>" . htmlspecialchars($permission) . "</code>
            </div>
            <div>
                <a href='/manager/dashboard' style='display:inline-block;padding:12px 24px;background:#C28840;color:#fff;text-decoration:none;border-radius:10px;font-weight:700;font-size:14px;'>← Yönetici Paneline Dön</a>
            </div>
        </div>");
    }

    return $user;
}

                                                                             
                                        
                                                                                

   
                                                       
                                                 
                                            
   
function getManagerAllowedBusinessIds(int $managerId): ?array {
    global $pdo;
    $stmt = $pdo->prepare("SELECT kapsam_turu FROM yoneticiler WHERE id = ?");
    $stmt->execute([$managerId]);
    $kapsam = $stmt->fetchColumn();

    if ($kapsam === 'tum_isletmeler') {
        return null;
    }

    $stmt = $pdo->prepare("SELECT isletme_id FROM yonetici_isletme_kapsami WHERE yonetici_id = ?");
    $stmt->execute([$managerId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
}

   
                                                                  
   
function currentBusinessScopeSql(string $column, array &$params): string {
    if (isCurrentUserAdmin()) return '1=1';
    if (!isCurrentUserManager()) return '1=0';
    $ids = getManagerAllowedBusinessIds((int)$_SESSION['manager_id']);
    if ($ids === null) return '1=1';
    if ($ids === []) return '1=0';
    $params = array_merge($params, array_map('intval', $ids));
    return $column . ' IN (' . implode(',', array_fill(0, count($ids), '?')) . ')';
}

function canAccessBusiness(int $businessId): bool {
    if (isCurrentUserManager() && !isCurrentUserAdmin() && !canCurrentUser('businesses.view')) return false;
    return currentBusinessScopeAllows($businessId);
}

function currentBusinessScopeAllows(int $businessId): bool {
                                       
    if (isset($_SESSION['admin_id']) && ($_SESSION['admin_role'] ?? '') === 'admin') {
        return true;
    }

                                                      
    if (isset($_SESSION['barber_id']) && ($_SESSION['barber_role'] ?? '') === 'barber') {
        return (int)$_SESSION['barber_id'] === $businessId;
    }

                                     
    if (isset($_SESSION['manager_id']) && ($_SESSION['manager_role'] ?? '') === 'manager') {
        $allowed = getManagerAllowedBusinessIds((int)$_SESSION['manager_id']);
        if ($allowed === null) return true;                           
        return in_array($businessId, array_map('intval', $allowed), true);
    }

    return false;
}

   
                                                                
   
function verifyAppointmentBusinessOwnership(int $appointmentId, int $businessId): bool {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM randevular WHERE id = ? AND isletme_id = ?");
    $stmt->execute([$appointmentId, $businessId]);
    return (int)$stmt->fetchColumn() > 0;
}

                                                                             
                                                        
                                                                                

function logAdminAction(string $actionType, ?string $targetType = null, ?int $targetId = null, ?string $detail = null): void {
    global $pdo;
    
    $userId = $_SESSION['admin_id'] ?? $_SESSION['manager_id'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    
    $stmt = $pdo->prepare("INSERT INTO islem_gunlukleri (yonetici_id, islem_turu, hedef_turu, hedef_id, detay, ip_adresi) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $actionType, $targetType, $targetId, $detail, $ip]);
}

function createNotification(string $title, string $message, string $type = 'info', ?string $link = null, ?int $targetManagerId = null): void {
    global $pdo;
    
    $stmt = $pdo->prepare("INSERT INTO bildirimler (hedef_yonetici_id, baslik, mesaj, tur, link) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$targetManagerId, $title, $message, $type, $link]);
}

function queueEmail(string $toEmail, ?string $toName, string $subject, string $body, ?string $idempotencyKey = null, ?string $tur = null): int {
    if (function_exists('enqueueMail')) {
        $res = enqueueMail($toEmail, $toName, $subject, $body, $idempotencyKey, $tur);
        return $res !== false ? (int)$res : 0;
    }
    global $pdo;
    if (!$pdo) {
        return 0;
    }
    $stmt = $pdo->prepare("INSERT INTO eposta_kuyrugu (alici_eposta, alici_ad, konu, icerik) VALUES (?, ?, ?, ?)");
    $stmt->execute([$toEmail, $toName, $subject, $body]);
    return (int)$pdo->lastInsertId();
}

function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function jsonResponse(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
