<?php
// Direct TLS signal only; never trust client-supplied forwarding headers or port alone.
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'lifetime' => 0, 'path' => '/', 'domain' => '',
        'secure' => in_array(strtolower((string)($_SERVER['HTTPS'] ?? '')), ['on', '1'], true),
        'httponly' => true, 'samesite' => 'Lax',
    ]);
    session_start();
}
