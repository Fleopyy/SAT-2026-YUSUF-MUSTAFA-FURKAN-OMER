<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

require_once __DIR__ . '/../data/mail_config.php';
require_once __DIR__ . '/../data/mail_service.php';
require_once __DIR__ . '/../admin/includes/auth.php';

$checks = 0;
function check1(bool $ok, string $label): void {
    global $checks;
    if (!$ok) {
        throw new RuntimeException("Stage 1 Assertion Failed: " . $label);
    }
    $checks++;
}

echo "=== Aşama 1: Gmail SMTP ve E-Posta Altyapısı Güvenlik Testi ===\n";

$emptyEnv = static fn(string $name) => false;
$defaultLocal = [];
$settings = mailSettings($defaultLocal, $emptyEnv);

check1($settings['host'] === 'smtp.gmail.com', 'default host smtp.gmail.com');
check1($settings['port'] === 587, 'default port 587');
check1($settings['secure'] === 'tls', 'default STARTTLS/tls');
check1($settings['username'] === 'bitirasofficial@gmail.com', 'default username');
check1($settings['from_email'] === 'bitirasofficial@gmail.com', 'default from_email');
check1($settings['from_name'] === 'BİTİRAS', 'default from_name');
check1($settings['auth'] === true, 'smtp auth enabled');
check1($settings['verify_peer'] === true, 'TLS verify_peer strictly true');
check1($settings['verify_peer_name'] === true, 'TLS verify_peer_name strictly true');

$envMock = [
    'SMTP_HOST'       => 'smtp-custom.example.com',
    'SMTP_PORT'       => '465',
    'SMTP_SECURE'     => 'ssl',
    'SMTP_USER'       => 'envuser@example.com',
    'SMTP_PASSWORD'   => 'super-secret-password-xyz',
    'SMTP_FROM_EMAIL' => 'notifications@example.com',
    'SMTP_FROM_NAME'  => 'BiTiras Test',
];
$envFn = static fn(string $name) => $envMock[$name] ?? false;
$envSettings = mailSettings(['host' => 'local.host', 'port' => 25], $envFn);

check1($envSettings['host'] === 'smtp-custom.example.com', 'env overrides host');
check1($envSettings['port'] === 465, 'env overrides port to 465');
check1($envSettings['secure'] === 'ssl', 'env overrides secure to ssl');
check1($envSettings['username'] === 'envuser@example.com', 'env overrides username');
check1($envSettings['password'] === 'super-secret-password-xyz', 'env overrides password');
check1($envSettings['from_email'] === 'notifications@example.com', 'env overrides from_email');
check1($envSettings['from_name'] === 'BiTiras Test', 'env overrides from_name');

$invalidCases = [
    ['port' => 0],
    ['port' => 70000],
    ['secure' => 'none'],
    ['host' => "smtp.gmail.com\r\nBcc: hacker@evil.com"],
    ['username' => "user@gmail.com\nInjection"],
    ['from_email' => 'invalid-email-address'],
    ['from_name' => "Admin\r\nX-Spam: true"],
];

foreach ($invalidCases as $case) {
    $rejected = false;
    try {
        mailSettings($case, $emptyEnv);
    } catch (RuntimeException $e) {
        $rejected = true;
        check1(!str_contains($e->getMessage(), 'super-secret-password-xyz'), 'error never contains secret password');
    }
    check1($rejected, 'invalid configuration rejected: ' . json_encode($case));
}

$mailer = createMailer($settings);
check1($mailer instanceof \PHPMailer\PHPMailer\PHPMailer, 'mailer instance created');
check1($mailer->Mailer === 'smtp', 'isSMTP configured');
check1($mailer->Host === 'smtp.gmail.com', 'mailer host set');
check1($mailer->Port === 587, 'mailer port set');
check1($mailer->SMTPAuth === true, 'mailer auth enabled');
check1($mailer->SMTPSecure === \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS, 'mailer STARTTLS enabled');
check1(isset($mailer->SMTPOptions['ssl']['verify_peer']) && $mailer->SMTPOptions['ssl']['verify_peer'] === true, 'SMTPOptions verify_peer true');
check1(isset($mailer->SMTPOptions['ssl']['verify_peer_name']) && $mailer->SMTPOptions['ssl']['verify_peer_name'] === true, 'SMTPOptions verify_peer_name true');
check1(isset($mailer->SMTPOptions['ssl']['allow_self_signed']) && $mailer->SMTPOptions['ssl']['allow_self_signed'] === false, 'SMTPOptions allow_self_signed false');

$badRecipient = sendMailDirect('invalid-email', 'Test User', 'Konu', '<p>Test</p>');
check1($badRecipient['success'] === false, 'invalid email rejected');

$headerInj = sendMailDirect("test@example.com\r\nBcc: hacker@evil.com", 'Test User', "Konu\r\nInjected", '<p>Test</p>');
check1($headerInj['success'] === false, 'CRLF injection rejected');

$emptyPassRes = sendMailDirect('test@example.com', 'Test User', 'Konu', '<p>Test</p>', '', ['password' => '']);
check1($emptyPassRes['success'] === false && str_contains($emptyPassRes['message'], 'SMTP kimlik bilgisi eksik'), 'empty password rejected safely');

$maliciousTitle = "<script>alert('XSS')</script>Randevu Onayı";
$maliciousBody = "<p>Sayın <b>Müşteri</b>, randevunuz oluşturuldu.</p>";
$maliciousUrl = "javascript:alert('XSS')";
$maliciousNotice = "<img src=x onerror=alert(1)>Güvenlik Uyarısı";

$renderedHtml = renderEmailTemplate($maliciousTitle, $maliciousBody, "Buton", $maliciousUrl, $maliciousNotice);
check1(!str_contains($renderedHtml, "<script>alert('XSS')</script>"), 'title escaped against XSS');
check1(str_contains($renderedHtml, "&lt;script&gt;alert(&#039;XSS&#039;)&lt;/script&gt;"), 'title properly HTML encoded');
check1(!str_contains($renderedHtml, "<img src=x"), 'notice escaped against XSS');
check1(str_contains($renderedHtml, 'BİTİRAS'), 'brand header present');
check1(str_contains($renderedHtml, '#C28840'), 'brand accent color present');

$root = dirname(__DIR__);
$rootHtaccess = file_get_contents($root . '/.htaccess');
check1(str_contains($rootHtaccess, 'mail\.local'), 'root .htaccess blocks mail.local');
check1(str_contains(file_get_contents($root . '/data/.htaccess'), 'Require all denied'), 'data/.htaccess blocks all HTTP access');
check1(str_contains(file_get_contents($root . '/.gitignore'), '/data/mail.local.php'), '.gitignore tracks /data/mail.local.php');

check1(function_exists('queueEmail'), 'queueEmail exists in auth.php');
check1(function_exists('enqueueMail'), 'enqueueMail exists in mail_service.php');
check1(function_exists('sendMailDirect'), 'sendMailDirect exists in mail_service.php');
check1(function_exists('processMailQueue'), 'processMailQueue exists in mail_service.php');

echo "Aşama 1 Güvenlik ve Altyapı Testi: $checks kontrol başarıyla geçti.\n";
