<?php
require_once __DIR__ . '/_common.php';
$currentBusinessPage = 'appointments';
$pageTitle = 'Randevularım';
$errors = [];
$allowedFilters = ['all', 'today', 'tomorrow', 'pending', 'confirmed', 'completed', 'cancelled', 'rejected'];
$filter = is_string($_GET['filter'] ?? null) && in_array($_GET['filter'], $allowedFilters, true) ? $_GET['filter'] : 'all';
$dateFrom = is_string($_GET['from'] ?? null) && businessValidDate($_GET['from']) ? $_GET['from'] : '';
$dateTo = is_string($_GET['to'] ?? null) && businessValidDate($_GET['to']) ? $_GET['to'] : '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    businessRequirePost();
    try {
        $appointmentId = filter_var($_POST['appointment_id'] ?? null, FILTER_VALIDATE_INT);
        $newStatus = is_string($_POST['status'] ?? null) ? $_POST['status'] : '';
        if (!$appointmentId) {
            throw new RuntimeException('Randevu seçimi geçersiz.');
        }
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT r.durum, r.randevu_zamani, x.bitis_zamani, h.sure_dakika
                               FROM randevular r
                               LEFT JOIN randevu_ek_bilgileri x ON x.randevu_id = r.id
                               LEFT JOIN hizmetler h ON h.id = r.hizmet_id
                               WHERE r.id = ? AND r.isletme_id = ? FOR UPDATE');
        $stmt->execute([$appointmentId, $businessId]);
        $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$appointment) {
            throw new RuntimeException('Randevu bu işletmeye ait değil veya bulunamadı.');
        }
        $transitions = [
            'pending' => ['confirmed', 'rejected', 'cancelled'],
            'confirmed' => ['completed', 'cancelled'],
        ];
        if (!in_array($newStatus, $transitions[$appointment['durum']] ?? [], true)) {
            throw new RuntimeException('Bu randevu için seçilen durum geçişine izin verilmiyor.');
        }
        $stmt = $pdo->prepare('UPDATE randevular SET durum = ? WHERE id = ? AND isletme_id = ? AND durum = ?');
        $stmt->execute([$newStatus, $appointmentId, $businessId, $appointment['durum']]);
        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException('Randevu durumu aynı anda değişmiş olabilir; yenileyip tekrar deneyin.');
        }
        $pdo->commit();
        businessFlash('success', 'Randevu durumu güncellendi.');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Business appointment update failed: ' . $e->getMessage());
        $errors[] = $e instanceof RuntimeException ? $e->getMessage() : 'Randevu güncellenemedi.';
    }
}

$where = ['r.isletme_id = ?'];
$params = [$businessId];
if ($filter === 'today') {
    $where[] = 'r.randevu_zamani >= CURDATE() AND r.randevu_zamani < DATE_ADD(CURDATE(), INTERVAL 1 DAY)';
} elseif ($filter === 'tomorrow') {
    $where[] = 'r.randevu_zamani >= DATE_ADD(CURDATE(), INTERVAL 1 DAY) AND r.randevu_zamani < DATE_ADD(CURDATE(), INTERVAL 2 DAY)';
} elseif (in_array($filter, ['pending','confirmed','completed','cancelled','rejected'], true)) {
    $where[] = 'r.durum = ?';
    $params[] = $filter;
}
if ($dateFrom !== '') {
    $where[] = 'r.randevu_zamani >= ?';
    $params[] = $dateFrom . ' 00:00:00';
}
if ($dateTo !== '') {
    $where[] = 'r.randevu_zamani < DATE_ADD(?, INTERVAL 1 DAY)';
    $params[] = $dateTo;
}
$sql = "SELECT r.id, r.randevu_zamani, r.durum, k.ad_soyad AS musteri_adi, k.telefon AS musteri_telefon,
               h.hizmet_adi, h.fiyat, h.sure_dakika, c.ad_soyad AS calisan_adi,
               i.isletme_sahibi,
               x.fiyat_snapshot, x.bitis_zamani, x.musteri_notu
        FROM randevular r
        LEFT JOIN kullanicilar k ON k.id = r.kullanici_id
        LEFT JOIN hizmetler h ON h.id = r.hizmet_id
        LEFT JOIN calisanlar c ON c.id = r.calisan_id
        INNER JOIN isletmeler i ON i.id = r.isletme_id
        LEFT JOIN randevu_ek_bilgileri x ON x.randevu_id = r.id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY r.randevu_zamani DESC LIMIT 250";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
$filterLabels = ['all'=>'Tümü','today'=>'Bugün','tomorrow'=>'Yarın','pending'=>'Onay bekleyen','confirmed'=>'Onaylanan','completed'=>'Tamamlanan','cancelled'=>'İptal','rejected'=>'Reddedilen'];
include __DIR__ . '/_header.php';
?>
<?php foreach ($errors as $error): ?><div class="business-notice error"><?= businessEscape($error) ?></div><?php endforeach; ?>
<section class="admin-panel">
    <div class="panel-header"><h2><i class="fa-solid fa-filter"></i><span>Randevu Filtreleri</span></h2></div>
    <div class="panel-body padded">
        <div class="filter-tabs" style="margin-bottom:14px;">
            <?php foreach ($filterLabels as $value => $label): ?><a class="filter-tab <?= $filter === $value ? 'active' : '' ?>" href="/isletme/randevular.php?filter=<?= businessEscape($value) ?>"><?= businessEscape($label) ?></a><?php endforeach; ?>
        </div>
        <form method="GET" class="business-form-grid admin-form">
            <input type="hidden" name="filter" value="<?= businessEscape($filter) ?>">
            <div class="form-group"><label>Başlangıç tarihi</label><input type="date" name="from" value="<?= businessEscape($dateFrom) ?>"></div>
            <div class="form-group"><label>Bitiş tarihi</label><input type="date" name="to" value="<?= businessEscape($dateTo) ?>"></div>
            <div class="form-group" style="align-self:end"><button class="btn-admin primary">Filtrele</button> <a class="btn-admin outline" href="/isletme/randevular.php">Temizle</a></div>
        </form>
    </div>
