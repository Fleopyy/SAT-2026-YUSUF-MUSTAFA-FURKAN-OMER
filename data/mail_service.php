<?php
// Central Mail Service for BİTİRAS
// Secure Gmail SMTP transmission via PHPMailer with strict TLS validation.

require_once __DIR__ . '/mail_config.php';
require_once dirname(__DIR__) . '/includes/PHPMailer/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

/**
 * Creates and configures a PHPMailer instance using verified Gmail SMTP settings.
 */
function createMailer(?array $settings = null): PHPMailer
{
    $settings = $settings ?? mailSettings();

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = $settings['host'];
    $mail->Port       = (int)$settings['port'];
    $mail->SMTPAuth   = (bool)$settings['auth'];
    $mail->Username   = $settings['username'];
    $mail->Password   = $settings['password'];
    $mail->CharSet    = 'UTF-8';
    $mail->Timeout = 20;
    $mail->SMTPDebug = 0;

    // Strict STARTTLS / TLS configuration
    if ($settings['secure'] === 'tls') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    } elseif ($settings['secure'] === 'ssl') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    }

    // Explicit TLS peer and name verification; never disable certificate verification.
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer'       => true,
            'verify_peer_name'  => true,
            'allow_self_signed' => false,
        ],
    ];

    $mail->setFrom($settings['from_email'], $settings['from_name']);
    return $mail;
}

/**
 * Sends an email directly via Gmail SMTP.
 * Returns an array with ['success' => bool, 'message' => string].
 * Secrets and passwords are NEVER exposed in return values or error logs.
 */
function sendMailDirect(
    string $toEmail,
    string $toName,
    string $subject,
    string $htmlBody,
    string $textBody = '',
    ?array $customSettings = null
): array {
    $toEmail = trim($toEmail);
    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Geçersiz alıcı e-posta adresi.'];
    }

    // Check for header injection in subject or recipient
    if (preg_match('/[\r\n\x00]/', $subject) || preg_match('/[\r\n\x00]/', $toName)) {
        return ['success' => false, 'message' => 'E-posta başlığında geçersiz karakterler bulundu.'];
    }

    $deliveryStarted = false;
    try {
        $settings = $customSettings ?? mailSettings();
        if (empty($settings['password'])) {
            error_log('BİTİRAS Mail: SMTP şifresi yapılandırılmamış. Gönderim beklemeye alındı.');
            return [
                'success' => false,
                'message' => 'SMTP kimlik bilgisi eksik. Lütfen güvenli yapılandırma dosyasını kontrol edin.'
            ];
        }

        $mailer = createMailer($settings);
        $mailer->addAddress($toEmail, $toName);
        $mailer->isHTML(true);
        $mailer->Subject = $subject;
        $mailer->Body    = $htmlBody;
        $mailer->AltBody = !empty($textBody) ? $textBody : strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $htmlBody));

        // A connect/auth failure is safe to retry. Once message transmission begins,
        // a lost SMTP response cannot prove non-delivery; never retry it automatically.
        if (!$mailer->smtpConnect()) throw new RuntimeException('SMTP connection unavailable');
        $deliveryStarted = true;
        $mailer->send();
        return ['success' => true, 'message' => 'E-posta başarıyla gönderildi.'];
    } catch (Exception $e) {
        error_log('SMTP delivery failed.');
        return [
            'success' => false,
            'retryable' => !$deliveryStarted,
            'message' => 'E-posta gonderimi basarisiz oldu.'
        ];
    } catch (Throwable $t) {
        error_log('Mail service unavailable.');
        return [
            'success' => false,
            'retryable' => !$deliveryStarted,
            'message' => 'E-posta servisi geçici olarak kullanılamıyor.'
        ];
    }
}

/**
 * Enqueues an email into eposta_kuyrugu with idempotency / deduplication support.
 * Backwards compatible with legacy 4-column schema as well as migrated schema.
 */
