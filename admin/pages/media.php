<?php
   
                                    
   

require_once __DIR__ . '/../includes/auth.php';

$admin = requirePermission('media.manage');
$currentPage = 'media';
$pageTitle = 'Medya Yönetimi';
$pageSubtitle = 'İşletmeler tarafından yüklenen fotoğrafları inceleyin ve denetleyin';

$search = trim($_GET['q'] ?? '');

$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "i.isletme_adi LIKE ?";
    $params[] = "%{$search}%";
}

$whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
$sql = "SELECT f.*, i.isletme_adi, i.sehir, i.ilce
        FROM isletme_fotograflari f
        LEFT JOIN isletmeler i ON f.isletme_id = i.id
        {$whereSql}
        ORDER BY f.olusturulma_tarihi DESC";

$photos = [];
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$csrfToken = generateCsrfToken();
include __DIR__ . '/../includes/header.php';
?>

                          
<div class="admin-panel" style="margin-bottom:20px;">
    <div style="padding:16px 20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
        <div style="font-size:14px;color:var(--admin-text-muted);">
            Toplam <strong><?= count($photos) ?></strong> işletme fotoğrafı bulunuyor.
        </div>
        <form method="GET" action="" style="display:flex;gap:8px;">
            <div style="position:relative;">
                <input type="text" name="q" value="<?= e($search) ?>" placeholder="İşletme adı ara..." class="form-control" style="padding-left:36px;width:220px;height:38px;">
                <i class="fa-solid fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94A3B8;font-size:13px;"></i>
            </div>
            <button type="submit" class="btn btn-secondary btn-sm" style="height:38px;">Ara</button>
        </form>
    </div>
</div>

                                
<div class="admin-panel">
    <div class="panel-header">
        <h2>
            <i class="fa-solid fa-images"></i>
            <span>İşletme Fotoğrafları</span>
        </h2>
    </div>
    <div class="panel-body padded">
        <?php if (empty($photos)): ?>
            <div style="padding:60px 20px;text-align:center;color:var(--admin-text-muted);">
                <i class="fa-regular fa-image" style="font-size:48px;color:#CBD5E1;margin-bottom:14px;display:block;"></i>
                <h4 style="color:var(--admin-text);margin-bottom:6px;">Fotoğraf Bulunamadı</h4>
                <p style="font-size:13px;">İşletmeler tarafından henüz galeri fotoğrafı yüklenmemiş.</p>
            </div>
        <?php else: ?>
            <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(260px, 1fr));gap:20px;">
                <?php foreach ($photos as $photo): ?>
                    <div style="border:1px solid var(--admin-border);border-radius:12px;overflow:hidden;background:#fff;transition:var(--transition);display:flex;flex-direction:column;">
                        <div style="height:180px;background:#0F172A;overflow:hidden;position:relative;">
                            <img src="<?= e($photo['fotograf_yolu']) ?>" alt="<?= e($photo['isletme_adi']) ?>" 
                                 style="width:100%;height:100%;object-fit:cover;"
                                 onerror="this.src='/uploads/placeholder.jpg';">
                        </div>
                        <div style="padding:14px;flex:1;display:flex;flex-direction:column;justify-content:space-between;">
                            <div>
                                <strong style="display:block;font-size:14px;color:var(--admin-text);margin-bottom:4px;"><?= e($photo['isletme_adi']) ?></strong>
                                <small style="color:var(--admin-text-muted);font-size:11px;"><?= e($photo['sehir']) ?> / <?= e($photo['ilce']) ?></small>
                            </div>
                            <div style="display:flex;align-items:center;justify-content:space-between;margin-top:14px;padding-top:10px;border-top:1px solid #F1F5F9;">
                                <small style="font-size:11px;color:var(--admin-text-muted);"><?= date('d.m.Y', strtotime($photo['olusturulma_tarihi'])) ?></small>
                                <form method="POST" action="/admin/actions/media_action.php" onsubmit="return confirm('Bu fotoğrafı silmek istediğinize emin misiniz?');">
                                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="photo_id" value="<?= $photo['id'] ?>">
                                    <button type="submit" class="btn btn-secondary btn-sm" style="color:var(--admin-danger);border-color:var(--admin-danger);" title="Fotoğrafı Kaldır">
                                        <i class="fa-solid fa-trash-can"></i> Sil
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
