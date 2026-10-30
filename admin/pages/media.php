<?php
   
                                    
   

require_once __DIR__ . '/../includes/auth.php';

$admin = requirePermission('business_photos.view');
$currentPage = 'media';
$pageTitle = 'Medya Yönetimi';
$pageSubtitle = 'İşletmeler tarafından yüklenen fotoğrafları inceleyin ve denetleyin';

$search = trim($_GET['q'] ?? '');

$where = [];
$params = [];
$where[] = currentBusinessScopeSql('f.isletme_id', $params);

if (!empty($search)) {
    $where[] = "i.isletme_adi LIKE ?";
    $params[] = "%{$search}%";
}

$whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
$sql = "SELECT f.*, i.isletme_adi, i.sehir, i.ilce
        FROM isletme_fotograflari f
        LEFT JOIN isletmeler i ON f.isletme_id = i.id
        {$whereSql}
        ORDER BY i.isletme_adi ASC, f.olusturulma_tarihi DESC";

$photos = [];
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

/* ── Fotoğrafları işletme bazında grupla ── */
$grouped = [];
foreach ($photos as $photo) {
    $bizId = (int)$photo['isletme_id'];
    if (!isset($grouped[$bizId])) {
        $grouped[$bizId] = [
            'isletme_adi' => $photo['isletme_adi'] ?? 'Bilinmeyen İşletme',
            'sehir'       => $photo['sehir'] ?? '',
            'ilce'        => $photo['ilce'] ?? '',
            'photos'      => [],
        ];
    }
    $grouped[$bizId]['photos'][] = $photo;
}

$totalPhotos  = count($photos);
$totalBizCount = count($grouped);

$csrfToken = generateCsrfToken();
include __DIR__ . '/../includes/header.php';
?>

<!-- ── Üst Bilgi & Arama Barı ── -->
<div class="admin-panel" style="margin-bottom:20px;">
    <div style="padding:16px 20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
        <div style="font-size:14px;color:var(--admin-text-muted);">
            <strong><?= $totalBizCount ?></strong> işletmeye ait toplam <strong><?= $totalPhotos ?></strong> fotoğraf bulunuyor.
        </div>
        <form method="GET" action="" style="display:flex;gap:8px;align-items:center;">
            <div style="position:relative;">
                <input type="text" name="q" value="<?= e($search) ?>" placeholder="İşletme adı ara..." class="form-control" style="padding-left:36px;width:230px;height:38px;">
                <i class="fa-solid fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94A3B8;font-size:13px;"></i>
            </div>
            <button type="submit" class="btn btn-primary btn-sm" style="height:38px;">
                <i class="fa-solid fa-magnifying-glass"></i> Ara
            </button>
            <?php if (!empty($search)): ?>
                <a href="/admin/pages/media.php" class="btn btn-secondary btn-sm" style="height:38px;color:#DC2626;" title="Aramayı Temizle">
                    <i class="fa-solid fa-xmark"></i>
                </a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- ── İşletme Fotoğrafları (Accordion) ── -->
