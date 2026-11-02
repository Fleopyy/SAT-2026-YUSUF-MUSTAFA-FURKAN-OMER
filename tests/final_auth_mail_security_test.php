<?php
// CLI only: production functions extracted without bootstrap; no DB or SMTP.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
function loadFinalFunction($file, $name) {
    $s = file_get_contents(__DIR__ . '/../' . $file);
    $a = strpos($s, 'function ' . $name . '(');
    $b = strpos($s, "\nfunction ", $a + 1);
    eval(substr($s, $a, $b === false ? null : $b - $a));
}
$n = 0;
function ck($ok, $label) { global $n; if (!$ok) throw new RuntimeException($label); $n++; }
$sent = false; $queued = false; $throws = false; $queueCalls = 0;
function sendMailDirect(...$args) { if ($GLOBALS['throws']) throw new RuntimeException('SECRET'); return ['success'=>$GLOBALS['sent'], 'retryable'=>$GLOBALS['retryable'] ?? true]; }
function enqueueMail(...$args) { $GLOBALS['queueCalls']++; if ($GLOBALS['queueThrows'] ?? false) throw new RuntimeException('SECRET'); return $GLOBALS['queued']; }
loadFinalFunction('data/mail_service.php', 'dispatchAccountMail');
ck(!dispatchAccountMail('x','x','x','x','x','x'), 'both failures propagate');
$queued = 12;
ck(dispatchAccountMail('x','x','x','x','x','x') && $GLOBALS['account_mail_status'] === 'queued', 'queue acceptance distinct from delivery');
$sent = true;
ck(dispatchAccountMail('x','x','x','x','x','x') && $GLOBALS['account_mail_status'] === 'smtp_accepted', 'SMTP acceptance distinct from mailbox delivery');
$sent = false; $retryable = false; $before = $queueCalls;
ck(!dispatchAccountMail('x','x','x','x','x','x') && $before === $queueCalls, 'uncertain SMTP outcome never retried');
$throws = true;
ck(!dispatchAccountMail('x','x','x','x','x','x'), 'SMTP exception contained');
$throws = false; $retryable = true; $queueThrows = true;
ck(!dispatchAccountMail('x','x','x','x','x','x'), 'queue exception contained');
$queueThrows = false;
foreach (['base32Decode','calculateTotpCode','matchingTotpTimeSlice','consumeAdminTotpSlice'] as $fn) loadFinalFunction('data/two_factor_service.php', $fn);
class SliceDB {
    public $last = null, $affected = 0, $secret = 'JBSWY3DPEHPK3PXP';
    function prepare($sql) { ck(str_contains($sql, 'son_totp_zaman_dilimi < ?') && str_contains($sql, 'totp_gizli_anahtar = ?'), 'atomic account and secret predicate'); return $this; }
    function execute($p) { $this->affected = ($p[2] === $this->secret && ($this->last === null || $this->last < $p[0])) ? 1 : 0; if ($this->affected) $this->last = $p[0]; }
    function rowCount() { return $this->affected; }
}
$d = new SliceDB(); $slice = 60000000;
ck(matchingTotpTimeSlice($d->secret, calculateTotpCode($d->secret, $slice), $slice*30) === $slice, 'matching slice returned');
ck(consumeAdminTotpSlice($d, 1, $d->secret, $slice), 'first slice accepted');
ck(!consumeAdminTotpSlice($d, 1, $d->secret, $slice), 'replay and concurrent loser rejected');
ck(!consumeAdminTotpSlice($d, 1, $d->secret, $slice-1), 'older window rejected');
ck(consumeAdminTotpSlice($d, 1, $d->secret, $slice+1), 'next slice accepted');
ck(!consumeAdminTotpSlice($d, 1, 'changed', $slice+2), 'secret rotation race rejected');
class ReminderDB extends PDO {
    public $tx = false, $ledger = false, $snapshot, $failRecord = false, $affected;
    function __construct() {}
    function inTransaction(): bool { return $this->tx; }
    function beginTransaction(): bool { $this->snapshot = [$this->ledger, $GLOBALS['queued']]; return $this->tx = true; }
    function commit(): bool { $this->tx = false; return true; }
    function rollBack(): bool { [$this->ledger, $GLOBALS['queued']] = $this->snapshot; $this->tx = false; return true; }
    function prepare($query, $options = []): PDOStatement|false { return new ReminderStatement($this); }
}
class ReminderStatement extends PDOStatement {
    function __construct(public $db) {}
    function execute(?array $params = null): bool { return true; }
    function fetchColumn(int $column = 0): mixed { return 1; }
}
function isAppointmentNotificationSent($db, ...$args) { return $db->ledger; }
function recordAppointmentNotification($db, ...$args) { if ($db->failRecord) return false; return $db->ledger = true; }
loadFinalFunction('data/appointment_notification_service.php', 'queueAppointmentReminder');
$d = new ReminderDB(); $queued = false;
$args = [$d,1,'hatirlatma_2h','test@example.invalid','n','s','body','key'];
ck(!queueAppointmentReminder(...$args) && !$d->ledger && !$d->tx, 'queue failure leaves reminder retryable');
$queued = 9; $d->failRecord = true;
ck(!queueAppointmentReminder(...$args) && !$d->ledger && !$d->tx, 'ledger failure rolls back');
$d->failRecord = false;
ck(queueAppointmentReminder(...$args) && $d->ledger, 'retry commits queue and ledger');
$before = $queueCalls;
ck(!queueAppointmentReminder(...$args) && $before === $queueCalls, 'duplicate reminder does not enqueue');
$d->ledger = false; $queueThrows = true;
ck(!queueAppointmentReminder(...$args) && !$d->ledger && !$d->tx, 'queue exception rolls back');
$s = file_get_contents(__DIR__.'/../login.php');
ck(str_contains($s, "completeBarberPasswordLogin($" . "barber, $" . "loginVal, '/barber/dashboard')") && !str_contains($s, '/isletme/giris.php?step=2fa'), 'homepage uses common business MFA');
$s = file_get_contents(__DIR__.'/../data/two_factor_service.php');
$a = strpos($s, 'function completeBarberPasswordLogin'); $s = substr($s, $a);
ck(str_contains($s, "unset($" . "_SESSION['barber_id']"), 'pending login clears authorized roles');
ck(strpos($s, '/isletme/giris.php?step=2fa') < strpos($s, "renewAuthenticatedSession('barber'"), 'MFA redirect precedes authenticated session');

