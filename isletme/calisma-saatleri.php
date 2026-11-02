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
        $errors[] = ($e instanceof RuntimeException && !($e instanceof PDOException)) ? $e->getMessage() : 'Çalışma saati kaydedilemedi.';
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
    <div class="panel-header">
        <h2><i class="fa-solid fa-clock"></i><span>Haftalık Çalışma Saatleri</span></h2>
    </div>
    <div class="panel-body padded">
        <!-- Hero Quick Action Banner -->
        <div class="schedule-hero-banner">
            <div class="hero-info">
                <div class="hero-icon"><i class="fa-solid fa-wand-magic-sparkles"></i></div>
                <div class="hero-text">
                    <h4>Haftalık Çalışma & Mola Düzeni</h4>
                    <p>Müşterileriniz randevu oluştururken bu saat aralıklarını seçebilir. Kapalı günleri tek tıkla pasife alabilirsiniz.</p>
                </div>
            </div>
            <div class="schedule-quick-actions">
                <button type="button" class="btn-admin outline sm" onclick="copyMondayToWeekdays()">
                    <i class="fa-solid fa-copy"></i> Pazartesi Saatini Hafta İçine Uygula    
                </button>
            </div>
        </div>

        <form method="POST" id="weeklyScheduleForm">
            <input type="hidden" name="csrf_token" value="<?= businessEscape(generateCsrfToken()) ?>">
            <input type="hidden" name="action" value="save_business_hours">
            
            <div class="schedule-days-container">
                <?php 
                $dayShorts = [1=>'Pzt', 2=>'Sal', 3=>'Çar', 4=>'Per', 5=>'Cum', 6=>'Cmt', 7=>'Paz'];
                foreach ($dayNames as $day => $name): 
                    $periods = $businessPeriods[$day] ?? []; 
                    $isOpen = !empty($periods);
                    if (!$isOpen) {
                        $periods = [['baslangic'=>'09:00:00', 'bitis'=>'20:00:00', 'mola_baslangic'=>null, 'mola_bitis'=>null]];
                    }
                ?>
                    <div class="schedule-day-card <?= $isOpen ? '' : 'is-closed' ?>" id="day-card-<?= $day ?>">
                        <!-- Card Header -->
                        <div class="schedule-card-header">
                            <div class="schedule-header-left">
                                <div class="schedule-day-avatar"><?= $dayShorts[$day] ?></div>
                                <div>
                                    <span class="schedule-day-name"><?= businessEscape($name) ?></span>
                                    <span class="schedule-status-pill <?= $isOpen ? 'open' : 'closed' ?>" id="status-pill-<?= $day ?>">
                                        <?= $isOpen ? 'Açık' : 'Kapalı' ?>
                                    </span>
                                </div>
                            </div>
                            <div class="schedule-toggle-wrapper">
                                <span class="schedule-toggle-label"><?= $isOpen ? 'Gün Açık' : 'Gün Kapalı' ?></span>
                                <label class="switch-ui" title="<?= $isOpen ? 'Günü kapat' : 'Günü aç' ?>">
                                    <input type="checkbox" class="day-toggle-cb" data-day="<?= $day ?>" <?= $isOpen ? 'checked' : '' ?> onchange="toggleDayStatus(<?= $day ?>, this.checked)">
                                    <span class="slider-ui"></span>
                                </label>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="schedule-card-body">
                            <div class="schedule-rows-list" id="business-day-<?= $day ?>">
                                <?php foreach ($periods as $index => $period): ?>
                                    <div class="schedule-row-item" data-row-index="<?= $index ?>">
                                        <!-- Mesai Saatleri -->
                                        <div class="schedule-col-group">
                                            <label class="schedule-col-label"><i class="fa-regular fa-clock"></i> Mesai Saatleri</label>
                                            <div class="schedule-inputs-inline">
                                                <input type="time" name="weekly[<?= $day ?>][<?= $index ?>][start]" value="<?= businessEscape(isset($period['baslangic']) ? substr($period['baslangic'], 0, 5) : ($isOpen ? '09:00' : '')) ?>" title="Açılış Saati" <?= !$isOpen ? 'disabled' : '' ?>>
                                                <span class="schedule-separator">—</span>
                                                <input type="time" name="weekly[<?= $day ?>][<?= $index ?>][end]" value="<?= businessEscape(isset($period['bitis']) ? substr($period['bitis'], 0, 5) : ($isOpen ? '20:00' : '')) ?>" title="Kapanış Saati" <?= !$isOpen ? 'disabled' : '' ?>>
                                            </div>
                                        </div>
                                        <!-- Öğle / Mola -->
                                        <div class="schedule-col-group">
                                            <label class="schedule-col-label break"><i class="fa-solid fa-mug-hot"></i> Mola / Öğle Arası (Opsiyonel)</label>
                                            <div class="schedule-inputs-inline">
                                                <input type="time" name="weekly[<?= $day ?>][<?= $index ?>][break_start]" value="<?= businessEscape(isset($period['mola_baslangic']) && $period['mola_baslangic'] ? substr($period['mola_baslangic'], 0, 5) : '') ?>" title="Mola Başlangıç" <?= !$isOpen ? 'disabled' : '' ?>>
                                                <span class="schedule-separator">—</span>
                                                <input type="time" name="weekly[<?= $day ?>][<?= $index ?>][break_end]" value="<?= businessEscape(isset($period['mola_bitis']) && $period['mola_bitis'] ? substr($period['mola_bitis'], 0, 5) : '') ?>" title="Mola Bitiş" <?= !$isOpen ? 'disabled' : '' ?>>
                                            </div>
                                        </div>
                                        <!-- Sil -->
                                        <button type="button" class="btn-remove-schedule-row" onclick="removeScheduleRow(this, <?= $day ?>)" title="Bu aralığı kaldır">
                                            <i class="fa-regular fa-trash-can"></i>
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div style="display:flex;justify-content:flex-start;margin-top:6px;">
                                <button type="button" class="btn-admin outline sm" onclick="addSchedulePeriod(<?= $day ?>)">
                                    <i class="fa-solid fa-plus"></i> Yeni Saat Aralığı Ekle
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Sticky Save Bar -->
            <div class="schedule-save-banner">
                <div class="save-info">
                    <i class="fa-solid fa-circle-check" style="color:var(--admin-success);font-size:18px;"></i>
                    <span>Kaydettiğiniz çalışma ve mola saatleri randevu takviminize anında yansır.</span>
                </div>
                <button class="btn-admin primary lg" type="submit">
                    <i class="fa-solid fa-floppy-disk"></i> Haftalık Çalışma Saatlerini Kaydet
                </button>
            </div>
        </form>
    </div>