function enqueueMail(
    string $toEmail,
    ?string $toName,
    string $subject,
    string $body,
    ?string $idempotencyKey = null,
    ?string $tur = null
): int|false {
    global $pdo;
    if (!$pdo) {
        return false;
    }

    $toEmail = trim($toEmail);
    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $lockHeld = false;
    try {
    $enqueueLock = 'bitiras_enqueue_' . substr(hash('sha256', (string)$pdo->query('SELECT DATABASE()')->fetchColumn() . ($idempotencyKey ?? ($toEmail . $subject))), 0, 40);
    $lockStmt = $pdo->prepare('SELECT GET_LOCK(?, 2)');
    $lockStmt->execute([$enqueueLock]);
    if ((int)$lockStmt->fetchColumn() !== 1) return false;
    $lockHeld = true;
    // Inspect schema columns once per request
    static $hasIdempotencyCol = null;
    static $hasTurCol = null;
    if ($hasIdempotencyCol === null) {
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM eposta_kuyrugu LIKE 'idempotency_key'");
            $hasIdempotencyCol = (bool)$stmt->fetch();
            $stmt2 = $pdo->query("SHOW COLUMNS FROM eposta_kuyrugu LIKE 'tur'");
            $hasTurCol = (bool)$stmt2->fetch();
        } catch (Throwable $e) {
            $hasIdempotencyCol = false;
            $hasTurCol = false;
        }
    }

    // Deduplication check
    if (!empty($idempotencyKey)) {
        if ($hasIdempotencyCol) {
            $stmt = $pdo->prepare("SELECT id FROM eposta_kuyrugu WHERE idempotency_key = ? LIMIT 1");
            $stmt->execute([$idempotencyKey]);
            $existingId = $stmt->fetchColumn();
            if ($existingId) {
                return (int)$existingId; // Already queued or processed
            }
        } else {
            // Legacy schema: deduplicate the exact payload across pending and completed jobs.
            $stmt = $pdo->prepare("SELECT id FROM eposta_kuyrugu WHERE alici_eposta = ? AND konu = ? AND icerik = ? LIMIT 1");
            $stmt->execute([$toEmail, $subject, $body]);
            $existingId = $stmt->fetchColumn();
            if ($existingId) {
                return (int)$existingId;
            }
        }
    }

    // Insert into database
    if ($hasIdempotencyCol && $hasTurCol) {
        $stmt = $pdo->prepare("INSERT INTO eposta_kuyrugu (alici_eposta, alici_ad, konu, icerik, idempotency_key, tur) VALUES (?, ?, ?, ?, ?, ?)");
        if (!$stmt->execute([$toEmail, $toName, $subject, $body, $idempotencyKey, $tur])) return false;
    } else {
        $stmt = $pdo->prepare("INSERT INTO eposta_kuyrugu (alici_eposta, alici_ad, konu, icerik) VALUES (?, ?, ?, ?)");
        if (!$stmt->execute([$toEmail, $toName, $subject, $body])) return false;
    }

    $id = (int)$pdo->lastInsertId();
    return $id > 0 ? $id : false;
    } catch (Throwable $e) {
        error_log('Mail queue unavailable.');
        return false;
    } finally {
        if ($lockHeld) {
            try {
                $release = $pdo->prepare('SELECT RELEASE_LOCK(?)');
                $release->execute([$enqueueLock]);
            } catch (Throwable $e) { error_log('Mail queue lock release failed.'); }
        }
    }
}

/**
 * Processes pending items from eposta_kuyrugu and transmits them via SMTP.
 * Keeps track of attempts, stores sanitized failure messages, and flags exhausted jobs.
 */
function processMailQueue(int $batchSize = 20, ?array $customSettings = null): array
{
    global $pdo;
    if (!$pdo) {
        return ['processed' => 0, 'sent' => 0, 'failed' => 0, 'message' => 'Veritabanı bağlantısı yok.'];
    }

    $lock = $pdo->query("SELECT GET_LOCK(CONCAT('bitiras_queue_', LEFT(SHA2(DATABASE(), 256), 40)), 0)");
    if ((int)$lock->fetchColumn() !== 1) return ['processed'=>0, 'sent'=>0, 'failed'=>0];
    try {
    $batchSize = max(1, min(100, $batchSize));
    $stmt = $pdo->prepare("SELECT id, alici_eposta, alici_ad, konu, icerik, deneme_sayisi FROM eposta_kuyrugu WHERE durum = 'pending' AND deneme_sayisi < 3 AND (gonderim_tarihi IS NULL OR gonderim_tarihi <= DATE_SUB(NOW(), INTERVAL 5 MINUTE)) ORDER BY id ASC LIMIT ?");
    $stmt->bindValue(1, $batchSize, PDO::PARAM_INT);
    $stmt->execute();
    $queue = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sentCount = 0;
    $failedCount = 0;

    foreach ($queue as $item) {
        $newAttempts = (int)$item['deneme_sayisi'] + 1;
        $claim = $pdo->prepare("UPDATE eposta_kuyrugu SET durum = 'failed', deneme_sayisi = ?, gonderim_tarihi = NOW(), hata_mesaji = 'Delivery outcome uncertain; manual review required' WHERE id = ? AND durum = 'pending' AND deneme_sayisi = ?");
        $claim->execute([$newAttempts, $item['id'], $item['deneme_sayisi']]);
        if ($claim->rowCount() !== 1) continue;
        $result = sendMailDirect(
            $item['alici_eposta'],
            $item['alici_ad'] ?? '',
            $item['konu'],
            $item['icerik'],
            '',
            $customSettings
        );

        if ($result['success']) {
            $upd = $pdo->prepare("UPDATE eposta_kuyrugu SET durum = 'sent', gonderim_tarihi = NOW(), deneme_sayisi = ?, hata_mesaji = NULL WHERE id = ?");
            $upd->execute([$newAttempts, $item['id']]);
            $sentCount++;
        } else {
            $newStatus = ($newAttempts >= 3 || ($result['retryable'] ?? true) === false) ? 'failed' : 'pending';
            $safeError = ($result['retryable'] ?? true) ? 'SMTP delivery failed' : 'Delivery outcome uncertain; manual review required';
            $upd = $pdo->prepare("UPDATE eposta_kuyrugu SET durum = ?, deneme_sayisi = ?, hata_mesaji = ? WHERE id = ?");
            $upd->execute([$newStatus, $newAttempts, $safeError, $item['id']]);
            $failedCount++;
        }
    }

    return [
        'processed' => count($queue),
        'sent'      => $sentCount,
        'failed'    => $failedCount,
    ];
    } finally {
        $pdo->query("SELECT RELEASE_LOCK(CONCAT('bitiras_queue_', LEFT(SHA2(DATABASE(), 256), 40)))");
    }
}

