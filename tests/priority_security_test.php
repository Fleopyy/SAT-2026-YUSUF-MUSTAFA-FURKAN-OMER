<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require_once __DIR__ . '/../data/app_url.php';
require_once __DIR__ . '/../data/mail_service.php';
$checks = 0;
function verifyPriority(bool $ok, string $label): void {
    global $checks;
    if (!$ok) throw new RuntimeException($label);
    $checks++;
}
$smtp = createMailer(mailSettings([], static fn(string $name) => false));
verifyPriority($smtp->Host === 'smtp.gmail.com' && $smtp->Port === 587 && $smtp->SMTPAuth, 'SMTP defaults preserved without sending');
verifyPriority($smtp->SMTPOptions['ssl']['verify_peer'] && $smtp->SMTPOptions['ssl']['verify_peer_name'] && !$smtp->SMTPOptions['ssl']['allow_self_signed'], 'SMTP certificate validation preserved');
$template = renderEmailTemplate('Test', '<p>Test</p>', 'Panel', 'http://localhost:8080/app/musteri/randevularim.php');
verifyPriority(str_contains($template, 'http://localhost:8080/app/musteri/randevularim.php'), 'notification template preserves local port and subdirectory');
$previous = getenv('APP_BASE_URL');
$previousEnv = getenv('APP_ENV');
$previousServer = $_SERVER;
try {
    putenv('APP_ENV=development');
    putenv('APP_BASE_URL');
    $_SERVER = ['HTTP_HOST'=>'attacker.invalid', 'HTTP_X_FORWARDED_HOST'=>'attacker.invalid', 'HTTPS'=>'off'];
    verifyPriority(getSiteBaseUrl() === 'http://localhost', 'host poisoning blocked in local CLI development');
    putenv('APP_ENV');
    verifyPriority(getSiteBaseUrl() === 'http://localhost', 'unset environment defaults to local development without a purchased domain');
    putenv('APP_ENV=development');
    $_SERVER += ['SERVER_ADDR'=>'127.0.0.1', 'REMOTE_ADDR'=>'::1', 'SERVER_PORT'=>'8080'];
    verifyPriority(getSiteBaseUrl() === 'http://localhost:8080', 'XAMPP loopback with custom port');
    $_SERVER['REMOTE_ADDR'] = '192.0.2.1';
    $rejected = false;
    try { getSiteBaseUrl(); } catch (RuntimeException $e) { $rejected = true; }
    verifyPriority($rejected, 'remote client cannot select localhost');
    foreach (['https://example.com/app/'=>'https://example.com/app', 'http://localhost:8000'=>'http://localhost:8000'] as $input=>$expected) {
        putenv('APP_BASE_URL=' . $input);
        verifyPriority(getSiteBaseUrl() === $expected, 'trusted deployment URL');
    }
    foreach (['http://example.com', 'https://user:pass@example.com', 'https://example.com/?x=1', "https://example.com/\r\n", 'javascript:alert(1)'] as $input) {
        putenv('APP_BASE_URL=' . $input);
        $rejected = false;
        try { getSiteBaseUrl(); } catch (RuntimeException $e) { $rejected = true; }
        verifyPriority($rejected, 'invalid deployment URL fails closed');
    }
    putenv('APP_BASE_URL');
    $_SERVER = ['SERVER_ADDR'=>'::1', 'REMOTE_ADDR'=>'127.0.0.1', 'SERVER_PORT'=>'8081', 'DOCUMENT_ROOT'=>dirname(__DIR__, 2), 'HTTP_HOST'=>'evil.invalid', 'HTTPS'=>'on'];
    verifyPriority(getSiteBaseUrl() === 'http://localhost:8081/' . rawurlencode(basename(dirname(__DIR__))), 'local subdirectory and port preserved without Host or TLS inference');
    $_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
    $_SERVER['SERVER_PORT'] = '80';
    verifyPriority(getSiteBaseUrl() === 'http://localhost', 'XAMPP document root uses localhost without a mount path or default port');
    $_SERVER['SERVER_PORT'] = 'invalid';
    $rejected = false;
    try { getSiteBaseUrl(); } catch (RuntimeException $e) { $rejected = true; }
    verifyPriority($rejected, 'invalid local port rejected');
    putenv('APP_ENV=production');
    foreach ([null, '', 'http://localhost', 'https://localhost', 'https://127.0.0.1', 'http://example.com', 'https://[::1]'] as $input) {
        putenv($input === null ? 'APP_BASE_URL' : 'APP_BASE_URL=' . $input);
        $rejected = false;
        try { getSiteBaseUrl(); } catch (RuntimeException $e) { $rejected = true; }
        verifyPriority($rejected, 'production requires explicit nonlocal HTTPS domain');
    }
    putenv('APP_BASE_URL=https://example.com:8443/application/');
    verifyPriority(getSiteBaseUrl() === 'https://example.com:8443/application', 'production preserves configured port and subdirectory');
    putenv('APP_ENV=typo');
    $rejected = false;
    try { getSiteBaseUrl(); } catch (RuntimeException $e) { $rejected = true; }
    verifyPriority($rejected, 'unknown environment fails closed');
} finally {
    putenv($previous === false ? 'APP_BASE_URL' : 'APP_BASE_URL=' . $previous);
    putenv($previousEnv === false ? 'APP_ENV' : 'APP_ENV=' . $previousEnv);
    $_SERVER = $previousServer;
}

