<?php
// CLI only; temporary files and fake DB. Never load application/database bootstrap.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require_once __DIR__ . '/../data/owned_media.php';
$checks = 0;
function check3d(bool $ok, string $name): void {
    global $checks;
    $checks++;
    if (!$ok) throw new RuntimeException($name);
}
class MediaTestDatabase {
    public bool $active = false, $committed = false, $rolledBack = false, $failCommit = false;
    public function beginTransaction(): bool { $this->active = true; return true; }
    public function commit(): bool {
        if ($this->failCommit) throw new RuntimeException('Simulated commit failure');
        $this->active = false; $this->committed = true; return true;
    }
    public function inTransaction(): bool { return $this->active; }
    public function rollBack(): bool { $this->active = false; $this->rolledBack = true; return true; }
}
// Isolated subprocess for production action exits and authorization ordering.
if (($argv[1] ?? '') === 'settings') {
    $grants = explode(',', $argv[2]);
    function requirePermission($permission) {
        global $grants;
        if (!in_array($permission, $grants, true)) { echo 'DENIED'; exit; }
        return ['id'=>1];
    }
    function verifyCsrfToken() { return true; }
    function logAdminAction(...$args) {}
    function queueEmail(...$args) { throw new RuntimeException('Queue must not be called'); }
    $pdo = new class {
        public array $writes = [];
        public function prepare($sql) {
            return new class($this) {
                public function __construct(private $db) {}
                public function execute($params = []) {
                    if (str_starts_with($params[0] ?? '', 'smtp_')) throw new RuntimeException('SMTP overwrite');
                    $this->db->writes[] = $params;
                }
            };
        }
        public function query($sql) { throw new RuntimeException('Unexpected query'); }
    };
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = ['action'=>$argv[3] ?? 'test_smtp', 'test_email'=>$argv[4] ?? 'test@example.invalid',
        'smtp_host'=>'127.0.0.1', 'smtp_port'=>'0', 'smtp_sifreleme'=>'file', 'smtp_sifre'=>$argv[5] ?? 'replacement'];
    $_SESSION = [];
    register_shutdown_function(function () use ($pdo) { echo json_encode([$_SESSION, $pdo->writes], JSON_UNESCAPED_UNICODE); });
    $source = file_get_contents(__DIR__.'/../admin/actions/settings_action.php');
    $source = preg_replace('/^require_once .*;\s*$/m', '', $source);
    eval('?>'.$source);
    exit;
}
$root = __DIR__ . '/.sec15-' . bin2hex(random_bytes(10));
mkdir($root . '/uploads/isletme/7/gallery', 0700, true);
mkdir($root . '/uploads/isletme/8', 0700, true);
$root = realpath($root);
$relative = 'uploads/isletme/7/gallery/photo.jpg';
$file = $root . '/' . $relative;
try {
    file_put_contents($file, 'temporary test content');
    foreach ([$relative, '/'.$relative, str_replace('/', '\\', $relative)] as $path) {
        check3d(ownedMediaPath($path, 7, $root) === realpath($file), 'legacy path accepted');
    }
    foreach (['../photo.jpg', 'uploads/isletme/8/photo.jpg', 'uploads/isletme/70/a.jpg',
        'uploads/isletme/7/../8/a.jpg', 'uploads/isletme/7/gallery/missing.jpg',
        'C:\\Windows\\file', '//server/share', 'https://example.invalid/a',
        'uploads/isletme/7/gallery/photo.jpg:stream', "uploads/isletme/7/\0photo"] as $path) {
        $rejected = false;
        try { ownedMediaPath($path, 7, $root); } catch (RuntimeException $e) { $rejected = true; }
        check3d($rejected && file_exists($file), 'unsafe path rejected: '.json_encode($path));
    }
    foreach (['change', 'commit'] as $failure) {
        $db = new MediaTestDatabase();
        $db->failCommit = $failure === 'commit';
        try {
            deleteOwnedMedia($relative, 7, $db, function () use ($failure) {
                if ($failure === 'change') throw new RuntimeException('Simulated SQL failure');
            }, $root);
            throw new LogicException('Failure expected');
        } catch (RuntimeException $e) { check3d(str_starts_with($e->getMessage(), 'Simulated'), 'expected failure was exercised'); }
        check3d($db->rolledBack && !$db->committed && file_get_contents($file) === 'temporary test content', 'rollback restores file: '.$failure);
        check3d(glob(dirname($file).'/.delete-*') === [], 'rollback clears backup');
    }
    $db = new MediaTestDatabase();
    deleteOwnedMedia($relative, 7, $db, function () {}, $root);
    check3d($db->committed && !file_exists($file), 'successful deletion');
    check3d(glob(dirname($file).'/.delete-*') === [], 'success clears backup');
    file_put_contents($file, 'link test');
    $link = dirname($file).'/link.jpg';
    $linkWarning = '';
    set_error_handler(function ($severity, $message) use (&$linkWarning) { $linkWarning = $message; return true; });
    try { $linked = symlink($file, $link); } finally { restore_error_handler(); }
    if ($linked) {
        $rejected = false;
        try { ownedMediaPath('uploads/isletme/7/gallery/link.jpg', 7, $root); } catch (RuntimeException $e) { $rejected = true; }
        check3d($rejected && is_file($file), 'symlink rejected');
        unlink($link);
    } else { echo "SKIP symlink creation unavailable: $linkWarning\n"; }
    foreach (['settings.view', 'settings.edit', ''] as $permission) {
        $out = shell_exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' settings '.escapeshellarg($permission));
        check3d($permission === 'settings.edit' ? str_contains($out, 'flash_error') : str_contains($out, 'DENIED'), 'SMTP permission and closed test: '.$permission);
    }
    $source = file_get_contents(__DIR__.'/../admin/actions/settings_action.php');
    check3d(!preg_match('/\b(fsockopen|stream_socket_client|queueEmail)\s*\(/', $source), 'no SMTP network or queue bypass');
    check3d(str_contains($source, "!str_starts_with(\$key, 'smtp_')"), 'all saved SMTP values preserved');
    foreach (['', 'new-password'] as $password) {
        $out = shell_exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' settings settings.edit save_settings test@example.invalid '.escapeshellarg($password));
        check3d(str_contains($out, 'flash_success') && !str_contains($out, 'SMTP overwrite'), 'save leaves SMTP credentials intact');
    }
    foreach (['invalid', '', "bad\r\nrecipient@example.invalid"] as $recipient) {
        $out = shell_exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' settings settings.edit test_smtp '.escapeshellarg($recipient));
        check3d(str_contains($out, 'flash_error'), 'invalid SMTP tests remain closed');
    }
    echo "Stage 3D: $checks checks passed.\n";
} finally {
    // Only explicitly named test paths beneath this random temporary root.
    if (is_file($file)) unlink($file);
    foreach (glob(dirname($file).'/.delete-*') as $backup) unlink($backup);
    rmdir($root.'/uploads/isletme/7/gallery');
    rmdir($root.'/uploads/isletme/7'); rmdir($root.'/uploads/isletme/8');
    rmdir($root.'/uploads/isletme'); rmdir($root.'/uploads'); rmdir($root);
}
