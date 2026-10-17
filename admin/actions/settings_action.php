<?php
   
                                                          
   

require_once __DIR__ . '/../includes/auth.php';

$admin = requirePermission('content.manage');

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
    $testTo = trim($_POST['test_email'] ?? '');
    if (empty($testTo) || !filter_var($testTo, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['flash_error'] = 'Lütfen geçerli bir test alıcı e-posta adresi girin.';
        header('Location: /admin/pages/content.php#smtp');
        exit;
    }

                                           
    $stmt = $pdo->query("SELECT anahtar, deger FROM site_ayarlari WHERE anahtar LIKE 'smtp_%'");
    $smtpSettings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $host = $smtpSettings['smtp_host'] ?? '';
    $port = (int)($smtpSettings['smtp_port'] ?? 587);
    $user = $smtpSettings['smtp_kullanici'] ?? '';
    $pass = $smtpSettings['smtp_sifre'] ?? '';
    $fromEmail = $smtpSettings['smtp_gonderen_eposta'] ?? 'noreply@bitiras.com';
    $fromName = $smtpSettings['smtp_gonderen_ad'] ?? 'BiTıraş.com';
    $crypto = $smtpSettings['smtp_sifreleme'] ?? 'tls';

                                
    $queueId = queueEmail($testTo, 'Test Alıcısı', 'BiTıraş.com — SMTP Test Mesajı', 'Bu e-posta, BiTıraş.com yönetim panelinden yapılan SMTP yapılandırma testidir. Eğer bu mesajı görüyorsanız e-posta alt yapısı çalışmaktadır.');

                                                                    
    $connectionDiagnostic = "";
    if (!empty($host)) {
        $errno = 0;
        $errstr = '';
        $timeout = 5;
        $socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
        if ($socket) {
            fclose($socket);
            $connectionDiagnostic = " ({$host}:{$port} sunucusuna TCP bağlantısı başarıyla kuruldu).";
        } else {
            $connectionDiagnostic = " (Dikkat: {$host}:{$port} sunucusuna doğrudan soket bağlantısı kurulamadı: {$errstr}).";
        }
    } else {
        $connectionDiagnostic = " (SMTP Host henüz yapılandırılmamış, test e-postası veritabanı kuyruğuna eklendi).";
    }

    logAdminAction('smtp_test', 'site_ayarlari', null, "SMTP test e-postası tetiklendi: {$testTo}");
    $_SESSION['flash_success'] = "Test e-postası e-posta kuyruğuna (#{$queueId}) eklendi{$connectionDiagnostic}";
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
    $_SESSION['flash_success'] = "Site ve e-posta ayarları başarıyla kaydedildi.";
    header('Location: /admin/pages/content.php');
    exit;
}

header('Location: /admin/pages/content.php');
exit;