</section>
<section class="admin-panel">
    <div class="panel-header"><h2><i class="fa-solid fa-calendar-days"></i><span><?= businessEscape($filterLabels[$filter]) ?> randevular</span></h2><span class="status-badge"><?= count($appointments) ?> kayıt</span></div>
    <div class="panel-body">
        <?php if (!$appointments): ?><div class="empty-state"><i class="fa-regular fa-calendar"></i><h4>Randevu bulunamadı</h4><p>Seçili filtrelerde görüntülenecek randevu yok.</p></div>
        <?php else: ?><div style="overflow-x:auto;"><table class="admin-table"><thead><tr><th>Müşteri</th><th>Hizmet / fiyat</th><th>Çalışan</th><th>Tarih ve saat</th><th>Durum</th><th>Müşteri notu</th><th>İşlem</th></tr></thead><tbody>
            <?php foreach ($appointments as $appointment):
                $duration = (int)($appointment['sure_dakika'] ?: 30);
                $end = $appointment['bitis_zamani'] ?: date('Y-m-d H:i:s', strtotime($appointment['randevu_zamani']) + $duration * 60);
                $statusLabels = ['pending'=>'Onay bekliyor','confirmed'=>'Onaylandı','completed'=>'Tamamlandı','cancelled'=>'İptal edildi','rejected'=>'Reddedildi'];
            ?>
                <tr>
                    <td><strong><?= businessEscape($appointment['musteri_adi'] ?? 'Müşteri') ?></strong><small style="display:block"><?= businessEscape($appointment['musteri_telefon'] ?? '') ?></small></td>
                    <td><?= businessEscape($appointment['hizmet_adi'] ?? 'Hizmet') ?><small style="display:block;color:#64748B"><?= $appointment['fiyat_snapshot'] !== null ? number_format((float)$appointment['fiyat_snapshot'],2,',','.') : number_format((float)($appointment['fiyat'] ?? 0),2,',','.') ?> ₺</small></td>
                    <td><?= businessEscape($appointment['calisan_adi'] ?? ('İşletme sahibi — ' . $appointment['isletme_sahibi'])) ?></td>
                    <td><?= date('d.m.Y', strtotime($appointment['randevu_zamani'])) ?><small style="display:block"><?= date('H:i', strtotime($appointment['randevu_zamani'])) ?>–<?= date('H:i', strtotime($end)) ?></small></td>
                    <td><span class="status-badge <?= in_array($appointment['durum'], ['confirmed','completed'], true) ? 'active' : (in_array($appointment['durum'], ['cancelled','rejected'], true) ? 'suspended' : 'pending') ?>"><?= businessEscape($statusLabels[$appointment['durum']] ?? $appointment['durum']) ?></span></td>
                    <td><?= businessEscape($appointment['musteri_notu'] ?? '—') ?></td>
                    <td><div class="business-actions">
                        <?php if ($appointment['durum'] === 'pending'): ?>
                            <?php foreach (['confirmed'=>'Onayla','rejected'=>'Reddet','cancelled'=>'İptal'] as $status=>$label): ?>
                                <form method="POST" onsubmit="return confirm('<?= businessEscape($label) ?> işlemini onaylıyor musunuz?')"><input type="hidden" name="csrf_token" value="<?= businessEscape(generateCsrfToken()) ?>"><input type="hidden" name="appointment_id" value="<?= (int)$appointment['id'] ?>"><button class="btn-admin <?= $status === 'confirmed' ? 'success' : 'danger' ?> sm" name="status" value="<?= businessEscape($status) ?>"><?= businessEscape($label) ?></button></form>
                            <?php endforeach; ?>
                        <?php elseif ($appointment['durum'] === 'confirmed'): ?>
                            <form method="POST" onsubmit="return confirm('Randevuyu tamamlandı olarak işaretleyin?')"><input type="hidden" name="csrf_token" value="<?= businessEscape(generateCsrfToken()) ?>"><input type="hidden" name="appointment_id" value="<?= (int)$appointment['id'] ?>"><button class="btn-admin success sm" name="status" value="completed">Tamamlandı</button></form>
                            <form method="POST" onsubmit="return confirm('Randevuyu iptal etmek istiyor musunuz?')"><input type="hidden" name="csrf_token" value="<?= businessEscape(generateCsrfToken()) ?>"><input type="hidden" name="appointment_id" value="<?= (int)$appointment['id'] ?>"><button class="btn-admin danger sm" name="status" value="cancelled">İptal</button></form>
                        <?php endif; ?>
                    </div></td>
                </tr>
            <?php endforeach; ?>
        </tbody></table></div><?php endif; ?>
    </div>
</section>
<?php include __DIR__ . '/_footer.php'; ?>
