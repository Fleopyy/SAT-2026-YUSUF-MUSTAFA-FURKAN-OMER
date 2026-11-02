<?php
// BİTİRAS — İki Aşamalı Doğrulama (2FA) ve Risk Bazlı Cihaz Tanıma Servisi
// TOTP (RFC 6238), E-Posta OTP, Kurtarma Kodları ve Güvenilir Cihaz Yönetimi

require_once __DIR__ . '/security.php';
require_once __DIR__ . '/mail_service.php';

// =========================================================================
// 1. TOTP (RFC 6238) - Pure PHP Time-Based One-Time Password Implementation
// =========================================================================

function generateTotpSecret(int $byteCount = 16): string {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $bytes = random_bytes($byteCount);
    $secret = '';
    for ($i = 0; $i < $byteCount; $i++) {
        $secret .= $chars[ord($bytes[$i]) & 31];
    }
    return $secret;
}

function base32Decode(string $b32): string {
    $b32 = strtoupper(preg_replace('/[^A-Z2-7]/', '', $b32));
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $buffer = 0;
    $bitsLeft = 0;
    $output = '';

    for ($i = 0, $len = strlen($b32); $i < $len; $i++) {
        $val = strpos($chars, $b32[$i]);
        if ($val === false) continue;
        $buffer = ($buffer << 5) | $val;
        $bitsLeft += 5;
        if ($bitsLeft >= 8) {
            $bitsLeft -= 8;
            $output .= chr(($buffer >> $bitsLeft) & 0xFF);
        }
    }
    return $output;
}

function calculateTotpCode(string $secret, ?int $timeSlice = null): string {
    $timeSlice = $timeSlice ?? (int)floor(time() / 30);
    $key = base32Decode($secret);
    $timeBytes = pack('J', $timeSlice); // 64-bit unsigned big-endian integer

    $hash = hash_hmac('sha1', $timeBytes, $key, true);
    $offset = ord($hash[strlen($hash) - 1]) & 0x0F;

    $binary = ((ord($hash[$offset]) & 0x7F) << 24) |
              ((ord($hash[$offset + 1]) & 0xFF) << 16) |
              ((ord($hash[$offset + 2]) & 0xFF) << 8) |
              (ord($hash[$offset + 3]) & 0xFF);

    $code = $binary % 1000000;
    return str_pad((string)$code, 6, '0', STR_PAD_LEFT);
}

function verifyTotpCode(string $secret, string $inputCode, int $window = 1): bool {
    $inputCode = trim($inputCode);
    if (strlen($inputCode) !== 6 || !ctype_digit($inputCode)) {
        return false;
    }
    $currentTimeSlice = (int)floor(time() / 30);
    for ($offset = -$window; $offset <= $window; $offset++) {
        if (hash_equals(calculateTotpCode($secret, $currentTimeSlice + $offset), $inputCode)) {
            return true;
        }
    }
    return false;
}

function generateRecoveryCodes(int $count = 8): array {
    $plain = [];
    $hashed = [];
    for ($i = 0; $i < $count; $i++) {
        $p1 = strtoupper(bin2hex(random_bytes(2)));
        $p2 = strtoupper(bin2hex(random_bytes(2)));
        $code = "{$p1}-{$p2}";
        $plain[] = $code;
        $hashed[] = hash('sha256', $code);
    }
    return ['plain' => $plain, 'hashed' => $hashed];
}

function verifyAndConsumeRecoveryCode(PDO $pdo, int $adminId, string $inputCode): bool {
    $inputCode = strtoupper(trim($inputCode));
    $stmt = $pdo->prepare("SELECT kurtarma_kodlari_hash FROM yoneticiler WHERE id = ?");
    $stmt->execute([$adminId]);
    $raw = $stmt->fetchColumn();
    if (empty($raw)) return false;

    $hashes = json_decode($raw, true);
    if (!is_array($hashes)) return false;

    $inputHash = hash('sha256', $inputCode);
    $foundIndex = -1;
    foreach ($hashes as $idx => $storedHash) {
        if (hash_equals($storedHash, $inputHash)) {
            $foundIndex = $idx;
            break;
        }
    }

    if ($foundIndex >= 0) {
        unset($hashes[$foundIndex]);
        $newJson = json_encode(array_values($hashes));
        $consume = $pdo->prepare("UPDATE yoneticiler SET kurtarma_kodlari_hash = ? WHERE id = ? AND kurtarma_kodlari_hash = ?");
        $consume->execute([$newJson, $adminId, $raw]);
        return $consume->rowCount() === 1;
    }
    return false;
}

