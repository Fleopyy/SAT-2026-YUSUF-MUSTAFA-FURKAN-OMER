<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}
$failures = [];
$checks = 0;
function check(bool $condition, string $name): void {
    global $checks, $failures;
    $checks++;
    if (!$condition) $failures[] = $name;
}
$source = file_get_contents(__DIR__ . '/../data/security.php');
$connection = "require_once __DIR__ . '/database.php';";
check(substr_count($source, $connection) === 1, 'connection isolation');
$source = str_replace($connection, '', $source);
$source = str_replace("require_once __DIR__ . '/session.php';", 'require_once ' . var_export(dirname(__DIR__) . '/data/session.php', true) . ';', $source);
ini_set('session.save_path', sys_get_temp_dir());
class SecurityStatement {
    public function __construct(private SecurityDatabase $db, private string $sql) {}
    public function execute(array $params): void { $this->db->params = $params; }
    public function fetchColumn(): mixed {
        return str_contains($this->sql, 'COUNT(*)') ? $this->db->count : $this->db->hash;
    }
}
class SecurityDatabase {
    public int $count = 0;
    public string $hash = '';
    public array $params = [];
    public string $sql = '';
    public function prepare(string $sql): SecurityStatement {
        $this->sql = $sql;
        return new SecurityStatement($this, $sql);
    }
}
$pdo = new SecurityDatabase();
$_SERVER['HTTPS'] = 'on';
eval('?>' . $source);
check(ini_get('session.use_strict_mode') === '1', 'strict session mode');
$cookie = session_get_cookie_params();
check($cookie['secure'] && $cookie['httponly'] && $cookie['samesite'] === 'Lax', 'session cookie policy');
check(!verifyCsrfToken('invalid'), 'missing CSRF rejected');
$token = generateCsrfToken();
check(verifyCsrfToken($token), 'valid CSRF accepted');
check(!verifyCsrfToken('wrong') && !verifyCsrfToken(['invalid']), 'malformed CSRF rejected');
$oldHash = password_hash('test-password-one', PASSWORD_DEFAULT);
$newHash = password_hash('test-password-two', PASSWORD_DEFAULT);
check(password_verify('test-password-one', $oldHash), 'real password accepted');
check(!password_verify($oldHash, $oldHash), 'hash cannot authenticate as password');
check(!password_verify('legacy-plaintext', 'legacy-plaintext'), 'legacy plaintext rejected');
check(!validNewPassword('short') && validNewPassword(str_repeat('x', 10)) && validNewPassword(str_repeat('x', 72)) && !validNewPassword(str_repeat('x', 73)), 'password length bounds');
foreach (['user', 'barber', 'admin', 'manager'] as $kind) {
    $_SESSION = [];
    $_SESSION['admin_id'] = 99;
    generateCsrfToken();
    $before = session_id();
    renewAuthenticatedSession($kind, $oldHash);
    check(session_id() !== $before, $kind . ' login rotates session id');
    check(!isset($_SESSION['admin_id']) && !isset($_SESSION['csrf_token']), $kind . ' clears previous identity and token');
    $_SESSION[$kind . '_id'] = 1;
    $pdo->hash = $oldHash;
    validatePasswordSessions();
    check(isset($_SESSION[$kind . '_id']), $kind . ' current session retained');
    $secondDevice = $_SESSION;
    $pdo->hash = $newHash;
    bindPasswordSession($kind, $newHash);
    validatePasswordSessions();
    check(isset($_SESSION[$kind . '_id']), $kind . ' password changing session retained');
    $_SESSION = $secondDevice;
    validatePasswordSessions();
    check(!isset($_SESSION[$kind . '_id']), $kind . ' second device revoked');
    $_SESSION[$kind . '_id'] = 1;
    unset($_SESSION[$kind . '_password_version']);
    validatePasswordSessions();
    check(!isset($_SESSION[$kind . '_id']), $kind . ' unversioned session rejected');
}
$_POST = ['eposta' => ' Example@Invalid.test '];
$pdo->count = 4;
check(checkLoginRateLimit('192.0.2.1'), 'attempt below threshold allowed');
check($pdo->params === ['192.0.2.1', 'example@invalid.test', 15], 'account normalized and positive default window');
check(str_contains($pdo->sql, 'ip_adresi = ? OR LOWER(eposta) = ?'), 'IP and account rate keys');
$pdo->count = 5;
check(!checkLoginRateLimit('192.0.2.2'), 'threshold blocked');
checkLoginRateLimit('192.0.2.2', 5, 0);
check($pdo->params[2] === 1, 'zero window clamped');
recordLoginAttempt('192.0.2.1', 'example@invalid.test', false);
check($pdo->params[2] === 0, 'failed attempt recorded');
recordLoginAttempt('192.0.2.1', 'example@invalid.test', true);
check($pdo->params[2] === 1, 'successful attempt recorded');
$login = file_get_contents(__DIR__ . '/../login.php');
preg_match_all('/<form\b[^>]*class="auth-form"[^>]*>(.*?)<\/form>/s', $login, $forms);
check(count($forms[1]) === 4, 'all four auth forms present');
foreach ($forms[1] as $form) check(str_contains($form, 'name="csrf_token"'), 'auth form CSRF field');
$auth = file_get_contents(__DIR__ . '/../admin/includes/auth.php');
check(!str_contains($source, 'X-Robots-Tag') && str_contains($auth, 'X-Robots-Tag'), 'panel headers remain separate');
session_destroy();
foreach ($failures as $failure) echo "FAIL: $failure\n";
echo ($checks - count($failures)) . "/$checks checks passed\n";
exit($failures ? 1 : 0);
