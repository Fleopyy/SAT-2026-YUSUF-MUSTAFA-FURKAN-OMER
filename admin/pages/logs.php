<?php
   
                                                     
   

require_once __DIR__ . '/../includes/auth.php';

$admin = requirePermission('audit_logs.view');
$currentPage = 'logs';
$pageTitle = 'İşlem Kayıtları';
$pageSubtitle = 'Panelde yöneticiler tarafından gerçekleştirilen tüm kritik eylemlerin güvenli denetim günlüğü';

$actionFilter = trim($_GET['action_type'] ?? '');
$search = trim($_GET['q'] ?? '');

$where = [];
$params = [];

if (!empty($actionFilter)) {
    $where[] = "l.islem_turu = ?";
    $params[] = $actionFilter;
}

if (!empty($search)) {
    $where[] = "(l.detay LIKE ? OR l.ip_adresi LIKE ? OR y.ad_soyad LIKE ?)";
    $like = "%{$search}%";
    $params = array_merge($params, [$like, $like, $like]);
}

$whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
$sql = "SELECT l.*, y.ad_soyad AS yonetici_adi, y.rol AS yonetici_rol
        FROM islem_gunlukleri l
        LEFT JOIN yoneticiler y ON l.yonetici_id = y.id
        {$whereSql}
        ORDER BY l.olusturulma_tarihi DESC
        LIMIT 100";

$logs = [];
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

                                        
$actionTypes = [];
try {
    $actionTypes = $pdo->query("SELECT DISTINCT islem_turu FROM islem_gunlukleri ORDER BY islem_turu ASC")->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {}

include __DIR__ . '/../includes/header.php';
?>

                  
<div class="admin-panel" style="margin-bottom:20px;">
    <div style="padding:16px 20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
        <form method="GET" action="" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
            <div>
                <select name="action_type" class="form-control" style="height:38px;min-width:180px;" onchange="this.form.submit()">
                    <option value="">Tüm İşlem Türleri</option>
                    <?php foreach ($actionTypes as $type): ?>
                        <option value="<?= e($type) ?>" <?= $actionFilter === $type ? 'selected' : '' ?>><?= e($type) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="position:relative;">
                <input type="text" name="q" value="<?= e($search) ?>" placeholder="Detay, IP, yönetici ara..." class="form-control" style="padding-left:36px;width:240px;height:38px;">
                <i class="fa-solid fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94A3B8;font-size:13px;"></i>
            </div>

            <button type="submit" class="btn btn-secondary btn-sm" style="height:38px;">Filtrele</button>
            <?php if (!empty($actionFilter) || !empty($search)): ?>
                <a href="/admin/pages/logs.php" class="btn btn-secondary btn-sm" style="height:38px;color:#DC2626;" title="Filtreleri Temizle">
                    <i class="fa-solid fa-xmark"></i>
                </a>
            <?php endif; ?>
        </form>

        <div style="font-size:13px;color:var(--admin-text-muted);">
            Son <strong><?= count($logs) ?></strong> işlem kaydı listeleniyor.
        </div>
    </div>
</div>

                         
<div class="admin-panel">
    <div class="panel-header">
        <h2>
            <i class="fa-solid fa-clock-rotate-left"></i>
            <span>Sistem Denetim Günlüğü</span>
        </h2>
    </div>
    <div class="panel-body">
        <?php if (empty($logs)): ?>
            <div style="padding:60px 20px;text-align:center;color:var(--admin-text-muted);">
                <i class="fa-solid fa-list" style="font-size:40px;color:#CBD5E1;margin-bottom:14px;display:block;"></i>
                <h4 style="color:var(--admin-text);margin-bottom:6px;">Kayıt Bulunamadı</h4>
                <p style="font-size:13px;">Seçilen kriterlere uygun denetim kaydı bulunmuyor.</p>
            </div>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Tarih & Saat</th>
                            <th>Yönetici</th>
                            <th>İşlem Türü</th>
                            <th>Hedef</th>
                            <th>Detay</th>
                            <th>IP Adresi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td style="white-space:nowrap;font-size:13px;color:var(--admin-text-muted);">
                                    <?= date('d.m.Y H:i:s', strtotime($log['olusturulma_tarihi'])) ?>
                                </td>
                                <td>
                                    <?php if (!empty($log['yonetici_adi'])): ?>
                                        <strong><?= e($log['yonetici_adi']) ?></strong>
                                        <small style="display:block;color:var(--admin-text-muted);font-size:11px;"><?= $log['yonetici_rol'] === 'admin' ? 'Süper Admin' : 'Yönetici' ?></small>
                                    <?php else: ?>
                                        <span style="color:var(--admin-text-muted);">Sistem</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <code style="background:#F1F5F9;color:#334155;padding:3px 8px;border-radius:6px;font-size:12px;font-weight:600;">
                                        <?= e($log['islem_turu']) ?>
                                    </code>
                                </td>
                                <td>
                                    <?php if (!empty($log['hedef_turu'])): ?>
                                        <span style="font-size:12px;color:var(--admin-text-muted);">
                                            <?= e($log['hedef_turu']) ?> #<?= (int)$log['hedef_id'] ?>
                                        </span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td style="font-size:13px;">
                                    <?= e($log['detay'] ?? '-') ?>
                                </td>
                                <td>
                                    <code style="font-size:11px;color:#64748B;"><?= e($log['ip_adresi'] ?? '-') ?></code>
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
