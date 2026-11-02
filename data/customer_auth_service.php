<?php
// BİTİRAS — Müşteri Doğrulama ve Hesap Güvenliği Servisi
// E-posta doğrulama kodları, 1-tık onay bağlantıları ve şifre sıfırlama işlemleri.

require_once __DIR__ . '/security.php';
require_once __DIR__ . '/mail_service.php';

/**
 * Base site URL helper for verification and reset links.
 */
require_once __DIR__ . '/app_url.php';

/**
 * Generates and sends a 6-digit verification code and secure 1-click link to customer.
 */
function sendCustomerVerificationEmail(int $customerId, string $email, string $fullName): bool {
    try {
    global $pdo;
    if (!consumeMailRequestLimit('customer_verify', (string)$customerId)) return false;

    $code = (string)random_int(100000, 999999);
    $token = bin2hex(random_bytes(32));

    $codeHash = hash('sha256', $code);
    $tokenHash = hash('sha256', $token);
    $combinedHash = $codeHash . ':' . $tokenHash;

    $stmt = $pdo->prepare("UPDATE kullanicilar 
                           SET eposta_dogrulama_kodu_hash = ?,
                               eposta_dogrulama_son_tarih = DATE_ADD(NOW(), INTERVAL 15 MINUTE),
                               eposta_dogrulama_deneme = 0
                           WHERE id = ?");
    $stmt->execute([$combinedHash, $customerId]);

    $verifyUrl = getSiteBaseUrl() . '/dogrula.php?token=' . urlencode($token);

    $bodyHtml = "
    <p>Merhaba <strong>" . htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') . "</strong>,</p>
    <p>BiTıraş.com'a hoş geldiniz! Randevularınızı kolayca yönetmek ve hesap güvenliğinizi sağlamak için e-posta adresinizi doğrulamanız gerekmektedir.</p>
    
    <div style='background: #F8FAFC; border: 2px dashed #C28840; border-radius: 12px; padding: 22px; text-align: center; margin: 24px 0;'>
        <div style='font-size: 13px; text-transform: uppercase; letter-spacing: 1.5px; color: #64748B; font-weight: 600; margin-bottom: 8px;'>E-Posta Doğrulama Kodunuz</div>
        <div style='font-size: 34px; font-weight: 800; letter-spacing: 8px; color: #0F172A; font-family: monospace;'>{$code}</div>
        <div style='font-size: 12px; color: #94A3B8; margin-top: 8px;'>Bu kod 15 dakika boyunca geçerlidir.</div>
    </div>
    
    <p style='font-size: 14px; color: #475569;'>Profilinizdeki <strong>Hesabı Doğrula</strong> alanına yukarıdaki 6 haneli kodu girebilir veya aşağıdaki butona tıklayarak hesabınızı doğrudan doğrulayabilirsiniz:</p>
    ";

    $html = renderEmailTemplate(
        'E-Posta Adresinizi Doğrulayın',
        $bodyHtml,
        'Hesabımı Doğrula',
        $verifyUrl,
        'Bu hesabı siz oluşturmadıysanız, lütfen bu iletiyi dikkate almayınız.'
    );

    $mailAccepted = dispatchAccountMail($email, $fullName, 'BİTİRAS — E-Posta Doğrulama Kodunuz', $html, 'verify_user_' . $customerId . '_' . time(), 'musteri_kayit');

    return $mailAccepted;
    } catch (Throwable $e) {
        error_log('Account mail preparation unavailable.');
        return false;
    }
}

/**
 * Verifies a 6-digit code entered manually in the customer dashboard.
 */
function verifyCustomerEmailCode(int $customerId, string $inputCode): array {
    global $pdo;
    $attempt = $pdo->prepare("UPDATE kullanicilar SET eposta_dogrulama_deneme = eposta_dogrulama_deneme + 1 WHERE id = ? AND eposta_dogrulandi = 0 AND eposta_dogrulama_deneme < 5 AND eposta_dogrulama_son_tarih > NOW()");
    $attempt->execute([$customerId]);
    if ($attempt->rowCount() !== 1) return ['success'=>false, 'message'=>'Kod kullanilmis, suresi dolmus veya deneme siniri asilmis.'];

    $inputCode = trim($inputCode);
    if (strlen($inputCode) !== 6 || !ctype_digit($inputCode)) {
        return ['success' => false, 'message' => 'Lütfen 6 haneli doğrulama kodunu eksiksiz girin.'];
    }

    $stmt = $pdo->prepare("SELECT eposta_dogrulandi, eposta_dogrulama_kodu_hash, eposta_dogrulama_son_tarih, eposta_dogrulama_deneme 
                           FROM kullanicilar WHERE id = ?");
    $stmt->execute([$customerId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        return ['success' => false, 'message' => 'Kullanıcı bulunamadı.'];
    }

    if (!empty($user['eposta_dogrulandi'])) {
        return ['success' => true, 'message' => 'E-posta adresiniz zaten doğrulanmış.'];
    }

    if ((int)$user['eposta_dogrulama_deneme'] > 5) {
        return ['success' => false, 'message' => 'Çok fazla hatalı deneme yapıldı. Güvenliğiniz için lütfen yeni bir kod talep edin.'];
    }

    if (empty($user['eposta_dogrulama_kodu_hash']) || empty($user['eposta_dogrulama_son_tarih'])) {
        return ['success' => false, 'message' => 'Geçerli bir doğrulama kodu bulunamadı. Lütfen yeni kod isteyin.'];
    }

    if (strtotime($user['eposta_dogrulama_son_tarih']) < time()) {
        return ['success' => false, 'message' => 'Doğrulama kodunun geçerlilik süresi dolmuş. Lütfen yeni bir kod isteyin.'];
    }

    $parts = explode(':', (string)$user['eposta_dogrulama_kodu_hash']);
    $expectedCodeHash = $parts[0] ?? '';

    if (hash_equals($expectedCodeHash, hash('sha256', $inputCode))) {
        $upd = $pdo->prepare("UPDATE kullanicilar 
                              SET eposta_dogrulandi = 1,
                                  eposta_dogrulama_kodu_hash = NULL,
                                  eposta_dogrulama_son_tarih = NULL,
                                  eposta_dogrulama_deneme = 0
                              WHERE id = ? AND eposta_dogrulandi = 0 AND eposta_dogrulama_kodu_hash = ? AND eposta_dogrulama_son_tarih > NOW() AND eposta_dogrulama_deneme <= 5");
        $upd->execute([$customerId, $user['eposta_dogrulama_kodu_hash']]);
        if ($upd->rowCount() !== 1) return ['success'=>false, 'message'=>'Kod kullanilmis veya gecersiz.'];
        return ['success' => true, 'message' => 'E-posta adresiniz başarıyla doğrulandı!'];
    }

    return ['success' => false, 'message' => 'Girdiğiniz doğrulama kodu hatalı. Lütfen kontrol edip tekrar deneyin.'];
}

/**
 * Verifies customer email via 1-click token link from the email.
 */
function verifyCustomerEmailToken(string $token): array {
    global $pdo;

    $token = trim($token);
    if (strlen($token) !== 64 || !ctype_xdigit($token)) {
        return ['success' => false, 'message' => 'Geçersiz doğrulama bağlantısı.'];
    }

    $tokenHash = hash('sha256', $token);

    $stmt = $pdo->prepare("SELECT id, ad_soyad, eposta, eposta_dogrulama_kodu_hash, eposta_dogrulama_son_tarih 
                           FROM kullanicilar 
                           WHERE eposta_dogrulandi = 0 
                             AND eposta_dogrulama_son_tarih > NOW()
                             AND SUBSTRING_INDEX(eposta_dogrulama_kodu_hash, ':', -1) = ? LIMIT 1");
    $stmt->execute([$tokenHash]);
    $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($candidates as $cand) {
        $parts = explode(':', (string)$cand['eposta_dogrulama_kodu_hash']);
        $expectedTokenHash = $parts[1] ?? '';
        if (!empty($expectedTokenHash) && hash_equals($expectedTokenHash, $tokenHash)) {
            $upd = $pdo->prepare("UPDATE kullanicilar 
                                  SET eposta_dogrulandi = 1,
                                      eposta_dogrulama_kodu_hash = NULL,
                                      eposta_dogrulama_son_tarih = NULL,
                                      eposta_dogrulama_deneme = 0
                                  WHERE id = ? AND eposta_dogrulandi = 0 AND eposta_dogrulama_kodu_hash = ? AND eposta_dogrulama_son_tarih > NOW()");
            $upd->execute([$cand['id'], $cand['eposta_dogrulama_kodu_hash']]);
            if ($upd->rowCount() !== 1) break;
            return [
                'success' => true,
                'message' => 'Tebrikler! E-posta adresiniz başarıyla doğrulandı.',
                'user'    => $cand
            ];
        }
    }

    return ['success' => false, 'message' => 'Doğrulama bağlantısı geçersiz veya süresi dolmuş.'];
}

/**
 * Sends a password reset link without leaking whether the account exists.
 */
function requestPasswordReset(string $email): bool {
    try {
    global $pdo;

    $email = strtolower(trim($email));
    if (!consumeMailRequestLimit('password_reset', $email)) return true;
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return true; // Always return generic true to avoid user enumeration
    }

    $stmt = $pdo->prepare("SELECT id, ad_soyad FROM kullanicilar WHERE LOWER(eposta) = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);

        $pdo->prepare("UPDATE kullanicilar 
                       SET sifre_sifirlama_token_hash = ?,
                           sifre_sifirlama_son_tarih = DATE_ADD(NOW(), INTERVAL 30 MINUTE),
                           sifre_sifirlama_deneme = 0
                       WHERE id = ?")
            ->execute([$tokenHash, $user['id']]);

        $resetUrl = getSiteBaseUrl() . '/sifre_sifirla.php?token=' . urlencode($token);

        $bodyHtml = "
        <p>Merhaba <strong>" . htmlspecialchars($user['ad_soyad'], ENT_QUOTES, 'UTF-8') . "</strong>,</p>
        <p>BiTıraş.com hesabınız için şifre sıfırlama talebinde bulunuldu. Şifrenizi yenilemek için aşağıdaki butona tıklayabilirsiniz:</p>
        ";

        $html = renderEmailTemplate(
            'Şifre Sıfırlama Talebi',
            $bodyHtml,
            'Şifremi Sıfırla',
            $resetUrl,
            'Bu talebi siz yapmadıysanız şifreniz değişmeyecektir. Güvenliğiniz için bu bağlantıyı kimseyle paylaşmayın. Bağlantı 30 dakika geçerlidir.'
        );

        $mailAccepted = dispatchAccountMail($email, $user['ad_soyad'], 'BİTİRAS — Şifre Sıfırlama Bağlantısı', $html, 'pwd_reset_' . $user['id'] . '_' . time(), 'sifre_sifirla');
    }

    return true;
    } catch (Throwable $e) { error_log('Password reset request unavailable.'); }
    return true;
}

/**
 * Resets user password with valid reset token.
 */
function resetPasswordWithToken(string $token, string $newPassword): array {
    global $pdo;

    $token = trim($token);
    if (strlen($token) !== 64 || !ctype_xdigit($token)) {
        return ['success' => false, 'message' => 'Geçersiz şifre sıfırlama bağlantısı.'];
    }

    if (!validNewPassword($newPassword)) {
        return ['success' => false, 'message' => 'Şifreniz en az 10, en fazla 72 karakter uzunluğunda olmalıdır.'];
    }

    $tokenHash = hash('sha256', $token);

    $stmt = $pdo->prepare("SELECT id, ad_soyad, eposta, sifre_sifirlama_token_hash, sifre_sifirlama_son_tarih, sifre_sifirlama_deneme 
                           FROM kullanicilar 
                           WHERE sifre_sifirlama_token_hash = ? 
                             AND sifre_sifirlama_son_tarih > NOW()
                           LIMIT 1");
    $stmt->execute([$tokenHash]);
    $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $matchedUser = null;
    foreach ($candidates as $cand) {
        if (hash_equals($cand['sifre_sifirlama_token_hash'], $tokenHash)) {
            $matchedUser = $cand;
            break;
        }
    }

    if (!$matchedUser) {
        return ['success' => false, 'message' => 'Şifre sıfırlama bağlantısının süresi dolmuş veya bağlantı geçersiz. Lütfen tekrar sıfırlama talebinde bulunun.'];
    }

    if ((int)$matchedUser['sifre_sifirlama_deneme'] >= 5) {
        return ['success' => false, 'message' => 'Çok fazla deneme yapıldı. Lütfen yeni bir şifre sıfırlama bağlantısı talep edin.'];
    }

    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);

    $upd = $pdo->prepare("UPDATE kullanicilar 
                          SET sifre_hash = ?,
                              sifre_sifirlama_token_hash = NULL,
                              sifre_sifirlama_son_tarih = NULL,
                              sifre_sifirlama_deneme = 0
                          WHERE id = ? AND sifre_sifirlama_token_hash = ? AND sifre_sifirlama_son_tarih > NOW() AND sifre_sifirlama_deneme < 5");
    $upd->execute([$newHash, $matchedUser['id'], $tokenHash]);
    if ($upd->rowCount() !== 1) return ['success'=>false, 'message'=>'Baglanti kullanilmis veya gecersiz.'];

    // Send confirmation email
    sendPasswordChangedNotification((int)$matchedUser['id'], $matchedUser['eposta'], $matchedUser['ad_soyad']);

    return ['success' => true, 'message' => 'Şifreniz başarıyla yenilendi! Yeni şifrenizle giriş yapabilirsiniz.'];
}

/**
 * Sends security alert when customer password is changed.
 */
function sendPasswordChangedNotification(int $userId, string $email, string $fullName): void {
    try {
    $now = date('d.m.Y H:i');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Belirtilmedi';

    $bodyHtml = "
    <p>Merhaba <strong>" . htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') . "</strong>,</p>
    <p>BiTıraş.com hesabınızın şifresi <strong>{$now}</strong> tarihinde başarıyla değiştirildi.</p>
    <div style='background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; padding: 16px; margin: 20px 0; font-size: 13px; color: #475569;'>
        <div><strong>İşlem Zamanı:</strong> {$now}</div>
        <div><strong>İşlem Yapılan IP:</strong> " . htmlspecialchars($ip, ENT_QUOTES, 'UTF-8') . "</div>
    </div>
    <p>Bu işlemi siz gerçekleştirdiyseniz herhangi bir işlem yapmanıza gerek yoktur.</p>
    ";

    $html = renderEmailTemplate(
        'Hesap Güvenlik Bildirimi: Şifreniz Güncellendi',
        $bodyHtml,
        null,
        null,
        'Bu işlemi siz gerçekleştirmediyseniz, hesabınız tehlikede olabilir. Lütfen derhal destek ekibimizle iletişime geçin.'
    );

    $mailAccepted = dispatchAccountMail($email, $fullName, 'BİTİRAS — Şifreniz Güncellendi', $html, 'pwd_changed_' . $userId . '_' . time(), 'guvenlik_uyari');
    } catch (Throwable $e) {
        error_log('Account mail preparation unavailable.');
        return;
    }
}
