<?php
   
                                                                          
                                      
   

require_once __DIR__ . '/../includes/auth.php';

$admin = requireAdmin();
$currentPage = 'managers';
$pageTitle = 'Yönetici Hesapları & İzinler';
$pageSubtitle = 'Panel yöneticilerini, granüler modül izinlerini ve işletme kapsam sınırlarını yönetin';

$allPermissions = getAllPermissions();
$permissionGroups = getPermissionGroups();
$actionView = $_GET['action'] ?? '';
$editId = (int)($_GET['edit'] ?? 0);
$logsId = (int)($_GET['logs'] ?? 0);

                                                    
$allBusinesses = $pdo->query("SELECT id, isletme_adi, sehir, ilce FROM isletmeler WHERE durum = 'active' ORDER BY isletme_adi ASC")->fetchAll(PDO::FETCH_ASSOC);

                  
$editManager = null;
$editPermissions = [];
$editScopedBusinessIds = [];

if ($editId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM yoneticiler WHERE id = ?");
    $stmt->execute([$editId]);
    $editManager = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($editManager) {
        $stmt = $pdo->prepare("SELECT izin_anahtari FROM yonetici_izinleri WHERE yonetici_id = ?");
        $stmt->execute([$editId]);
        $editPermissions = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

        $stmt = $pdo->prepare("SELECT isletme_id FROM yonetici_isletme_kapsami WHERE yonetici_id = ?");
        $stmt->execute([$editId]);
        $editScopedBusinessIds = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }
}

                                                           
$managerLogs = [];
$logManagerUser = null;
if ($logsId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM yoneticiler WHERE id = ?");
    $stmt->execute([$logsId]);
    $logManagerUser = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT * FROM islem_gunlukleri WHERE yonetici_id = ? ORDER BY olusturulma_tarihi DESC LIMIT 20");
    $stmt->execute([$logsId]);
    $managerLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

                    
$managers = [];
try {
    $stmt = $pdo->query("SELECT y.*, 
                         (SELECT COUNT(*) FROM yonetici_izinleri yi WHERE yi.yonetici_id = y.id) AS izin_sayisi,
                         (SELECT COUNT(*) FROM yonetici_isletme_kapsami yk WHERE yk.yonetici_id = y.id) AS kapsam_isletme_sayisi
                         FROM yoneticiler y 
                         ORDER BY y.rol ASC, y.olusturulma_tarihi DESC");
    $managers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$csrfToken = generateCsrfToken();
include __DIR__ . '/../includes/header.php';
?>

                                            
<?php if ($logManagerUser): ?>
    <div class="admin-panel" style="border:2px solid var(--admin-accent);background:#FCFBF9;margin-bottom:24px;">
        <div class="panel-header" style="background:#FFFBF5;">
            <h2>
                <i class="fa-solid fa-clock-rotate-left"></i>
                <span>Yönetici İşlem Kayıtları: <strong><?= e($logManagerUser['ad_soyad']) ?></strong></span>
            </h2>
            <a href="/admin/pages/managers.php" class="btn btn-secondary btn-sm"><i class="fa-solid fa-xmark"></i> Kapat</a>
        </div>
        <div class="panel-body">
            <?php if (empty($managerLogs)): ?>
                <div style="padding:32px;text-align:center;color:#64748B;">Bu yöneticiye ait kayıtlı bir işlem bulunmuyor.</div>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Zaman</th>
                                <th>İşlem Türü</th>
                                <th>Hedef</th>
                                <th>Detay</th>
                                <th>IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($managerLogs as $l): ?>
                                <tr>
                                    <td><?= date('d.m.Y H:i:s', strtotime($l['olusturulma_tarihi'])) ?></td>
                                    <td><code><?= e($l['islem_turu']) ?></code></td>
                                    <td><?= e($l['hedef_turu'] ?? '-') ?> #<?= (int)$l['hedef_id'] ?></td>
                                    <td><?= e($l['detay'] ?? '-') ?></td>
                                    <td><small><?= e($l['ip_adresi'] ?? '-') ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

                                    
<?php if ($actionView === 'create'): ?>
    <div class="admin-panel" style="border:2px solid var(--admin-accent);margin-bottom:28px;">
        <div class="panel-header">
            <h2><i class="fa-solid fa-user-plus"></i><span>Yeni Yönetici Hesabı Oluştur</span></h2>
            <a href="/admin/pages/managers.php" class="btn btn-secondary btn-sm"><i class="fa-solid fa-xmark"></i> İptal</a>
        </div>
        <div class="panel-body padded">
            <form method="POST" action="/admin/actions/manager_action.php" id="managerForm">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="action" value="create">

                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;margin-bottom:20px;">
                    <div>
                        <label class="form-label" style="font-weight:700;font-size:13px;display:block;margin-bottom:6px;">Ad Soyad *</label>
                        <input type="text" name="ad_soyad" required class="form-control" style="width:100%;box-sizing:border-box;">
                    </div>
                    <div>
                        <label class="form-label" style="font-weight:700;font-size:13px;display:block;margin-bottom:6px;">E-posta Adresi *</label>
                        <input type="email" name="eposta" required class="form-control" style="width:100%;box-sizing:border-box;">
                    </div>
                    <div>
                        <label class="form-label" style="font-weight:700;font-size:13px;display:block;margin-bottom:6px;">Giriş Şifresi * (En az 8 karakter)</label>
                        <input type="password" name="sifre" required minlength="8" class="form-control" style="width:100%;box-sizing:border-box;">
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;">
                    <div>
                        <label class="form-label" style="font-weight:700;font-size:13px;display:block;margin-bottom:6px;">Hesap Rolü</label>
                        <select name="rol" class="form-control" style="width:100%;height:42px;" onchange="toggleRoleControls(this.value)">
                            <option value="manager" selected>Yetkilendirilmiş Yönetici (Manager — İzin Tabanlı)</option>
                            <option value="admin">Süper Admin (Tam Yetkili Sistem Yöneticisi)</option>
                        </select>
                    </div>

                    <div id="scopeTypeWrap">
                        <label class="form-label" style="font-weight:700;font-size:13px;display:block;margin-bottom:6px;">İşletme Kapsamı (Scope Sınırı)</label>
                        <select name="kapsam_turu" class="form-control" style="width:100%;height:42px;" onchange="toggleScopeBox(this.value)">
                            <option value="tum_isletmeler" selected>Tüm İşletmeler (Sınırsız Kapsam)</option>
                            <option value="belirli_isletmeler">Yalnızca Seçili İşletmeler (Kapsam Kısıtlamalı)</option>
                        </select>
                    </div>
                </div>

                                                    
                <div id="scopedBusinessesBox" style="display:none;background:#FFFBF5;border:1px solid #FDE68A;border-radius:12px;padding:20px;margin-bottom:24px;">
                    <label style="font-size:13px;font-weight:700;color:#92400E;display:block;margin-bottom:8px;">Yöneticinin Erişebileceği İşletmeleri Seçin:</label>
                    <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(260px, 1fr));gap:8px;max-height:180px;overflow-y:auto;background:#fff;padding:12px;border-radius:8px;border:1px solid #E2E8F0;">
                        <?php foreach ($allBusinesses as $b): ?>
                            <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer;">
                                <input type="checkbox" name="scoped_businesses[]" value="<?= $b['id'] ?>">
                                <span><?= e($b['isletme_adi']) ?> <small style="color:#64748B;">(<?= e($b['sehir']) ?>)</small></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                                                     
                <div id="permissionsSection" style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:14px;padding:22px;margin-bottom:24px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;flex-wrap:wrap;gap:12px;">
                        <h4 style="font-size:15px;font-weight:800;color:var(--admin-text);margin:0;">
                            <i class="fa-solid fa-shield-halved" style="color:var(--admin-accent);margin-right:6px;"></i>
                            Granüler Modül İzinleri
                        </h4>

                                                                 
                        <div style="display:flex;gap:6px;flex-wrap:wrap;">
                            <button type="button" class="btn btn-secondary btn-sm" onclick="applyPreset('group_business')">İşletme Sorumlusu</button>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="applyPreset('group_appointments')">Randevu Yöneticisi</button>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="applyPreset('group_content')">İçerik Editörü</button>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="applyPreset('group_reports')">Rapor Uzmanı</button>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="selectAllPerms(true)" style="color:var(--admin-success);">Tümünü Seç</button>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="selectAllPerms(false)" style="color:var(--admin-danger);">Temizle</button>
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(280px, 1fr));gap:10px;">
                        <?php foreach ($allPermissions as $permKey => $permMeta): ?>
                            <label style="display:flex;align-items:flex-start;gap:10px;padding:10px 14px;background:#fff;border-radius:8px;border:1px solid #E2E8F0;cursor:pointer;">
                                <input type="checkbox" name="permissions[]" value="<?= e($permKey) ?>" class="perm-checkbox" style="margin-top:3px;">
                                <div style="font-size:13px;">
                                    <strong style="display:block;color:var(--admin-text);"><?= e($permMeta['label']) ?></strong>
                                    <code style="font-size:10px;color:var(--admin-text-muted);"><?= e($permKey) ?></code>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div style="display:flex;gap:10px;">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Yöneticiyi Kaydet</button>
                    <a href="/admin/pages/managers.php" class="btn btn-secondary">İptal</a>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

                                   
<?php if ($editManager): ?>
    <div class="admin-panel" style="border:2px solid var(--admin-accent);margin-bottom:28px;">
        <div class="panel-header">
            <h2><i class="fa-solid fa-user-pen"></i><span>Yönetici Düzenle: <strong><?= e($editManager['ad_soyad']) ?></strong></span></h2>
            <a href="/admin/pages/managers.php" class="btn btn-secondary btn-sm"><i class="fa-solid fa-xmark"></i> İptal</a>
        </div>
        <div class="panel-body padded">
            <form method="POST" action="/admin/actions/manager_action.php" id="managerForm">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="manager_id" value="<?= $editManager['id'] ?>">

                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;margin-bottom:20px;">
                    <div>
                        <label class="form-label" style="font-weight:700;font-size:13px;display:block;margin-bottom:6px;">Ad Soyad *</label>
                        <input type="text" name="ad_soyad" value="<?= e($editManager['ad_soyad']) ?>" required class="form-control" style="width:100%;box-sizing:border-box;">
                    </div>
                    <div>
                        <label class="form-label" style="font-weight:700;font-size:13px;display:block;margin-bottom:6px;">E-posta Adresi *</label>
                        <input type="email" name="eposta" value="<?= e($editManager['eposta']) ?>" required class="form-control" style="width:100%;box-sizing:border-box;">
                    </div>
                    <div>
                        <label class="form-label" style="font-weight:700;font-size:13px;display:block;margin-bottom:6px;">Yeni Şifre (Değişmeyecekse boş bırakın)</label>
                        <input type="password" name="sifre" minlength="8" placeholder="••••••••" class="form-control" style="width:100%;box-sizing:border-box;">
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;margin-bottom:24px;align-items:center;">
                    <div>
                        <label class="form-label" style="font-weight:700;font-size:13px;display:block;margin-bottom:6px;">Rol</label>
                        <select name="rol" class="form-control" style="width:100%;height:42px;" onchange="toggleRoleControls(this.value)">
                            <option value="manager" <?= $editManager['rol'] === 'manager' ? 'selected' : '' ?>>Yetkilendirilmiş Yönetici (Manager)</option>
                            <option value="admin" <?= $editManager['rol'] === 'admin' ? 'selected' : '' ?>>Süper Admin</option>
                        </select>
                    </div>

                    <div id="scopeTypeWrap" style="display:<?= $editManager['rol'] === 'admin' ? 'none' : 'block' ?>;">
                        <label class="form-label" style="font-weight:700;font-size:13px;display:block;margin-bottom:6px;">İşletme Kapsamı</label>
                        <select name="kapsam_turu" class="form-control" style="width:100%;height:42px;" onchange="toggleScopeBox(this.value)">
                            <option value="tum_isletmeler" <?= ($editManager['kapsam_turu'] ?? '') === 'tum_isletmeler' ? 'selected' : '' ?>>Tüm İşletmeler</option>
                            <option value="belirli_isletmeler" <?= ($editManager['kapsam_turu'] ?? '') === 'belirli_isletmeler' ? 'selected' : '' ?>>Belirli İşletmeler</option>
                        </select>
                    </div>

                    <div style="padding-top:20px;">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:700;font-size:14px;">
                            <input type="checkbox" name="aktif" value="1" <?= $editManager['aktif'] ? 'checked' : '' ?>>
                            <span>Hesap Aktif</span>
                        </label>
                    </div>
                </div>

                                                    
                <div id="scopedBusinessesBox" style="display:<?= ($editManager['kapsam_turu'] ?? '') === 'belirli_isletmeler' && $editManager['rol'] !== 'admin' ? 'block' : 'none' ?>;background:#FFFBF5;border:1px solid #FDE68A;border-radius:12px;padding:20px;margin-bottom:24px;">
                    <label style="font-size:13px;font-weight:700;color:#92400E;display:block;margin-bottom:8px;">Yöneticinin Erişebileceği İşletmeleri Seçin:</label>
                    <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(260px, 1fr));gap:8px;max-height:180px;overflow-y:auto;background:#fff;padding:12px;border-radius:8px;border:1px solid #E2E8F0;">
                        <?php foreach ($allBusinesses as $b): ?>
                            <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer;">
                                <input type="checkbox" name="scoped_businesses[]" value="<?= $b['id'] ?>" <?= in_array($b['id'], $editScopedBusinessIds) ? 'checked' : '' ?>>
                                <span><?= e($b['isletme_adi']) ?> <small style="color:#64748B;">(<?= e($b['sehir']) ?>)</small></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                                                     
                <div id="permissionsSection" style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:14px;padding:22px;margin-bottom:24px;display:<?= $editManager['rol'] === 'admin' ? 'none' : 'block' ?>;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;flex-wrap:wrap;gap:12px;">
                        <h4 style="font-size:15px;font-weight:800;color:var(--admin-text);margin:0;">
                            <i class="fa-solid fa-shield-halved" style="color:var(--admin-accent);margin-right:6px;"></i>
                            Granüler Modül İzinleri
                        </h4>

                                                       
                        <div style="display:flex;gap:6px;flex-wrap:wrap;">
                            <button type="button" class="btn btn-secondary btn-sm" onclick="applyPreset('group_business')">İşletme Sorumlusu</button>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="applyPreset('group_appointments')">Randevu Yöneticisi</button>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="applyPreset('group_content')">İçerik Editörü</button>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="applyPreset('group_reports')">Rapor Uzmanı</button>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="selectAllPerms(true)" style="color:var(--admin-success);">Tümünü Seç</button>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="selectAllPerms(false)" style="color:var(--admin-danger);">Temizle</button>
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(280px, 1fr));gap:10px;">
                        <?php foreach ($allPermissions as $permKey => $permMeta): ?>
                            <?php $isChecked = in_array($permKey, $editPermissions); ?>
                            <label style="display:flex;align-items:flex-start;gap:10px;padding:10px 14px;background:#fff;border-radius:8px;border:1px solid #E2E8F0;cursor:pointer;">
                                <input type="checkbox" name="permissions[]" value="<?= e($permKey) ?>" <?= $isChecked ? 'checked' : '' ?> class="perm-checkbox" style="margin-top:3px;">
                                <div style="font-size:13px;">
                                    <strong style="display:block;color:var(--admin-text);"><?= e($permMeta['label']) ?></strong>
                                    <code style="font-size:10px;color:var(--admin-text-muted);"><?= e($permKey) ?></code>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div style="display:flex;gap:10px;">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Değişiklikleri Kaydet</button>
                    <a href="/admin/pages/managers.php" class="btn btn-secondary">İptal</a>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

                             
