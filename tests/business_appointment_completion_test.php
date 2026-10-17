<?php
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_METHOD'] = 'GET';
require_once __DIR__ . '/../data/database.php';

$businessId = 0;
$customerId = 0;
$employeeId = 0;
$serviceId = 0;
$appointmentId = 0;

try {
    $suffix = bin2hex(random_bytes(4));
    $stmt = $pdo->prepare("INSERT INTO isletmeler (isletme_sahibi, isletme_adi, telefon, eposta, sehir, ilce, sifre_hash, durum)
                           VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");
    $stmt->execute([
        'Completion Test Owner',
        'Completion Test Business ' . $suffix,
        '0555' . random_int(1000000, 9999999),
        'completion-' . $suffix . '@example.test',
        'Ankara',
        'Çankaya',
        password_hash('test-password', PASSWORD_DEFAULT),
    ]);
    $businessId = (int)$pdo->lastInsertId();

    $stmt = $pdo->prepare('INSERT INTO kullanicilar (ad_soyad, telefon, eposta, sifre_hash) VALUES (?, ?, ?, ?)');
    $stmt->execute([
        'Completion Test Customer',
        '0544' . random_int(1000000, 9999999),
        'completion-customer-' . $suffix . '@example.test',
        password_hash('test-password', PASSWORD_DEFAULT),
    ]);
    $customerId = (int)$pdo->lastInsertId();

    $stmt = $pdo->prepare('INSERT INTO hizmetler (isletme_id, hizmet_adi, fiyat, sure_dakika) VALUES (?, ?, 300, 30)');
    $stmt->execute([$businessId, 'Completion Test Service']);
    $serviceId = (int)$pdo->lastInsertId();
    $stmt = $pdo->prepare('INSERT INTO calisanlar (isletme_id, ad_soyad) VALUES (?, ?)');
    $stmt->execute([$businessId, 'Completion Test Employee']);
    $employeeId = (int)$pdo->lastInsertId();

    $stmt = $pdo->prepare("INSERT INTO randevular (kullanici_id, isletme_id, calisan_id, hizmet_id, randevu_zamani, durum)
                           VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 1 DAY), 'confirmed')");
    $stmt->execute([$customerId, $businessId, $employeeId, $serviceId]);
    $appointmentId = (int)$pdo->lastInsertId();

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION = [
        'barber_id' => $businessId,
        'barber_role' => 'barber',
        'barber_name' => 'Completion Test Business',
        'user_role' => 'barber',
        'csrf_token' => bin2hex(random_bytes(32)),
    ];
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = [
        'csrf_token' => $_SESSION['csrf_token'],
        'appointment_id' => $appointmentId,
        'status' => 'completed',
    ];

    ob_start();
    include __DIR__ . '/../isletme/randevular.php';
    $output = ob_get_clean();

    $stmt = $pdo->prepare('SELECT durum FROM randevular WHERE id = ? AND isletme_id = ?');
    $stmt->execute([$appointmentId, $businessId]);
    $completed = $stmt->fetchColumn() === 'completed';
    echo $completed ? "[PASS] Future confirmed appointment was marked completed\n" : "[FAIL] Appointment status was not updated\n";
    echo strpos($output, 'Randevu durumu güncellendi.') !== false
        ? "[PASS] Completion success message is shown\n"
        : "[FAIL] Completion success message was not shown\n";

    if (!$completed || strpos($output, 'Randevu durumu güncellendi.') === false) {
        throw new RuntimeException('Appointment completion integration test failed.');
    }
} catch (Throwable $e) {
    if (ob_get_level() > 0) {
        ob_end_clean();
    }
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
} finally {
    if ($appointmentId) {
        $pdo->prepare('DELETE FROM randevular WHERE id = ?')->execute([$appointmentId]);
    }
    if ($serviceId) {
        $pdo->prepare('DELETE FROM hizmetler WHERE id = ?')->execute([$serviceId]);
    }
    if ($employeeId) {
        $pdo->prepare('DELETE FROM calisanlar WHERE id = ?')->execute([$employeeId]);
    }
    if ($customerId) {
        $pdo->prepare('DELETE FROM kullanicilar WHERE id = ?')->execute([$customerId]);
    }
    if ($businessId) {
        $pdo->prepare('DELETE FROM isletmeler WHERE id = ?')->execute([$businessId]);
    }
}
