<?php
   
                                         
   

require_once __DIR__ . '/../includes/auth.php';

$admin = requirePermission('appointments.view');
$currentPage = 'appointments';
$pageTitle = 'Randevular';
$pageSubtitle = 'Sistemdeki tüm berber randevularını filtreleyin ve durumlarını yönetin';

$search = trim($_GET['q'] ?? '');
$statusFilter = $_GET['status'] ?? 'all';
$dateFilter = $_GET['date'] ?? 'all';

                          
$where = [];
$params = [];

if ($statusFilter !== 'all' && in_array($statusFilter, ['pending', 'confirmed', 'completed', 'cancelled', 'rejected'])) {
    $where[] = "r.durum = ?";
    $params[] = $statusFilter;
}

if ($dateFilter === 'today') {
    $where[] = "DATE(r.randevu_zamani) = CURDATE()";
} elseif ($dateFilter === 'tomorrow') {
    $where[] = "DATE(r.randevu_zamani) = DATE_ADD(CURDATE(), INTERVAL 1 DAY)";
} elseif ($dateFilter === 'future') {
    $where[] = "r.randevu_zamani >= NOW()";
}

if (!empty($search)) {
    $where[] = "(k.ad_soyad LIKE ? OR k.telefon LIKE ? OR i.isletme_adi LIKE ?)";
    $like = "%{$search}%";
    $params = array_merge($params, [$like, $like, $like]);
}

                                      
if (($admin['rol'] ?? '') === 'manager') {
    $allowedScope = getManagerAllowedBusinessIds($admin['id'] ?? 0);
    if ($allowedScope !== null) {
        if (empty($allowedScope)) {
            $where[] = "1=0";
        } else {
            $inPlaceholders = implode(',', array_fill(0, count($allowedScope), '?'));
            $where[] = "r.isletme_id IN ($inPlaceholders)";
            $params = array_merge($params, $allowedScope);
        }
    }
}

$whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
$sql = "SELECT r.*, 
        k.ad_soyad AS musteri_adi, k.telefon AS musteri_telefon, k.eposta AS musteri_eposta,
        i.isletme_adi, i.telefon AS isletme_telefon, i.sehir, i.ilce,
        h.hizmet_adi, h.fiyat, h.sure_dakika,
        c.ad_soyad AS calisan_adi
        FROM randevular r
        LEFT JOIN kullanicilar k ON r.kullanici_id = k.id
        LEFT JOIN isletmeler i ON r.isletme_id = i.id
        LEFT JOIN hizmetler h ON r.hizmet_id = h.id
        LEFT JOIN calisanlar c ON r.calisan_id = c.id
        {$whereSql}
        ORDER BY r.randevu_zamani DESC";

$appointments = [];
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

            
$counts = [
    'all' => 0,
    'today' => 0,
    'pending' => 0,
    'confirmed' => 0,
    'completed' => 0
];
try {
    $counts['all'] = (int)$pdo->query("SELECT COUNT(*) FROM randevular")->fetchColumn();
    $counts['today'] = (int)$pdo->query("SELECT COUNT(*) FROM randevular WHERE DATE(randevu_zamani) = CURDATE()")->fetchColumn();
    $counts['pending'] = (int)$pdo->query("SELECT COUNT(*) FROM randevular WHERE durum = 'pending'")->fetchColumn();
    $counts['confirmed'] = (int)$pdo->query("SELECT COUNT(*) FROM randevular WHERE durum = 'confirmed'")->fetchColumn();
    $counts['completed'] = (int)$pdo->query("SELECT COUNT(*) FROM randevular WHERE durum = 'completed'")->fetchColumn();
} catch (Exception $e) {}

$csrfToken = generateCsrfToken();
include __DIR__ . '/../includes/header.php';
?>

                           
<div class="admin-panel" style="margin-bottom:20px;">
    <div style="padding:16px 20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
        <div style="display:flex;align-items:center;gap:8px;overflow-x:auto;">
            <a href="?status=all&date=<?= e($dateFilter) ?>" class="btn <?= $statusFilter === 'all' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">Tümü (<?= $counts['all'] ?>)</a>
            <a href="?status=pending&date=<?= e($dateFilter) ?>" class="btn <?= $statusFilter === 'pending' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                <i class="fa-solid fa-clock"></i> Bekleyen (<?= $counts['pending'] ?>)
            </a>
            <a href="?status=confirmed&date=<?= e($dateFilter) ?>" class="btn <?= $statusFilter === 'confirmed' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                <i class="fa-solid fa-check"></i> Onaylı (<?= $counts['confirmed'] ?>)
            </a>
            <a href="?status=completed&date=<?= e($dateFilter) ?>" class="btn <?= $statusFilter === 'completed' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                <i class="fa-solid fa-check-double"></i> Tamamlanan (<?= $counts['completed'] ?>)
            </a>
            <a href="?date=today&status=<?= e($statusFilter) ?>" class="btn <?= $dateFilter === 'today' ? 'btn-primary' : 'btn-secondary' ?> btn-sm" style="background:<?= $dateFilter === 'today' ? 'var(--admin-accent)' : '' ?>;">
                <i class="fa-solid fa-calendar-day"></i> Bugün (<?= $counts['today'] ?>)
            </a>
        </div>

        <form method="GET" action="" style="display:flex;gap:8px;">
            <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
            <input type="hidden" name="date" value="<?= e($dateFilter) ?>">
            <div style="position:relative;">
                <input type="text" name="q" value="<?= e($search) ?>" placeholder="Müşteri, telefon, işletme..." class="form-control" style="padding-left:36px;width:240px;height:38px;">
                <i class="fa-solid fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94A3B8;font-size:13px;"></i>
            </div>
            <button type="submit" class="btn btn-secondary btn-sm" style="height:38px;">Ara</button>
            <?php if (!empty($search)): ?>
                <a href="?status=<?= e($statusFilter) ?>&date=<?= e($dateFilter) ?>" class="btn btn-secondary btn-sm" style="height:38px;color:#DC2626;" title="Aramayı Temizle">
                    <i class="fa-solid fa-xmark"></i>
                </a>
            <?php endif; ?>
        </form>
    </div>
