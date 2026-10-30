<?php
   
                                             
   

require_once __DIR__ . '/../includes/auth.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    exit;
}
requireCsrf();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verifyCsrfToken()) {
        $admin = getAuthenticatedAdmin();
        if ($admin) {
            logAdminAction('admin_logout', 'yoneticiler', $admin['id'], 'Admin panelinden çıkış yapıldı');
        }
    }
}

                             
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $params['path'], 'domain' => $params['domain'],
        'secure' => $params['secure'], 'httponly' => $params['httponly'],
        'samesite' => $params['samesite'] ?? 'Lax',
    ]);
}
session_destroy();

header('Location: /admin/login.php?logged_out=1');
exit;
