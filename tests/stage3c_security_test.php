<?php
// CLI only. Production handlers with doubles, customer SQL with SQLite :memory:.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
$checks = 0;
function verify3c(bool $ok, string $name): void {
    global $checks;
    $checks++;
    if (!$ok) throw new RuntimeException($name);
}
foreach (['business', 'application'] as $handler) {
    $actions = $handler === 'business' ? ['toggle_status', 'edit'] : ['approve', 'reject', 'suspend'];
    foreach ($actions as $action) foreach (['pending','rejected','active','suspended'] as $state) {
        $grant = match($action) {
            'edit'=>'businesses.edit', 'toggle_status'=>$state === 'active' ? 'businesses.suspend' : 'businesses.reactivate',
            default=>'businesses.' . $action
        };
        foreach (['allowed','denied'] as $scope) foreach ([true, false] as $permission) {
            $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__.'/stage3b_security_test.php') . ' handler ' . $handler . ' ' . $action . ' ' . ($permission ? $grant : 'none') . ' ' . $scope . ' ' . $state;
            $out = shell_exec($cmd);
            preg_match('/RESULT:(.*)/', $out ?? '', $m);
            $result = json_decode($m[1] ?? '', true);
            $transition = match($action) {
                'edit'=>true, 'toggle_status'=>in_array($state,['active','suspended'],true),
                'approve'=>in_array($state,['pending','rejected'],true), 'reject'=>$state==='pending', 'suspend'=>$state==='active'
            };
            $allowed = $scope === 'allowed' && $permission && $transition;
            verify3c(is_array($result) && ($allowed ? $result['writes'] > 0 : $result['writes'] === 0), "$handler $action $state $scope permission=$permission");
            if ($action === 'edit' && $allowed) {
                foreach ($result['queries'] as [$sql]) if (str_starts_with($sql,'UPDATE isletmeler')) verify3c(!str_contains($sql,'durum'), 'edit never writes status');
            }
            if ($allowed && $action !== 'edit') {
                foreach ($result['queries'] as [$sql]) if (str_starts_with($sql,'UPDATE isletmeler')) verify3c(str_contains($sql,'AND durum = ?'), 'atomic expected state');
                $race = shell_exec($cmd . ' 0');
                preg_match('/RESULT:(.*)/', $race ?? '', $m);
                $raceResult = json_decode($m[1] ?? '',true);
                verify3c(($raceResult['status'] ?? 0) === 409, 'concurrent state conflict rejected');
                verify3c(count(array_filter($raceResult['queries'], fn($q)=>preg_match('/^\s*(INSERT|UPDATE|DELETE)/i',$q[0]))) === 1, 'no notifications/logs/email after conflict');
            }
        }
    }
}
// Run actual authorization functions with the existing DB double for identity/scope,
// and actual customer SELECT statements against a separate in-memory PDO database.
$bootstrap = file_get_contents(__DIR__.'/stage3b_security_test.php');
$bootstrap = substr($bootstrap,0,strpos($bootstrap,'$checks = 0;'));
$argv = [__FILE__];
eval('?>'.$bootstrap);
$scopeDb = $pdo;
$scopeDb->permissions = ['customers.view'];
$db = new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$db->exec('CREATE TABLE kullanicilar (id INTEGER, ad_soyad TEXT, telefon TEXT, eposta TEXT, sehir TEXT, olusturulma_tarihi TEXT);
CREATE TABLE randevular (id INTEGER, kullanici_id INTEGER, isletme_id INTEGER, hizmet_id INTEGER, calisan_id INTEGER, durum TEXT, randevu_zamani TEXT);
CREATE TABLE isletmeler (id INTEGER, isletme_adi TEXT, sehir TEXT, ilce TEXT);
CREATE TABLE hizmetler (id INTEGER, hizmet_adi TEXT, fiyat INTEGER);
CREATE TABLE calisanlar (id INTEGER, ad_soyad TEXT);');
$db->exec("INSERT INTO kullanicilar VALUES (1,'Shared','555','s@test','CityA','2026'),(2,'Hidden','999','h@test','SecretCity','2026'),(3,'NoAppointment','','','OtherCity','2026');
INSERT INTO randevular VALUES (1,1,7,NULL,NULL,'completed','2026'),(2,1,8,NULL,NULL,'completed','2026'),(3,2,8,NULL,NULL,'pending','2026');");
for ($i=4;$i<=58;$i++) {
    $db->exec("INSERT INTO kullanicilar VALUES ($i,'Visible$i','','','CityA','2026'); INSERT INTO randevular VALUES ($i,$i,7,NULL,NULL,'pending','2026');");
}
$code = file_get_contents(__DIR__.'/../admin/pages/customers.php');
$code = preg_replace('/^require_once .*;\s*$/m','',$code);
$code = substr($code,0,strpos($code,'/*',strpos($code,'$customers = [];'))); // stop before pageUrl declaration
// Keep auth/scope calls on the double; all customer queries use real SQLite.
$code = str_replace('$scopeParams = [];', '$pdo = $scopeDb; $scopeParams = [];', $code);
$code = str_replace('if ($viewId > 0)', '$pdo = $db; if ($viewId > 0)', $code);
foreach ([[7],[],null,[7,8]] as $ids) foreach ([1,2,3] as $id) foreach (['','Hidden','Shared'] as $q) {
    $scopeDb->scope = $ids === null ? 'tum_isletmeler' : 'belirli_isletmeler';
    $scopeDb->ids = $ids ?? [];
    $pdo = $scopeDb;
    $_GET = ['id'=>$id,'q'=>$q,'sayfa'=>999];
    eval('?>'.$code);
    $canSee = $ids === null || ($id === 1 && $ids !== []) || ($id === 2 && in_array(8,$ids,true));
    verify3c((bool)$viewCustomer === $canSee,'customer detail scope');
    foreach ($customerAppointments as $apt) verify3c($ids === null || in_array((int)$apt['isletme_id'],$ids,true),'shared customer history isolated');
    if ($ids === []) verify3c($customers === [] && $cityList === [] && $totalCount === 0,'empty scope denies all surfaces');
    if ($ids === [7]) {
        verify3c(!in_array('SecretCity',$cityList,true) && !in_array('OtherCity',$cityList,true),'city privacy');
        verify3c($totalCount === ($q === '' ? 56 : ($q === 'Shared' ? 1 : 0)),'search and count scoped');
        if ($q === '') verify3c($page===2 && count($customers)===6,'pagination scoped');
        foreach ($customers as $customer) {
            verify3c((int)$customer['toplam_randevu']===1,'appointment counters scoped');
            verify3c((int)$customer['tamamlanan_randevu']===((int)$customer['id']===1 ? 1 : 0),'completed counters scoped');
        }
    }
}
// City filters cannot expose otherwise hidden customers.
$scopeDb->ids=[7]; $scopeDb->scope='belirli_isletmeler'; $pdo=$scopeDb;
$_GET=['sehir'=>'SecretCity']; eval('?>'.$code);
verify3c($totalCount===0 && $customers===[],'city filter scope');
echo "SEC-11/SEC-13: $checks checks passed; no real database connection.\n";
