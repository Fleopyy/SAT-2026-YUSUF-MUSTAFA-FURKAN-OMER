<?php
require_once __DIR__ . '/_common.php';
$currentBusinessPage = 'staff';
$pageTitle = 'Çalışan Yönetimi';
$errors = [];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    businessRequirePost();
    $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
    try {
        $employeeId = filter_var($_POST['employee_id'] ?? null, FILTER_VALIDATE_INT);
        if ($action === 'save') {
            $name = trim(is_string($_POST['name'] ?? null) ? $_POST['name'] : '');
            $phone = trim(is_string($_POST['phone'] ?? null) ? $_POST['phone'] : '');
            $specialty = trim(is_string($_POST['specialty'] ?? null) ? $_POST['specialty'] : '');
            $serviceIds = $_POST['service_ids'] ?? [];
            if ($name === '' || mb_strlen($name, 'UTF-8') > 150 || mb_strlen($phone, 'UTF-8') > 25 || mb_strlen($specialty, 'UTF-8') > 180) {
                throw new RuntimeException('Çalışan adı zorunludur; alan uzunluklarını kontrol edin.');
            }
            if (!is_array($serviceIds)) {
                throw new RuntimeException('Hizmet seçimi geçersiz.');
            }
            $serviceIds = array_values(array_unique(array_filter(array_map('intval', $serviceIds))));
            if ($serviceIds) {
                $placeholders = implode(',', array_fill(0, count($serviceIds), '?'));
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM hizmetler WHERE isletme_id = ? AND aktif_mi = 1 AND id IN ($placeholders)");
                $stmt->execute(array_merge([$businessId], $serviceIds));
                if ((int)$stmt->fetchColumn() !== count($serviceIds)) {
                    throw new RuntimeException('Seçilen hizmetlerden biri işletmenize ait değil veya aktif değil.');
                }
            }
            if ($employeeId) {
                $stmt = $pdo->prepare('SELECT id FROM calisanlar WHERE id = ? AND isletme_id = ?');
                $stmt->execute([$employeeId, $businessId]);
                if (!$stmt->fetchColumn()) {
                    throw new RuntimeException('Çalışan bu işletmeye ait değil.');
                }
            }

            $photoPath = null;
            if (($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $photoPath = businessUploadImage($_FILES['photo'], $businessId, 'employees');
            }

            try {
                $pdo->beginTransaction();
                if ($employeeId) {
                    $pdo->prepare('UPDATE calisanlar SET ad_soyad = ?, telefon = ? WHERE id = ? AND isletme_id = ?')
                        ->execute([$name, $phone !== '' ? $phone : null, $employeeId, $businessId]);
                } else {
                    $pdo->prepare('INSERT INTO calisanlar (isletme_id, ad_soyad, telefon, aktif_mi) VALUES (?, ?, ?, 1)')
                        ->execute([$businessId, $name, $phone !== '' ? $phone : null]);
                    $employeeId = (int)$pdo->lastInsertId();
                }
                $stmt = $pdo->prepare('INSERT INTO calisan_detaylari (calisan_id, uzmanlik, fotograf_yolu) VALUES (?, ?, ?)
                                       ON DUPLICATE KEY UPDATE uzmanlik = VALUES(uzmanlik), fotograf_yolu = COALESCE(VALUES(fotograf_yolu), fotograf_yolu)');
                $stmt->execute([$employeeId, $specialty !== '' ? $specialty : null, $photoPath]);
                $pdo->prepare('DELETE hc FROM hizmet_calisanlari hc INNER JOIN hizmetler h ON h.id = hc.hizmet_id WHERE hc.calisan_id = ? AND h.isletme_id = ?')
                    ->execute([$employeeId, $businessId]);
                $assign = $pdo->prepare('INSERT INTO hizmet_calisanlari (hizmet_id, calisan_id) VALUES (?, ?)');
                foreach ($serviceIds as $serviceId) {
                    $assign->execute([$serviceId, $employeeId]);
                }
                $pdo->commit();
                businessFlash('success', 'Çalışan bilgileri kaydedildi.');
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                if ($photoPath) {
                    businessDeleteOwnedFile($photoPath, $businessId);
                }
                throw $e;
            }
        } elseif ($action === 'toggle') {
            $active = filter_var($_POST['active'] ?? null, FILTER_VALIDATE_INT);
            if (!$employeeId || !in_array($active, [0, 1], true)) {
                throw new RuntimeException('Çalışan durumu geçersiz.');
            }
            $stmt = $pdo->prepare('UPDATE calisanlar SET aktif_mi = ? WHERE id = ? AND isletme_id = ?');
            $stmt->execute([$active, $employeeId, $businessId]);
            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException('Çalışan bu işletmeye ait değil veya durumu değişmedi.');
            }
            businessFlash('success', $active ? 'Çalışan aktifleştirildi.' : 'Çalışan pasife alındı; geçmiş randevu kayıtları korundu.');
        } else {
            throw new RuntimeException('İşlem tanınmadı.');
        }
        businessRedirect('calisanlar.php');
    } catch (Throwable $e) {
        error_log('Business staff action failed: ' . $e->getMessage());
        $errors[] = $e instanceof RuntimeException ? $e->getMessage() : 'Çalışan kaydedilemedi.';
    }
}

