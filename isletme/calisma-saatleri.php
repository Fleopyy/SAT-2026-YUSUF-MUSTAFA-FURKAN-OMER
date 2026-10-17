<?php
require_once __DIR__ . '/_common.php';
$currentBusinessPage = 'hours';
$pageTitle = 'Çalışma Saatlerim';
$errors = [];
$dayNames = [1 => 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Pazar'];

function validateBusinessPeriods($periods, bool $withBreak = false): array {
    if (!is_array($periods)) {
        throw new RuntimeException('Saat aralıkları geçersiz.');
    }
    $result = [];
    foreach ($periods as $period) {
        if (!is_array($period)) {
            throw new RuntimeException('Saat aralığı biçimi geçersiz.');
        }
        $start = is_string($period['start'] ?? null) ? $period['start'] : '';
        $end = is_string($period['end'] ?? null) ? $period['end'] : '';
        if ($start === '' && $end === '') {
            if (($period['break_start'] ?? '') !== '' || ($period['break_end'] ?? '') !== '') {
                throw new RuntimeException('Mola girebilmek için çalışma başlangıç ve bitiş saatlerini de girin.');
            }
            continue;
        }
        if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $start) || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $end) || $start >= $end) {
            throw new RuntimeException('Başlangıç ve bitiş saatlerini 24 saat biçiminde girin; başlangıç bitişten önce olmalıdır.');
        }
        $breakStart = null;
        $breakEnd = null;
        if ($withBreak) {
            $breakStart = is_string($period['break_start'] ?? null) ? $period['break_start'] : '';
            $breakEnd = is_string($period['break_end'] ?? null) ? $period['break_end'] : '';
            if (($breakStart === '') !== ($breakEnd === '')
                || ($breakStart !== '' && (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $breakStart)
                    || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $breakEnd)
                    || $breakStart >= $breakEnd || $breakStart < $start || $breakEnd > $end))) {
                throw new RuntimeException('Mola saatleri çalışma aralığının içinde ve geçerli olmalıdır.');
            }
        }
        $result[] = [$start . ':00', $end . ':00', $breakStart !== '' && $breakStart !== null ? $breakStart . ':00' : null, $breakEnd !== '' && $breakEnd !== null ? $breakEnd . ':00' : null];
    }
    usort($result, static fn($a, $b) => strcmp($a[0], $b[0]));
    for ($i = 1; $i < count($result); $i++) {
        if ($result[$i][0] < $result[$i - 1][1]) {
            throw new RuntimeException('Aynı günün çalışma aralıkları çakışamaz.');
        }
    }
    return $result;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    businessRequirePost();
    try {
        $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
        if ($action === 'save_business_hours') {
            $weekly = $_POST['weekly'] ?? [];
            if (!is_array($weekly)) {
                throw new RuntimeException('Haftalık program geçersiz.');
            }
            $pdo->beginTransaction();
            $pdo->prepare('DELETE FROM isletme_calisma_araliklari WHERE isletme_id = ?')->execute([$businessId]);
            $insert = $pdo->prepare('INSERT INTO isletme_calisma_araliklari (isletme_id, haftanin_gunu, baslangic, bitis, mola_baslangic, mola_bitis) VALUES (?, ?, ?, ?, ?, ?)');
            foreach ($dayNames as $day => $_name) {
                $periods = validateBusinessPeriods($weekly[$day] ?? [], true);
                foreach ($periods as [$start, $end, $breakStart, $breakEnd]) {
                    $insert->execute([$businessId, $day, $start, $end, $breakStart, $breakEnd]);
                }
            }
            $pdo->commit();
            businessFlash('success', 'İşletme haftalık çalışma saatleri kaydedildi.');
        } elseif ($action === 'save_special') {
            $date = is_string($_POST['date'] ?? null) ? $_POST['date'] : '';
            $closed = isset($_POST['closed']) ? 1 : 0;
            if (!businessValidDate($date) || $date < date('Y-m-d')) {
                throw new RuntimeException('Bugün veya ileri bir geçerli tarih seçin.');
            }
            $start = trim(is_string($_POST['start'] ?? null) ? $_POST['start'] : '');
            $end = trim(is_string($_POST['end'] ?? null) ? $_POST['end'] : '');
            if (!$closed && (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $start) || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $end) || $start >= $end)) {
                throw new RuntimeException('Özel gün açılış ve kapanış saatlerini doğru girin.');
            }
            $note = trim(is_string($_POST['note'] ?? null) ? $_POST['note'] : '');
            $stmt = $pdo->prepare('INSERT INTO isletme_ozel_calisma_gunleri (isletme_id, tarih, kapali, baslangic, bitis, not_metni)
                                   VALUES (?, ?, ?, ?, ?, ?)
                                   ON DUPLICATE KEY UPDATE kapali = VALUES(kapali), baslangic = VALUES(baslangic), bitis = VALUES(bitis), not_metni = VALUES(not_metni)');
            $stmt->execute([$businessId, $date, $closed, $closed ? null : $start . ':00', $closed ? null : $end . ':00', $note !== '' ? $note : null]);
            businessFlash('success', 'Özel gün programı kaydedildi.');
        } elseif ($action === 'delete_special') {
            $specialId = filter_var($_POST['special_id'] ?? null, FILTER_VALIDATE_INT);
            $stmt = $pdo->prepare('DELETE FROM isletme_ozel_calisma_gunleri WHERE id = ? AND isletme_id = ?');
            $stmt->execute([$specialId, $businessId]);
            if (!$stmt->rowCount()) {
                throw new RuntimeException('Özel gün kaydı bulunamadı.');
            }
            businessFlash('success', 'Özel gün kaydı kaldırıldı.');
        } elseif ($action === 'save_employee_hours') {
            $employeeId = filter_var($_POST['employee_id'] ?? null, FILTER_VALIDATE_INT);
            $weekly = $_POST['employee_weekly'] ?? [];
            $stmt = $pdo->prepare('SELECT id FROM calisanlar WHERE id = ? AND isletme_id = ?');
            $stmt->execute([$employeeId, $businessId]);
            if (!$employeeId || !$stmt->fetchColumn() || !is_array($weekly)) {
                throw new RuntimeException('Çalışan seçimi geçersiz.');
            }
            $pdo->beginTransaction();
            $pdo->prepare('DELETE FROM calisan_calisma_araliklari WHERE calisan_id = ?')->execute([$employeeId]);
            $insert = $pdo->prepare('INSERT INTO calisan_calisma_araliklari (calisan_id, haftanin_gunu, baslangic, bitis) VALUES (?, ?, ?, ?)');
            foreach ($dayNames as $day => $_name) {
                foreach (validateBusinessPeriods($weekly[$day] ?? []) as [$start, $end]) {
                    $insert->execute([$employeeId, $day, $start, $end]);
                }
            }
            $pdo->commit();
            businessFlash('success', 'Çalışan çalışma saatleri kaydedildi.');
        } elseif ($action === 'add_leave') {
            $employeeId = filter_var($_POST['employee_id'] ?? null, FILTER_VALIDATE_INT);
            $date = is_string($_POST['date'] ?? null) ? $_POST['date'] : '';
            $start = trim(is_string($_POST['start'] ?? null) ? $_POST['start'] : '');
            $end = trim(is_string($_POST['end'] ?? null) ? $_POST['end'] : '');
            $reason = trim(is_string($_POST['reason'] ?? null) ? $_POST['reason'] : '');
            $stmt = $pdo->prepare('SELECT id FROM calisanlar WHERE id = ? AND isletme_id = ?');
            $stmt->execute([$employeeId, $businessId]);
            if (!$employeeId || !$stmt->fetchColumn() || !businessValidDate($date) || $date < date('Y-m-d') || mb_strlen($reason, 'UTF-8') > 255) {
                throw new RuntimeException('İzin bilgilerini kontrol edin.');
            }
            if (($start === '') !== ($end === '') || ($start !== '' && (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $start) || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $end) || $start >= $end))) {
                throw new RuntimeException('Tam gün izin için saatleri boş bırakın; kısmi izin için geçerli aralık girin.');
            }
            $stmt = $pdo->prepare('INSERT INTO calisan_izinleri (calisan_id, tarih, baslangic, bitis, neden) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$employeeId, $date, $start !== '' ? $start . ':00' : null, $end !== '' ? $end . ':00' : null, $reason !== '' ? $reason : null]);
            businessFlash('success', 'Çalışan izni kaydedildi.');
        } elseif ($action === 'delete_leave') {
            $leaveId = filter_var($_POST['leave_id'] ?? null, FILTER_VALIDATE_INT);
            $stmt = $pdo->prepare('DELETE l FROM calisan_izinleri l INNER JOIN calisanlar c ON c.id = l.calisan_id WHERE l.id = ? AND c.isletme_id = ?');
            $stmt->execute([$leaveId, $businessId]);
            if (!$stmt->rowCount()) {
                throw new RuntimeException('İzin kaydı bu işletmeye ait değil.');
            }
            businessFlash('success', 'İzin kaydı kaldırıldı.');
        } else {
            throw new RuntimeException('İşlem tanınmadı.');
        }
        businessRedirect('calisma-saatleri.php');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Business schedule action failed: ' . $e->getMessage());
        $errors[] = $e instanceof RuntimeException ? $e->getMessage() : 'Çalışma saati kaydedilemedi.';
    }
}