// =========================================================================
// 2. E-Posta 2FA Kodları (Admin & İşletme)
// =========================================================================

function sendAdmin2faEmail(int $adminId, string $email, string $fullName): bool {
    try {
    global $pdo;

    if (!consumeMailRequestLimit('mfa_send', (string)$adminId)) return false;
    $code = (string)random_int(100000, 999999);
    $codeHash = hash('sha256', $code);

    $pdo->prepare("UPDATE yoneticiler 
                   SET eposta_2fa_kod_hash = ?,
                       eposta_2fa_son_tarih = DATE_ADD(NOW(), INTERVAL 5 MINUTE),
                       eposta_2fa_deneme = 0
                   WHERE id = ?")
        ->execute([$codeHash, $adminId]);

    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Belirtilmedi';
    $timeStr = date('H:i:s d.m.Y');

    $bodyHtml = "
    <p>Merhaba <strong>" . htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') . "</strong>,</p>
    <p>Yönetici paneline giriş yapabilmek için gereken tek kullanımlık güvenlik kodunuz aşağıdadır:</p>
    
    <div style='background: #F8FAFC; border: 2px dashed #C28840; border-radius: 12px; padding: 20px; text-align: center; margin: 24px 0;'>
        <div style='font-size: 12px; text-transform: uppercase; letter-spacing: 1.5px; color: #64748B; font-weight: 700; margin-bottom: 6px;'>Yönetici 2FA Doğrulama Kodu</div>
        <div style='font-size: 34px; font-weight: 800; letter-spacing: 8px; color: #0F172A; font-family: monospace;'>{$code}</div>
        <div style='font-size: 12px; color: #DC2626; margin-top: 8px; font-weight: 600;'>Bu kod yalnızca 5 dakika geçerlidir.</div>
    </div>
    
    <div style='font-size: 13px; color: #64748B; background: #FFFDF5; border: 1px solid #FDE68A; border-radius: 8px; padding: 12px;'>
        <div><strong>Giriş Saati:</strong> {$timeStr}</div>
        <div><strong>Talep Eden IP:</strong> " . htmlspecialchars($ip, ENT_QUOTES, 'UTF-8') . "</div>
    </div>
    ";

    $html = renderEmailTemplate(
        'Yönetici Paneli İki Aşamalı Doğrulama Kodu',
        $bodyHtml,
        null,
        null,
        'Bu giriş girişimini siz yapmadıysanız lütfen derhal sistem yöneticisi ile iletişime geçin ve parolanızı değiştirin.'
    );

    // Gmail'in önceki maillerle birleştirmesini (thread) önlemek için konuya saat:dakika eklenir
    $subject = 'BİTİRAS — Yönetici 2FA Giriş Kodu [' . date('H:i') . ']';
    $mailAccepted = dispatchAccountMail($email, $fullName, $subject, $html, 'admin_2fa_' . $adminId . '_' . time(), 'admin_2fa');
    return $mailAccepted;
    } catch (Throwable $e) {
        error_log('Account mail preparation unavailable.');
        return false;
    }
}

function verifyAdmin2faCode(int $adminId, string $inputCode): array {
    global $pdo;
    $attempt = $pdo->prepare("UPDATE yoneticiler SET eposta_2fa_deneme = eposta_2fa_deneme + 1 WHERE id = ? AND eposta_2fa_deneme < 8");
    $attempt->execute([$adminId]);
    if ($attempt->rowCount() !== 1) return ['success'=>false, 'message'=>'Deneme siniri asildi.'];


    // Girdi temizleme: Boşlukları ve görünmez karakterleri yok et
    $raw = trim($inputCode);
    $cleanNumeric = preg_replace('/\D/', '', $raw);
    if (empty($raw)) {
        return ['success' => false, 'message' => 'Lütfen doğrulama kodunu girin.'];
    }

    $stmt = $pdo->prepare("SELECT eposta_2fa_kod_hash, eposta_2fa_son_tarih, eposta_2fa_deneme, totp_gizli_anahtar, kurtarma_kodlari_hash, iki_adimli_tur 
                           FROM yoneticiler WHERE id = ?");
    $stmt->execute([$adminId]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin) {
        return ['success' => false, 'message' => 'Yönetici hesabı bulunamadı.'];
    }

    if ((int)$admin['eposta_2fa_deneme'] > 8) {
        return ['success' => false, 'message' => 'Çok fazla hatalı deneme yapıldı. Güvenliğiniz için lütfen yeniden giriş yapın.'];
    }

    // Aday kodlar (Temiz 6 hane, ham metin, büyük harf)
    $candidates = array_unique(array_filter([$cleanNumeric, $raw, strtoupper($raw)]));

    // 1. Try TOTP Code if secret exists
    if (!empty($admin['totp_gizli_anahtar'])) {
        foreach ($candidates as $cand) {
            $slice = matchingTotpTimeSlice($admin['totp_gizli_anahtar'], $cand);
            if ($slice !== null) {
                if (!consumeAdminTotpSlice($pdo, $adminId, $admin['totp_gizli_anahtar'], $slice)) {
                    return ['success'=>false, 'message'=>'Kod kullanilmis veya dogrulama kullanilamiyor.'];
                }
                return ['success'=>true, 'message'=>'TOTP dogrulamasi basarili.'];
            }
        }
    }

    // 2. Try Recovery Code
    foreach ($candidates as $cand) {
        if (str_contains($cand, '-') && verifyAndConsumeRecoveryCode($pdo, $adminId, $cand)) {
            return ['success' => true, 'message' => 'Kurtarma kodu ile giriş başarılı. Lütfen yeni kurtarma kodları oluşturun.'];
        }
    }

    // 3. Try Email 2FA Code
    $now = time();
    $matched = false;

    // DB'deki aktif kod kontrolü
    if (!empty($admin['eposta_2fa_kod_hash']) && !empty($admin['eposta_2fa_son_tarih'])) {
        $expiryTime = strtotime($admin['eposta_2fa_son_tarih']);
        if ($expiryTime >= $now) {
            foreach ($candidates as $cand) {
                if (hash_equals($admin['eposta_2fa_kod_hash'], hash('sha256', $cand))) {
                    $matched = true;
                    break;
                }
            }
        }
    }

    if ($matched) {
        $consume = $pdo->prepare("UPDATE yoneticiler SET eposta_2fa_kod_hash = NULL, eposta_2fa_son_tarih = NULL, eposta_2fa_deneme = 0 WHERE id = ? AND eposta_2fa_kod_hash = ? AND eposta_2fa_son_tarih > NOW() AND eposta_2fa_deneme <= 8");
        $consume->execute([$adminId, $admin['eposta_2fa_kod_hash']]);
        if ($consume->rowCount() !== 1) return ['success'=>false, 'message'=>'Kod kullanilmis veya gecersiz.'];
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['2fa_pending']['valid_hashes'])) {
            unset($_SESSION['2fa_pending']['valid_hashes']);
        }
        return ['success' => true, 'message' => 'E-posta doğrulaması başarılı.'];
    }

    return ['success' => false, 'message' => 'Girdiğiniz doğrulama kodu hatalı. Lütfen tekrar deneyin.'];
}

