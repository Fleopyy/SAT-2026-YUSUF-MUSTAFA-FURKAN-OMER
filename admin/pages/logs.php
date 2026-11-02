<?php
   
                                                     
   

require_once __DIR__ . '/../includes/auth.php';

$admin = requirePermission('audit_logs.view');
$currentPage = 'logs';
$pageTitle = 'İşlem Kayıtları';
$pageSubtitle = 'Panelde yöneticiler tarafından gerçekleştirilen tüm kritik eylemlerin güvenli denetim günlüğü';

$actionFilter = trim($_GET['action_type'] ?? '');
$search = trim($_GET['q'] ?? '');

// 30 günden eski kayıtları otomatik sil
try {
    $pdo->exec("DELETE FROM islem_gunlukleri WHERE olusturulma_tarihi < DATE_SUB(NOW(), INTERVAL 30 DAY)");
} catch (Exception $e) {}

$page = max(1, (int)($_GET['sayfa'] ?? 1));
$perPage = 50;
$offset = ($page - 1) * $perPage;

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

// Toplam kayıt sayısı
$totalCount = 0;
try {
    $countSql = "SELECT COUNT(*) FROM islem_gunlukleri l LEFT JOIN yoneticiler y ON l.yonetici_id = y.id {$whereSql}";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalCount = (int)$countStmt->fetchColumn();
} catch (Exception $e) {}

$totalPages = max(1, (int)ceil($totalCount / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

$sql = "SELECT l.*, y.ad_soyad AS yonetici_adi, y.rol AS yonetici_rol
        FROM islem_gunlukleri l
        LEFT JOIN yoneticiler y ON l.yonetici_id = y.id
        {$whereSql}
        ORDER BY l.olusturulma_tarihi DESC
        LIMIT {$perPage} OFFSET {$offset}";

$logs = [];
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

function pageUrl($p, $act, $q) {
    $params = ['sayfa' => $p];
    if (!empty($act)) $params['action_type'] = $act;
    if (!empty($q)) $params['q'] = $q;
    return '?' . http_build_query($params);
}

                                        
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
            Toplam <strong><?= $totalCount ?></strong> kayıttan <strong><?= count($logs) ?></strong> tanesi listeleniyor (Sayfa <?= $page ?> / <?= $totalPages ?>).
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

            <!-- Sayfalama (Pagination) -->
            <?php if ($totalPages > 1): ?>
            <div style="padding:16px 20px;border-top:1px solid #E2E8F0;display:flex;align-items:center;justify-content:center;gap:6px;flex-wrap:wrap;">
                <!-- Önceki -->
                <?php if ($page > 1): ?>
                    <a href="<?= pageUrl($page - 1, $actionFilter, $search) ?>"
                       style="display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:8px;border:1px solid #E2E8F0;background:#fff;color:var(--admin-text);font-size:13px;font-weight:600;text-decoration:none;"
                       onmouseover="this.style.background='#F8FAFC'" onmouseout="this.style.background='#fff'">
                        <i class="fa-solid fa-chevron-left" style="font-size:11px;"></i>
                    </a>
                <?php else: ?>
                    <span style="display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:8px;border:1px solid #E2E8F0;background:#F8FAFC;color:#CBD5E1;cursor:not-allowed;">
                        <i class="fa-solid fa-chevron-left" style="font-size:11px;"></i>
                    </span>
                <?php endif; ?>

                <!-- Sayfa numaraları -->
                <?php
                $pagesToShow = [1];
                for ($i = max(2, $page - 2); $i <= min($totalPages - 1, $page + 2); $i++) {
                    $pagesToShow[] = $i;
                }
                if ($totalPages > 1) $pagesToShow[] = $totalPages;
                $pagesToShow = array_unique($pagesToShow);
                sort($pagesToShow);
                $prev = null;
                foreach ($pagesToShow as $pn):
                ?>
                    <?php if ($prev !== null && $pn - $prev > 1): ?>
                        <span style="color:#94A3B8;font-size:14px;padding:0 2px;">…</span>
                    <?php endif; ?>
                    <?php if ($pn === $page): ?>
                        <span style="display:inline-flex;align-items:center;justify-content:center;min-width:36px;height:36px;padding:0 8px;border-radius:8px;background:var(--admin-accent);color:#fff;font-size:13px;font-weight:700;">
                            <?= $pn ?>
                        </span>
                    <?php else: ?>
                        <a href="<?= pageUrl($pn, $actionFilter, $search) ?>"
                           style="display:inline-flex;align-items:center;justify-content:center;min-width:36px;height:36px;padding:0 8px;border-radius:8px;border:1px solid #E2E8F0;background:#fff;color:var(--admin-text);font-size:13px;font-weight:600;text-decoration:none;"
                           onmouseover="this.style.background='#F8FAFC'" onmouseout="this.style.background='#fff'">
                            <?= $pn ?>
                        </a>
                    <?php endif; ?>
                <?php $prev = $pn; endforeach; ?>

                <!-- Sonraki -->
                <?php if ($page < $totalPages): ?>
                    <a href="<?= pageUrl($page + 1, $actionFilter, $search) ?>"
                       style="display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:8px;border:1px solid #E2E8F0;background:#fff;color:var(--admin-text);font-size:13px;font-weight:600;text-decoration:none;"
                       onmouseover="this.style.background='#F8FAFC'" onmouseout="this.style.background='#fff'">
                        <i class="fa-solid fa-chevron-right" style="font-size:11px;"></i>
                    </a>
                <?php else: ?>
                    <span style="display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:8px;border:1px solid #E2E8F0;background:#F8FAFC;color:#CBD5E1;cursor:not-allowed;">
                        <i class="fa-solid fa-chevron-right" style="font-size:11px;"></i>
                    </span>
                <?php endif; ?>
            </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