$stmt = $pdo->prepare('SELECT * FROM isletme_calisma_araliklari WHERE isletme_id = ? ORDER BY haftanin_gunu, baslangic');
$stmt->execute([$businessId]);
$businessPeriods = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $period) {
    $businessPeriods[(int)$period['haftanin_gunu']][] = $period;
}
$stmt = $pdo->prepare('SELECT * FROM isletme_ozel_calisma_gunleri WHERE isletme_id = ? AND tarih >= CURDATE() ORDER BY tarih LIMIT 50');
$stmt->execute([$businessId]);
$specialDays = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt = $pdo->prepare('SELECT id, ad_soyad FROM calisanlar WHERE isletme_id = ? ORDER BY aktif_mi DESC, ad_soyad');
$stmt->execute([$businessId]);
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
$employeeHours = [];
$employeeLeaves = [];
foreach ($employees as $employee) {
    $stmt = $pdo->prepare('SELECT * FROM calisan_calisma_araliklari WHERE calisan_id = ? ORDER BY haftanin_gunu, baslangic');
    $stmt->execute([$employee['id']]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $period) {
        $employeeHours[(int)$employee['id']][(int)$period['haftanin_gunu']][] = $period;
    }
}
$stmt = $pdo->prepare('SELECT l.* FROM calisan_izinleri l INNER JOIN calisanlar c ON c.id = l.calisan_id WHERE c.isletme_id = ? AND l.tarih >= CURDATE() ORDER BY l.tarih LIMIT 100');
$stmt->execute([$businessId]);
$employeeLeaves = $stmt->fetchAll(PDO::FETCH_ASSOC);
include __DIR__ . '/_header.php';
?>
<?php foreach ($errors as $error): ?><div class="business-notice error"><?= businessEscape($error) ?></div><?php endforeach; ?>
<section class="admin-panel">
    <div class="panel-header"><h2><i class="fa-solid fa-clock"></i><span>Haftalık İşletme Saatleri</span></h2></div>
    <div class="panel-body padded">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= businessEscape(generateCsrfToken()) ?>"><input type="hidden" name="action" value="save_business_hours">
            <?php foreach ($dayNames as $day => $name): $periods = $businessPeriods[$day] ?? []; $periods[] = []; ?>
                <fieldset style="border:1px solid #E2E8F0;border-radius:10px;padding:14px;margin-bottom:14px;">
                    <legend style="font-weight:800;font-size:14px;"><?= businessEscape($name) ?> <?= empty($businessPeriods[$day]) ? '<span class="status-badge suspended">Kapalı</span>' : '<span class="status-badge active">Açık</span>' ?></legend>
                    <div id="business-day-<?= $day ?>">
                        <?php foreach ($periods as $index => $period): ?>
                            <div class="business-form-grid schedule-row" style="margin-bottom:8px;">
                                <div class="form-group"><label>Açılış</label><input type="time" name="weekly[<?= $day ?>][<?= $index ?>][start]" value="<?= businessEscape(isset($period['baslangic']) ? substr($period['baslangic'], 0, 5) : '') ?>"></div>
                                <div class="form-group"><label>Kapanış</label><input type="time" name="weekly[<?= $day ?>][<?= $index ?>][end]" value="<?= businessEscape(isset($period['bitis']) ? substr($period['bitis'], 0, 5) : '') ?>"></div>
                                <div class="form-group"><label>Mola başlangıcı</label><input type="time" name="weekly[<?= $day ?>][<?= $index ?>][break_start]" value="<?= businessEscape(isset($period['mola_baslangic']) ? substr($period['mola_baslangic'], 0, 5) : '') ?>"></div>
                                <div class="form-group"><label>Mola bitişi</label><input type="time" name="weekly[<?= $day ?>][<?= $index ?>][break_end]" value="<?= businessEscape(isset($period['mola_bitis']) ? substr($period['mola_bitis'], 0, 5) : '') ?>"></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn-admin outline sm" onclick="addSchedulePeriod('business-day-<?= $day ?>','weekly[<?= $day ?>]')"><i class="fa-solid fa-plus"></i> Aralık ekle</button>
                </fieldset>
            <?php endforeach; ?>
            <button class="btn-admin primary" type="submit">Haftalık saatleri kaydet</button>
        </form>
    </div>