</section>

<!-- Özel Gün / Tatil -->
<section class="admin-panel">
    <div class="panel-header">
        <h2><i class="fa-solid fa-calendar-day"></i><span>Özel Gün & Tatil Programı</span></h2>
    </div>
    <div class="panel-body padded">
        <form method="POST" class="admin-form business-filter-form">
            <input type="hidden" name="csrf_token" value="<?= businessEscape(generateCsrfToken()) ?>">
            <input type="hidden" name="action" value="save_special">
            <div class="form-group" style="min-width:160px;">
                <label>Tarih</label>
                <input type="date" name="date" min="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="form-group" style="min-width:150px;align-self:center;padding-top:20px;">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13.5px;font-weight:700;">
                    <input type="checkbox" name="closed" value="1" checked onchange="this.form.querySelectorAll('.special-time').forEach(el=>el.disabled=this.checked)" style="width:16px;height:16px;accent-color:var(--admin-accent);">
                    Kapalı (Tam gün tatil)
                </label>
            </div>
            <div class="form-group" style="min-width:110px;">
                <label>Açılış</label>
                <input class="special-time" type="time" name="start" disabled>
            </div>
            <div class="form-group" style="min-width:110px;">
                <label>Bitiş</label>
                <input class="special-time" type="time" name="end" disabled>
            </div>
            <div class="form-group" style="min-width:200px;flex:2;">
                <label>Açıklama / Not</label>
                <input name="note" maxlength="255" placeholder="Örn: Resmi Bayram Tatili">
            </div>
            <div class="business-filter-actions">
                <button type="submit" class="btn-admin primary"><i class="fa-solid fa-plus"></i> Özel Günü Kaydet</button>
            </div>
        </form>
        <div style="overflow-x:auto;margin-top:20px;">
            <table class="admin-table">
                <thead>
                    <tr><th>Tarih</th><th>Durum / Saat</th><th>Açıklama</th><th>İşlem</th></tr>
                </thead>
                <tbody>
                    <?php if (!$specialDays): ?>
                        <tr><td colspan="4" style="text-align:center;color:#64748B;padding:20px;">Kayıtlı özel gün veya tatil bulunmuyor.</td></tr>
                    <?php else: ?>
                        <?php foreach ($specialDays as $special): ?>
                            <tr>
                                <td><strong><?= date('d.m.Y', strtotime($special['tarih'])) ?></strong></td>
                                <td>
                                    <?php if ($special['kapali']): ?>
                                        <span class="status-badge suspended">Tam Gün Kapalı</span>
                                    <?php else: ?>
                                        <span class="status-badge active"><?= businessEscape(substr($special['baslangic'], 0, 5) . ' – ' . substr($special['bitis'], 0, 5)) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= businessEscape($special['not_metni'] ?: '—') ?></td>
                                <td>
                                    <form method="POST" onsubmit="return adminConfirmForm(event, this, 'Özel gün kaydı silinsin mi?');" style="margin:0;">
                                        <input type="hidden" name="csrf_token" value="<?= businessEscape(generateCsrfToken()) ?>">
                                        <input type="hidden" name="action" value="delete_special">
                                        <input type="hidden" name="special_id" value="<?= (int)$special['id'] ?>">
                                        <button class="btn-admin danger sm"><i class="fa-solid fa-trash-can"></i> Sil</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- Çalışan Saatleri ve İzinleri -->
