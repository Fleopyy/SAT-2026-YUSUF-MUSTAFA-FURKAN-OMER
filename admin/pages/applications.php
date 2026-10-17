<?php
   
                                                 
   

require_once __DIR__ . '/../includes/auth.php';

$admin = requirePermission('businesses.view');
$currentPage = 'applications';
$pageTitle = 'İşletme Başvuruları';
$pageSubtitle = 'Yeni berber & kuaför kayıtlarını inceleyin, onaylayın veya reddedin';

                                
$currentTab = $_GET['tab'] ?? 'pending';
$search = trim($_GET['q'] ?? '');
$detailId = (int)($_GET['detail'] ?? 0);

                         
$counts = [
    'all' => 0,
    'pending' => 0,
    'active' => 0,
    'rejected' => 0,
    'suspended' => 0
];

try {
    $counts['all'] = (int)$pdo->query("SELECT COUNT(*) FROM isletmeler")->fetchColumn();
    $counts['pending'] = (int)$pdo->query("SELECT COUNT(*) FROM isletmeler WHERE durum = 'pending'")->fetchColumn();
    $counts['active'] = (int)$pdo->query("SELECT COUNT(*) FROM isletmeler WHERE durum = 'active'")->fetchColumn();
    $counts['rejected'] = (int)$pdo->query("SELECT COUNT(*) FROM isletmeler WHERE durum = 'rejected'")->fetchColumn();
    $counts['suspended'] = (int)$pdo->query("SELECT COUNT(*) FROM isletmeler WHERE durum = 'suspended'")->fetchColumn();
} catch (Exception $e) {}

                   
$where = [];
$params = [];

if (in_array($currentTab, ['pending', 'active', 'rejected', 'suspended'])) {
    $where[] = "durum = ?";
    $params[] = $currentTab;
}

if (!empty($search)) {
    $where[] = "(isletme_adi LIKE ? OR isletme_sahibi LIKE ? OR telefon LIKE ? OR eposta LIKE ? OR sehir LIKE ? OR ilce LIKE ?)";
    $like = "%{$search}%";
    $params = array_merge($params, [$like, $like, $like, $like, $like, $like]);
}

$whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
$sql = "SELECT * FROM isletmeler {$whereSql} ORDER BY olusturulma_tarihi DESC";