</section>
<section class="admin-panel">
    <div class="panel-header"><h2><i class="fa-solid fa-calendar-day"></i><span>Özel gün / tatil</span></h2></div>
    <div class="panel-body padded">
        <form method="POST" class="admin-form business-form-grid">
            <input type="hidden" name="csrf_token" value="<?= businessEscape(generateCsrfToken()) ?>"><input type="hidden" name="action" value="save_special">
            <div class="form-group"><label>Tarih</label><input type="date" name="date" min="<?= date('Y-m-d') ?>" required></div>
            <div class="form-group"><label><input type="checkbox" name="closed" value="1" checked onchange="this.form.querySelectorAll('.special-time').forEach(el=>el.disabled=this.checked)"> Kapalı (tatil)</label></div>
            <div class="form-group"><label>Başlangıç</label><input class="special-time" type="time" name="start" disabled></div>
            <div class="form-group"><label>Bitiş</label><input class="special-time" type="time" name="end" disabled></div>
            <div class="form-group"><label>Açıklama</label><input name="note" maxlength="255"></div>
            <div class="form-group" style="align-self:end"><button class="btn-admin primary">Özel günü kaydet</button></div>
        </form>
        <div style="overflow-x:auto;margin-top:18px;"><table class="admin-table"><thead><tr><th>Tarih</th><th>Durum</th><th>Açıklama</th><th></th></tr></thead><tbody>
            <?php foreach ($specialDays as $special): ?><tr><td><?= businessEscape($special['tarih']) ?></td><td><?= $special['kapali'] ? 'Kapalı' : businessEscape(substr($special['baslangic'], 0, 5) . '–' . substr($special['bitis'], 0, 5)) ?></td><td><?= businessEscape($special['not_metni'] ?? '') ?></td><td><form method="POST" onsubmit="return confirm('Özel gün kaydı silinsin mi?')"><input type="hidden" name="csrf_token" value="<?= businessEscape(generateCsrfToken()) ?>"><input type="hidden" name="action" value="delete_special"><input type="hidden" name="special_id" value="<?= (int)$special['id'] ?>"><button class="btn-admin danger sm">Sil</button></form></td></tr><?php endforeach; ?>
        </tbody></table></div>
    </div>