<section class="admin-panel">
    <div class="panel-header">
        <h2><i class="fa-solid fa-user-clock"></i><span>Çalışan Saatleri ve İzinleri</span></h2>
    </div>
    <div class="panel-body padded">
        <?php if (!$employees): ?>
            <div class="empty-state"><i class="fa-solid fa-users"></i><h4>Kayıtlı aktif çalışan yok</h4></div>
        <?php else: ?>
            <?php foreach ($employees as $employee): ?>
                <details style="border:1.5px solid #E2E8F0;border-radius:12px;padding:16px;margin-bottom:14px;background:#FFFFFF;">
                    <summary style="font-weight:800;font-size:15px;cursor:pointer;display:flex;align-items:center;justify-content:space-between;">
                        <span><i class="fa-solid fa-user-tie" style="color:var(--admin-accent);margin-right:8px;"></i> <?= businessEscape($employee['ad_soyad']) ?></span>
                        <span class="btn-admin outline sm" style="pointer-events:none;"><i class="fa-solid fa-chevron-down"></i> Saatleri Düzenle</span>
                    </summary>
                    <form method="POST" style="margin-top:18px;">
                        <input type="hidden" name="csrf_token" value="<?= businessEscape(generateCsrfToken()) ?>">
                        <input type="hidden" name="action" value="save_employee_hours">
                        <input type="hidden" name="employee_id" value="<?= (int)$employee['id'] ?>">
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:12px;margin-bottom:14px;">
                            <?php foreach ($dayNames as $day => $name): $periods = $employeeHours[(int)$employee['id']][$day] ?? []; if (empty($periods)) $periods = [[]]; ?>
                                <div style="border:1px solid #E2E8F0;border-radius:10px;padding:12px;background:#F8FAFC;">
                                    <div style="font-weight:700;font-size:13px;margin-bottom:8px;color:#0F172A;"><?= businessEscape($name) ?></div>
                                    <div id="employee-<?= (int)$employee['id'] ?>-day-<?= $day ?>">
                                        <?php foreach ($periods as $index => $period): ?>
                                            <div style="display:flex;align-items:center;gap:6px;margin-bottom:6px;">
                                                <input type="time" name="employee_weekly[<?= $day ?>][<?= $index ?>][start]" value="<?= businessEscape(isset($period['baslangic']) ? substr($period['baslangic'], 0, 5) : '') ?>" style="flex:1;height:36px;border:1.5px solid #CBD5E1;border-radius:7px;padding:0 8px;font-size:13px;text-align:center;">
                                                <span style="color:#94A3B8;font-weight:700;">—</span>
                                                <input type="time" name="employee_weekly[<?= $day ?>][<?= $index ?>][end]" value="<?= businessEscape(isset($period['bitis']) ? substr($period['bitis'], 0, 5) : '') ?>" style="flex:1;height:36px;border:1.5px solid #CBD5E1;border-radius:7px;padding:0 8px;font-size:13px;text-align:center;">
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <button type="button" class="btn-admin outline sm" style="width:100%;margin-top:4px;" onclick="addEmployeePeriod(<?= (int)$employee['id'] ?>,<?= $day ?>)"><i class="fa-solid fa-plus"></i> Aralık ekle</button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button class="btn-admin primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Çalışan Saatlerini Kaydet</button>
                    </form>

                    <!-- İzin Formu -->
                    <div style="margin-top:20px;padding-top:16px;border-top:1px dashed #E2E8F0;">
                        <h4 style="font-size:13.5px;font-weight:800;margin-bottom:12px;color:var(--admin-text);"><i class="fa-solid fa-calendar-plus" style="color:var(--admin-accent);"></i> Bu Çalışan İçin İzin Ekle</h4>
                        <form method="POST" class="admin-form business-filter-form">
                            <input type="hidden" name="csrf_token" value="<?= businessEscape(generateCsrfToken()) ?>">
                            <input type="hidden" name="action" value="add_leave">
                            <input type="hidden" name="employee_id" value="<?= (int)$employee['id'] ?>">
                            <div class="form-group" style="min-width:150px;"><label>İzin tarihi</label><input type="date" name="date" min="<?= date('Y-m-d') ?>" required></div>
                            <div class="form-group" style="min-width:110px;"><label>Başlangıç (boş: tam gün)</label><input type="time" name="start"></div>
                            <div class="form-group" style="min-width:110px;"><label>Bitiş</label><input type="time" name="end"></div>
                            <div class="form-group" style="min-width:180px;flex:2;"><label>Neden</label><input name="reason" maxlength="255" placeholder="Örn: Yıllık İzin"></div>
                            <div class="business-filter-actions"><button class="btn-admin outline" type="submit"><i class="fa-solid fa-plus"></i> İzin ekle</button></div>
                        </form>
                    </div>
                </details>
            <?php endforeach; ?>
        <?php endif; ?>

        <h3 style="margin-top:24px;margin-bottom:12px;font-size:15px;font-weight:800;"><i class="fa-solid fa-calendar-check" style="color:var(--admin-accent);"></i> Yaklaşan Çalışan İzinleri</h3>
        <div style="overflow-x:auto;">
            <table class="admin-table">
                <thead><tr><th>Çalışan</th><th>Tarih</th><th>Saat</th><th>Neden</th><th>İşlem</th></tr></thead>
                <tbody>
                    <?php if (!$employeeLeaves): ?>
                        <tr><td colspan="5" style="text-align:center;color:#64748B;padding:20px;">Yaklaşan izin kaydı yok.</td></tr>
                    <?php else: ?>
                        <?php foreach ($employeeLeaves as $leave): ?>
                            <tr>
                                <td><strong><?php foreach ($employees as $employee) { if ((int)$employee['id'] === (int)$leave['calisan_id']) { echo businessEscape($employee['ad_soyad']); break; } } ?></strong></td>
                                <td><?= date('d.m.Y', strtotime($leave['tarih'])) ?></td>
                                <td><span class="status-badge <?= $leave['baslangic'] ? 'pending' : 'suspended' ?>"><?= $leave['baslangic'] ? businessEscape(substr($leave['baslangic'], 0, 5) . ' – ' . substr($leave['bitis'], 0, 5)) : 'Tam gün' ?></span></td>
                                <td><?= businessEscape($leave['neden'] ?: '—') ?></td>
                                <td>
                                    <form method="POST" onsubmit="return adminConfirmForm(event, this, 'İzin kaydı silinsin mi?');" style="margin:0;">
                                        <input type="hidden" name="csrf_token" value="<?= businessEscape(generateCsrfToken()) ?>">
                                        <input type="hidden" name="action" value="delete_leave">
                                        <input type="hidden" name="leave_id" value="<?= (int)$leave['id'] ?>">
                                        <button class="btn-admin danger sm"><i class="fa-solid fa-trash-can"></i> Sil</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<script>