function sendBarber2faEmail(int $barberId, string $email, string $shopName): bool {
    try {
    global $pdo;

    if (!consumeMailRequestLimit('barber_mfa_send', (string)$barberId)) return false;
    $code = (string)random_int(100000, 999999);
    $codeHash = hash('sha256', $code);

    $pdo->prepare("UPDATE isletmeler 
                   SET eposta_2fa_kod_hash = ?,
                       eposta_2fa_son_tarih = DATE_ADD(NOW(), INTERVAL 5 MINUTE),
                       eposta_2fa_deneme = 0
                   WHERE id = ?")
        ->execute([$codeHash, $barberId]);

    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Belirtilmedi';
    $timeStr = date('H:i:s d.m.Y');

    $bodyHtml = "
    <p>Merhaba <strong>" . htmlspecialchars($shopName, ENT_QUOTES, 'UTF-8') . "</strong>,</p>
    <p>İşletme panelinize yeni veya tanınmayan bir cihazdan giriş yapılıyor. Hesabınızın güvenliği için tek kullanımlık onay kodunuz aşağıdadır:</p>
    
    <div style='background: #F8FAFC; border: 2px dashed #C28840; border-radius: 12px; padding: 20px; text-align: center; margin: 24px 0;'>
        <div style='font-size: 12px; text-transform: uppercase; letter-spacing: 1.5px; color: #64748B; font-weight: 700; margin-bottom: 6px;'>İşletme Güvenlik Giriş Kodu</div>
        <div style='font-size: 34px; font-weight: 800; letter-spacing: 8px; color: #0F172A; font-family: monospace;'>{$code}</div>
        <div style='font-size: 12px; color: #DC2626; margin-top: 8px; font-weight: 600;'>Bu kod yalnızca 5 dakika geçerlidir.</div>
    </div>
    
    <div style='font-size: 13px; color: #64748B; background: #FFFDF5; border: 1px solid #FDE68A; border-radius: 8px; padding: 12px;'>
        <div><strong>Giriş Saati:</strong> {$timeStr}</div>
        <div><strong>Cihaz IP:</strong> " . htmlspecialchars($ip, ENT_QUOTES, 'UTF-8') . "</div>
    </div>
    ";

    $html = renderEmailTemplate(
        'İşletme Girişi İçin Güvenlik Kodu',
        $bodyHtml,
        null,
        null,
        'Bu girişi siz başlatmadıysanız şifrenizi derhal yenileyin.'
    );

    $mailAccepted = dispatchAccountMail($email, $shopName, 'BİTİRAS — İşletme Girişi Doğrulama Kodu', $html, 'barber_2fa_' . $barberId . '_' . time(), 'isletme_2fa');
    return $mailAccepted;
    } catch (Throwable $e) {
        error_log('Account mail preparation unavailable.');
        return false;
    }
}

function verifyBarber2faCode(int $barberId, string $inputCode): array {
    global $pdo;
    $attempt = $pdo->prepare("UPDATE isletmeler SET eposta_2fa_deneme = eposta_2fa_deneme + 1 WHERE id = ? AND eposta_2fa_deneme < 5");
    $attempt->execute([$barberId]);
    if ($attempt->rowCount() !== 1) return ['success'=>false, 'message'=>'Deneme siniri asildi.'];


    $inputCode = trim($inputCode);
    if (strlen($inputCode) !== 6 || !ctype_digit($inputCode)) {
        return ['success' => false, 'message' => 'Lütfen 6 haneli doğrulama kodunu girin.'];
    }

    $stmt = $pdo->prepare("SELECT eposta_2fa_kod_hash, eposta_2fa_son_tarih, eposta_2fa_deneme 
                           FROM isletmeler WHERE id = ?");
    $stmt->execute([$barberId]);
    $barber = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$barber) {
        return ['success' => false, 'message' => 'İşletme hesabı bulunamadı.'];
    }

    if ((int)$barber['eposta_2fa_deneme'] > 5) {
        return ['success' => false, 'message' => 'Çok fazla hatalı deneme yapıldı. Lütfen tekrar giriş yapın.'];
    }

    if (empty($barber['eposta_2fa_kod_hash']) || empty($barber['eposta_2fa_son_tarih'])) {
        return ['success' => false, 'message' => 'Geçerli bir doğrulama kodu bulunamadı.'];
    }

    if (strtotime($barber['eposta_2fa_son_tarih']) < time()) {
        return ['success' => false, 'message' => 'Doğrulama kodunun 5 dakikalık süresi dolmuş.'];
    }

    if (hash_equals($barber['eposta_2fa_kod_hash'], hash('sha256', $inputCode))) {
        $consume = $pdo->prepare("UPDATE isletmeler 
                      SET eposta_2fa_kod_hash = NULL,
                          eposta_2fa_son_tarih = NULL,
                          eposta_2fa_deneme = 0,
                          son_giris = NOW()
                      WHERE id = ? AND eposta_2fa_kod_hash = ? AND eposta_2fa_son_tarih > NOW() AND eposta_2fa_deneme <= 5");
        $consume->execute([$barberId, $barber['eposta_2fa_kod_hash']]);
        if ($consume->rowCount() !== 1) return ['success'=>false, 'message'=>'Kod kullanilmis veya gecersiz.'];
        return ['success' => true, 'message' => 'Doğrulama başarılı.'];
    }



    return ['success' => false, 'message' => 'Girdiğiniz doğrulama kodu hatalı.'];
}

// =========================================================================
// 3. Risk Bazlı Cihaz Tanıma & Güvenilir Cihazlar (guvenilir_cihazlar)
// =========================================================================

function getDeviceCookieName(): string {
    return 'bitiras_device_token';
}

function isTrustedDevice(string $userType, int $userId): bool {
    global $pdo;

    $cookieToken = $_COOKIE[getDeviceCookieName()] ?? '';
    if (empty($cookieToken) || strlen($cookieToken) !== 64 || !ctype_xdigit($cookieToken)) {
        return false;
    }

    $tokenHash = hash('sha256', $cookieToken);

    $stmt = $pdo->prepare("SELECT id, son_gecerlilik FROM guvenilir_cihazlar 
                           WHERE kullanici_turu = ? 
                             AND kullanici_id = ? 
                             AND cihaz_belirtec_hash = ? 
                             AND son_gecerlilik > NOW() 
                           LIMIT 1");
    $stmt->execute([$userType, $userId, $tokenHash]);
    $device = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($device) {
        $pdo->prepare("UPDATE guvenilir_cihazlar SET son_kullanim = NOW() WHERE id = ?")
            ->execute([$device['id']]);
        return true;
    }
    return false;
}

function registerTrustedDevice(string $userType, int $userId, int $validDays = 30): string {
    global $pdo;

    $validDays = max(1, min(30, $validDays));
    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);

    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $ua = mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 250);
    $deviceLabel = 'Web Tarayıcı (' . date('d.m.Y H:i') . ')';

    $stmt = $pdo->prepare("INSERT INTO guvenilir_cihazlar 
                          (kullanici_turu, kullanici_id, cihaz_belirtec_hash, cihaz_adi, ip_adresi, user_agent, son_kullanim, son_gecerlilik) 
                          VALUES (?, ?, ?, ?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ? DAY))");
    $stmt->execute([$userType, $userId, $tokenHash, $deviceLabel, $ip, $ua, $validDays]);

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $expireTimestamp = time() + ($validDays * 86400);

    if (!headers_sent()) {
        setcookie(getDeviceCookieName(), $token, [
            'expires'  => $expireTimestamp,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
    $_COOKIE[getDeviceCookieName()] = $token;

    return $token;
}

/**
 * Risk-based analysis for Barber Login.
 * Conditions requiring 2FA:
 * 1. Untrusted or new device (cookie token not recognized in guvenilir_cihazlar)
 * 2. Inactive account (no login in the last 14 days)
 */
function evaluateBarberLoginRisk(array $barber): array {
    $barberId = (int)$barber['id'];
    $trusted = isTrustedDevice('isletme', $barberId);

    if (!$trusted) {
        return [
            'requires_2fa' => true,
            'reason'       => 'Yeni veya tanınmayan cihazdan giriş'
        ];
    }

    if (empty($barber['son_giris'])) {
        return [
            'requires_2fa' => true,
            'reason'       => 'İlk giriş güvenlik doğrulaması'
        ];
    }

    $lastLoginTs = strtotime((string)$barber['son_giris']);
    if ($lastLoginTs < (time() - 14 * 86400)) {
        return [
            'requires_2fa' => true,
            'reason'       => '14 gündür kullanılmayan hesap doğrulaması'
        ];
    }

    return [
        'requires_2fa' => false,
        'reason'       => 'Güvenilir cihaz'
    ];
}

function sendLoginSecurityAlert(string $userType, int $userId, string $email, string $fullName): void {
    try {
    $now = date('d.m.Y H:i');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Belirtilmedi';
    $ua = mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? 'Belirtilmedi'), 0, 150);

    $bodyHtml = "
    <p>Merhaba <strong>" . htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') . "</strong>,</p>
    <p>Hesabınıza yeni bir cihazdan başarılı giriş yapıldı.</p>
    
    <div style='background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; padding: 16px; margin: 20px 0; font-size: 13px; color: #475569;'>
        <div><strong>Giriş Zamanı:</strong> {$now}</div>
        <div><strong>IP Adresi:</strong> " . htmlspecialchars($ip, ENT_QUOTES, 'UTF-8') . "</div>
        <div><strong>Tarayıcı:</strong> " . htmlspecialchars($ua, ENT_QUOTES, 'UTF-8') . "</div>
    </div>
    
    <p>Bu giriş sizin tarafınızdan yapıldıysa herhangi bir işlem yapmanıza gerek yoktur.</p>
    ";

    $html = renderEmailTemplate(
        'Yeni Cihazdan Giriş Güvenlik Bildirimi',
        $bodyHtml,
        null,
        null,
        'Bu işlemi siz gerçekleştirmediyseniz, hesabınız ele geçirilmiş olabilir. Lütfen derhal şifrenizi yenileyin ve destek ekibimizle iletişime geçin.'
    );

    $mailAccepted = dispatchAccountMail($email, $fullName, 'BİTİRAS — Yeni Cihazdan Giriş Bildirimi', $html, 'new_dev_' . $userType . '_' . $userId . '_' . time(), 'guvenlik_uyari');
    } catch (Throwable $e) {
        error_log('Account mail preparation unavailable.');
        return;
    }
}

// Called only after password verification and active-account checks.
function completeBarberPasswordLogin(array $barber, string $loginInput, string $destination): void {
    global $pdo;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (!in_array($destination, ['/isletme/', '/barber/dashboard'], true)) {
        throw new RuntimeException('Invalid login destination.');
    }
    $risk = evaluateBarberLoginRisk($barber);
    if ($risk['requires_2fa']) {
        unset($_SESSION['barber_id'], $_SESSION['barber_role'], $_SESSION['barber_name'], $_SESSION['admin_id'], $_SESSION['admin_role'], $_SESSION['admin_name'], $_SESSION['manager_id'], $_SESSION['manager_role'], $_SESSION['manager_name'], $_SESSION['user_id'], $_SESSION['user_role'], $_SESSION['user_name']);
        $_SESSION['2fa_pending'] = [
            'type'     => 'barber',
            'user_id'  => (int)$barber['id'],
            'rol'      => 'barber',
            'name'     => $barber['isletme_adi'],
            'email'    => $barber['eposta'],
            'hash'     => $barber['sifre_hash'],
            'reason'   => $risk['reason'],
            'expires'  => time() + 300,
            'destination' => $destination,
        ];
        $mailAccepted = sendBarber2faEmail((int)$barber['id'], $barber['eposta'], $barber['isletme_adi']);
        $_SESSION['2fa_resend_flash'] = $mailAccepted ? 'Kod gonderim icin kabul edildi; teslimat henuz dogrulanmadi.' : 'Kod gonderilemedi. Lutfen yeniden kod isteyin.';
        recordLoginAttempt($ip, $loginInput, true);
        header('Location: /isletme/giris.php?step=2fa');
        exit;
    } else {
        // Güvenilir cihaz doğrudan giriş
        renewAuthenticatedSession('barber', $barber['sifre_hash']);
        unset($_SESSION['admin_id'], $_SESSION['admin_role'], $_SESSION['admin_name']);
        unset($_SESSION['manager_id'], $_SESSION['manager_role'], $_SESSION['manager_name']);
    
        unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['2fa_pending']);
        $_SESSION['user_role'] = 'barber';
        $_SESSION['barber_id'] = $barber['id'];
        $_SESSION['barber_role'] = 'barber';
        $_SESSION['barber_name'] = $barber['isletme_adi'];
        $pdo->prepare("UPDATE isletmeler SET son_giris = NOW() WHERE id = ?")->execute([$barber['id']]);
        recordLoginAttempt($ip, $loginInput, true);
    
        header('Location: ' . $destination);
        exit;
    }
}

function matchingTotpTimeSlice(string $secret, string $code, ?int $now = null): ?int {
    if (strlen($code) !== 6 || !ctype_digit($code)) return null;
    $current = (int)floor(($now ?? time()) / 30);
    for ($slice = $current + 1; $slice >= $current - 1; $slice--) {
        if (hash_equals(calculateTotpCode($secret, $slice), $code)) return $slice;
    }
    return null;
}

function consumeAdminTotpSlice($pdo, int $adminId, string $secret, int $slice): bool {
    try {
        $stmt = $pdo->prepare("UPDATE yoneticiler SET son_totp_zaman_dilimi = ?, eposta_2fa_kod_hash = NULL, eposta_2fa_son_tarih = NULL, eposta_2fa_deneme = 0 WHERE id = ? AND totp_gizli_anahtar = ? AND (son_totp_zaman_dilimi IS NULL OR son_totp_zaman_dilimi < ?)");
        $stmt->execute([$slice, $adminId, $secret, $slice]);
        return $stmt->rowCount() === 1;
    } catch (Throwable $e) {
        error_log('TOTP consumption unavailable.');
        return false;
    }
}
