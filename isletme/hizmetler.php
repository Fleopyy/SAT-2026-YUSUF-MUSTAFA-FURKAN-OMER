<?php
require_once __DIR__ . '/_common.php';
$currentBusinessPage = 'services';
$pageTitle = 'Hizmetler ve Fiyatlar';
$errors = [];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    businessRequirePost();
    try {
        $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
        $serviceId = filter_var($_POST['service_id'] ?? null, FILTER_VALIDATE_INT);
        if ($action === 'save') {
            $name = trim(is_string($_POST['name'] ?? null) ? $_POST['name'] : '');
            $description = trim(is_string($_POST['description'] ?? null) ? $_POST['description'] : '');
            $priceRaw = $_POST['price'] ?? null;
            $price = filter_var($priceRaw, FILTER_VALIDATE_FLOAT);
            $duration = filter_var($_POST['duration'] ?? null, FILTER_VALIDATE_INT);
            $employeeIds = $_POST['employee_ids'] ?? [];
            if ($name === '' || mb_strlen($name, 'UTF-8') > 150 || $price === false || $price < 0 || $duration === false || $duration < 1 || $duration > 1440 || mb_strlen($description, 'UTF-8') > 5000 || !is_array($employeeIds)) {
                throw new RuntimeException('Hizmet adı, sıfırdan küçük olmayan fiyat, pozitif süre ve açıklama alanlarını kontrol edin.');
            }
            $employeeIds = array_values(array_unique(array_filter(array_map('intval', $employeeIds))));
            if ($employeeIds) {
                $placeholders = implode(',', array_fill(0, count($employeeIds), '?'));
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM calisanlar WHERE isletme_id = ? AND aktif_mi = 1 AND id IN ($placeholders)");
                $stmt->execute(array_merge([$businessId], $employeeIds));
                if ((int)$stmt->fetchColumn() !== count($employeeIds)) {
                    throw new RuntimeException('Seçilen çalışanlardan biri işletmenize ait değil veya aktif değil.');
                }
            }
            if ($serviceId) {
                $stmt = $pdo->prepare('SELECT id FROM hizmetler WHERE id = ? AND isletme_id = ?');
                $stmt->execute([$serviceId, $businessId]);
                if (!$stmt->fetchColumn()) {
                    throw new RuntimeException('Hizmet bu işletmeye ait değil.');
                }
            }
            $pdo->beginTransaction();
            if ($serviceId) {
                $pdo->prepare('UPDATE hizmetler SET hizmet_adi = ?, fiyat = ?, sure_dakika = ? WHERE id = ? AND isletme_id = ?')
                    ->execute([$name, number_format((float)$price, 2, '.', ''), $duration, $serviceId, $businessId]);
            } else {
                $pdo->prepare('INSERT INTO hizmetler (isletme_id, hizmet_adi, fiyat, sure_dakika, aktif_mi) VALUES (?, ?, ?, ?, 1)')
                    ->execute([$businessId, $name, number_format((float)$price, 2, '.', ''), $duration]);
                $serviceId = (int)$pdo->lastInsertId();
            }
            $pdo->prepare('INSERT INTO hizmet_detaylari (hizmet_id, aciklama) VALUES (?, ?) ON DUPLICATE KEY UPDATE aciklama = VALUES(aciklama)')
                ->execute([$serviceId, $description !== '' ? $description : null]);
            $pdo->prepare('DELETE hc FROM hizmet_calisanlari hc INNER JOIN calisanlar c ON c.id = hc.calisan_id WHERE hc.hizmet_id = ? AND c.isletme_id = ?')
                ->execute([$serviceId, $businessId]);
            $assign = $pdo->prepare('INSERT INTO hizmet_calisanlari (hizmet_id, calisan_id) VALUES (?, ?)');
            foreach ($employeeIds as $employeeId) {
                $assign->execute([$serviceId, $employeeId]);
            }
            $pdo->commit();
            businessFlash('success', 'Hizmet bilgileri kaydedildi.');
        } elseif ($action === 'toggle') {
            $active = filter_var($_POST['active'] ?? null, FILTER_VALIDATE_INT);
            if (!$serviceId || !in_array($active, [0, 1], true)) {
                throw new RuntimeException('Hizmet durumu geçersiz.');
            }
            $stmt = $pdo->prepare('UPDATE hizmetler SET aktif_mi = ? WHERE id = ? AND isletme_id = ?');
            $stmt->execute([$active, $serviceId, $businessId]);
            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException('Hizmet bu işletmeye ait değil veya durumu değişmedi.');
            }
            businessFlash('success', $active ? 'Hizmet aktifleştirildi.' : 'Hizmet pasife alındı; geçmiş randevular korundu.');
        } else {
            throw new RuntimeException('İşlem tanınmadı.');
        }
        businessRedirect('hizmetler.php');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Business service action failed: ' . $e->getMessage());
        $errors[] = $e instanceof RuntimeException ? $e->getMessage() : 'Hizmet kaydedilemedi.';
    }
}

