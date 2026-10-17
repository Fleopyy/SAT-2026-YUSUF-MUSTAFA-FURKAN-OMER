<?php
require_once __DIR__ . '/_common.php';
$currentBusinessPage = 'photos';
$pageTitle = 'İşletme Fotoğraflarım';
$errors = [];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    businessRequirePost();
    $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
    try {
        if ($action === 'upload') {
            $path = businessUploadImage($_FILES['photo'] ?? [], $businessId);
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare('INSERT INTO isletme_fotograflari (isletme_id, fotograf_yolu) VALUES (?, ?)');
                $stmt->execute([$businessId, $path]);
                $photoId = (int)$pdo->lastInsertId();
                $stmt = $pdo->prepare('SELECT COALESCE(MAX(sira), -1) + 1 FROM isletme_fotograf_siralama WHERE isletme_id = ?');
                $stmt->execute([$businessId]);
                $order = (int)$stmt->fetchColumn();
                $pdo->prepare('INSERT INTO isletme_fotograf_siralama (fotograf_id, isletme_id, sira) VALUES (?, ?, ?)')->execute([$photoId, $businessId, $order]);
                $pdo->commit();
                businessFlash('success', 'Fotoğraf galeriye yüklendi.');
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                businessDeleteOwnedFile($path, $businessId);
                throw $e;
            }
        } elseif ($action === 'feature') {
            $photoId = filter_var($_POST['photo_id'] ?? null, FILTER_VALIDATE_INT);
            $slot = is_string($_POST['slot'] ?? null) ? $_POST['slot'] : '';
            if (!$photoId || !in_array($slot, ['profile', 'cover'], true)) {
                throw new RuntimeException('Fotoğraf seçimi geçersiz.');
            }
            $stmt = $pdo->prepare('SELECT id FROM isletme_fotograflari WHERE id = ? AND isletme_id = ?');
            $stmt->execute([$photoId, $businessId]);
            if (!$stmt->fetchColumn()) {
                throw new RuntimeException('Fotoğraf bu işletmeye ait değil.');
            }
            $pdo->prepare('INSERT INTO isletme_panel_medya (isletme_id, profil_fotograf_id, kapak_fotograf_id)
                           VALUES (?, ?, ?)
                           ON DUPLICATE KEY UPDATE ' . ($slot === 'profile' ? 'profil_fotograf_id = VALUES(profil_fotograf_id)' : 'kapak_fotograf_id = VALUES(kapak_fotograf_id)'))
                ->execute([$businessId, $slot === 'profile' ? $photoId : null, $slot === 'cover' ? $photoId : null]);
            businessFlash('success', $slot === 'profile' ? 'Profil fotoğrafı güncellendi.' : 'Kapak fotoğrafı güncellendi.');
        } elseif ($action === 'delete') {
            $photoId = filter_var($_POST['photo_id'] ?? null, FILTER_VALIDATE_INT);
            $stmt = $pdo->prepare('SELECT fotograf_yolu FROM isletme_fotograflari WHERE id = ? AND isletme_id = ?');
            $stmt->execute([$photoId, $businessId]);
            $photoPath = $stmt->fetchColumn();
            if (!$photoPath) {
                throw new RuntimeException('Fotoğraf bu işletmeye ait değil.');
            }
            $pdo->beginTransaction();
            $pdo->prepare('DELETE FROM isletme_fotograflari WHERE id = ? AND isletme_id = ?')->execute([$photoId, $businessId]);
            $pdo->commit();
            businessDeleteOwnedFile($photoPath, $businessId);
            businessFlash('success', 'Fotoğraf silindi.');
        } elseif ($action === 'reorder') {
            $positions = $_POST['position'] ?? [];
            if (!is_array($positions)) {
                throw new RuntimeException('Galeri sıralaması geçersiz.');
            }
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('SELECT id FROM isletme_fotograflari WHERE isletme_id = ?');
            $stmt->execute([$businessId]);
            $ownedIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
            $submittedIds = array_map('intval', array_keys($positions));
            if (count($ownedIds) !== count($submittedIds) || array_diff($submittedIds, $ownedIds) || array_diff($ownedIds, $submittedIds)) {
                throw new RuntimeException('Sıralama yalnızca bu işletmenin fotoğraflarını içermelidir.');
            }
            $update = $pdo->prepare('INSERT INTO isletme_fotograf_siralama (fotograf_id, isletme_id, sira) VALUES (?, ?, ?)
                                     ON DUPLICATE KEY UPDATE isletme_id = VALUES(isletme_id), sira = VALUES(sira)');
            $usedPositions = [];
            foreach ($positions as $id => $position) {
                $position = filter_var($position, FILTER_VALIDATE_INT);
                if ($position === false || $position < 0 || isset($usedPositions[$position])) {
                    throw new RuntimeException('Fotoğraf sıralaması geçersiz veya tekrar ediyor.');
                }
                $usedPositions[$position] = true;
                $update->execute([(int)$id, $businessId, $position]);
            }
            $pdo->commit();
            businessFlash('success', 'Galeri sıralaması kaydedildi.');
        } else {
            throw new RuntimeException('İşlem tanınmadı.');
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Business photo action failed: ' . $e->getMessage());
        $errors[] = $e instanceof RuntimeException ? $e->getMessage() : 'Fotoğraf işlemi tamamlanamadı.';
    }
    if (!$errors) {
        businessRedirect('fotograflar.php');
    }
}

$stmt = $pdo->prepare("SELECT f.id, f.fotograf_yolu, COALESCE(s.sira, 0) AS sira,
                              (m.profil_fotograf_id = f.id) AS is_profile,
                              (m.kapak_fotograf_id = f.id) AS is_cover
                       FROM isletme_fotograflari f
                       LEFT JOIN isletme_fotograf_siralama s ON s.fotograf_id = f.id AND s.isletme_id = f.isletme_id
                       LEFT JOIN isletme_panel_medya m ON m.isletme_id = f.isletme_id
                       WHERE f.isletme_id = ? ORDER BY s.sira, f.id");
$stmt->execute([$businessId]);
$photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
include __DIR__ . '/_header.php';
?>
<section class="admin-panel">
    <div class="panel-header"><h2><i class="fa-solid fa-images"></i><span>Fotoğraf Galerisi</span></h2><span class="status-badge"><?= count($photos) ?> fotoğraf</span></div>
    <div class="panel-body padded">
        <?php foreach ($errors as $error): ?><div class="business-notice error"><?= businessEscape($error) ?></div><?php endforeach; ?>
        <form method="POST" enctype="multipart/form-data" class="admin-form" style="display:flex;gap:12px;align-items:end;flex-wrap:wrap;margin-bottom:24px;">
            <input type="hidden" name="csrf_token" value="<?= businessEscape(generateCsrfToken()) ?>"><input type="hidden" name="action" value="upload">
            <div class="form-group" style="margin:0;flex:1;min-width:220px;"><label for="photo">Yeni fotoğraf (JPG/PNG/WEBP, en fazla 5 MB)</label><input id="photo" type="file" name="photo" accept="image/jpeg,image/png,image/webp" required></div>
            <button type="submit" class="btn-admin primary"><i class="fa-solid fa-upload"></i> Yükle</button>
        </form>
        <?php if (!$photos): ?>
            <div class="empty-state"><i class="fa-regular fa-images"></i><h4>Galeriniz henüz boş</h4><p>İşletmenizi tanıtan fotoğrafları buradan yükleyin.</p></div>
        <?php else: ?>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= businessEscape(generateCsrfToken()) ?>"><input type="hidden" name="action" value="reorder">
                <div class="business-gallery">
                    <?php foreach ($photos as $photo): ?>
                        <article class="admin-panel" style="margin:0;padding:10px;">
                            <img src="/<?= businessEscape($photo['fotograf_yolu']) ?>" alt="İşletme fotoğrafı">
                            <label style="display:block;margin:8px 0;font-size:12px;">Sıra <input type="number" name="position[<?= (int)$photo['id'] ?>]" value="<?= (int)$photo['sira'] ?>" min="0" style="width:70px;"></label>
                            <div class="business-actions">
                                <?php if ($photo['is_profile']): ?><span class="status-badge active">Profil</span><?php else: ?>
                                    <button class="btn-admin outline sm" type="button" onclick="submitPhotoAction(<?= (int)$photo['id'] ?>,'profile')">Profil yap</button>
                                <?php endif; ?>
                                <?php if ($photo['is_cover']): ?><span class="status-badge active">Kapak</span><?php else: ?>
                                    <button class="btn-admin outline sm" type="button" onclick="submitPhotoAction(<?= (int)$photo['id'] ?>,'cover')">Kapak yap</button>
                                <?php endif; ?>
                                <button class="btn-admin danger sm" type="button" onclick="if(confirm('Bu fotoğrafı silmek istediğinizden emin misiniz?'))submitPhotoAction(<?= (int)$photo['id'] ?>,'delete')">Sil</button>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                <button class="btn-admin outline" style="margin-top:16px;" type="submit">Sıralamayı Kaydet</button>
            </form>
        <?php endif; ?>
    </div>
</section>
<form id="photoActionForm" method="POST" action="/isletme/fotograflar.php" hidden>
    <input type="hidden" name="csrf_token" value="<?= businessEscape(generateCsrfToken()) ?>">
    <input id="photoAction" name="action">
    <input id="photoId" name="photo_id">
    <input id="photoSlot" name="slot">
</form>
<script>
function submitPhotoAction(id, action) {
    const form = document.getElementById('photoActionForm');
    document.getElementById('photoAction').value = action === 'delete' ? 'delete' : 'feature';
    document.getElementById('photoId').value = id;
    document.getElementById('photoSlot').value = action === 'delete' ? '' : action;
    form.submit();
}
</script>
<?php include __DIR__ . '/_footer.php'; ?>
