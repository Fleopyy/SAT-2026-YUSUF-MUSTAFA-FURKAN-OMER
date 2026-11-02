<?php
   
                                                          
   

require_once __DIR__ . '/../includes/auth.php';

$admin = requireAdminOrManager();
if (!canCurrentUser('settings.edit')) requirePermission('content.edit');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/pages/content.php');
    exit;
}

if (!verifyCsrfToken()) {
    $_SESSION['flash_error'] = 'Güvenlik doğrulaması başarısız oldu (CSRF).';
    header('Location: /admin/pages/content.php');
    exit;
}

$action = $_POST['action'] ?? 'save_settings';

if ($action === 'test_smtp') {
    requirePermission('settings.edit');
    // Fail closed until an explicit SMTP destination policy and pinned transport exist.
    $_SESSION['flash_error'] = 'SMTP testi beklemede: izinli sunucu ve kurum içi ağ politikası tanımlanmalı. Ağ bağlantısı açılmadı ve kuyruk kaydı oluşturulmadı.';
    header('Location: /admin/pages/content.php#smtp');
    exit;
}

if ($action === 'save_settings') {
    $allowedKeys = [
        'site_baslik',
        'site_aciklama',
        'hero_baslik',
        'hero_aciklama',
        'duyuru_bandi',
        'duyuru_aktif',
        'iletisim_eposta',
        'iletisim_telefon',
        'footer_metin',
        'footer_telif',
        'smtp_host',
        'smtp_port',
        'smtp_kullanici',
        'smtp_sifre',
        'smtp_gonderen_eposta',
        'smtp_gonderen_ad',
        'smtp_sifreleme'
    ];

    // SMTP changes are held pending policy; preserve every existing SMTP value/password.
    $allowedKeys = array_values(array_filter($allowedKeys, fn($key) => !str_starts_with($key, 'smtp_')));
    $updatedCount = 0;
    foreach ($allowedKeys as $key) {
        if (isset($_POST[$key])) {
            $value = trim($_POST[$key]);

                                                               
            if ($key === 'smtp_sifre' && empty($value)) {
                continue;
            }

            $stmt = $pdo->prepare("INSERT INTO site_ayarlari (anahtar, deger) VALUES (?, ?) ON DUPLICATE KEY UPDATE deger = VALUES(deger)");
            $stmt->execute([$key, $value]);
            $updatedCount++;
        }
    }

                                                               
    if (!isset($_POST['duyuru_aktif'])) {
        $stmt = $pdo->prepare("UPDATE site_ayarlari SET deger = '0' WHERE anahtar = 'duyuru_aktif'");
        $stmt->execute();
    }

    logAdminAction('settings_update', 'site_ayarlari', null, "{$updatedCount} site ayarı güncellendi.");
    $_SESSION['flash_success'] = "Site ayarları kaydedildi. SMTP değişiklikleri politika tanımlanana kadar beklemede; mevcut SMTP ayarları korundu.";
    header('Location: /admin/pages/content.php');
    exit;
}

header('Location: /admin/pages/content.php');
exit;