function toggleDayStatus(day, isChecked) {
    const card = document.getElementById('day-card-' + day);
    const pill = document.getElementById('status-pill-' + day);
    const label = card.querySelector('.schedule-toggle-label');
    const inputs = card.querySelectorAll('#business-day-' + day + ' input');
    
    if (isChecked) {
        card.classList.remove('is-closed');
        pill.className = 'schedule-status-pill open';
        pill.textContent = 'Açık';
        if (label) label.textContent = 'Gün Açık';
        inputs.forEach(el => {
            el.disabled = false;
        });
        // Check if all start/end inputs are empty, populate default 09:00 - 20:00
        const startInput = card.querySelector('input[name*="[start]"]');
        const endInput = card.querySelector('input[name*="[end]"]');
        if (startInput && !startInput.value) startInput.value = '09:00';
        if (endInput && !endInput.value) endInput.value = '20:00';
    } else {
        card.classList.add('is-closed');
        pill.className = 'schedule-status-pill closed';
        pill.textContent = 'Kapalı';
        if (label) label.textContent = 'Gün Kapalı';
        inputs.forEach(el => {
            el.disabled = true;
        });
    }
}

function removeScheduleRow(btn, day) {
    const container = document.getElementById('business-day-' + day);
    const rows = container.querySelectorAll('.schedule-row-item');
    if (rows.length > 1) {
        btn.closest('.schedule-row-item').remove();
    } else {
        // Only 1 row left: clear its inputs and toggle day closed
        const toggle = document.querySelector('.day-toggle-cb[data-day="' + day + '"]');
        if (toggle && toggle.checked) {
            toggle.checked = false;
            toggleDayStatus(day, false);
        }
    }
}