$stmt = $pdo->prepare('SELECT c.*, d.uzmanlik, d.fotograf_yolu FROM calisanlar c
                       LEFT JOIN calisan_detaylari d ON d.calisan_id = c.id
                       WHERE c.isletme_id = ? ORDER BY c.aktif_mi DESC, c.ad_soyad');
$stmt->execute([$businessId]);
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt = $pdo->prepare('SELECT id, hizmet_adi FROM hizmetler WHERE isletme_id = ? AND aktif_mi = 1 ORDER BY hizmet_adi');
$stmt->execute([$businessId]);
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);
$assigned = [];
if ($employees) {
    $ids = array_column($employees, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT hc.calisan_id, hc.hizmet_id FROM hizmet_calisanlari hc
                           INNER JOIN calisanlar c ON c.id = hc.calisan_id
                           WHERE c.isletme_id = ? AND c.id IN ($placeholders)");
    $stmt->execute(array_merge([$businessId], $ids));
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $assigned[(int)$row['calisan_id']][] = (int)$row['hizmet_id'];
    }
}
include __DIR__ . '/_header.php';
?>
<section class="admin-panel">
    <div class="panel-header"><h2><i class="fa-solid fa-user-plus"></i><span>Çalışan Ekle</span></h2></div>
    <div class="panel-body padded">
        <?php foreach ($errors as $error): ?><div class="business-notice error"><?= businessEscape($error) ?></div><?php endforeach; ?>
        <form method="POST" enctype="multipart/form-data" class="admin-form">
            <input type="hidden" name="csrf_token" value="<?= businessEscape(generateCsrfToken()) ?>"><input type="hidden" name="action" value="save">
            <div class="business-form-grid">
                <div class="form-group"><label>Ad soyad</label><input name="name" maxlength="150" required></div>
                <div class="form-group"><label>Telefon (isteğe bağlı)</label><input name="phone" maxlength="25"></div>
                <div class="form-group"><label>Uzmanlık / görev</label><input name="specialty" maxlength="180"></div>
                <div class="form-group"><label>Profil fotoğrafı (JPG/PNG/WEBP, 5 MB)</label><input type="file" name="photo" accept="image/jpeg,image/png,image/webp"></div>
                <fieldset class="form-group" style="grid-column:1/-1;border:1px solid #E2E8F0;border-radius:8px;padding:14px;"><legend style="font-size:12px;font-weight:700;">Sunabildiği hizmetler</legend>
                    <?php if (!$services): ?><small>Önce aktif hizmet ekleyin.</small><?php endif; ?>
                    <?php foreach ($services as $service): ?><label style="display:inline-flex;gap:8px;align-items:center;margin:6px 14px 6px 0;"><input type="checkbox" name="service_ids[]" value="<?= (int)$service['id'] ?>"> <?= businessEscape($service['hizmet_adi']) ?></label><?php endforeach; ?>
                </fieldset>
            </div>
            <button class="btn-admin primary" type="submit" style="margin-top:18px;"><i class="fa-solid fa-plus"></i> Çalışan ekle</button>
        </form>
    </div>