// Exercise the shared MFA path with controlled redirects and account state.
function evaluateBarberLoginRisk($barber) { return ['requires_2fa'=>true, 'reason'=>'new device']; }
function sendBarber2faEmail(...$args) { return false; }
function recordLoginAttempt(...$args) {}
function renewAuthenticatedSession(...$args) { throw new RuntimeException('Premature authorization'); }
function finalRedirect($value) { $GLOBALS['redirect'] = $value; }
class FinalExit extends Exception {}
$s = file_get_contents(__DIR__.'/../data/two_factor_service.php');
$a = strpos($s, 'function completeBarberPasswordLogin('); $b = strpos($s, "\nfunction ", $a+1);
$s = substr($s, $a, $b-$a);
$s = str_replace(['header(', 'exit;'], ['finalRedirect(', 'throw new FinalExit();'], $s); eval($s);
$_SESSION = ['barber_id'=>999, 'admin_id'=>5, 'user_id'=>6, 'manager_id'=>7];
try { completeBarberPasswordLogin(['id'=>1,'eposta'=>'x@example.invalid','isletme_adi'=>'n','sifre_hash'=>'h'], 'x', '/barber/dashboard'); } catch (FinalExit $e) {}
ck($GLOBALS['redirect'] === 'Location: /isletme/giris.php?step=2fa', 'shared MFA endpoint even on mail failure');
ck(!isset($_SESSION['barber_id']) && !isset($_SESSION['admin_id']) && !isset($_SESSION['user_id']) && !isset($_SESSION['manager_id']) && isset($_SESSION['2fa_pending']), 'no authorized session before MFA');
ck(str_contains($_SESSION['2fa_resend_flash'], 'gonderilemedi'), 'mail failure shown without granting access');
// Run the real enqueue implementation against a failing connection double.
$s = file_get_contents(__DIR__.'/../data/mail_service.php');
$a = strpos($s, 'function enqueueMail('); $b = strpos($s, "\nfunction ", $a+1);
$s = str_replace('function enqueueMail(', 'function enqueueMailUnderTest(', substr($s, $a, $b-$a)); eval($s);
class BrokenQueueDB { function query($sql) { throw new RuntimeException('SECRET'); } }
$pdo = new BrokenQueueDB();
ob_start(); $r = enqueueMailUnderTest('test@example.invalid','n','s','b','k','t'); $output = ob_get_clean();
ck($r === false && !str_contains($output, 'SECRET'), 'real enqueue catches connection exceptions without exposure');
class MissingSliceDB { function prepare($sql) { throw new RuntimeException('missing column'); } }
ck(!consumeAdminTotpSlice(new MissingSliceDB(), 1, 's', 1), 'missing migration fails closed');