$stmt = $pdo->prepare('SELECT c.id, c.ad_soyad FROM calisanlar c WHERE c.isletme_id = ? AND c.aktif_mi = 1 ORDER BY c.ad_soyad');
$stmt->execute([$businessId]);
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt = $pdo->prepare('SELECT h.*, d.aciklama FROM hizmetler h LEFT JOIN hizmet_detaylari d ON d.hizmet_id = h.id WHERE h.isletme_id = ? ORDER BY h.aktif_mi DESC, h.hizmet_adi');
$stmt->execute([$businessId]);
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);
$assigned = [];
if ($services) {
    $serviceIds = array_column($services, 'id');
    $placeholders = implode(',', array_fill(0, count($serviceIds), '?'));
    $stmt = $pdo->prepare("SELECT hc.hizmet_id, hc.calisan_id FROM hizmet_calisanlari hc INNER JOIN hizmetler h ON h.id = hc.hizmet_id WHERE h.isletme_id = ? AND h.id IN ($placeholders)");
    $stmt->execute(array_merge([$businessId], $serviceIds));
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $assigned[(int)$row['hizmet_id']][] = (int)$row['calisan_id'];
    }
}
include __DIR__ . '/_header.php';
?>
<section class="admin-panel">
    <div class="panel-header"><h2><i class="fa-solid fa-plus"></i><span>Yeni Hizmet Ekle</span></h2></div>
    <div class="panel-body padded">
        <?php foreach ($errors as $error): ?><div class="business-notice error"><?= businessEscape($error) ?></div><?php endforeach; ?>
        <form method="POST" class="admin-form" id="newServiceForm" onsubmit="return validateServiceForm(this)">
            <input type="hidden" name="csrf_token" value="<?= businessEscape(generateCsrfToken()) ?>"><input type="hidden" name="action" value="save">
            <div class="business-form-grid">
                <div class="form-group"><label>Hizmet adı</label><input name="name" maxlength="150" required></div>
                <div class="form-group"><label>Fiyat (TL)</label><input name="price" type="number" min="0" step="0.01" required></div>
                <div class="form-group"><label>Süre (dakika)</label><input name="duration" type="number" min="1" max="1440" required></div>
                <div class="form-group" style="grid-column:1/-1"><label>Açıklama</label><textarea name="description" maxlength="5000"></textarea></div>
                <fieldset class="form-group" style="grid-column:1/-1;border:1px solid #E2E8F0;border-radius:8px;padding:14px;"><legend style="font-size:12px;font-weight:700;">Hizmeti sunabilecek çalışanlar</legend>
                    <?php if (!$employees): ?><small>Çalışan ekleyip aktif hale getirin.</small><?php endif; ?>
                    <?php foreach ($employees as $employee): ?><label style="display:inline-flex;gap:8px;align-items:center;margin:6px 14px 6px 0;"><input type="checkbox" name="employee_ids[]" value="<?= (int)$employee['id'] ?>"> <?= businessEscape($employee['ad_soyad']) ?></label><?php endforeach; ?>
                </fieldset>
            </div>
            <button class="btn-admin primary" style="margin-top:18px;" type="submit"><i class="fa-solid fa-floppy-disk"></i> Hizmeti Kaydet</button>
        </form>
    </div>