</section>
<section class="admin-panel">
    <div class="panel-header"><h2><i class="fa-solid fa-user-clock"></i><span>Çalışan saatleri ve izinleri</span></h2></div>
    <div class="panel-body padded">
        <?php foreach ($employees as $employee): ?>
            <details style="border:1px solid #E2E8F0;border-radius:10px;padding:14px;margin-bottom:12px;"><summary style="font-weight:800;cursor:pointer;"><?= businessEscape($employee['ad_soyad']) ?></summary>
                <form method="POST" style="margin-top:16px;">
                    <input type="hidden" name="csrf_token" value="<?= businessEscape(generateCsrfToken()) ?>"><input type="hidden" name="action" value="save_employee_hours"><input type="hidden" name="employee_id" value="<?= (int)$employee['id'] ?>">
                    <?php foreach ($dayNames as $day => $name): $periods = $employeeHours[(int)$employee['id']][$day] ?? []; $periods[] = []; ?>
                        <fieldset style="border:1px solid #E2E8F0;border-radius:8px;padding:10px;margin-bottom:8px;"><legend><?= businessEscape($name) ?></legend><div id="employee-<?= (int)$employee['id'] ?>-day-<?= $day ?>">
                            <?php foreach ($periods as $index => $period): ?><div class="business-form-grid schedule-row" style="margin-bottom:8px;">
                                <div class="form-group"><label>Açılış</label><input type="time" name="employee_weekly[<?= $day ?>][<?= $index ?>][start]" value="<?= businessEscape(isset($period['baslangic']) ? substr($period['baslangic'], 0, 5) : '') ?>"></div>
                                <div class="form-group"><label>Kapanış</label><input type="time" name="employee_weekly[<?= $day ?>][<?= $index ?>][end]" value="<?= businessEscape(isset($period['bitis']) ? substr($period['bitis'], 0, 5) : '') ?>"></div>
                            </div><?php endforeach; ?>
                        </div><button type="button" class="btn-admin outline sm" onclick="addEmployeePeriod(<?= (int)$employee['id'] ?>,<?= $day ?>)">Aralık ekle</button></fieldset>
                    <?php endforeach; ?>
                    <button class="btn-admin primary">Çalışma saatlerini kaydet</button>
                </form>
                <form method="POST" class="business-form-grid admin-form" style="margin-top:16px;">
                    <input type="hidden" name="csrf_token" value="<?= businessEscape(generateCsrfToken()) ?>"><input type="hidden" name="action" value="add_leave"><input type="hidden" name="employee_id" value="<?= (int)$employee['id'] ?>">
                    <div class="form-group"><label>İzin tarihi</label><input type="date" name="date" min="<?= date('Y-m-d') ?>" required></div>
                    <div class="form-group"><label>Başlangıç (boş: tam gün)</label><input type="time" name="start"></div>
                    <div class="form-group"><label>Bitiş</label><input type="time" name="end"></div>
                    <div class="form-group"><label>Neden</label><input name="reason" maxlength="255"></div>
                    <div class="form-group" style="align-self:end"><button class="btn-admin outline">İzin ekle</button></div>
                </form>
            </details>
        <?php endforeach; ?>
        <h3 style="margin-top:20px;font-size:15px;">Yaklaşan izinler</h3>
        <div style="overflow-x:auto;"><table class="admin-table"><thead><tr><th>Çalışan</th><th>Tarih</th><th>Saat</th><th>Neden</th><th></th></tr></thead><tbody>
            <?php foreach ($employeeLeaves as $leave): ?><tr><td><?php foreach ($employees as $employee) { if ((int)$employee['id'] === (int)$leave['calisan_id']) { echo businessEscape($employee['ad_soyad']); break; } } ?></td><td><?= businessEscape($leave['tarih']) ?></td><td><?= $leave['baslangic'] ? businessEscape(substr($leave['baslangic'], 0, 5) . '–' . substr($leave['bitis'], 0, 5)) : 'Tam gün' ?></td><td><?= businessEscape($leave['neden'] ?? '') ?></td><td><form method="POST" onsubmit="return confirm('İzin kaydı silinsin mi?')"><input type="hidden" name="csrf_token" value="<?= businessEscape(generateCsrfToken()) ?>"><input type="hidden" name="action" value="delete_leave"><input type="hidden" name="leave_id" value="<?= (int)$leave['id'] ?>"><button class="btn-admin danger sm">Sil</button></form></td></tr><?php endforeach; ?>
        </tbody></table></div>
    </div>