function consumeMailRequestLimit(...$args) { return true; }
function renderEmailTemplate(...$args) { return 'html'; }
function getSiteBaseUrl() { return 'http://localhost'; }
class MailPreparationDB {
    public $fail = false;
    function prepare($sql) { if ($this->fail) throw new RuntimeException('SECRET'); return $this; }
    function execute($args) { return true; }
}
$pdo = new MailPreparationDB(); $queueThrows = false; $queued = false;
foreach (['sendCustomerVerificationEmail','sendAdmin2faEmail','sendBarber2faEmail'] as $fn) {
    if ($fn === 'sendBarber2faEmail') {
        $source = file_get_contents(__DIR__.'/../data/two_factor_service.php');
        $a = strpos($source, 'function sendBarber2faEmail(');
        $b = strpos($source, chr(10).'function ', $a+1);
        eval(str_replace('function sendBarber2faEmail(', 'function sendBarber2faEmailUnderTest(', substr($source, $a, $b-$a)));
        $fn = 'sendBarber2faEmailUnderTest';
    } else {
        loadFinalFunction($fn === 'sendCustomerVerificationEmail' ? 'data/customer_auth_service.php' : 'data/two_factor_service.php', $fn);
    }
    ck(!$fn(1, 'test@example.invalid', 'n'), 'sender propagates failed dispatch');
    $queued = 3; ck($fn(1, 'test@example.invalid', 'n'), 'sender propagates queue acceptance'); $queued = false;
    $pdo->fail = true; ck(!$fn(1, 'test@example.invalid', 'n'), 'sender contains preparation exceptions'); $pdo->fail = false;
}
loadFinalFunction('data/two_factor_service.php', 'verifyAndConsumeRecoveryCode');
class RecoveryDB extends PDO {
    public $raw, $affected = 0;
    function __construct() { $this->raw = json_encode([hash('sha256','ABCD-1234')]); }
    function prepare($query, $options = []): PDOStatement|false { return new RecoveryStatement($this); }
}
class RecoveryStatement extends PDOStatement {
    function __construct(public $db) {}
    function execute(?array $params = null): bool { $this->db->affected = 0; if (count($params) === 3 && $params[2] === $this->db->raw) { $this->db->raw = $params[0]; $this->db->affected = 1; } return true; }
    function fetchColumn(int $column = 0): mixed { return $this->db->raw; }
    function rowCount(): int { return $this->db->affected; }
}
$d = new RecoveryDB();
ck(verifyAndConsumeRecoveryCode($d,1,'ABCD-1234'), 'recovery remains available');
ck(!verifyAndConsumeRecoveryCode($d,1,'ABCD-1234'), 'recovery replay rejected');
echo "Final auth/mail security: $n checks passed; no DB or SMTP.\n";