function addSchedulePeriod(day) {
    const container = document.getElementById('business-day-' + day);
    const index = container.querySelectorAll('.schedule-row-item').length;
    const row = document.createElement('div');
    row.className = 'schedule-row-item';
    row.setAttribute('data-row-index', index);
    row.innerHTML = `
        <div class="schedule-col-group">
            <label class="schedule-col-label"><i class="fa-regular fa-clock"></i> Mesai Saatleri</label>
            <div class="schedule-inputs-inline">
                <input type="time" name="weekly[${day}][${index}][start]" value="14:00" title="Açılış Saati">
                <span class="schedule-separator">—</span>
                <input type="time" name="weekly[${day}][${index}][end]" value="21:00" title="Kapanış Saati">
            </div>
        </div>
        <div class="schedule-col-group">
            <label class="schedule-col-label break"><i class="fa-solid fa-mug-hot"></i> Mola / Öğle Arası (Opsiyonel)</label>
            <div class="schedule-inputs-inline">
                <input type="time" name="weekly[${day}][${index}][break_start]" value="" title="Mola Başlangıç">
                <span class="schedule-separator">—</span>
                <input type="time" name="weekly[${day}][${index}][break_end]" value="" title="Mola Bitiş">
            </div>
        </div>
        <button type="button" class="btn-remove-schedule-row" onclick="removeScheduleRow(this, ${day})" title="Bu aralığı kaldır">
            <i class="fa-regular fa-trash-can"></i>
        </button>
    `;
    container.appendChild(row);
}