<div class="admin-panel">
    <div class="panel-header">
        <h2>
            <i class="fa-solid fa-images"></i>
            <span>İşletme Fotoğrafları</span>
        </h2>
    </div>
    <div class="panel-body">
        <?php if (empty($grouped)): ?>
            <div style="padding:60px 20px;text-align:center;color:var(--admin-text-muted);">
                <i class="fa-regular fa-image" style="font-size:48px;color:#CBD5E1;margin-bottom:14px;display:block;"></i>
                <h4 style="color:var(--admin-text);margin-bottom:6px;">Fotoğraf Bulunamadı</h4>
                <p style="font-size:13px;">İşletmeler tarafından henüz galeri fotoğrafı yüklenmemiş.</p>
            </div>
        <?php else: ?>
            <?php foreach ($grouped as $bizId => $biz): ?>
                <!-- İşletme Accordion Satırı -->
                <div class="media-accordion-item" style="border-bottom:1px solid #F1F5F9;">
                    <!-- Başlık Satırı (her zaman görünür) -->
                    <button type="button" class="media-accordion-toggle" onclick="toggleMediaAccordion(this)" style="
                        width:100%;
                        display:flex;
                        align-items:center;
                        justify-content:space-between;
                        padding:16px 24px;
                        background:transparent;
                        border:none;
                        cursor:pointer;
                        transition:background 0.2s ease;
                        font-family:inherit;
                        text-align:left;
                    ">
                        <div style="display:flex;align-items:center;gap:14px;flex:1;min-width:0;">
                            <!-- İşletme İkonu -->
                            <div style="
                                width:42px;height:42px;min-width:42px;
                                border-radius:10px;
                                background:linear-gradient(135deg, rgba(194,136,64,0.12), rgba(194,136,64,0.06));
                                border:1px solid rgba(194,136,64,0.2);
                                display:flex;align-items:center;justify-content:center;
                                color:var(--admin-accent);font-size:16px;
                            ">
                                <i class="fa-solid fa-store"></i>
                            </div>
                            <!-- İşletme Bilgileri -->
                            <div style="min-width:0;">
                                <div style="font-size:15px;font-weight:700;color:var(--admin-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                    <?= e($biz['isletme_adi']) ?>
                                </div>
                                <div style="font-size:12px;color:var(--admin-text-muted);margin-top:2px;display:flex;align-items:center;gap:8px;">
                                    <?php if (!empty($biz['sehir'])): ?>
                                        <span><i class="fa-solid fa-location-dot" style="margin-right:3px;font-size:10px;color:var(--admin-accent);"></i><?= e($biz['sehir']) ?> / <?= e($biz['ilce']) ?></span>
                                    <?php endif; ?>
                                    <span style="display:inline-flex;align-items:center;gap:4px;background:#EFF6FF;color:#2563EB;padding:2px 8px;border-radius:10px;font-weight:600;font-size:11px;">
                                        <i class="fa-regular fa-image" style="font-size:10px;"></i>
                                        <?= count($biz['photos']) ?> Fotoğraf
                                    </span>
                                </div>
                            </div>
                        </div>
                        <!-- Aç/Kapat Butonu -->
                        <div class="media-accordion-arrow" style="
                            width:34px;height:34px;min-width:34px;
                            border-radius:8px;
                            background:#F1F5F9;
                            display:flex;align-items:center;justify-content:center;
                            color:#64748B;font-size:14px;
                            transition:all 0.3s ease;
                            margin-left:12px;
                        ">
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                    </button>

                    <!-- Fotoğraf Galerisi (varsayılan kapalı) -->
                    <div class="media-accordion-body" style="display:none;padding:0 24px 20px 24px;">
                        <div style="
                            display:grid;
                            grid-template-columns:repeat(auto-fill, minmax(200px, 1fr));
                            gap:14px;
                            padding:16px;
                            background:#FAFBFC;
                            border-radius:12px;
                            border:1px solid #E2E8F0;
                        ">
                            <?php foreach ($biz['photos'] as $photo): ?>
                                <div style="
                                    border-radius:10px;
                                    overflow:hidden;
                                    background:#fff;
                                    border:1px solid #E2E8F0;
                                    transition:all 0.2s ease;
                                    position:relative;
                                " onmouseover="this.style.boxShadow='0 4px 14px rgba(15,23,42,0.1)';this.style.transform='translateY(-2px)'"
                                   onmouseout="this.style.boxShadow='none';this.style.transform='none'">
                                    <!-- Fotoğraf -->
                                    <div style="height:150px;background:#0F172A;overflow:hidden;">
                                        <img src="/<?= e(ltrim($photo['fotograf_yolu'], '/')) ?>" 
                                             alt="<?= e($biz['isletme_adi']) ?>" 
                                             style="width:100%;height:100%;object-fit:cover;display:block;"
                                             onerror="this.style.display='none';this.parentElement.innerHTML='<div style=\'display:flex;align-items:center;justify-content:center;height:100%;color:#475569;font-size:24px;\'><i class=\'fa-regular fa-image\'></i></div>';">
                                    </div>
                                    <!-- Alt Bilgi -->
                                    <div style="padding:10px 12px;display:flex;align-items:center;justify-content:space-between;">
                                        <small style="font-size:11px;color:var(--admin-text-muted);">
                                            <i class="fa-regular fa-calendar" style="margin-right:3px;"></i>
                                            <?= date('d.m.Y', strtotime($photo['olusturulma_tarihi'])) ?>
                                        </small>
                                        <?php if (canCurrentUser('business_photos.delete')): ?>
                                        <form method="POST" action="/admin/actions/media_action.php" style="margin:0;" onsubmit="return confirm('Bu fotoğrafı silmek istediğinize emin misiniz?');">
                                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="photo_id" value="<?= $photo['id'] ?>">
                                            <button type="submit" class="action-btn action-btn-danger" title="Fotoğrafı Sil" style="width:28px;height:28px;min-width:28px;font-size:12px;">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Accordion Toggle Script -->
<script>
function toggleMediaAccordion(btn) {
    const item = btn.closest('.media-accordion-item');
    const body = item.querySelector('.media-accordion-body');
    const arrow = btn.querySelector('.media-accordion-arrow');
    const isOpen = body.style.display !== 'none';

    if (isOpen) {
        body.style.display = 'none';
        arrow.style.transform = 'rotate(0deg)';
        arrow.style.background = '#F1F5F9';
        arrow.style.color = '#64748B';
        btn.style.background = 'transparent';
    } else {
        body.style.display = 'block';
        arrow.style.transform = 'rotate(180deg)';
        arrow.style.background = 'rgba(194,136,64,0.12)';
        arrow.style.color = 'var(--admin-accent)';
        btn.style.background = '#FAFBFC';
    }
}
</script>

<!-- Accordion Hover Styles -->
<style>
.media-accordion-toggle:hover {
    background: #FAFBFC !important;
}
.media-accordion-toggle:hover .media-accordion-arrow {
    background: rgba(194,136,64,0.12) !important;
    color: var(--admin-accent) !important;
}
.media-accordion-item:last-child {
    border-bottom: none !important;
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