<div class="admin-panel">
    <div class="panel-header">
        <h2>
            <i class="fa-solid fa-user-shield"></i>
            <span>Sistem Yöneticileri</span>
            <small style="color:var(--admin-text-muted);font-weight:400;font-size:13px;">(<?= count($managers) ?> Yönetici)</small>
        </h2>
        <?php if ($actionView !== 'create' && !$editManager): ?>
            <a href="?action=create" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-user-plus"></i> Yeni Yönetici Ekle
            </a>
        <?php endif; ?>
    </div>
    <div class="panel-body">
        <div style="overflow-x:auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Yönetici</th>
                        <th>E-posta</th>
                        <th>Rol</th>
                        <th>İşletme Kapsamı</th>
                        <th>Yetkiler</th>
                        <th>Durum</th>
                        <th>Son Giriş</th>
                        <th style="text-align:right;">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($managers as $m): ?>
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <div style="width:34px;height:34px;border-radius:8px;background:linear-gradient(135deg, #C28840, #A66E2C);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;">
                                        <?= mb_strtoupper(mb_substr($m['ad_soyad'], 0, 1, 'UTF-8'), 'UTF-8') ?>
                                    </div>
                                    <div style="font-weight:700;color:var(--admin-text);"><?= e($m['ad_soyad']) ?></div>
                                </div>
                            </td>
                            <td><?= e($m['eposta']) ?></td>
                            <td>
                                <span class="status-badge <?= $m['rol'] === 'admin' ? 'active' : '' ?>" style="background:<?= $m['rol'] === 'admin' ? '' : '#F1F5F9' ?>;color:<?= $m['rol'] === 'admin' ? '' : '#475569' ?>;">
                                    <?= $m['rol'] === 'admin' ? 'Süper Admin' : 'Manager' ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($m['rol'] === 'admin'): ?>
                                    <span style="font-size:12px;color:var(--admin-text-muted);">Sınırsız</span>
                                <?php elseif (($m['kapsam_turu'] ?? '') === 'belirli_isletmeler'): ?>
                                    <span style="font-size:12px;font-weight:700;color:var(--admin-warning);">
                                        <i class="fa-solid fa-filter"></i> <?= (int)$m['kapsam_isletme_sayisi'] ?> İşletme
                                    </span>
                                <?php else: ?>
                                    <span style="font-size:12px;color:var(--admin-success);font-weight:600;">Tüm İşletmeler</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($m['rol'] === 'admin'): ?>
                                    <span style="font-size:12px;color:var(--admin-success);font-weight:700;"><i class="fa-solid fa-shield-check"></i> Tam Yetkili</span>
                                <?php else: ?>
                                    <span style="font-size:12px;font-weight:600;"><?= $m['izin_sayisi'] ?> İzin Tanımlı</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-badge <?= $m['aktif'] ? 'active' : 'suspended' ?>">
                                    <?= $m['aktif'] ? 'Aktif' : 'Devre Dışı' ?>
                                </span>
                            </td>
                            <td>
                                <?= $m['son_giris'] ? date('d.m.Y H:i', strtotime($m['son_giris'])) : '<span style="color:var(--admin-text-muted);font-size:12px;">Henüz giriş yok</span>' ?>
                            </td>
                            <td style="text-align:right;white-space:nowrap;">
                                <a href="?logs=<?= $m['id'] ?>" class="btn btn-secondary btn-sm" title="İşlem Kayıtları">
                                    <i class="fa-solid fa-clock-rotate-left"></i> Loglar
                                </a>
                                <a href="?edit=<?= $m['id'] ?>" class="btn btn-secondary btn-sm" title="Düzenle">
                                    <i class="fa-solid fa-pen"></i>
                                </a>

                                <?php if ($m['id'] !== $admin['id']): ?>
                                    <form method="POST" action="/admin/actions/manager_action.php" style="display:inline;" onsubmit="return confirm('Bu yöneticinin durumunu değiştirmek istediğinize emin misiniz?');">
                                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                        <input type="hidden" name="action" value="toggle_active">
                                        <input type="hidden" name="manager_id" value="<?= $m['id'] ?>">
                                        <button type="submit" class="btn btn-sm" style="background:<?= $m['aktif'] ? '#FEF2F2;color:#DC2626' : '#ECFDF5;color:#059669' ?>;border:none;" title="<?= $m['aktif'] ? 'Devre Dışı Bırak' : 'Aktifleştir' ?>">
                                            <i class="fa-solid <?= $m['aktif'] ? 'fa-user-slash' : 'fa-user-check' ?>"></i>
                                        </button>
                                    </form>

                                    <form method="POST" action="/admin/actions/manager_action.php" style="display:inline;" onsubmit="return confirm('Bu yöneticiyi kalıcı olarak silmek istediğinize emin misiniz? Bu işlem geri alınamaz!');">
                                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="manager_id" value="<?= $m['id'] ?>">
                                        <button type="submit" class="btn btn-sm" style="background:#FEE2E2;color:#991B1B;border:none;" title="Sil">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
