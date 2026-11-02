<?php
// CLI only: no application bootstrap or database access.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
$checks = 0;
function check4c(bool $ok, string $label): void {
    global $checks;
    if (!$ok) throw new RuntimeException($label);
    $checks++;
}
// Fresh subprocesses avoid carrying session configuration between TLS cases.
if (isset($argv[1]) && $argv[1] === 'cookie') {
    $_SERVER['HTTPS'] = $argv[2];
    $_SERVER['SERVER_PORT'] = $argv[3];
    $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
    // In-memory session backend: never touch application session files.
    session_set_save_handler(
        fn($path, $name) => true, fn() => true, fn($id) => '',
        fn($id, $data) => true, fn($id) => true, fn($age) => 0
    );
    require __DIR__ . '/../data/session.php';
    $params = session_get_cookie_params();
    session_abort();
    echo json_encode([$params, ini_get('session.use_strict_mode'), ini_get('session.use_only_cookies')]);
    exit;
}
foreach ([['', '80', false], ['off', '443', false], ['on', '443', true], ['ON', '443', true], ['1', '443', true], ['false', '80', false]] as [$https, $port, $secure]) {
    $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' cookie ' . escapeshellarg($https) . ' ' . escapeshellarg($port);
    exec($command, $output, $status);
    check4c($status === 0, 'cookie subprocess');
    [$params, $strict, $onlyCookies] = json_decode(implode('', $output), true, 512, JSON_THROW_ON_ERROR);
    unset($output);
    check4c($params['secure'] === $secure, 'TLS-only Secure flag');
    check4c($params['httponly'] && $params['samesite'] === 'Lax' && $params['path'] === '/' && $params['domain'] === '', 'cookie scope and flags');
    check4c($strict === '1' && $onlyCookies === '1', 'session fixation policy');
}
$root = dirname(__DIR__);
$htaccess = file_get_contents($root . '/.htaccess');
foreach (['X-Content-Type-Options', 'X-Frame-Options', 'Referrer-Policy', 'Permissions-Policy', 'Content-Security-Policy-Report-Only'] as $header) {
    check4c(substr_count($htaccess, 'Header always set ' . $header . ' ') === 1, 'one authoritative ' . $header);
    check4c(str_contains($htaccess, 'Header onsuccess unset ' . $header), 'clear alternate header table');
}
check4c(!str_contains($htaccess, 'unsafe-inline') && !str_contains($htaccess, 'unsafe-eval'), 'no broad CSP bypass');
check4c(!preg_match('/Header\s+.*(?:Strict-Transport-Security|set Content-Security-Policy\s)/i', $htaccess), 'no HSTS or enforcing CSP');
check4c(str_contains($htaccess, "frame-ancestors 'self'") && str_contains($htaccess, "form-action 'self'"), 'framing and forms');
foreach (['admin', 'barber', 'manager'] as $panel) {
    $rules = file_get_contents($root . '/' . $panel . '/.htaccess');
    check4c(!str_contains($rules, 'X-Frame-Options') && !str_contains($rules, 'X-Content-Type-Options'), 'panel inherits security headers');
}
echo "Stage 4C: $checks checks passed.\n";
