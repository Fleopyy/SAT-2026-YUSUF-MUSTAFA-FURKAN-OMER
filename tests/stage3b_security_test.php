<?php
// Isolated execution of production authorization and handlers; no real DB or files changed.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require_once __DIR__ . '/../data/owned_media.php';
class Stage3Database {
    public array $permissions = [], $ids = [7], $queries = [];
    public string $scope = 'belirli_isletmeler';
    public int $writes = 0;
    public string $status = 'pending';
    public int $affected = 1;
    public function prepare(string $sql): Stage3Statement { return new Stage3Statement($this, $sql); }
    public function query(string $sql): Stage3Statement { $s = $this->prepare($sql); $s->execute([]); return $s; }
    public function beginTransaction(): void { $this->writes++; }
    public function commit(): void {}
    public function inTransaction(): bool { return false; }
    public function lastInsertId(): int { return 1; }
}
class Stage3Statement {
    private array $params = [];
    public function __construct(private Stage3Database $db, private string $sql) {}
    public function execute(array $params = []): void {
        $this->params = $params;
        $this->db->queries[] = [$this->sql, $params];
        if (preg_match('/^\s*(INSERT|UPDATE|DELETE)/i', $this->sql)) $this->db->writes++;
    }
    public function rowCount(): int { return $this->db->affected; }
    public function fetchColumn(): mixed {
        if (str_contains($this->sql, 'SELECT kapsam_turu')) return $this->db->scope;
        if (str_contains($this->sql, 'yonetici_izinleri')) return (int)in_array($this->params[1], $this->db->permissions, true);
        return 0;
    }
    public function fetch(int $mode = 0): array {
        if (str_contains($this->sql, 'yoneticiler')) return ['id'=>1, 'rol'=>'manager', 'aktif'=>1];
        return ['id'=>7, 'isletme_id'=>7, 'isletme_adi'=>'Test', 'isletme_sahibi'=>'Test', 'eposta'=>'test@example.invalid', 'durum'=>$this->db->status, 'fotograf_yolu'=>'tests/nonexistent-stage3b-photo'];
    }
    public function fetchAll(int $mode = 0): array {
        return str_contains($this->sql, 'yonetici_isletme_kapsami') ? $this->db->ids : [];
    }
}
$pdo = new Stage3Database();
$_SESSION = ['manager_id'=>1, 'manager_role'=>'manager'];
$auth = file_get_contents(__DIR__ . '/../admin/includes/auth.php');
$auth = preg_replace('/^require_once .*;\s*$/m', '', $auth);
eval('?>' . $auth);
function verifyCsrfToken(): bool { return true; }
function generateCsrfToken(): string { return 'test-token'; }