function copyMondayToWeekdays() {
    if (!confirm('Pazartesi gününün saatleri Salı, Çarşamba, Perşembe ve Cuma günlerine uygulansın mı?')) return;
    const mondayContainer = document.getElementById('business-day-1');
    const mondayRows = mondayContainer.querySelectorAll('.schedule-row-item');
    const mondayToggle = document.querySelector('.day-toggle-cb[data-day="1"]');
    const mondayOpen = mondayToggle ? mondayToggle.checked : true;
    
    for (let day = 2; day <= 5; day++) {
        const toggle = document.querySelector('.day-toggle-cb[data-day="' + day + '"]');
        if (toggle) {
            toggle.checked = mondayOpen;
            toggleDayStatus(day, mondayOpen);
        }
        if (mondayOpen) {
            const container = document.getElementById('business-day-' + day);
            container.innerHTML = '';
            mondayRows.forEach((mRow, idx) => {
                const start = mRow.querySelector('input[name*="[start]"]').value;
                const end = mRow.querySelector('input[name*="[end]"]').value;
                const bStart = mRow.querySelector('input[name*="[break_start]"]').value;
                const bEnd = mRow.querySelector('input[name*="[break_end]"]').value;
                
                const newRow = document.createElement('div');
                newRow.className = 'schedule-row-item';
                newRow.setAttribute('data-row-index', idx);
                newRow.innerHTML = `
                    <div class="schedule-col-group">
                        <label class="schedule-col-label"><i class="fa-regular fa-clock"></i> Mesai Saatleri</label>
                        <div class="schedule-inputs-inline">
                            <input type="time" name="weekly[${day}][${idx}][start]" value="${start}" title="Açılış Saati">
                            <span class="schedule-separator">—</span>
                            <input type="time" name="weekly[${day}][${idx}][end]" value="${end}" title="Kapanış Saati">
                        </div>
                    </div>
                    <div class="schedule-col-group">
                        <label class="schedule-col-label break"><i class="fa-solid fa-mug-hot"></i> Mola / Öğle Arası (Opsiyonel)</label>
                        <div class="schedule-inputs-inline">
                            <input type="time" name="weekly[${day}][${idx}][break_start]" value="${bStart}" title="Mola Başlangıç">
                            <span class="schedule-separator">—</span>
                            <input type="time" name="weekly[${day}][${idx}][break_end]" value="${bEnd}" title="Mola Bitiş">
                        </div>
                    </div>
                    <button type="button" class="btn-remove-schedule-row" onclick="removeScheduleRow(this, ${day})" title="Bu aralığı kaldır">
                        <i class="fa-regular fa-trash-can"></i>
                    </button>
                `;
                container.appendChild(newRow);
            });
        }
    }
}

function addEmployeePeriod(employeeId, day) {
    const container = document.getElementById('employee-' + employeeId + '-day-' + day);
    const index = container.querySelectorAll('div').length;
    const row = document.createElement('div');
    row.style.cssText = 'display:flex;align-items:center;gap:6px;margin-bottom:6px;';
    row.innerHTML = `
        <input type="time" name="employee_weekly[${day}][${index}][start]" value="09:00" style="flex:1;height:36px;border:1.5px solid #CBD5E1;border-radius:7px;padding:0 8px;font-size:13px;text-align:center;">
        <span style="color:#94A3B8;font-weight:700;">—</span>
        <input type="time" name="employee_weekly[${day}][${index}][end]" value="18:00" style="flex:1;height:36px;border:1.5px solid #CBD5E1;border-radius:7px;padding:0 8px;font-size:13px;text-align:center;">
    `;
    container.appendChild(row);
}
</script>
<?php include __DIR__ . '/_footer.php'; ?>