</section>
<section class="admin-panel">
    <div class="panel-header"><h2><i class="fa-solid fa-users"></i><span>Çalışanlarım (<?= count($employees) ?>)</span></h2></div>
    <div class="panel-body">
        <?php if (!$employees): ?><div class="empty-state"><i class="fa-solid fa-users"></i><h4>Henüz çalışan eklenmemiş</h4></div>
        <?php else: ?><div style="overflow-x:auto;"><table class="admin-table"><thead><tr><th>Çalışan</th><th>Telefon</th><th>Uzmanlık</th><th>Hizmetler</th><th>Durum</th><th>İşlem</th></tr></thead><tbody>
            <?php foreach ($employees as $employee): ?>
                <tr>
                    <td><strong><?= businessEscape($employee['ad_soyad']) ?></strong><?php if ($employee['fotograf_yolu']): ?><img src="/<?= businessEscape($employee['fotograf_yolu']) ?>" alt="" style="width:34px;height:34px;object-fit:cover;border-radius:50%;margin-top:5px;"><?php endif; ?></td>
                    <td><?= businessEscape($employee['telefon'] ?? '—') ?></td>
                    <td><?= businessEscape($employee['uzmanlik'] ?? '—') ?></td>
                    <td><?php foreach ($services as $service): if (in_array((int)$service['id'], $assigned[(int)$employee['id']] ?? [], true)): ?><span class="status-badge"><?= businessEscape($service['hizmet_adi']) ?></span><?php endif; endforeach; ?></td>
                    <td><span class="status-badge <?= $employee['aktif_mi'] ? 'active' : 'suspended' ?>"><?= $employee['aktif_mi'] ? 'Aktif' : 'Pasif' ?></span></td>
                    <td class="business-actions">
                        <details><summary class="btn-admin outline sm">Düzenle</summary>
                            <form method="POST" enctype="multipart/form-data" class="admin-form" style="min-width:280px;padding:12px;">
                                <input type="hidden" name="csrf_token" value="<?= businessEscape(generateCsrfToken()) ?>"><input type="hidden" name="action" value="save"><input type="hidden" name="employee_id" value="<?= (int)$employee['id'] ?>">
                                <div class="form-group"><label>Ad soyad</label><input name="name" value="<?= businessEscape($employee['ad_soyad']) ?>" required maxlength="150"></div>
                                <div class="form-group"><label>Telefon</label><input name="phone" value="<?= businessEscape($employee['telefon'] ?? '') ?>" maxlength="25"></div>
                                <div class="form-group"><label>Uzmanlık</label><input name="specialty" value="<?= businessEscape($employee['uzmanlik'] ?? '') ?>" maxlength="180"></div>
                                <div class="form-group"><label>Yeni fotoğraf (isteğe bağlı)</label><input type="file" name="photo" accept="image/jpeg,image/png,image/webp"></div>
                                <?php foreach ($services as $service): ?><label style="display:flex;gap:8px;align-items:center;margin:6px 0;"><input type="checkbox" name="service_ids[]" value="<?= (int)$service['id'] ?>" <?= in_array((int)$service['id'], $assigned[(int)$employee['id']] ?? [], true) ? 'checked' : '' ?>><?= businessEscape($service['hizmet_adi']) ?></label><?php endforeach; ?>
                                <button class="btn-admin primary sm" type="submit">Kaydet</button>
                            </form>
                        </details>
                        <form method="POST" onsubmit="return confirm('Çalışan durumunu değiştirmek istiyor musunuz?')">
                            <input type="hidden" name="csrf_token" value="<?= businessEscape(generateCsrfToken()) ?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="employee_id" value="<?= (int)$employee['id'] ?>"><input type="hidden" name="active" value="<?= $employee['aktif_mi'] ? 0 : 1 ?>">
                            <button class="btn-admin <?= $employee['aktif_mi'] ? 'danger' : 'success' ?> sm" type="submit"><?= $employee['aktif_mi'] ? 'Pasife al' : 'Aktifleştir' ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody></table></div><?php endif; ?>
    </div>
</section>
<?php include __DIR__ . '/_footer.php'; ?>