// Child processes allow the real handlers to exit normally and expose side effects.
if (($argv[1] ?? '') === 'handler') {
    [$unused, $mode, $handler, $action, $permission, $scope] = $argv;
    $pdo->permissions = explode(',', $permission);
    if ($handler === 'application' && $action === 'suspend') $pdo->status = 'active';
    if ($handler === 'business') $pdo->permissions[] = 'businesses.view';
    $pdo->ids = $scope === 'allowed' ? [7] : [];
    $pdo->status = $argv[6] ?? $pdo->status;
    $pdo->affected = (int)($argv[7] ?? 1);
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
    $_POST = ['action'=>$action, 'business_id'=>7, 'photo_id'=>1, 'isletme_id'=>999];
    if ($handler === 'business') $_POST += ['isletme_adi'=>'Test', 'isletme_sahibi'=>'Test', 'telefon'=>'555', 'eposta'=>'test@example.invalid', 'durum'=>'active'];
    register_shutdown_function(function () use ($pdo) {
        echo "\nRESULT:" . json_encode(['status'=>http_response_code(), 'writes'=>$pdo->writes, 'queries'=>$pdo->queries]);
    });
    $code = file_get_contents(__DIR__ . '/../admin/actions/' . $handler . '_action.php');
    $code = preg_replace('/^require_once .*;\s*$/m', '', $code);
    eval('?>' . $code);
    exit;
}
$checks = 0; $failures = [];
function check3(bool $ok, string $name): void {
    global $checks, $failures; $checks++; if (!$ok) $failures[] = $name;
}
foreach (['business_photos.view', 'business_photos.upload', 'media.manage', 'content.view', 'content.manage', 'settings.view', 'applications.approve'] as $grant) {
    $pdo->permissions = [$grant];
    foreach (['business_photos.delete', 'settings.edit', 'businesses.approve'] as $target) {
        check3(!hasPermission(1, $target, true), "$grant cannot grant $target");
    }
}
$pdo->permissions = ['business_photos.delete', 'settings.edit'];
check3(hasPermission(1, 'business_photos.delete', true) && hasPermission(1, 'settings.edit', true), 'explicit grants work');
foreach ([['tum_isletmeler', [], null], ['belirli_isletmeler', [], []], ['belirli_isletmeler', ['7'], ['7']]] as [$type, $ids, $expected]) {
    $pdo->scope = $type; $pdo->ids = $ids;
    check3(getManagerAllowedBusinessIds(1) === $expected, 'scope null/empty/selected preserved');
    $params = []; $sql = currentBusinessScopeSql('i.id', $params);
    check3($sql === ($expected === null ? '1=1' : ($ids === [] ? '1=0' : 'i.id IN (?)')), 'scope SQL');
    check3(currentBusinessScopeAllows(7) === ($expected === null || $ids !== []), 'scope independent of businesses.view');
    check3(currentBusinessScopeAllows(8) === ($expected === null), 'other business denied unless all');
}
foreach (['approve'=>'businesses.approve', 'reject'=>'businesses.reject', 'suspend'=>'businesses.suspend'] as $action=>$permission) {
    foreach (['allowed', 'denied'] as $scope) {
        $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . " handler application $action $permission $scope";
        $out = shell_exec($cmd);
        preg_match('/RESULT:(.*)/', $out ?? '', $match); $result = json_decode($match[1] ?? '', true);
        check3(is_array($result) && ($scope === 'denied' ? ($result['status'] === 403 && $result['writes'] === 0) : $result['writes'] > 0), "application $action $scope side effects");
    }
}
foreach ([['business_photos.view','allowed',false], ['business_photos.upload','allowed',false], ['business_photos.delete','denied',false], ['business_photos.delete','allowed',true]] as [$permission,$scope,$allowed]) {
    $out = shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . " handler media delete $permission $scope");
    preg_match('/RESULT:(.*)/', $out ?? '', $match); $result = json_decode($match[1] ?? '', true);
    // Authorized request still cannot delete the double's non-upload/missing file.
    check3(is_array($result) && ($allowed ? $result['writes'] === 0 : ($result['status'] === 403 && $result['writes'] === 0)), "media $permission $scope verifies stored business and rejects invalid file");
}
foreach ([['settings.view',false], ['content.manage',false], ['settings.edit',true]] as [$permission,$allowed]) {
    $out = shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . " handler settings save_settings $permission allowed");
    preg_match('/RESULT:(.*)/', $out ?? '', $match); $result = json_decode($match[1] ?? '', true);
    check3(is_array($result) && ($allowed ? $result['writes'] > 0 : ($result['status'] === 403 && $result['writes'] === 0)), "settings $permission side effects");
}
// Run page query construction up to the presentation include, including counts and details.
$pdo->permissions = ['businesses.view', 'businesses.view_details', 'business_photos.view', 'settings.view'];
$_GET = ['detail'=>7];
foreach (['applications', 'media'] as $page) {
    foreach ([[], [7]] as $scopeIds) {
        $pdo->scope = 'belirli_isletmeler'; $pdo->ids = $scopeIds; $pdo->queries = [];
        $code = file_get_contents(__DIR__ . '/../admin/pages/' . $page . '.php');
        $code = preg_replace('/^require_once .*;\s*$/m', '', $code);
        $code = substr($code, 0, strpos($code, "include __DIR__ . '/../includes/header.php';"));
        eval('?>' . $code);
        $selects = array_filter($pdo->queries, fn($q) => str_contains($q[0], 'FROM isletmeler') || str_contains($q[0], 'FROM isletme_fotograflari'));
        check3(count($selects) >= ($page === 'applications' ? 6 : 1), "$page queries executed");
        foreach ($selects as [$query,$bindings]) {
            if (str_contains($query, 'LEFT JOIN yoneticiler')) {
                check3($scopeIds !== [] && $bindings === [7], 'application detail scope');
            } else {
                check3(str_contains($query, $scopeIds === [] ? '1=0' : ' IN (?)'), "$page list/count scope predicate");
                if ($scopeIds !== []) check3(($bindings[0] ?? null) === 7, "$page scope bound to query");
            }
        }
        if ($page === 'applications') check3(($detailApp !== null) === ($scopeIds !== []), 'detail denied for empty scope');
    }
}
$pdo->permissions = ['businesses.view', 'business_photos.view', 'settings.view'];
$menu = getAdminSidebarItems();
$menuByKey = array_column($menu, null, 'key');
check3(($menuByKey['media']['permission'] ?? '') === 'business_photos.view', 'media menu matches page permission');
check3(($menuByKey['content']['permission'] ?? '') === 'settings.view', 'settings menu matches page permission');
$sidebar = file_get_contents(__DIR__ . '/../admin/includes/sidebar.php');
check3(str_contains($sidebar, 'currentBusinessScopeSql($badgeColumn, $badgeParams)') && str_contains($sidebar, '$badgeStmt->execute($badgeParams)'), 'badge uses bound scope predicate');
$badgeStart = strpos($sidebar, '$badgeParams = [];');
$badgeEnd = strpos($sidebar, '$badgeCount = (int)$badgeStmt->fetchColumn();', $badgeStart) + strlen('$badgeCount = (int)$badgeStmt->fetchColumn();');
foreach (['applications'=>'id', 'appointments'=>'isletme_id'] as $key=>$column) {
    foreach ([[], [7]] as $ids) {
        $pdo->ids = $ids; $pdo->queries = [];
        $item = ['key'=>$key, 'badge_query'=> $key === 'applications' ? "SELECT COUNT(*) FROM isletmeler WHERE durum = 'pending'" : 'SELECT COUNT(*) FROM randevular WHERE DATE(randevu_zamani) = CURDATE()'];
        eval(substr($sidebar, $badgeStart, $badgeEnd-$badgeStart));
        [$query,$bindings] = $pdo->queries[count($pdo->queries)-1];
        check3(str_contains($query, $ids === [] ? 'AND 1=0' : "AND $column IN (?)") && $bindings === $ids, "$key badge actual scope query");
    }
}
foreach ([['edit','businesses.edit','allowed',true], ['edit','businesses.edit,businesses.approve','allowed',true], ['edit','businesses.edit,businesses.approve','denied',false], ['toggle_status','businesses.suspend','allowed',false], ['toggle_status','businesses.approve','allowed',false]] as [$action,$permission,$scope,$allowed]) {
    $out = shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . " handler business $action $permission $scope");
    preg_match('/RESULT:(.*)/', $out ?? '', $match); $result = json_decode($match[1] ?? '', true);
    check3(is_array($result) && ($allowed ? $result['writes'] > 0 : (in_array($result['status'], [403, 409], true) && $result['writes'] === 0)), "business $action $permission $scope");
}
foreach ($failures as $failure) echo "FAIL: $failure\n";
echo ($checks-count($failures)) . "/$checks checks passed\n";
exit($failures ? 1 : 0);
