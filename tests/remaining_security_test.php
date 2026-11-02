<?php
// CLI only. Execute production functions with in-memory doubles; no DB, SMTP or HTTP.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
function loadSecurityFunction(string $file, string $name): void {
    $source = file_get_contents(dirname(__DIR__) . '/' . $file);
    $start = strpos($source, 'function ' . $name . '(');
    if ($start === false) throw new RuntimeException('Missing function');
    $end = strpos($source, "\nfunction ", $start + 1);
    eval(substr($source, $start, $end === false ? null : $end - $start));
}
$checks = 0;
function remainingCheck(bool $ok, string $label): void {
    global $checks;
    if (!$ok) throw new RuntimeException($label);
    $checks++;
}
function validNewPassword($value) { return strlen($value) >= 10 && strlen($value) <= 72; }
$deliveries = 0; $notifications = 0; $deliverySuccess = true;
function sendMailDirect(...$args) {
    $GLOBALS['deliveries']++;
    if ($GLOBALS['crash'] ?? false) throw new RuntimeException('Simulated worker interruption');
    return ['success'=>$GLOBALS['deliverySuccess'], 'retryable'=>$GLOBALS['deliveryRetryable'] ?? true, 'message'=>'test failure'];
}
function sendPasswordChangedNotification(...$args) { $GLOBALS['notifications']++; }
class RemainingDB {
    public bool $locked = true, $conflict = false, $due = true;
    public int $rateCount = 0, $released = 0;
    public array $queries = [], $row = [];
    public function prepare($sql) { return new RemainingStatement($this, $sql); }
    public function query($sql) { $s = $this->prepare($sql); $s->execute(); return $s; }
}
class RemainingStatement {
    private int $affected = 0;
    public function __construct(private RemainingDB $db, private string $sql) {}
    public function bindValue(...$args) {}
    public function execute($params = []) {
        $this->db->queries[] = [$this->sql, $params];
        if (str_contains($this->sql, 'RELEASE_LOCK')) $this->db->released++;
        if (str_starts_with($this->sql, 'INSERT INTO giris_denemeleri')) $this->db->rateCount++;
        if (str_starts_with($this->sql, 'UPDATE kullanicilar')) {
            remainingCheck(str_contains($this->sql, 'AND sifre_sifirlama_token_hash = ?') && str_contains($this->sql, 'sifre_sifirlama_son_tarih > NOW()'), 'reset CAS and expiry enforced in write');
            if (!$this->db->conflict && ($this->db->row['sifre_sifirlama_token_hash'] ?? null) === $params[2]) {
                $this->db->row['sifre_sifirlama_token_hash'] = null; $this->affected = 1;
            }
        }
        if (str_starts_with($this->sql, 'UPDATE eposta_kuyrugu')) {
            if (str_contains($this->sql, "AND durum = 'pending'")) {
                if (!$this->db->conflict && $this->db->row['durum'] === 'pending') {
                    $this->db->row['durum'] = 'failed';
                    $this->db->row['deneme_sayisi'] = $params[0];
                    $this->affected = 1;
                }
            } elseif (str_contains($this->sql, "durum = 'sent'")) $this->db->row['durum'] = 'sent';
            else { $this->db->row['durum'] = $params[0]; $this->db->due = false; }
        }
        return true;
    }
    public function rowCount() { return $this->affected; }
    public function fetchColumn() {
        if (str_contains($this->sql, 'GET_LOCK')) return (int)$this->db->locked;
        if (str_contains($this->sql, 'COUNT(*)')) return $this->db->rateCount;
        return 'isolated_test_schema';
    }
    public function fetchAll($mode = null) {
        if (str_contains($this->sql, 'FROM eposta_kuyrugu')) {
            remainingCheck(str_contains($this->sql, 'INTERVAL 5 MINUTE') && str_contains($this->sql, 'deneme_sayisi < 3'), 'queue backoff and attempt budget');
            return $this->db->row['durum'] === 'pending' && $this->db->due ? [$this->db->row] : [];
        }
        $query = end($this->db->queries);
        remainingCheck(str_contains($this->sql, 'WHERE sifre_sifirlama_token_hash = ?') && !str_contains($this->sql, 'LIMIT 10'), 'reset lookup directly matches token');
        return ($this->db->row['sifre_sifirlama_token_hash'] ?? null) === $query[1][0] ? [$this->db->row] : [];
    }
}
loadSecurityFunction('data/security.php', 'consumeMailRequestLimit');
loadSecurityFunction('data/customer_auth_service.php', 'resetPasswordWithToken');
loadSecurityFunction('data/mail_service.php', 'processMailQueue');
$_SERVER['REMOTE_ADDR'] = '192.0.2.1';
$pdo = new RemainingDB();
for ($i = 0; $i < 3; $i++) remainingCheck(consumeMailRequestLimit('reset', 'test@example.invalid'), 'request budget available');
remainingCheck(!consumeMailRequestLimit('reset', 'test@example.invalid') && $pdo->rateCount === 3, 'fourth request blocked without insertion');
$pdo->locked = false;
remainingCheck(!consumeMailRequestLimit('reset', 'other@example.invalid'), 'limiter fails closed on lock contention');
$token = str_repeat('a', 64);
$fixture = ['id'=>42, 'ad_soyad'=>'Fixture', 'eposta'=>'test@example.invalid', 'sifre_sifirlama_token_hash'=>hash('sha256', $token), 'sifre_sifirlama_deneme'=>0];
$pdo = new RemainingDB(); $pdo->row = $fixture;
remainingCheck(resetPasswordWithToken($token, 'Valid-password-123')['success'], 'valid reset accepted');
remainingCheck(!resetPasswordWithToken($token, 'Valid-password-123')['success'] && $notifications === 1, 'reset replay rejected without another notification');
$pdo->row = $fixture; $pdo->conflict = true;
remainingCheck(!resetPasswordWithToken($token, 'Valid-password-123')['success'] && $notifications === 1, 'concurrent reset loser rejected');
$mail = ['id'=>1, 'alici_eposta'=>'test@example.invalid', 'alici_ad'=>'Fixture', 'konu'=>'Fixture', 'icerik'=>'Fixture', 'deneme_sayisi'=>0, 'durum'=>'pending'];
$pdo = new RemainingDB(); $pdo->row = $mail;
remainingCheck(processMailQueue()['sent'] === 1 && $pdo->row['durum'] === 'sent', 'mail sent once');
remainingCheck(processMailQueue()['sent'] === 0 && $deliveries === 1, 'sent mail excluded on next run');
$pdo->row = $mail; $pdo->conflict = true;
remainingCheck(processMailQueue()['sent'] === 0 && $deliveries === 1, 'lost claim never sends');
$pdo->conflict = false; $deliverySuccess = false;
remainingCheck(processMailQueue()['failed'] === 1 && $pdo->row['durum'] === 'pending', 'known failure remains retryable');
remainingCheck(processMailQueue()['processed'] === 0 && $deliveries === 2, 'cooldown prevents immediate retry');
$pdo->due = true; $pdo->row['deneme_sayisi'] = 2;
remainingCheck(processMailQueue()['failed'] === 1 && $pdo->row['durum'] === 'failed', 'third failure is terminal');
$pdo->row = $mail; $pdo->due = true; $deliveryRetryable = false;
remainingCheck(processMailQueue()['failed'] === 1 && $pdo->row['durum'] === 'failed', 'uncertain SMTP response never triggers automatic retry');
$deliveryRetryable = true;
$pdo->row = $mail; $pdo->due = true; $crash = true;
try { processMailQueue(); } catch (RuntimeException $e) {}
remainingCheck($pdo->row['durum'] === 'failed', 'interrupted delivery not retried automatically');
$crash = false;
remainingCheck(processMailQueue()['processed'] === 0 && $pdo->released > 0, 'uncertain delivery stays stopped and lock released');
require dirname(__DIR__) . '/data/privacy.php';
foreach ([null, '{bad', json_encode(['version'=>1,'functional'=>true,'updated_at'=>'2000-01-01']), json_encode(['version'=>1,'functional'=>'true','updated_at'=>gmdate('c')])] as $cookie) {
    $_COOKIE['bitiras_cookie_consent'] = $cookie;
    remainingCheck(!hasFunctionalConsent(), 'invalid consent rejected');
}
$_COOKIE['bitiras_cookie_consent'] = json_encode(['version'=>1,'functional'=>true,'updated_at'=>gmdate('c')]);
remainingCheck(hasFunctionalConsent(), 'explicit unexpired functional consent accepted');
// Exercise the actual resend controller without its application bootstrap or side effects.
class RemainingExit extends Exception {}
function verifyCsrfToken() { return $GLOBALS['testCsrf']; }
function remainingHeader($value) {}
function sendAdmin2faEmail(...$args) { $GLOBALS['resends']++; return true; }
function sendBarber2faEmail(...$args) { $GLOBALS['resends']++; return true; }
$controller = file_get_contents(dirname(__DIR__) . '/admin/login.php');
$start = strpos($controller, "if (\$_SERVER['REQUEST_METHOD'] === 'POST' && (\$_POST['action'] ?? '') === 'resend_2fa')");
$end = strpos($controller, '$resendFlash =', $start);
$controller = substr($controller, $start, $end - $start);
$controller = preg_replace('/exit\([^;]*\);|exit;/', 'throw new RemainingExit();', $controller);
$controller = str_replace('header(', 'remainingHeader(', $controller);
$challenge = ['type'=>'admin','user_id'=>42,'email'=>'test@example.invalid','name'=>'Fixture','expires'=>time()+180,'last_resend'=>time()-61,'attempts'=>0,'resends'=>0];
foreach ([['GET',true,[],0], ['POST',false,[],0], ['POST',true,['expires'=>time()-1],0], ['POST',true,['attempts'=>8],0], ['POST',true,['resends'=>3],0], ['POST',true,['last_resend'=>time()],0], ['POST',true,[],1]] as [$method,$testCsrf,$overrides,$expected]) {
    $_SERVER['REQUEST_METHOD'] = $method;
    $_POST = ['action'=>'resend_2fa']; $_GET = ['step'=>'2fa','resend'=>1];
    $_SESSION = ['2fa_pending'=>array_replace($challenge,$overrides)];
    $originalExpiry = $_SESSION['2fa_pending']['expires']; $resends = 0;
    try { eval($controller); } catch (RemainingExit $e) {}
    remainingCheck($resends === $expected, 'resend method, CSRF, expiry, attempt, budget and cooldown gates');
    remainingCheck($_SESSION['2fa_pending']['expires'] === $originalExpiry, 'resend never extends challenge');
}
// Real PDO with SQLite memory only; prove a recovery code cannot be replayed.
loadSecurityFunction('data/two_factor_service.php', 'verifyAndConsumeRecoveryCode');
$memory = new PDO('sqlite::memory:');
$memory->exec('CREATE TABLE yoneticiler (id INTEGER PRIMARY KEY, kurtarma_kodlari_hash TEXT)');
$insert = $memory->prepare('INSERT INTO yoneticiler VALUES (1, ?)');
$insert->execute([json_encode([hash('sha256', 'ABCD-1234'), hash('sha256', 'DEAD-BEEF')])]);
remainingCheck(verifyAndConsumeRecoveryCode($memory, 1, 'ABCD-1234'), 'recovery code consumed');
remainingCheck(!verifyAndConsumeRecoveryCode($memory, 1, 'ABCD-1234'), 'recovery replay rejected');
remainingCheck(verifyAndConsumeRecoveryCode($memory, 1, 'DEAD-BEEF'), 'other recovery code preserved');
loadSecurityFunction('data/two_factor_service.php', 'verifyAdmin2faCode');
class RemainingMfaDB {
    public bool $conflict = false;
    public array $row = ['eposta_2fa_kod_hash'=>null,'eposta_2fa_son_tarih'=>null,'eposta_2fa_deneme'=>0,'totp_gizli_anahtar'=>null,'kurtarma_kodlari_hash'=>null,'iki_adimli_tur'=>'email'];
    public function prepare($sql) { return new RemainingMfaStatement($this, $sql); }
}
class RemainingMfaStatement {
    private int $affected = 0;
    public function __construct(private RemainingMfaDB $db, private string $sql) {}
    public function execute($params) {
        if (str_contains($this->sql, 'eposta_2fa_deneme = eposta_2fa_deneme + 1')) {
            if ($this->db->row['eposta_2fa_deneme'] < 8) { $this->db->row['eposta_2fa_deneme']++; $this->affected = 1; }
        } elseif (str_starts_with($this->sql, 'UPDATE')) {
            remainingCheck(str_contains($this->sql, 'AND eposta_2fa_kod_hash = ?') && str_contains($this->sql, 'eposta_2fa_son_tarih > NOW()'), 'MFA atomic consume checks credential and expiry');
            if (!$this->db->conflict && $params[1] === $this->db->row['eposta_2fa_kod_hash']) { $this->db->row['eposta_2fa_kod_hash'] = null; $this->affected = 1; }
        }
    }
    public function fetch($mode) { return $this->db->row; }
    public function rowCount() { return $this->affected; }
}
$pdo = new RemainingMfaDB();
$pdo->row['eposta_2fa_kod_hash'] = hash('sha256', '123456'); $pdo->row['eposta_2fa_son_tarih'] = date('Y-m-d H:i:s', time()+60);
remainingCheck(verifyAdmin2faCode(42, '123456')['success'], 'email MFA accepted once');
remainingCheck(!verifyAdmin2faCode(42, '123456')['success'], 'email MFA replay rejected');
$pdo->row['eposta_2fa_kod_hash'] = hash('sha256', '123456'); $pdo->conflict = true;
remainingCheck(!verifyAdmin2faCode(42, '123456')['success'], 'concurrent MFA consume loser rejected');
$pdo->row['eposta_2fa_deneme'] = 8;
remainingCheck(!verifyAdmin2faCode(42, '123456')['success'], 'MFA exhausted budget rejects even a correct code');
echo "Remaining security: $checks checks passed (isolated doubles).\n";
