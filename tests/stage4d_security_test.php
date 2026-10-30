<?php
// CLI only. Configuration tests and a fake PDO; never loads a real driver.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require_once __DIR__ . '/../data/database_config.php';
if (isset($argv[1])) {
    $source = file_get_contents(__DIR__ . '/../data/database.php');
    $source = str_replace(['<?php', '__DIR__', 'new PDO('], ['', var_export(dirname(__DIR__) . '/data', true), 'new Stage4DFakePDO('], $source);
    class Stage4DFakePDO {
        public function __construct($dsn, $user, $password) {
            if ($GLOBALS['argv'][1] === 'failure') throw new PDOException('SENSITIVE_DRIVER_DETAIL');
            if (!str_contains($dsn, 'charset=utf8mb4')) throw new LogicException('charset');
        }
        public function setAttribute($key, $value) {
            if ($key !== PDO::ATTR_ERRMODE || $value !== PDO::ERRMODE_EXCEPTION) throw new LogicException('error mode');
        }
    }
    eval($source);
    if ($pdo !== $db || !($pdo instanceof Stage4DFakePDO)) throw new LogicException('connection aliases');
    echo 'FAKE_CONNECTION_OK';
    exit;
}
$checks = 0;
function check4d(bool $ok, string $label): void {
    global $checks;
    if (!$ok) throw new RuntimeException($label);
    $checks++;
}
$local = ['host'=>'localhost', 'username'=>'test_user', 'password'=>'', 'dbname'=>'test_db'];
$missing = fn($key) => false;
check4d(databaseSettings($local, $missing) === $local, 'legacy settings including empty password');
$env = ['DB_HOST'=>'127.0.0.1', 'DB_USER'=>'app_user', 'DB_PASSWORD'=>'test_only', 'DB_NAME'=>'app_db'];
check4d(databaseSettings([], fn($key) => $env[$key] ?? false)['username'] === 'app_user', 'environment only');
check4d(databaseSettings($local, fn($key) => $key === 'DB_USER' ? 'override_user' : false)['username'] === 'override_user', 'environment precedence');
foreach ([[], array_replace($local, ['host'=>'localhost;dbname=other']), array_replace($local, ['dbname'=>"bad\nname"]), array_replace($local, ['username'=>''])] as $bad) {
    $rejected = false;
    try { databaseSettings($bad, $missing); } catch (RuntimeException $e) { $rejected = true; }
    check4d($rejected, 'invalid configuration fails closed');
}
// Fake PDO subprocess exercises actual bootstrap, including its exit path.
foreach (['success', 'failure'] as $mode) {
    $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' ' . $mode . ' 2>&1';
    $output = shell_exec($command);
    check4d(!str_contains($output, 'SENSITIVE_DRIVER_DETAIL'), 'no driver detail in response or log');
    check4d(str_contains($output, $mode === 'success' ? 'FAKE_CONNECTION_OK' : 'Veritabani baglantisi kurulamadi.'), 'bootstrap ' . $mode);
}
$root = dirname(__DIR__);
check4d(str_contains(file_get_contents($root . '/data/.htaccess'), 'Require all denied'), 'internal files denied');
check4d(str_contains(file_get_contents($root . '/.htaccess'), 'FilesMatch'), 'secret export denial');
foreach (array_merge(glob($root . '/isletme/*.php'), glob($root . '/musteri/*.php'), [$root . '/randevu_olustur.php']) as $path) {
    check4d(!str_contains(file_get_contents($path), '$e instanceof RuntimeException ?'), 'PDO exceptions excluded from user messages');
}
echo "Stage 4D: $checks checks passed.\n";
