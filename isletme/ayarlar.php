<?php
require_once __DIR__ . '/_common.php';
$currentBusinessPage = 'settings';
$pageTitle = 'Hesap Ayarları';
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    businessRequirePost();
    $currentPassword = is_string($_POST['current_password'] ?? null) ? $_POST['current_password'] : '';
    $newPassword = is_string($_POST['new_password'] ?? null) ? $_POST['new_password'] : '';
    $confirmPassword = is_string($_POST['confirm_password'] ?? null) ? $_POST['confirm_password'] : '';
    $stmt = $pdo->prepare('SELECT sifre_hash FROM isletmeler WHERE id = ?');
    $stmt->execute([$businessId]);
    $storedHash = $stmt->fetchColumn();
    if (!$storedHash || !(password_verify($currentPassword, $storedHash) || hash_equals($storedHash, $currentPassword))) {
        $error = 'Mevcut şifre doğru değil.';
    } elseif (strlen($newPassword) < 10 || strlen($newPassword) > 200) {
        $error = 'Yeni şifre 10-200 karakter arasında olmalıdır.';
    } elseif (!hash_equals($newPassword, $confirmPassword)) {
        $error = 'Yeni şifre ve tekrarı eşleşmiyor.';
    } else {
        $stmt = $pdo->prepare('UPDATE isletmeler SET sifre_hash = ? WHERE id = ?');
        $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $businessId]);
        session_regenerate_id(true);
        businessFlash('success', 'Şifreniz güvenli biçimde güncellendi.');
        businessRedirect('ayarlar.php');
    }
}
include __DIR__ . '/_header.php';
?>
<section class="admin-panel">
    <div class="panel-header"><h2><i class="fa-solid fa-shield-halved"></i><span>Şifre değiştir</span></h2></div>
    <div class="panel-body padded">
        <?php if ($error !== ''): ?><div class="business-notice error"><?= businessEscape($error) ?></div><?php endif; ?>
        <form method="POST" class="admin-form" style="max-width:520px;">
            <input type="hidden" name="csrf_token" value="<?= businessEscape(generateCsrfToken()) ?>">
            <div class="form-group"><label for="current_password">Mevcut şifre</label><input id="current_password" name="current_password" type="password" autocomplete="current-password" required></div>
            <div class="form-group"><label for="new_password">Yeni şifre (en az 10 karakter)</label><input id="new_password" name="new_password" type="password" minlength="10" maxlength="200" autocomplete="new-password" required></div>
            <div class="form-group"><label for="confirm_password">Yeni şifreyi tekrarlayın</label><input id="confirm_password" name="confirm_password" type="password" minlength="10" maxlength="200" autocomplete="new-password" required></div>
            <button class="btn-admin primary">Şifreyi güncelle</button>
        </form>
    </div>
</section>
<?php include __DIR__ . '/_footer.php'; ?>