/**
 * Renders a responsive, brand-aligned HTML email template.
 * All dynamic parameters are properly sanitized to eliminate HTML injection.
 */
function renderEmailTemplate(
    string $title,
    string $bodyHtml,
    ?string $buttonText = null,
    ?string $buttonUrl = null,
    ?string $securityNotice = null
): string {
    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $buttonHtml = '';
    if (!empty($buttonText) && !empty($buttonUrl)) {
        $safeButtonText = htmlspecialchars($buttonText, ENT_QUOTES, 'UTF-8');
        $safeButtonUrl = htmlspecialchars($buttonUrl, ENT_QUOTES, 'UTF-8');
        $buttonHtml = "
        <table role='presentation' border='0' cellpadding='0' cellspacing='0' style='margin: 28px auto;'>
            <tr>
                <td align='center' style='border-radius: 8px; background: #C28840;'>
                    <a href='{$safeButtonUrl}' target='_blank' style='border: 1px solid #C28840; border-radius: 8px; color: #FFFFFF; display: inline-block; font-size: 15px; font-weight: bold; padding: 14px 28px; text-decoration: none;'>
                        {$safeButtonText}
                    </a>
                </td>
            </tr>
        </table>";
    }

    $noticeHtml = '';
    if (!empty($securityNotice)) {
        $safeNotice = htmlspecialchars($securityNotice, ENT_QUOTES, 'UTF-8');
        $noticeHtml = "
        <div style='margin-top: 24px; padding: 14px; background: #F8FAFC; border-left: 4px solid #C28840; border-radius: 4px; font-size: 13px; color: #475569;'>
            <strong>Güvenlik Notu:</strong> {$safeNotice}
        </div>";
    }

    return "<!DOCTYPE html>
<html lang='tr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>{$safeTitle}</title>
</head>
<body style='margin: 0; padding: 0; background-color: #F1F5F9; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif;'>
    <table role='presentation' width='100%' border='0' cellpadding='0' cellspacing='0' style='background-color: #F1F5F9; padding: 40px 10px;'>
        <tr>
            <td align='center'>
                <table role='presentation' width='100%' border='0' cellpadding='0' cellspacing='0' style='max-width: 580px; background-color: #FFFFFF; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.06); overflow: hidden;'>
                    <!-- Brand Header -->
                    <tr>
                        <td align='center' style='background-color: #0F172A; padding: 32px 20px; border-bottom: 3px solid #C28840;'>
                            <h1 style='margin: 0; font-size: 24px; font-weight: 800; color: #F8FAFC; letter-spacing: 2px;'>BiTıraş</h1>
                            <p style='margin: 6px 0 0; font-size: 12px; color: #94A3B8; text-transform: uppercase; letter-spacing: 1px;'>Online Berber & Randevu Platformu</p>
                        </td>
                    </tr>
                    <!-- Content Body -->
                    <tr>
                        <td style='padding: 36px 32px; color: #1E293B; line-height: 1.65; font-size: 15px;'>
                            <h2 style='margin-top: 0; margin-bottom: 20px; font-size: 19px; color: #0F172A; font-weight: 700;'>{$safeTitle}</h2>
                            {$bodyHtml}
                            {$buttonHtml}
                            {$noticeHtml}
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td align='center' style='background-color: #F8FAFC; padding: 24px 20px; border-top: 1px solid #E2E8F0; font-size: 12px; color: #64748B; line-height: 1.5;'>
                            <p style='margin: 0;'>Bu e-posta <strong>BİTİRAS Güvenli Otomatik E-Posta Sistemi</strong> tarafından gönderilmiştir.</p>
                            <p style='margin: 6px 0 0;'>Eğer bu işlemi siz gerçekleştirmediyseniz, lütfen derhal destek ekibimizle iletişime geçin.</p>
                            <p style='margin: 8px 0 0; color: #94A3B8;'>&copy; " . date('Y') . " BİTİRAS. Tüm hakları saklıdır.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>";
}

/** Processing acceptance does not establish mailbox delivery. */
function dispatchAccountMail(string $email, string $name, string $subject, string $html, string $key, string $type): bool {
    $GLOBALS['account_mail_status'] = 'failed';
    try {
        $result = sendMailDirect($email, $name, $subject, $html);
        if ($result['success']) {
            $GLOBALS['account_mail_status'] = 'smtp_accepted';
            return true;
        }
        if (($result['retryable'] ?? false) && enqueueMail($email, $name, $subject, $html, $key, $type) !== false) {
            $GLOBALS['account_mail_status'] = 'queued';
            return true;
        }
    } catch (Throwable $e) { error_log('Account mail unavailable.'); }
    return false;
}
