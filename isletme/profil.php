<?php
require_once __DIR__ . '/_common.php';
$currentBusinessPage = 'profile';
$pageTitle = 'İşletme Profilim';
$errors = [];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    businessRequirePost();
    $fields = [
        'isletme_adi' => trim((string)($_POST['isletme_adi'] ?? '')),
        'isletme_sahibi' => trim((string)($_POST['isletme_sahibi'] ?? '')),
        'telefon' => trim((string)($_POST['telefon'] ?? '')),
        'eposta' => trim((string)($_POST['eposta'] ?? '')),
        'sehir' => trim((string)($_POST['sehir'] ?? '')),
        'ilce' => trim((string)($_POST['ilce'] ?? '')),
        'tam_adres' => trim((string)($_POST['tam_adres'] ?? '')),
        'aciklama' => trim((string)($_POST['aciklama'] ?? '')),
    ];

    foreach (['isletme_adi', 'isletme_sahibi', 'telefon', 'sehir', 'ilce'] as $required) {
        if ($fields[$required] === '') {
            $errors[] = 'Zorunlu alanları doldurun.';
            break;
        }
    }
    if (!filter_var($fields['eposta'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Geçerli bir e-posta adresi girin.';
    }
    if (mb_strlen($fields['aciklama'], 'UTF-8') > 2000) {
        $errors[] = 'İşletme tanıtımı en fazla 2000 karakter olabilir.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM isletmeler WHERE id <> ? AND (telefon = ? OR eposta = ?)');
        $stmt->execute([$businessId, $fields['telefon'], $fields['eposta']]);
        if ((int)$stmt->fetchColumn() > 0) {
            $errors[] = 'Bu telefon veya e-posta başka bir işletme tarafından kullanılıyor.';
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare('UPDATE isletmeler SET isletme_adi = ?, isletme_sahibi = ?, telefon = ?, eposta = ?, sehir = ?, ilce = ?, tam_adres = ?, aciklama = ? WHERE id = ?');
        $stmt->execute([
            $fields['isletme_adi'], $fields['isletme_sahibi'], $fields['telefon'], $fields['eposta'],
            $fields['sehir'], $fields['ilce'], $fields['tam_adres'], $fields['aciklama'], $businessId,
        ]);
        businessFlash('success', 'İşletme profiliniz kaydedildi.');
        businessRedirect('profil.php');
    }
    $business = array_merge($business, $fields);
}

include __DIR__ . '/_header.php';
?>
<section class="admin-panel">
    <div class="panel-header"><h2><i class="fa-solid fa-store"></i><span>İşletme Bilgileri</span></h2></div>
    <div class="panel-body padded">
        <?php foreach ($errors as $error): ?><div class="business-notice error"><?= businessEscape($error) ?></div><?php endforeach; ?>
        <form method="POST" class="admin-form" id="businessProfileForm">
            <input type="hidden" name="csrf_token" value="<?= businessEscape(generateCsrfToken()) ?>">
            <div class="business-form-grid">
                <?php foreach ([
                    'isletme_adi' => ['İşletme adı', true],
                    'isletme_sahibi' => ['Yetkili kişi', true],
                    'telefon' => ['Telefon', true],
                    'eposta' => ['E-posta', true],
                    'sehir' => ['Şehir', true],
                    'ilce' => ['İlçe', true],
                ] as $name => [$label, $required]): ?>
                    <div class="form-group"><label for="<?= businessEscape($name) ?>"><?= businessEscape($label) ?></label><input id="<?= businessEscape($name) ?>" name="<?= businessEscape($name) ?>" value="<?= businessEscape($business[$name] ?? '') ?>" <?= $required ? 'required' : '' ?>></div>
                <?php endforeach; ?>
                <div class="form-group" style="grid-column:1/-1"><label for="tam_adres">Tam adres</label><textarea id="tam_adres" name="tam_adres"><?= businessEscape($business['tam_adres'] ?? '') ?></textarea></div>
                <div class="form-group" style="grid-column:1/-1"><label for="aciklama">İşletme tanıtımı</label><textarea id="aciklama" name="aciklama" maxlength="2000" rows="6"><?= businessEscape($business['aciklama'] ?? '') ?></textarea><small id="descriptionCount" style="color:#64748B;">2000 karakter kaldı</small></div>
            </div>
            <div class="business-actions" style="margin-top:20px;">
                <button class="btn-admin primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Değişiklikleri Kaydet</button>
                <a class="btn-admin outline" href="/isletme/fotograflar.php"><i class="fa-solid fa-images"></i> Fotoğrafları Yönet</a>
            </div>
        </form>
    </div>
</section>
<script>
const descriptionField = document.getElementById('aciklama');
const descriptionCount = document.getElementById('descriptionCount');
function updateDescriptionCount() { descriptionCount.textContent = (2000 - descriptionField.value.length) + ' karakter kaldı'; }
descriptionField.addEventListener('input', updateDescriptionCount);
updateDescriptionCount();
</script>
<?php include __DIR__ . '/_footer.php'; ?>