</section>
<section class="admin-panel">
    <div class="panel-header"><h2><i class="fa-solid fa-list"></i><span>Hizmetlerim (<?= count($services) ?>)</span></h2></div>
    <div class="panel-body">
        <?php if (!$services): ?><div class="empty-state"><i class="fa-solid fa-scissors"></i><h4>Henüz hizmet eklenmemiş</h4></div>
        <?php else: ?><div style="overflow-x:auto;"><table class="admin-table"><thead><tr><th>Hizmet</th><th>Fiyat</th><th>Süre</th><th>Çalışanlar</th><th>Durum</th><th>İşlem</th></tr></thead><tbody>
            <?php foreach ($services as $service): ?>
                <tr><td><strong><?= businessEscape($service['hizmet_adi']) ?></strong><small style="display:block;color:#64748B;"><?= businessEscape($service['aciklama'] ?? '') ?></small></td>
                    <td><?= number_format((float)$service['fiyat'], 2, ',', '.') ?> ₺</td><td><?= (int)$service['sure_dakika'] ?> dk</td>
                    <td><?php foreach ($employees as $employee): if (in_array((int)$employee['id'], $assigned[(int)$service['id']] ?? [], true)): ?><span class="status-badge"><?= businessEscape($employee['ad_soyad']) ?></span><?php endif; endforeach; ?></td>
                    <td><span class="status-badge <?= $service['aktif_mi'] ? 'active' : 'suspended' ?>"><?= $service['aktif_mi'] ? 'Aktif' : 'Pasif' ?></span></td>
                    <td><details><summary class="btn-admin outline sm">Düzenle</summary><form method="POST" class="admin-form" onsubmit="return validateServiceForm(this)" style="min-width:280px;padding:12px;">
                        <input type="hidden" name="csrf_token" value="<?= businessEscape(generateCsrfToken()) ?>"><input type="hidden" name="action" value="save"><input type="hidden" name="service_id" value="<?= (int)$service['id'] ?>">
                        <div class="form-group"><label>Hizmet adı</label><input name="name" value="<?= businessEscape($service['hizmet_adi']) ?>" required maxlength="150"></div>
                        <div class="form-group"><label>Fiyat (TL)</label><input name="price" type="number" min="0" step="0.01" value="<?= businessEscape($service['fiyat']) ?>" required></div>
                        <div class="form-group"><label>Süre (dakika)</label><input name="duration" type="number" min="1" max="1440" value="<?= (int)$service['sure_dakika'] ?>" required></div>
                        <div class="form-group"><label>Açıklama</label><textarea name="description" maxlength="5000"><?= businessEscape($service['aciklama'] ?? '') ?></textarea></div>
                        <?php foreach ($employees as $employee): ?><label style="display:flex;gap:8px;align-items:center;margin:6px 0;"><input type="checkbox" name="employee_ids[]" value="<?= (int)$employee['id'] ?>" <?= in_array((int)$employee['id'], $assigned[(int)$service['id']] ?? [], true) ? 'checked' : '' ?>><?= businessEscape($employee['ad_soyad']) ?></label><?php endforeach; ?>
                        <button class="btn-admin primary sm" type="submit">Kaydet</button>
                    </form></details>
                    <form method="POST" class="business-inline-form" onsubmit="return confirm('Hizmet durumunu değiştirmek istiyor musunuz?')">
                        <input type="hidden" name="csrf_token" value="<?= businessEscape(generateCsrfToken()) ?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="service_id" value="<?= (int)$service['id'] ?>"><input type="hidden" name="active" value="<?= $service['aktif_mi'] ? 0 : 1 ?>">
                        <button class="btn-admin <?= $service['aktif_mi'] ? 'danger' : 'success' ?> sm"><?= $service['aktif_mi'] ? 'Pasife al' : 'Aktifleştir' ?></button>
                    </form></td>
                </tr>
            <?php endforeach; ?>
        </tbody></table></div><?php endif; ?>
    </div>
</section>
<script>
function validateServiceForm(form) {
    const price = Number(form.elements.price.value);
    const duration = Number(form.elements.duration.value);
    if (!Number.isFinite(price) || price < 0 || !Number.isInteger(duration) || duration < 1 || duration > 1440) {
        alert('Fiyat sıfırdan küçük olamaz; süre 1-1440 dakika arasında tam sayı olmalıdır.');
        return false;
    }
    return true;
}
</script>
<?php include __DIR__ . '/_footer.php'; ?>