class PriorityExit extends Exception {}
$events = [];
function isTrustedDevice(...$args) { return $GLOBALS['trusted']; }
function sendBarber2faEmail(...$args) { $GLOBALS['events'][] = 'mail'; return true; }
function recordLoginAttempt(...$args) {}
function renewAuthenticatedSession(...$args) { $GLOBALS['events'][] = 'renew'; }
function priorityHeader($value) { $GLOBALS['events'][] = $value; }
class PriorityDB {
    public bool $fail = false;
    public function prepare($sql) { return $this; }
    public function execute($args) {
        if ($this->fail) throw new RuntimeException('fake database failure');
        $GLOBALS['events'][] = ['db', $args]; return true;
    }
}
$pdo = new PriorityDB();
$root = dirname(__DIR__);
foreach (['data/mail_config.php', 'data/customer_auth_service.php'] as $file) {
    $source = file_get_contents($root . '/' . $file);
    verifyPriority(str_contains($source, "require_once __DIR__ . '/app_url.php';") && !str_contains($source, 'HTTP_HOST'), 'mail services use trusted URL helper');
}
$service = file_get_contents($root . '/data/two_factor_service.php');
$start = strpos($service, 'function evaluateBarberLoginRisk(');
$end = strpos($service, 'function sendLoginSecurityAlert(', $start);
eval(substr($service, $start, $end - $start));
$shared = substr($service, strpos($service, 'function completeBarberPasswordLogin('));
eval(str_replace(['header(', 'exit;'], ['priorityHeader(', 'throw new PriorityExit();'], $shared));
$barber = ['id'=>12, 'isletme_adi'=>'Test', 'eposta'=>'test@example.invalid', 'sifre_hash'=>'fake', 'son_giris'=>date('Y-m-d H:i:s')];
foreach (['/isletme/', '/barber/dashboard'] as $destination) {
    foreach ([[false, $barber['son_giris'], true], [true, null, true], [true, date('Y-m-d H:i:s', time()-15*86400), true], [true, $barber['son_giris'], false]] as [$trusted, $last, $challenge]) {
        $_SESSION = []; $events = []; $barber['son_giris'] = $last;
        try { completeBarberPasswordLogin($barber, 'test', $destination); } catch (PriorityExit $e) {}
        verifyPriority(isset($_SESSION['2fa_pending']) === $challenge, 'same MFA decision on both entry points');
        verifyPriority(isset($_SESSION['barber_id']) === !$challenge, 'no authenticated session before MFA');
        verifyPriority(in_array('mail', $events, true) === $challenge, 'challenge delivery stub');
        verifyPriority(in_array('renew', $events, true) === !$challenge, 'session renewed only on permitted login');
        verifyPriority(end($events) === 'Location: ' . ($challenge ? '/isletme/giris.php?step=2fa' : $destination), 'fixed redirect');
        if ($challenge) verifyPriority($_SESSION['2fa_pending']['destination'] === $destination && $_SESSION['2fa_pending']['expires'] > time(), 'pending MFA destination and expiry');
    }
}
foreach (['isletme/giris.php', 'admin/login.php'] as $file) {
    verifyPriority(str_contains(file_get_contents($root . '/' . $file), 'completeBarberPasswordLogin('), 'entry point uses shared MFA');
}

function requirePermission($permission) { if (!$GLOBALS['permitted']) throw new PriorityExit('permission'); return []; }
function verifyCsrfToken() { return $GLOBALS['csrf']; }
function canAccessBusiness($id) { return $GLOBALS['access']; }
function logAdminAction(...$args) {}
function storeUploadImage($file, $max, $directory) {
    $GLOBALS['events'][] = ['store', $max, $directory];
    if ($GLOBALS['rejectImage']) throw new RuntimeException('fake invalid image/quota');
    return $directory . '/fixture.png';
}
function deleteOwnedMedia($path, $id) { $GLOBALS['events'][] = ['delete', $path, $id]; }
$action = file_get_contents($root . '/admin/actions/media_upload.php');
$action = preg_replace('/^require_once .*;\R/m', '', $action);
$action = str_replace(['<?php', '?>', 'header(', "exit('Forbidden');", 'exit;'], ['', '', 'priorityHeader(', "throw new PriorityExit('access');", 'throw new PriorityExit();'], $action);
foreach (['permission', 'csrf', 'access', 'success', 'quota', 'database'] as $case) {
    $permitted = $case !== 'permission'; $csrf = $case !== 'csrf'; $access = $case !== 'access';
    $rejectImage = $case === 'quota'; $pdo->fail = $case === 'database';
    $_SESSION = []; $events = []; $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = ['business_id'=>12];
    $_FILES = ['photos'=>['error'=>[UPLOAD_ERR_OK], 'tmp_name'=>['fake'], 'name'=>['fixture.png']]];
    try { eval($action); } catch (PriorityExit $e) {}
    $stores = array_values(array_filter($events, fn($e)=>is_array($e) && $e[0] === 'store'));
    $deletes = array_values(array_filter($events, fn($e)=>is_array($e) && $e[0] === 'delete'));
    verifyPriority(count($stores) === (in_array($case, ['success', 'quota', 'database'], true) ? 1 : 0), 'upload authorization/CSRF/ownership gates');
    if ($stores) verifyPriority($stores[0] === ['store', 2097152, 'uploads/isletme/12/gallery'], 'shared quota and owned gallery destination');
    verifyPriority(count($deletes) === ($case === 'database' ? 1 : 0), 'failed DB insert cleans up only newly stored file');
    verifyPriority(isset($_SESSION['flash_success']) === ($case === 'success'), 'success only after file and DB write');
}
echo "Priority security: $checks checks passed (fake DB/mail; no live application side effects).\n";