const PRESET_GROUPS = <?= json_encode($permissionGroups, JSON_UNESCAPED_UNICODE) ?>;

function applyPreset(groupKey) {
    if (!PRESET_GROUPS[groupKey]) return;
    const perms = PRESET_GROUPS[groupKey].perms;
    document.querySelectorAll('.perm-checkbox').forEach(cb => {
        if (perms.includes(cb.value)) {
            cb.checked = true;
        }
    });
}

function selectAllPerms(check) {
    document.querySelectorAll('.perm-checkbox').forEach(cb => {
        cb.checked = check;
    });
}

function toggleRoleControls(role) {
    const permSec = document.getElementById('permissionsSection');
    const scopeWrap = document.getElementById('scopeTypeWrap');
    const scopeBox = document.getElementById('scopedBusinessesBox');
    
    if (role === 'admin') {
        if (permSec) permSec.style.display = 'none';
        if (scopeWrap) scopeWrap.style.display = 'none';
        if (scopeBox) scopeBox.style.display = 'none';
    } else {
        if (permSec) permSec.style.display = 'block';
        if (scopeWrap) scopeWrap.style.display = 'block';
    }
}

function toggleScopeBox(type) {
    const box = document.getElementById('scopedBusinessesBox');
    if (box) {
        box.style.display = (type === 'belirli_isletmeler') ? 'block' : 'none';
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
