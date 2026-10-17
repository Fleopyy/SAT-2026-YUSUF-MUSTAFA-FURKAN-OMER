<?php
require_once __DIR__ . '/data/database.php';
require_once __DIR__ . '/admin/includes/auth.php';
require_once __DIR__ . '/isletme/_booking.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    exit('Bu işlem için POST isteği gereklidir.');
}
if (!verifyCsrfToken()) {
    http_response_code(403);
    exit('Geçersiz istek.');
}
if (($_SESSION['user_role'] ?? '') !== 'customer' || empty($_SESSION['user_id'])) {
    $_SESSION['mesaj'] = 'Randevu almak için müşteri hesabınızla giriş yapın.';
    $_SESSION['mesaj_turu'] = 'warning';
    header('Location: /');
    exit;
}

$requestedBusinessId = filter_var($_POST['business_id'] ?? null, FILTER_VALIDATE_INT);
$employeeId = filter_var($_POST['employee_id'] ?? null, FILTER_VALIDATE_INT);
$serviceId = filter_var($_POST['service_id'] ?? null, FILTER_VALIDATE_INT);
$startInput = is_string($_POST['start'] ?? null) ? $_POST['start'] : '';
$start = preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $startInput) ? str_replace('T', ' ', $startInput) : '';
$note = trim(is_string($_POST['note'] ?? null) ? $_POST['note'] : '');
if (!$requestedBusinessId || $employeeId === false || $employeeId === null || !$serviceId || mb_strlen($note, 'UTF-8') > 1000) {
    http_response_code(422);
    exit('Randevu bilgileri geçersiz.');
}

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("SELECT id FROM isletmeler WHERE id = ? AND durum = 'active' FOR UPDATE");
    $stmt->execute([$requestedBusinessId]);
    if (!$stmt->fetchColumn()) {
        throw new RuntimeException('Bu işletme şu anda randevu kabul etmiyor.');
    }
    if ($employeeId > 0) {
        $stmt = $pdo->prepare('SELECT id FROM calisanlar WHERE id = ? AND isletme_id = ? AND aktif_mi = 1 FOR UPDATE');
        $stmt->execute([$employeeId, $requestedBusinessId]);
        if (!$stmt->fetchColumn()) {
            throw new RuntimeException('Seçilen çalışan bu işletmede aktif değil.');
        }
    }

    [$available, $result] = businessCanBook($pdo, $requestedBusinessId, $employeeId, $serviceId, $start);
    if (!$available) {
        throw new RuntimeException($result);
    }
    $end = $result;

    $stmt = $pdo->prepare('INSERT INTO randevular (kullanici_id, isletme_id, calisan_id, hizmet_id, randevu_zamani, durum) VALUES (?, ?, ?, ?, ?, "pending")');
    $stmt->execute([(int)$_SESSION['user_id'], $requestedBusinessId, $employeeId > 0 ? $employeeId : null, $serviceId, $start . ':00']);
    $appointmentId = (int)$pdo->lastInsertId();
    $stmt = $pdo->prepare('UPDATE randevu_ek_bilgileri SET bitis_zamani = ?, musteri_notu = ? WHERE randevu_id = ?');
    $stmt->execute([$end, $note !== '' ? $note : null, $appointmentId]);
    $pdo->commit();
    $_SESSION['mesaj'] = 'Randevu isteğiniz işletmeye iletildi.';
    $_SESSION['mesaj_turu'] = 'success';
    header('Location: /berber.php?id=' . $requestedBusinessId);
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if (!($e instanceof RuntimeException) && $e->getCode() === '45000') {
        $message = 'Bu saat çalışan için artık müsait değil. Lütfen başka bir saat seçin.';
    } else {
        error_log('Customer booking failed: ' . $e->getMessage());
        $message = $e instanceof RuntimeException ? $e->getMessage() : 'Randevu oluşturulamadı. Lütfen başka bir saat seçin.';
    }
    $_SESSION['mesaj'] = $message;
    $_SESSION['mesaj_turu'] = 'warning';
    header('Location: /berber.php?id=' . $requestedBusinessId);
    exit;
}
