<?php
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_METHOD'] = 'GET';

require_once __DIR__ . '/../data/database.php';

$pdo->beginTransaction();

try {
    $suffix = bin2hex(random_bytes(4));
    $stmt = $pdo->prepare("INSERT INTO isletmeler (isletme_sahibi, isletme_adi, telefon, eposta, sehir, ilce, sifre_hash, durum)
                           VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");
    $stmt->execute([
        'Panel Test Yetkilisi',
        'Panel Test İşletmesi ' . $suffix,
        '0555' . random_int(1000000, 9999999),
        'panel-' . $suffix . '@example.test',
        'Ankara',
        'Çankaya',
        password_hash('test-password', PASSWORD_DEFAULT),
    ]);
    $businessId = (int)$pdo->lastInsertId();

    $stmt = $pdo->prepare("INSERT INTO kullanicilar (ad_soyad, telefon, eposta, sifre_hash) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        'Panel Test Müşterisi',
        '0544' . random_int(1000000, 9999999),
        'customer-' . $suffix . '@example.test',
        password_hash('test-password', PASSWORD_DEFAULT),
    ]);
    $customerId = (int)$pdo->lastInsertId();

    $stmt = $pdo->prepare("INSERT INTO hizmetler (isletme_id, hizmet_adi, fiyat, sure_dakika) VALUES (?, ?, 300, 30)");
    $stmt->execute([$businessId, 'Panel Test Saç Kesimi']);
    $serviceId = (int)$pdo->lastInsertId();

    $stmt = $pdo->prepare("INSERT INTO calisanlar (isletme_id, ad_soyad) VALUES (?, ?)");
    $stmt->execute([$businessId, 'Panel Test Çalışanı']);
    $staffId = (int)$pdo->lastInsertId();

    $stmt = $pdo->prepare("INSERT INTO randevular (kullanici_id, isletme_id, calisan_id, hizmet_id, randevu_zamani, durum)
                           VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$customerId, $businessId, $staffId, $serviceId, date('Y-m-d H:i:s', time() + 86400), 'pending']);
    $stmt->execute([$customerId, $businessId, $staffId, $serviceId, date('Y-m-d H:i:s', time() + 172800), 'confirmed']);
    $stmt->execute([$customerId, $businessId, $staffId, $serviceId, date('Y-m-d H:i:s', time() - 86400), 'completed']);
    $completedAppointmentId = (int)$pdo->lastInsertId();

    $stmt = $pdo->prepare("INSERT INTO degerlendirmeler (randevu_id, degerlendiren_turu, degerlendiren_id, puan, yorum)
                           VALUES (?, 'customer', ?, 5, ?)");
    $stmt->execute([$completedAppointmentId, $customerId, 'Panel test yorumu']);

    session_start();
    $_SESSION = [
        'barber_id' => $businessId,
        'barber_role' => 'barber',
        'barber_name' => 'Panel Test İşletmesi',
        'user_role' => 'barber',
    ];

    ob_start();
    include __DIR__ . '/../isletme/index.php';
    $output = ob_get_clean();

    $checks = [
        'Admin panel yapısı' => strpos($output, 'admin-sidebar') !== false && strpos($output, 'Genel Bakış') !== false,
        'İşletme randevuları' => strpos($output, 'Panel Test Müşterisi') !== false && strpos($output, 'randevular') !== false,
        'Canlı durum istatistikleri' => strpos($output, 'Onay Bekleyen') !== false
            && strpos($output, 'Tamamlanan') !== false
            && strpos($output, 'Ortalama müşteri puanı') !== false,
        'Müşteri yorumları' => strpos($output, 'Panel test yorumu') !== false && strpos($output, 'Ortalama müşteri puanı') !== false,
        'İşletmeye özel veriler' => strpos($output, 'Panel Test İşletmesi ' . $suffix) !== false,
    ];

    ob_start();
    include __DIR__ . '/../isletme/hizmetler.php';
    $servicesOutput = ob_get_clean();
    ob_start();
    include __DIR__ . '/../isletme/calisanlar.php';
    $staffOutput = ob_get_clean();
    ob_start();
    include __DIR__ . '/../isletme/randevular.php';
    $appointmentsOutput = ob_get_clean();
    $checks['Upcoming confirmed appointment can be marked completed'] =
        strpos($appointmentsOutput, 'name="status" value="completed" disabled') === false
        && strpos($appointmentsOutput, 'name="status" value="completed">Tamamlandı') !== false;
    $checks['Hizmetler ve çalışanlar işletmeye özel listeleniyor'] =
        strpos($servicesOutput, 'Panel Test Saç Kesimi') !== false
        && strpos($staffOutput, 'Panel Test Çalışanı') !== false;

    ob_start();
    include __DIR__ . '/../index.php';
    $homepageOutput = ob_get_clean();
    $checks['Ana sayfa profil menüsünden işletme paneline erişim'] =
        strpos($homepageOutput, 'account-menu-trigger') !== false
        && strpos($homepageOutput, 'İşletme Paneline Git') !== false
        && strpos($homepageOutput, 'href="/isletme/"') !== false;

    $_SESSION = [];
    ob_start();
    include __DIR__ . '/../isletme/giris.php';
    $loginOutput = ob_get_clean();
    $checks['Ayrı işletme giriş sayfası'] =
        strpos($loginOutput, 'İşletme hesabınıza giriş yapın') !== false
        && strpos($loginOutput, 'action="/isletme/giris.php"') !== false
        && strpos($loginOutput, 'admin/login.php') === false;

    $allPassed = !in_array(false, $checks, true);
    foreach ($checks as $label => $passed) {
        echo ($passed ? '[PASS] ' : '[FAIL] ') . $label . PHP_EOL;
    }
    echo $allPassed ? "Dashboard test passed.\n" : "Dashboard test failed.\n";

    $_SESSION = [];
    $pdo->rollBack();
    exit($allPassed ? 0 : 1);
} catch (Throwable $e) {
    if (ob_get_level() > 0) {
        ob_end_clean();
    }
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}
