<?php
   
                                           
   

require_once __DIR__ . '/../includes/auth.php';

$admin = requirePermission('businesses.view');
$currentPage = 'businesses';
$pageTitle = 'İşletmeler';
$pageSubtitle = 'Kayıtlı ve onaylanmış berber ve erkek kuaförlerini yönetin';

$search = trim($_GET['q'] ?? '');
$statusFilter = $_GET['status'] ?? 'all';
$viewId = (int)($_GET['id'] ?? 0);
$editId = (int)($_GET['edit'] ?? 0);

                                                          
$editBusiness = null;
if ($editId > 0 && canCurrentUser('businesses.edit') && canAccessBusiness($editId)) {
    $stmt = $pdo->prepare("SELECT * FROM isletmeler WHERE id = ?");
    $stmt->execute([$editId]);
    $editBusiness = $stmt->fetch(PDO::FETCH_ASSOC);
}

                                                           
$viewBusiness = null;
$businessServices = [];
$businessStaff = [];
$businessPhotos = [];
$businessAppointments = [];

if ($viewId > 0 && canAccessBusiness($viewId)) {
    $stmt = $pdo->prepare("SELECT * FROM isletmeler WHERE id = ?");
    $stmt->execute([$viewId]);
    $viewBusiness = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($viewBusiness) {
                    
        $stmt = $pdo->prepare("SELECT * FROM hizmetler WHERE isletme_id = ?");
        $stmt->execute([$viewId]);
        $businessServices = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        
        $stmt = $pdo->prepare("SELECT * FROM calisanlar WHERE isletme_id = ?");
        $stmt->execute([$viewId]);
        $businessStaff = $stmt->fetchAll(PDO::FETCH_ASSOC);

                       
        $stmt = $pdo->prepare("SELECT * FROM isletme_fotograflari WHERE isletme_id = ?");
        $stmt->execute([$viewId]);
        $businessPhotos = $stmt->fetchAll(PDO::FETCH_ASSOC);

                         
        $stmt = $pdo->prepare("SELECT r.*, k.ad_soyad AS musteri_adi, k.telefon AS musteri_telefon 
                               FROM randevular r 
                               LEFT JOIN kullanicilar k ON r.kullanici_id = k.id 
                               WHERE r.isletme_id = ? 
                               ORDER BY r.randevu_zamani DESC LIMIT 5");
        $stmt->execute([$viewId]);
        $businessAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

                
$where = [];
$params = [];

if ($statusFilter !== 'all') {
    $where[] = "i.durum = ?";
    $params[] = $statusFilter;
}

if (!empty($search)) {
    $where[] = "(i.isletme_adi LIKE ? OR i.isletme_sahibi LIKE ? OR i.telefon LIKE ? OR i.eposta LIKE ? OR i.sehir LIKE ? OR i.ilce LIKE ?)";
    $like = "%{$search}%";
    $params = array_merge($params, [$like, $like, $like, $like, $like, $like]);
}

                                     
if (($admin['rol'] ?? '') === 'manager') {
    $allowedScope = getManagerAllowedBusinessIds($admin['id'] ?? 0);
    if ($allowedScope !== null) {
        if (empty($allowedScope)) {
            $where[] = "1=0";
        } else {
            $inPlaceholders = implode(',', array_fill(0, count($allowedScope), '?'));
            $where[] = "i.id IN ($inPlaceholders)";
            $params = array_merge($params, $allowedScope);
        }
    }
}

$whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
$sql = "SELECT i.*, 
        (SELECT COUNT(*) FROM hizmetler h WHERE h.isletme_id = i.id) AS hizmet_sayisi,
        (SELECT COUNT(*) FROM calisanlar c WHERE c.isletme_id = i.id) AS calisan_sayisi,
        (SELECT COUNT(*) FROM randevular r WHERE r.isletme_id = i.id) AS randevu_sayisi
        FROM isletmeler i 
        {$whereSql} 
        ORDER BY i.olusturulma_tarihi DESC";

$businesses = [];
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $businesses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$csrfToken = generateCsrfToken();
include __DIR__ . '/../includes/header.php';
?>

                              
<?php if ($editBusiness): ?>
    <div class="admin-panel" style="border:2px solid var(--admin-accent);margin-bottom:24px;">
        <div class="panel-header">
            <h2>
                <i class="fa-solid fa-pen-to-square"></i>
                <span>İşletme Düzenle: <strong><?= e($editBusiness['isletme_adi']) ?></strong></span>
            </h2>
            <a href="/admin/pages/businesses.php?id=<?= $editBusiness['id'] ?>" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-xmark"></i> İptal
            </a>
        </div>
        <div class="panel-body padded">
            <form method="POST" action="/admin/actions/business_action.php">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="business_id" value="<?= $editBusiness['id'] ?>">

                <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(260px, 1fr));gap:20px;margin-bottom:20px;">
                    <div>
                        <label class="form-label" style="font-weight:700;font-size:13px;display:block;margin-bottom:6px;">İşletme Adı *</label>
                        <input type="text" name="isletme_adi" value="<?= e($editBusiness['isletme_adi']) ?>" class="form-control" required style="width:100%;box-sizing:border-box;">
                    </div>
                    <div>
                        <label class="form-label" style="font-weight:700;font-size:13px;display:block;margin-bottom:6px;">İşletme Sahibi / Yetkili *</label>
                        <input type="text" name="isletme_sahibi" value="<?= e($editBusiness['isletme_sahibi']) ?>" class="form-control" required style="width:100%;box-sizing:border-box;">
                    </div>
                    <div>
                        <label class="form-label" style="font-weight:700;font-size:13px;display:block;margin-bottom:6px;">Telefon Numarası *</label>
                        <input type="tel" name="telefon" value="<?= e($editBusiness['telefon']) ?>" class="form-control" required style="width:100%;box-sizing:border-box;">
                    </div>
                    <div>
                        <label class="form-label" style="font-weight:700;font-size:13px;display:block;margin-bottom:6px;">E-posta Adresi *</label>
                        <input type="email" name="eposta" value="<?= e($editBusiness['eposta']) ?>" class="form-control" required style="width:100%;box-sizing:border-box;">
                    </div>
                    <div>
                        <label class="form-label" style="font-weight:700;font-size:13px;display:block;margin-bottom:6px;">İl *</label>
                        <input type="text" name="sehir" value="<?= e($editBusiness['sehir']) ?>" class="form-control" required style="width:100%;box-sizing:border-box;">
                    </div>
                    <div>
                        <label class="form-label" style="font-weight:700;font-size:13px;display:block;margin-bottom:6px;">İlçe *</label>
                        <input type="text" name="ilce" value="<?= e($editBusiness['ilce']) ?>" class="form-control" required style="width:100%;box-sizing:border-box;">
                    </div>
                    <div>
                        <label class="form-label" style="font-weight:700;font-size:13px;display:block;margin-bottom:6px;">Durum</label>
                        <select name="durum" class="form-control" style="width:100%;box-sizing:border-box;height:42px;">
                            <option value="active" <?= $editBusiness['durum'] === 'active' ? 'selected' : '' ?>>Aktif (Yayında)</option>
                            <option value="suspended" <?= $editBusiness['durum'] === 'suspended' ? 'selected' : '' ?>>Askıya Alındı</option>
                            <option value="pending" <?= $editBusiness['durum'] === 'pending' ? 'selected' : '' ?>>Beklemede</option>
                            <option value="rejected" <?= $editBusiness['durum'] === 'rejected' ? 'selected' : '' ?>>Reddedildi</option>
                        </select>
                    </div>
                </div>

                <div style="margin-bottom:20px;">
                    <label class="form-label" style="font-weight:700;font-size:13px;display:block;margin-bottom:6px;">Tam Adres</label>
                    <textarea name="tam_adres" rows="3" class="form-control" style="width:100%;box-sizing:border-box;"><?= e($editBusiness['tam_adres'] ?? '') ?></textarea>
                </div>

                <div style="margin-bottom:24px;">
                    <label class="form-label" style="font-weight:700;font-size:13px;display:block;margin-bottom:6px;">Açıklama / Hakkında</label>
                    <textarea name="aciklama" rows="3" class="form-control" style="width:100%;box-sizing:border-box;"><?= e($editBusiness['aciklama'] ?? '') ?></textarea>
                </div>

                <div style="display:flex;gap:10px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-floppy-disk"></i> Değişiklikleri Kaydet
                    </button>
                    <a href="/admin/pages/businesses.php?id=<?= $editBusiness['id'] ?>" class="btn btn-secondary">İptal</a>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

                                  
<?php if ($viewBusiness && !$editBusiness): ?>
    <div class="admin-panel" style="border:2px solid var(--admin-accent);background:#FCFBF9;margin-bottom:24px;">
        <div class="panel-header" style="background:#FFFBF5;">
            <h2>
                <i class="fa-solid fa-store"></i>
                <span>İşletme Detayı: <strong><?= e($viewBusiness['isletme_adi']) ?></strong></span>
            </h2>
            <div style="display:flex;gap:8px;">
                <?php if (canCurrentUser('businesses.edit')): ?>
                    <a href="?edit=<?= $viewBusiness['id'] ?>" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-pen"></i> Düzenle
                    </a>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="openPasswordModal()">
                        <i class="fa-solid fa-key"></i> Şifre Değiştir
                    </button>
                <?php endif; ?>
                <a href="/admin/pages/businesses.php" class="btn btn-secondary btn-sm">
                    <i class="fa-solid fa-xmark"></i> Kapat
                </a>
            </div>
        </div>
        <div class="panel-body padded">
            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:20px;margin-bottom:20px;">
                <div>
                    <label style="font-size:11px;font-weight:700;color:var(--admin-text-muted);text-transform:uppercase;">Yetkili</label>
                    <div style="font-size:15px;font-weight:600;"><?= e($viewBusiness['isletme_sahibi']) ?></div>
                </div>
                <div>
                    <label style="font-size:11px;font-weight:700;color:var(--admin-text-muted);text-transform:uppercase;">Telefon</label>
                    <div><a href="tel:<?= e($viewBusiness['telefon']) ?>" style="color:var(--admin-accent);font-weight:700;"><?= e($viewBusiness['telefon']) ?></a></div>
                </div>
                <div>
                    <label style="font-size:11px;font-weight:700;color:var(--admin-text-muted);text-transform:uppercase;">E-posta</label>
                    <div><a href="mailto:<?= e($viewBusiness['eposta']) ?>" style="color:var(--admin-info);font-weight:600;"><?= e($viewBusiness['eposta']) ?></a></div>
                </div>
                <div>
                    <label style="font-size:11px;font-weight:700;color:var(--admin-text-muted);text-transform:uppercase;">Konum</label>
                    <div style="font-weight:600;"><?= e($viewBusiness['sehir']) ?> / <?= e($viewBusiness['ilce']) ?></div>
                </div>
                <div>
                    <label style="font-size:11px;font-weight:700;color:var(--admin-text-muted);text-transform:uppercase;">Durum</label>
                    <div>
                        <span class="status-badge <?= $viewBusiness['durum'] === 'active' ? 'active' : ($viewBusiness['durum'] === 'suspended' ? 'suspended' : 'pending') ?>">
                            <?= match($viewBusiness['durum']) { 'active' => 'Aktif', 'suspended' => 'Askıda', 'rejected' => 'Reddedildi', default => 'Beklemede' } ?>
                        </span>
                    </div>
                </div>
            </div>

            <?php if (!empty($viewBusiness['tam_adres'])): ?>
                <div style="margin-bottom:16px;background:#fff;padding:12px 16px;border-radius:8px;border:1px solid #E2E8F0;">
                    <label style="font-size:11px;font-weight:700;color:var(--admin-text-muted);text-transform:uppercase;display:block;margin-bottom:4px;">Açık Adres</label>
                    <div style="font-size:14px;"><?= nl2br(e($viewBusiness['tam_adres'])) ?></div>
                </div>
            <?php endif; ?>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:20px;">
                                  
                <div style="background:#fff;border-radius:8px;border:1px solid #E2E8F0;padding:16px;">
                    <h4 style="font-size:14px;font-weight:700;margin-bottom:10px;color:var(--admin-text);">Hizmetler (<?= count($businessServices) ?>)</h4>
                    <?php if (empty($businessServices)): ?>
                        <div style="font-size:12px;color:var(--admin-text-muted);">Tanımlı hizmet bulunmuyor.</div>
                    <?php else: ?>
                        <ul style="list-style:none;padding:0;margin:0;">
                            <?php foreach ($businessServices as $svc): ?>
                                <li style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #F1F5F9;font-size:13px;">
                                    <span><?= e($svc['hizmet_adi']) ?> <small style="color:var(--admin-text-muted);">(<?= $svc['sure_dakika'] ?> dk)</small></span>
                                    <strong style="color:var(--admin-accent);"><?= number_format($svc['fiyat'], 2) ?> ₺</strong>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                                      
                <div style="background:#fff;border-radius:8px;border:1px solid #E2E8F0;padding:16px;">
                    <h4 style="font-size:14px;font-weight:700;margin-bottom:10px;color:var(--admin-text);">Çalışanlar / Personel (<?= count($businessStaff) ?>)</h4>
                    <?php if (empty($businessStaff)): ?>
                        <div style="font-size:12px;color:var(--admin-text-muted);">Tanımlı çalışan bulunmuyor.</div>
                    <?php else: ?>
                        <ul style="list-style:none;padding:0;margin:0;">
                            <?php foreach ($businessStaff as $stf): ?>
                                <li style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #F1F5F9;font-size:13px;">
                                    <span><?= e($stf['ad_soyad']) ?></span>
                                    <small style="color:var(--admin-text-muted);"><?= e($stf['telefon'] ?? '-') ?></small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

                                        
    <div id="passwordModal" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,0.6);z-index:999;align-items:center;justify-content:center;padding:20px;">
        <div style="background:#fff;border-radius:16px;width:100%;max-width:420px;box-shadow:0 20px 40px rgba(0,0,0,0.2);overflow:hidden;">
            <div style="padding:18px 24px;border-bottom:1px solid #E2E8F0;display:flex;justify-content:space-between;align-items:center;">
                <h3 style="font-size:16px;font-weight:800;color:#0F172A;margin:0;">İşletme Şifresini Sıfırla</h3>
                <button type="button" onclick="closePasswordModal()" style="background:none;border:none;font-size:20px;cursor:pointer;color:#64748B;">&times;</button>
            </div>
            <form method="POST" action="/admin/actions/business_action.php" style="padding:24px;">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="business_id" value="<?= $viewBusiness['id'] ?>">

                <p style="font-size:13px;color:#475569;margin-bottom:16px;">
                    <strong><?= e($viewBusiness['isletme_adi']) ?></strong> için yeni bir giriş şifresi belirleyin:
                </p>

                <div style="margin-bottom:20px;">
                    <label style="display:block;font-size:12px;font-weight:700;color:#334155;margin-bottom:6px;">Yeni Şifre (En az 6 karakter)</label>
                    <input type="password" name="new_password" required minlength="6" class="form-control" style="width:100%;box-sizing:border-box;">
                </div>

                <div style="display:flex;justify-content:flex-end;gap:10px;">
                    <button type="button" class="btn btn-secondary" onclick="closePasswordModal()">Vazgeç</button>
                    <button type="submit" class="btn btn-primary">Şifreyi Güncelle</button>
                </div>
            </form>
        </div>
    </div>
    <script>
    function openPasswordModal() { document.getElementById('passwordModal').style.display = 'flex'; }
    function closePasswordModal() { document.getElementById('passwordModal').style.display = 'none'; }
    </script>
<?php endif; ?>

                       
<div class="admin-panel" style="margin-bottom:20px;">
    <div style="padding:16px 20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
        <div style="display:flex;align-items:center;gap:8px;">
            <a href="?status=all<?= !empty($search) ? '&q=' . urlencode($search) : '' ?>" class="btn <?= $statusFilter === 'all' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">Tümü</a>
            <a href="?status=active<?= !empty($search) ? '&q=' . urlencode($search) : '' ?>" class="btn <?= $statusFilter === 'active' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">Aktif</a>
            <a href="?status=suspended<?= !empty($search) ? '&q=' . urlencode($search) : '' ?>" class="btn <?= $statusFilter === 'suspended' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">Askıda</a>
            <a href="?status=pending<?= !empty($search) ? '&q=' . urlencode($search) : '' ?>" class="btn <?= $statusFilter === 'pending' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">Beklemede</a>
        </div>

        <form method="GET" action="" style="display:flex;gap:8px;">
            <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
            <div style="position:relative;">
                <input type="text" name="q" value="<?= e($search) ?>" placeholder="İşletme adı, il, yetkili..." class="form-control" style="padding-left:36px;width:240px;height:38px;">
                <i class="fa-solid fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94A3B8;font-size:13px;"></i>
            </div>
            <button type="submit" class="btn btn-secondary btn-sm" style="height:38px;">Ara</button>
        </form>
    </div>
</div>

                             
<div class="admin-panel">
    <div class="panel-header">
        <h2>
            <i class="fa-solid fa-store"></i>
            <span>Kayıtlı İşletmeler</span>
            <small style="color:var(--admin-text-muted);font-weight:400;font-size:13px;">(<?= count($businesses) ?> İşletme)</small>
        </h2>
    </div>
    <div class="panel-body">
        <?php if (empty($businesses)): ?>
            <div style="padding:60px 20px;text-align:center;color:var(--admin-text-muted);">
                <i class="fa-solid fa-store" style="font-size:40px;color:#CBD5E1;margin-bottom:14px;display:block;"></i>
                <h4 style="color:var(--admin-text);margin-bottom:6px;">İşletme Bulunamadı</h4>
                <p style="font-size:13px;">Arama kriterlerine uygun işletme bulunmamaktadır.</p>
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
                            <th>Hizmet / Personel</th>
                            <th>Randevu</th>
                            <th>Durum</th>
                            <th style="text-align:right;">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($businesses as $b): ?>
                            <tr>
                                <td>
                                    <div style="font-weight:700;color:var(--admin-text);"><?= e($b['isletme_adi']) ?></div>
                                    <small style="color:var(--admin-text-muted);"><?= e($b['eposta']) ?></small>
                                </td>
                                <td><?= e($b['isletme_sahibi']) ?></td>
                                <td>
                                    <a href="tel:<?= e($b['telefon']) ?>" style="font-weight:600;color:var(--admin-accent);">
                                        <?= e($b['telefon']) ?>
                                    </a>
                                </td>
                                <td><?= e($b['sehir']) ?> / <?= e($b['ilce']) ?></td>
                                <td>
                                    <span style="font-size:12px;font-weight:600;"><?= $b['hizmet_sayisi'] ?> Hizmet</span> · 
                                    <span style="font-size:12px;color:var(--admin-text-muted);"><?= $b['calisan_sayisi'] ?> Personel</span>
                                </td>
                                <td>
                                    <strong style="color:var(--admin-text);"><?= $b['randevu_sayisi'] ?></strong>
                                </td>
                                <td>
                                    <span class="status-badge <?= $b['durum'] === 'active' ? 'active' : ($b['durum'] === 'suspended' ? 'suspended' : 'pending') ?>">
                                        <?= match($b['durum']) { 'active' => 'Aktif', 'suspended' => 'Askıda', 'rejected' => 'Reddedildi', default => 'Beklemede' } ?>
                                    </span>
                                </td>
                                <td style="text-align:right;white-space:nowrap;">
                                    <a href="?id=<?= $b['id'] ?>" class="btn btn-secondary btn-sm" title="Detay">
                                        <i class="fa-regular fa-eye"></i> Detay
                                    </a>
                                    <?php if (canCurrentUser('businesses.edit')): ?>
                                        <a href="?edit=<?= $b['id'] ?>" class="btn btn-secondary btn-sm" title="Düzenle">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                    <?php endif; ?>
                                    <?php if (canCurrentUser('businesses.suspend') || canCurrentUser('businesses.reactivate')): ?>
                                        <form method="POST" action="/admin/actions/business_action.php" style="display:inline;" onsubmit="return confirm('İşletme durumunu değiştirmek istediğinize emin misiniz?');">
                                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="business_id" value="<?= $b['id'] ?>">
                                            <button type="submit" class="btn btn-sm" style="background:<?= $b['durum'] === 'active' ? '#FEF3C7;color:#92400E' : '#D1FAE5;color:#065F46' ?>;border:none;" title="<?= $b['durum'] === 'active' ? 'Askıya Al' : 'Aktifleştir' ?>">
                                                <i class="fa-solid <?= $b['durum'] === 'active' ? 'fa-pause' : 'fa-play' ?>"></i>
                                            </button>
                                        </form>
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