</section>
<script>
function addSchedulePeriod(containerId, prefix) {
    const container = document.getElementById(containerId);
    const index = container.querySelectorAll('.schedule-row').length;
    const row = document.createElement('div'); row.className = 'business-form-grid schedule-row'; row.style.marginBottom = '8px';
    [['start','Açılış'],['end','Kapanış'],['break_start','Mola başlangıcı'],['break_end','Mola bitişi']].forEach(([field,label]) => {
        row.insertAdjacentHTML('beforeend','<div class="form-group"><label>'+label+'</label><input type="time" name="'+prefix+'['+index+']['+field+']"></div>');
    });
    container.appendChild(row);
}
function addEmployeePeriod(employeeId, day) {
    const container = document.getElementById('employee-'+employeeId+'-day-'+day);
    const index = container.querySelectorAll('.schedule-row').length;
    const row = document.createElement('div'); row.className = 'business-form-grid schedule-row'; row.style.marginBottom = '8px';
    row.innerHTML = '<div class="form-group"><label>Açılış</label><input type="time" name="employee_weekly['+day+']['+index+'][start]"></div><div class="form-group"><label>Kapanış</label><input type="time" name="employee_weekly['+day+']['+index+'][end]"></div>';
    container.appendChild(row);
}
</script>
<?php include __DIR__ . '/_footer.php'; ?>