</div>

                           
<div class="admin-panel">
    <div class="panel-header">
        <h2>
            <i class="fa-solid fa-calendar-check"></i>
            <span>Randevu Listesi</span>
            <small style="color:var(--admin-text-muted);font-weight:400;font-size:13px;">(<?= count($appointments) ?> Randevu)</small>
        </h2>
    </div>
    <div class="panel-body">
        <?php if (empty($appointments)): ?>
            <div style="padding:60px 20px;text-align:center;color:var(--admin-text-muted);">
                <i class="fa-solid fa-calendar-xmark" style="font-size:40px;color:#CBD5E1;margin-bottom:14px;display:block;"></i>
                <h4 style="color:var(--admin-text);margin-bottom:6px;">Randevu Bulunamadı</h4>
                <p style="font-size:13px;">Seçilen filtrelere uygun randevu kaydı bulunmuyor.</p>
            </div>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Müşteri</th>
                            <th>İşletme</th>
                            <th>Hizmet / Personel</th>
                            <th>Randevu Zamanı</th>
                            <th>Durum</th>
                            <th style="text-align:right;">Durum Değiştir</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $apt): ?>
                            <tr>
                                <td>
                                    <div style="font-weight:700;color:var(--admin-text);"><?= e($apt['musteri_adi'] ?? 'Kayıtsız Müşteri') ?></div>
                                    <small style="color:var(--admin-text-muted);">
                                        <a href="tel:<?= e($apt['musteri_telefon'] ?? '') ?>" style="color:var(--admin-accent);font-weight:600;">
                                            <?= e($apt['musteri_telefon'] ?? '') ?>
                                        </a>
                                    </small>
                                </td>
                                <td>
                                    <div style="font-weight:600;"><?= e($apt['isletme_adi'] ?? '-') ?></div>
                                    <small style="color:var(--admin-text-muted);"><?= e($apt['sehir'] ?? '') ?> / <?= e($apt['ilce'] ?? '') ?></small>
                                </td>
                                <td>
                                    <div><strong><?= e($apt['hizmet_adi'] ?? 'Genel Randevu') ?></strong></div>
                                    <small style="color:var(--admin-text-muted);">
                                        <?= $apt['fiyat'] ? number_format($apt['fiyat'], 2) . ' ₺ · ' : '' ?>
                                        <?= e($apt['calisan_adi'] ?? 'Herhangi Bir Personel') ?>
                                    </small>
                                </td>
                                <td>
                                    <div style="font-weight:700;color:var(--admin-text);"><?= date('d.m.Y', strtotime($apt['randevu_zamani'])) ?></div>
                                    <small style="color:var(--admin-accent);font-weight:700;"><?= date('H:i', strtotime($apt['randevu_zamani'])) ?></small>
                                </td>
                                <td>
                                    <?php
                                    $st = $apt['durum'];
                                    $badgeClass = match($st) {
                                        'confirmed' => 'active',
                                        'completed' => 'active',
                                        'cancelled' => 'suspended',
                                        'rejected' => 'rejected',
                                        default => 'pending'
                                    };
                                    $stLabel = match($st) {
                                        'confirmed' => 'Onaylandı',
                                        'completed' => 'Tamamlandı',
                                        'cancelled' => 'İptal',
                                        'rejected' => 'Reddedildi',
                                        default => 'Beklemede'
                                    };
                                    ?>
                                    <span class="status-badge <?= $badgeClass ?>"><?= $stLabel ?></span>
                                </td>
                                <td style="text-align:right;">
                                    <?php 
                                    $canEditApt = canCurrentUser('appointments.edit');
                                    $canCancelApt = canCurrentUser('appointments.cancel');
                                    ?>
                                    <?php if ($canEditApt || $canCancelApt): ?>
                                        <form method="POST" action="/admin/actions/appointment_action.php" style="display:inline-flex;gap:6px;">
                                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                            <input type="hidden" name="appointment_id" value="<?= $apt['id'] ?>">

                                            <?php if ($canEditApt && $apt['durum'] !== 'confirmed'): ?>
                                                <button type="submit" name="status" value="confirmed" class="btn btn-secondary btn-sm" title="Onayla" style="color:var(--admin-success);">
                                                    <i class="fa-solid fa-check"></i>
                                                </button>
                                            <?php endif; ?>

                                            <?php if ($canEditApt && $apt['durum'] !== 'completed'): ?>
                                                <button type="submit" name="status" value="completed" class="btn btn-secondary btn-sm" title="Tamamlandı Olarak İşaretle" style="color:var(--admin-info);">
                                                    <i class="fa-solid fa-check-double"></i>
                                                </button>
                                            <?php endif; ?>

                                            <?php if ($canCancelApt && $apt['durum'] !== 'cancelled'): ?>
                                                <button type="submit" name="status" value="cancelled" class="btn btn-secondary btn-sm" title="İptal Et" style="color:var(--admin-danger);" onclick="return confirm('Randevuyu iptal etmek istediğinize emin misiniz?');">
                                                    <i class="fa-solid fa-ban"></i>
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                    <?php else: ?>
                                        <span style="color:var(--admin-text-muted);font-size:12px;">Sadece Görüntüleme</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
