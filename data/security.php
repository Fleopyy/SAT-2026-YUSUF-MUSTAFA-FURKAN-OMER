<?php
// HTTP security headers are owned by the root .htaccess (Apache mod_headers).
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/database.php';

function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token = null): bool {
    $token = $token ?? ($_POST['csrf_token'] ?? '');
    if (!is_string($token) || empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function requireCsrf(): void {
    if (!verifyCsrfToken()) {
        http_response_code(403);
        die(json_encode(['success' => false, 'message' => 'Geçersiz güvenlik tokeni (CSRF). Lütfen sayfayı yenileyip tekrar deneyin.']));
    }
}

function checkLoginRateLimit(string $ip, int $maxAttempts = 5, int $windowMinutes = 15): bool {              
    global $pdo;
    $windowMinutes = max(1, $windowMinutes);
    $account = strtolower(trim((string)($_POST['eposta'] ?? $_POST['email'] ?? $_POST['login'] ?? $_POST['telefon'] ?? $_POST['business_login'] ?? '')));
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM giris_denemeleri WHERE (ip_adresi = ? OR LOWER(eposta) = ?) AND basarili = 0 AND olusturulma_tarihi > DATE_SUB(NOW(), INTERVAL ? MINUTE)");
    $stmt->execute([$ip, $account, $windowMinutes]);
    return (int)$stmt->fetchColumn() < $maxAttempts;
}

function recordLoginAttempt(string $ip, ?string $email, bool $successful): void {
    global $pdo;
    
    $stmt = $pdo->prepare("INSERT INTO giris_denemeleri (ip_adresi, eposta, basarili) VALUES (?, ?, ?)");
    $stmt->execute([$ip, $email, $successful ? 1 : 0]);
}


function validNewPassword(string $password): bool {
    return strlen($password) >= 10 && strlen($password) <= 72;
}

// A digest of the current password record acts as a revocation version, without a schema change.
function bindPasswordSession(string $kind, string $hash): void {
    $_SESSION[$kind . '_password_version'] = hash('sha256', $hash);
}
function renewAuthenticatedSession(string $kind, string $hash): void {
    foreach (['user', 'barber', 'admin', 'manager'] as $prefix) {
        unset($_SESSION[$prefix . '_id'], $_SESSION[$prefix . '_role'], $_SESSION[$prefix . '_name'], $_SESSION[$prefix . '_password_version']);
    }
    session_regenerate_id(true);
    unset($_SESSION['csrf_token']);
    bindPasswordSession($kind, $hash);
}
function passwordSessionMatches(string $kind, $hash): bool {
    $version = $_SESSION[$kind . '_password_version'] ?? null;
    return is_string($hash) && is_string($version) && hash_equals(hash('sha256', $hash), $version);
}
function validatePasswordSessions(): void {
    global $pdo;
    foreach (['user' => 'kullanicilar', 'barber' => 'isletmeler', 'admin' => 'yoneticiler', 'manager' => 'yoneticiler'] as $kind => $table) {
        if (empty($_SESSION[$kind . '_id'])) continue;
        $stmt = $pdo->prepare("SELECT sifre_hash FROM $table WHERE id = ?");
        $stmt->execute([$_SESSION[$kind . '_id']]);
        if (!passwordSessionMatches($kind, $stmt->fetchColumn())) {
            unset($_SESSION[$kind . '_id'], $_SESSION[$kind . '_role'], $_SESSION[$kind . '_name'], $_SESSION[$kind . '_password_version']);
            if ($kind === 'barber' && ($_SESSION['user_role'] ?? '') === 'barber') unset($_SESSION['user_role']);
        }
    }
}
validatePasswordSessions();
