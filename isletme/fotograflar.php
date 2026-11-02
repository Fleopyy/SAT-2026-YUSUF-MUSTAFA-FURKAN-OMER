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
            $rawFiles = $_FILES['photos'] ?? $_FILES['photo'] ?? null;
            $fileList = [];
            if ($rawFiles && is_array($rawFiles['name'] ?? null)) {
                $count = count($rawFiles['name']);
                for ($i = 0; $i < $count; $i++) {
                    if (($rawFiles['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                        continue;
                    }
                    $fileList[] = [
                        'name' => $rawFiles['name'][$i] ?? '',
                        'type' => $rawFiles['type'][$i] ?? '',
                        'tmp_name' => $rawFiles['tmp_name'][$i] ?? '',
                        'error' => $rawFiles['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                        'size' => $rawFiles['size'][$i] ?? 0,
                    ];
                }
            } elseif ($rawFiles && is_array($rawFiles) && ($rawFiles['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $fileList[] = $rawFiles;
            }

            if (empty($fileList)) {
                throw new RuntimeException('Lütfen yüklemek için en az bir fotoğraf seçin.');
            }
            if (count($fileList) > 15) {
                throw new RuntimeException('Tek seferde en fazla 15 fotoğraf yükleyebilirsiniz.');
            }

            $uploadedPaths = [];
            try {
                $pdo->beginTransaction();
                $insertPhoto = $pdo->prepare('INSERT INTO isletme_fotograflari (isletme_id, fotograf_yolu) VALUES (?, ?)');
                $stmtOrder = $pdo->prepare('SELECT COALESCE(MAX(sira), -1) + 1 FROM isletme_fotograf_siralama WHERE isletme_id = ?');
                $stmtOrder->execute([$businessId]);
                $nextOrder = (int)$stmtOrder->fetchColumn();
                $insertOrder = $pdo->prepare('INSERT INTO isletme_fotograf_siralama (fotograf_id, isletme_id, sira) VALUES (?, ?, ?)');

                foreach ($fileList as $file) {
                    $path = businessUploadImage($file, $businessId);
                    $uploadedPaths[] = $path;
                    $insertPhoto->execute([$businessId, $path]);
                    $photoId = (int)$pdo->lastInsertId();
                    $insertOrder->execute([$photoId, $businessId, $nextOrder]);
                    $nextOrder++;
                }

                $pdo->commit();
                $uploadedCount = count($uploadedPaths);
                businessFlash('success', $uploadedCount === 1 ? 'Fotoğraf galeriye yüklendi.' : ($uploadedCount . ' adet fotoğraf galeriye başarıyla yüklendi.'));
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                foreach ($uploadedPaths as $path) {
                    businessDeleteOwnedFile($path, $businessId);
                }
                throw $e;
            }
        } elseif ($action === 'feature' || $action === 'unfeature') {
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
            // Zaten seçili mi kontrol et (toggle)
            $checkStmt = $pdo->prepare('SELECT profil_fotograf_id, kapak_fotograf_id FROM isletme_panel_medya WHERE isletme_id = ?');
            $checkStmt->execute([$businessId]);
            $currentMedia = $checkStmt->fetch(PDO::FETCH_ASSOC);
            $isAlreadySet = $currentMedia && (
                ($slot === 'profile' && (int)($currentMedia['profil_fotograf_id'] ?? 0) === $photoId) ||
                ($slot === 'cover'   && (int)($currentMedia['kapak_fotograf_id']  ?? 0) === $photoId)
            );
            if ($isAlreadySet || $action === 'unfeature') {
                // Kaldır (NULL yap)
                $col = $slot === 'profile' ? 'profil_fotograf_id' : 'kapak_fotograf_id';
                $pdo->prepare("INSERT INTO isletme_panel_medya (isletme_id, profil_fotograf_id, kapak_fotograf_id)
                               VALUES (?, ?, ?)
                               ON DUPLICATE KEY UPDATE {$col} = NULL")
                    ->execute([$businessId, $slot === 'profile' ? null : null, $slot === 'cover' ? null : null]);
                businessFlash('success', $slot === 'profile' ? 'Profil fotoğrafı kaldırıldı.' : 'Kapak fotoğrafı kaldırıldı.');
            } else {
                // Ayarla
                $pdo->prepare('INSERT INTO isletme_panel_medya (isletme_id, profil_fotograf_id, kapak_fotograf_id)
                               VALUES (?, ?, ?)
                               ON DUPLICATE KEY UPDATE ' . ($slot === 'profile' ? 'profil_fotograf_id = VALUES(profil_fotograf_id)' : 'kapak_fotograf_id = VALUES(kapak_fotograf_id)'))
                    ->execute([$businessId, $slot === 'profile' ? $photoId : null, $slot === 'cover' ? $photoId : null]);
                businessFlash('success', $slot === 'profile' ? 'Profil fotoğrafı güncellendi.' : 'Kapak fotoğrafı güncellendi.');
            }
        } elseif ($action === 'delete') {
            $photoId = filter_var($_POST['photo_id'] ?? null, FILTER_VALIDATE_INT);
            $stmt = $pdo->prepare('SELECT fotograf_yolu FROM isletme_fotograflari WHERE id = ? AND isletme_id = ?');
            $stmt->execute([$photoId, $businessId]);
            $photoPath = $stmt->fetchColumn();
            if (!$photoPath) {
                throw new RuntimeException('Fotoğraf bu işletmeye ait değil.');
            }
            deleteOwnedMedia($photoPath, $businessId, $pdo, function () use ($pdo, $photoId, $businessId, $photoPath) {
                $stmt = $pdo->prepare('DELETE FROM isletme_fotograflari WHERE id = ? AND isletme_id = ? AND fotograf_yolu = ?');
                $stmt->execute([$photoId, $businessId, $photoPath]);
                if ($stmt->rowCount() !== 1) throw new RuntimeException('Foto?raf kayd? de?i?ti.');
            });
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
        $errors[] = ($e instanceof RuntimeException && !($e instanceof PDOException)) ? $e->getMessage() : 'Fotoğraf işlemi tamamlanamadı.';
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
        <form method="POST" enctype="multipart/form-data" class="admin-form business-file-upload-row">
            <input type="hidden" name="csrf_token" value="<?= businessEscape(generateCsrfToken()) ?>"><input type="hidden" name="action" value="upload">
            <div class="form-group" style="flex:1;min-width:280px;">
                <label for="photo">Yeni Fotoğraf(lar) <span class="label-sub">(JPG/PNG/WEBP, dosya başı en fazla 2 MB — toplu seçim yapabilirsiniz)</span></label>
                <div class="custom-file-box">
                    <input id="photo" type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple required class="custom-file-input">
                    <div class="custom-file-trigger">
                        <span class="file-trigger-info">
                            <i class="fa-solid fa-cloud-arrow-up file-icon"></i>
                            <span class="file-text" data-default="Bilgisayarınızdan bir veya birden fazla fotoğraf seçin...">Bilgisayarınızdan bir veya birden fazla fotoğraf seçin...</span>
                        </span>
                        <span class="file-browse-btn">Gözat</span>
                    </div>
                </div>
            </div>
            <div class="upload-btn-wrap">
                <button type="submit" class="btn-admin primary"><i class="fa-solid fa-cloud-arrow-up"></i> Yükle</button>
            </div>
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
                                <?php if ($photo['is_profile']): ?>
                                    <button class="btn-admin success sm" type="button"
                                        title="Profil fotoğrafı olarak ayarlı — kaldırmak için tıkla"
                                        onclick="submitPhotoAction(<?= (int)$photo['id'] ?>,'profile','unfeature')">
                                        <i class="fa-solid fa-circle-check" style="margin-right:4px;"></i>Profil
                                    </button>
                                <?php else: ?>
                                    <button class="btn-admin outline sm" type="button"
                                        title="Bu fotoğrafı profil fotoğrafı yap"
                                        onclick="submitPhotoAction(<?= (int)$photo['id'] ?>,'profile','feature')">
                                        Profil yap
                                    </button>
                                <?php endif; ?>
                                <?php if ($photo['is_cover']): ?>
                                    <button class="btn-admin success sm" type="button"
                                        title="Kapak fotoğrafı olarak ayarlı — kaldırmak için tıkla"
                                        onclick="submitPhotoAction(<?= (int)$photo['id'] ?>,'cover','unfeature')">
                                        <i class="fa-solid fa-circle-check" style="margin-right:4px;"></i>Kapak
                                    </button>
                                <?php else: ?>
                                    <button class="btn-admin outline sm" type="button"
                                        title="Bu fotoğrafı kapak fotoğrafı yap"
                                        onclick="submitPhotoAction(<?= (int)$photo['id'] ?>,'cover','feature')">
                                        Kapak yap
                                    </button>
                                <?php endif; ?>
                                <button class="btn-admin danger sm" type="button" onclick="if(confirm('Bu fotoğrafı silmek istediğinizden emin misiniz?'))submitPhotoAction(<?= (int)$photo['id'] ?>,'delete','delete')">Sil</button>
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
function submitPhotoAction(id, slot, action) {
    const form = document.getElementById('photoActionForm');
    if (action === 'delete') {
        document.getElementById('photoAction').value = 'delete';
        document.getElementById('photoSlot').value = '';
    } else {
        document.getElementById('photoAction').value = action; // 'feature' veya 'unfeature'
        document.getElementById('photoSlot').value = slot;    // 'profile' veya 'cover'
    }
    document.getElementById('photoId').value = id;
    form.submit();
}
</script>
<?php include __DIR__ . '/_footer.php'; ?>