$applications = [];
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

                            
$detailApp = null;
if ($detailId > 0) {
    try {
        $stmt = $pdo->prepare("SELECT i.*, y.ad_soyad AS onaylayan_ad FROM isletmeler i LEFT JOIN yoneticiler y ON i.onaylayan_admin_id = y.id WHERE i.id = ?");
        $stmt->execute([$detailId]);
        $detailApp = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}

$csrfToken = generateCsrfToken();
include __DIR__ . '/../includes/header.php';
?>

                                
<div class="admin-panel" style="margin-bottom:20px;">
    <div style="padding:16px 20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
        <div style="display:flex;align-items:center;gap:8px;overflow-x:auto;">
            <a href="?tab=pending<?= !empty($search) ? '&q=' . urlencode($search) : '' ?>" 
               class="btn <?= $currentTab === 'pending' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                <i class="fa-solid fa-clock"></i>
                Bekleyenler (<?= $counts['pending'] ?>)
            </a>
            <a href="?tab=active<?= !empty($search) ? '&q=' . urlencode($search) : '' ?>" 
               class="btn <?= $currentTab === 'active' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                <i class="fa-solid fa-check-circle"></i>
                Onaylananlar (<?= $counts['active'] ?>)
            </a>
            <a href="?tab=rejected<?= !empty($search) ? '&q=' . urlencode($search) : '' ?>" 
               class="btn <?= $currentTab === 'rejected' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                <i class="fa-solid fa-circle-xmark"></i>
                Reddedilenler (<?= $counts['rejected'] ?>)
            </a>
            <a href="?tab=suspended<?= !empty($search) ? '&q=' . urlencode($search) : '' ?>" 
               class="btn <?= $currentTab === 'suspended' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                <i class="fa-solid fa-ban"></i>
                Askıdakiler (<?= $counts['suspended'] ?>)
            </a>
            <a href="?tab=all<?= !empty($search) ? '&q=' . urlencode($search) : '' ?>" 
               class="btn <?= $currentTab === 'all' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                Tümü (<?= $counts['all'] ?>)
            </a>
        </div>

        <form method="GET" action="" style="display:flex;gap:8px;">
            <input type="hidden" name="tab" value="<?= e($currentTab) ?>">
            <div style="position:relative;">
                <input type="text" name="q" value="<?= e($search) ?>" placeholder="İşletme adı, yetkili, telefon..." 
                       class="form-control" style="padding-left:36px;width:240px;height:38px;">
                <i class="fa-solid fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94A3B8;font-size:13px;"></i>
            </div>
            <button type="submit" class="btn btn-secondary btn-sm" style="height:38px;">Ara</button>
            <?php if (!empty($search)): ?>
                <a href="?tab=<?= e($currentTab) ?>" class="btn btn-secondary btn-sm" style="height:38px;color:#DC2626;" title="Aramayı Temizle">
                    <i class="fa-solid fa-xmark"></i>
                </a>
            <?php endif; ?>
        </form>
    </div>
</div>

                                                      
<?php if ($detailApp): ?>
    <div class="admin-panel" style="border:2px solid var(--admin-accent);background:#FCFBF9;margin-bottom:24px;">
        <div class="panel-header" style="background:#FFFBF5;">
            <h2>
                <i class="fa-solid fa-circle-info"></i>
                <span>İşletme Başvuru Detayı: <strong><?= e($detailApp['isletme_adi']) ?></strong></span>
            </h2>
            <a href="?tab=<?= e($currentTab) ?><?= !empty($search) ? '&q=' . urlencode($search) : '' ?>" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-xmark"></i> Kapat
            </a>
        </div>
        <div class="panel-body padded">
            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:20px;margin-bottom:20px;">
                <div>
                    <label style="font-size:11px;font-weight:700;color:var(--admin-text-muted);text-transform:uppercase;">İşletme Adı</label>
                    <div style="font-size:16px;font-weight:700;color:var(--admin-text);"><?= e($detailApp['isletme_adi']) ?></div>
                </div>
                <div>
                    <label style="font-size:11px;font-weight:700;color:var(--admin-text-muted);text-transform:uppercase;">İşletme Yetkilisi</label>
                    <div style="font-size:15px;font-weight:600;"><?= e($detailApp['isletme_sahibi']) ?></div>
                </div>
                <div>
                    <label style="font-size:11px;font-weight:700;color:var(--admin-text-muted);text-transform:uppercase;">Telefon</label>
                    <div><a href="tel:<?= e($detailApp['telefon']) ?>" style="color:var(--admin-accent);font-weight:700;font-size:15px;"><?= e($detailApp['telefon']) ?></a></div>
                </div>
                <div>
                    <label style="font-size:11px;font-weight:700;color:var(--admin-text-muted);text-transform:uppercase;">E-posta</label>
                    <div><a href="mailto:<?= e($detailApp['eposta']) ?>" style="color:var(--admin-info);font-weight:600;font-size:14px;"><?= e($detailApp['eposta']) ?></a></div>
                </div>
                <div>
                    <label style="font-size:11px;font-weight:700;color:var(--admin-text-muted);text-transform:uppercase;">Şehir / İlçe</label>
                    <div style="font-size:14px;font-weight:600;"><?= e($detailApp['sehir']) ?> / <?= e($detailApp['ilce']) ?></div>
                </div>
                <div>
                    <label style="font-size:11px;font-weight:700;color:var(--admin-text-muted);text-transform:uppercase;">Başvuru Tarihi</label>
                    <div style="font-size:14px;"><?= date('d.m.Y H:i', strtotime($detailApp['olusturulma_tarihi'])) ?></div>
                </div>
                <div>
                    <label style="font-size:11px;font-weight:700;color:var(--admin-text-muted);text-transform:uppercase;">Mevcut Durum</label>
                    <div>
                        <?php
                        $st = $detailApp['durum'];
                        $badgeClass = match($st) {
                            'active' => 'active',
                            'rejected' => 'rejected',
                            'suspended' => 'suspended',
                            default => 'pending'
                        };
                        $stLabel = match($st) {
                            'active' => 'Onaylandı (Aktif)',
                            'rejected' => 'Reddedildi',
                            'suspended' => 'Askıya Alındı',
                            default => 'Onay Bekliyor'
                        };
                        ?>
                        <span class="status-badge <?= $badgeClass ?>"><?= $stLabel ?></span>
                    </div>
                </div>
            </div>

            <?php if (!empty($detailApp['tam_adres'])): ?>
                <div style="margin-bottom:16px;background:#fff;padding:14px;border-radius:8px;border:1px solid #E2E8F0;">
                    <label style="font-size:11px;font-weight:700;color:var(--admin-text-muted);text-transform:uppercase;display:block;margin-bottom:4px;">Açık Adres</label>
                    <p style="margin:0;font-size:14px;color:var(--admin-text);"><?= nl2br(e($detailApp['tam_adres'])) ?></p>
                </div>
            <?php endif; ?>

            <?php if (!empty($detailApp['aciklama'])): ?>
                <div style="margin-bottom:16px;background:#fff;padding:14px;border-radius:8px;border:1px solid #E2E8F0;">
                    <label style="font-size:11px;font-weight:700;color:var(--admin-text-muted);text-transform:uppercase;display:block;margin-bottom:4px;">İşletme Açıklaması / Notlar</label>
                    <p style="margin:0;font-size:14px;color:var(--admin-text);"><?= nl2br(e($detailApp['aciklama'])) ?></p>
                </div>
            <?php endif; ?>

            <?php if ($detailApp['durum'] === 'rejected' && !empty($detailApp['red_gerekce'])): ?>
                <div style="margin-bottom:16px;background:#FEF2F2;border:1px solid #FECACA;padding:14px;border-radius:8px;color:#991B1B;">
                    <strong>Red Gerekçesi:</strong> <?= e($detailApp['red_gerekce']) ?>
                    <div style="font-size:11px;margin-top:4px;">Tarih: <?= date('d.m.Y H:i', strtotime($detailApp['red_tarihi'])) ?></div>
                </div>
            <?php endif; ?>

                                       
            <div style="display:flex;gap:10px;margin-top:20px;padding-top:16px;border-top:1px solid #E2E8F0;flex-wrap:wrap;">
                <?php if ($detailApp['durum'] !== 'active' && canCurrentUser('businesses.approve')): ?>
                    <form method="POST" action="/admin/actions/application_action.php" onsubmit="return confirm('Bu işletmeyi onaylamak ve yayına almak istediğinize emin misiniz?');">
                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="business_id" value="<?= $detailApp['id'] ?>">
                        <button type="submit" class="btn btn-primary" style="background:var(--admin-success);border-color:var(--admin-success);">
                            <i class="fa-solid fa-check"></i> Başvuruyu Onayla ve Yayına Al
                        </button>
                    </form>
                <?php endif; ?>

                <?php if ($detailApp['durum'] !== 'rejected' && canCurrentUser('businesses.reject')): ?>
                    <button type="button" class="btn btn-secondary" style="color:var(--admin-danger);border-color:var(--admin-danger);" 
                            onclick="openRejectModal(<?= $detailApp['id'] ?>, '<?= addslashes(e($detailApp['isletme_adi'])) ?>')">
                        <i class="fa-solid fa-xmark"></i> Başvuruyu Reddet
                    </button>
                <?php endif; ?>

                <?php if ($detailApp['durum'] === 'active' && canCurrentUser('businesses.suspend')): ?>
                    <form method="POST" action="/admin/actions/application_action.php" onsubmit="return confirm('İşletmeyi askıya almak istediğinize emin misiniz? Müşteriler randevu alamayacak.');">
                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                        <input type="hidden" name="action" value="suspend">
                        <input type="hidden" name="business_id" value="<?= $detailApp['id'] ?>">
                        <button type="submit" class="btn btn-secondary" style="color:var(--admin-warning);border-color:var(--admin-warning);">
                            <i class="fa-solid fa-ban"></i> İşletmeyi Askıya Al
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

                            
<div class="admin-panel">
    <div class="panel-header">
        <h2>
            <i class="fa-solid fa-list-check"></i>
            <span>Başvuru Listesi</span>
            <small style="color:var(--admin-text-muted);font-weight:400;font-size:13px;">(<?= count($applications) ?> Kayıt Bulundu)</small>
        </h2>
    </div>
    <div class="panel-body">
        <?php if (empty($applications)): ?>
            <div style="padding:60px 20px;text-align:center;color:var(--admin-text-muted);">
                <i class="fa-solid fa-magnifying-glass" style="font-size:40px;color:#CBD5E1;margin-bottom:14px;display:block;"></i>
                <h4 style="color:var(--admin-text);margin-bottom:6px;">Kayıt Bulunamadı</h4>
                <p style="font-size:13px;max-width:400px;margin:0 auto;">Seçilen kriterlere uygun başvuru bulunmamaktadır.</p>
            </div>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>İşletme Adı</th>
                            <th>Yetkili</th>
                            <th>İletişim</th>
                            <th>Konum</th>
                            <th>Tarih</th>
                            <th>Durum</th>
                            <th style="text-align:right;">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($applications as $app): ?>
                            <tr>
                                <td>
                                    <div style="font-weight:700;color:var(--admin-text);"><?= e($app['isletme_adi']) ?></div>
                                    <small style="color:var(--admin-text-muted);"><?= e($app['eposta']) ?></small>
                                </td>
                                <td><?= e($app['isletme_sahibi']) ?></td>
                                <td>
                                    <a href="tel:<?= e($app['telefon']) ?>" style="font-weight:600;color:var(--admin-accent);">
                                        <?= e($app['telefon']) ?>
                                    </a>
                                </td>
                                <td><?= e($app['sehir']) ?> / <?= e($app['ilce']) ?></td>
                                <td><?= date('d.m.Y H:i', strtotime($app['olusturulma_tarihi'])) ?></td>
                                <td>
                                    <?php
                                    $st = $app['durum'];
                                    $badgeClass = match($st) {
                                        'active' => 'active',
                                        'rejected' => 'rejected',
                                        'suspended' => 'suspended',
                                        default => 'pending'
                                    };
                                    $stLabel = match($st) {
                                        'active' => 'Onaylandı',
                                        'rejected' => 'Reddedildi',
                                        'suspended' => 'Askıda',
                                        default => 'Bekliyor'
                                    };
                                    ?>
                                    <span class="status-badge <?= $badgeClass ?>"><?= $stLabel ?></span>
                                </td>
                                <td style="text-align:right;white-space:nowrap;">
                                    <a href="?tab=<?= e($currentTab) ?>&detail=<?= $app['id'] ?><?= !empty($search) ? '&q=' . urlencode($search) : '' ?>" 
                                       class="btn btn-secondary btn-sm" title="Detaylı İncele">
                                        <i class="fa-regular fa-eye"></i> İncele
                                    </a>

                                    <?php if ($app['durum'] === 'pending'): ?>
                                        <?php if (canCurrentUser('businesses.approve')): ?>
                                            <form method="POST" action="/admin/actions/application_action.php" style="display:inline;" onsubmit="return confirm('<?= addslashes(e($app['isletme_adi'])) ?> işletmesini onaylamak istediğinize emin misiniz?');">
                                                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <input type="hidden" name="business_id" value="<?= $app['id'] ?>">
                                                <button type="submit" class="btn btn-sm" style="background:#059669;color:#fff;border:none;" title="Hızlı Onayla">
                                                    <i class="fa-solid fa-check"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if (canCurrentUser('businesses.reject')): ?>
                                            <button type="button" class="btn btn-sm" style="background:#DC2626;color:#fff;border:none;" title="Reddet"
                                                    onclick="openRejectModal(<?= $app['id'] ?>, '<?= addslashes(e($app['isletme_adi'])) ?>')">
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                        <?php endif; ?>
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

                         
<div id="rejectModal" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,0.6);z-index:999;align-items:center;justify-content:center;padding:20px;">
    <div style="background:#fff;border-radius:16px;width:100%;max-width:480px;box-shadow:0 20px 40px rgba(0,0,0,0.2);overflow:hidden;animation:modalIn 0.25s ease;">
        <div style="padding:20px 24px;border-bottom:1px solid #E2E8F0;display:flex;justify-content:space-between;align-items:center;">
            <h3 style="font-size:17px;font-weight:800;color:#0F172A;margin:0;">Başvuruyu Reddet</h3>
            <button type="button" onclick="closeRejectModal()" style="background:none;border:none;font-size:20px;cursor:pointer;color:#64748B;">&times;</button>
        </div>
        <form method="POST" action="/admin/actions/application_action.php" style="padding:24px;">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="action" value="reject">
            <input type="hidden" name="business_id" id="rejectBusinessId" value="">

            <p style="font-size:14px;color:#475569;margin-bottom:16px;">
                <strong id="rejectBusinessName"></strong> başlıklı işletme başvurusunu reddetmek üzeresiniz. Lütfen başvuru sahibine iletilecek gerekçeyi belirtin:
            </p>

            <div style="margin-bottom:20px;">
                <label style="display:block;font-size:12px;font-weight:700;color:#334155;margin-bottom:6px;">Red Gerekçesi</label>
                <textarea name="red_gerekce" rows="4" class="form-control" placeholder="Örn: Telefon numarasına ulaşılamadı veya işletme adres bilgileri eksik..." required style="width:100%;box-sizing:border-box;"></textarea>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:10px;">
                <button type="button" class="btn btn-secondary" onclick="closeRejectModal()">Vazgeç</button>
                <button type="submit" class="btn btn-primary" style="background:#DC2626;border-color:#DC2626;">Reddet ve Bildir</button>
            </div>
        </form>
    </div>
</div>

<script>
function openRejectModal(businessId, businessName) {
    document.getElementById('rejectBusinessId').value = businessId;
    document.getElementById('rejectBusinessName').textContent = businessName;
    const modal = document.getElementById('rejectModal');
    modal.style.display = 'flex';
}

function closeRejectModal() {
    const modal = document.getElementById('rejectModal');
    modal.style.display = 'none';
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
