<?php
require_once dirname(__DIR__) . '/admin/includes/auth.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !verifyCsrfToken()) {
    http_response_code(403);
    exit('Geçersiz istek.');
}

unset($_SESSION['barber_id'], $_SESSION['barber_role'], $_SESSION['barber_name']);
if (($_SESSION['user_role'] ?? '') === 'barber') {
    unset($_SESSION['user_role']);
}

header('Location: /');
exit;
