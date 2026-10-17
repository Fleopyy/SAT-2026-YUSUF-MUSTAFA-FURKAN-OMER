<?php
   
                                        
   

require_once __DIR__ . '/../includes/auth.php';

$admin = requirePermission('customers.view');
$currentPage = 'customers';
$pageTitle = 'Müşteriler';
$pageSubtitle = 'Sisteme kayıtlı müşterileri ve randevu geçmişlerini inceleyin';

$search = trim($_GET['q'] ?? '');
$viewId = (int)($_GET['id'] ?? 0);

                                         
$viewCustomer = null;
$customerAppointments = [];

if ($viewId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM kullanicilar WHERE id = ?");
    $stmt->execute([$viewId]);
    $viewCustomer = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($viewCustomer) {
        $stmt = $pdo->prepare("SELECT r.*, i.isletme_adi, i.sehir, i.ilce, h.hizmet_adi, h.fiyat, c.ad_soyad AS calisan_adi
                               FROM randevular r
                               LEFT JOIN isletmeler i ON r.isletme_id = i.id
                               LEFT JOIN hizmetler h ON r.hizmet_id = h.id
                               LEFT JOIN calisanlar c ON r.calisan_id = c.id
                               WHERE r.kullanici_id = ?
                               ORDER BY r.randevu_zamani DESC");
        $stmt->execute([$viewId]);
        $customerAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

                    
$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(ad_soyad LIKE ? OR telefon LIKE ? OR eposta LIKE ?)";
    $like = "%{$search}%";
    $params = [$like, $like, $like];
}

$whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
$sql = "SELECT k.*,
        (SELECT COUNT(*) FROM randevular r WHERE r.kullanici_id = k.id) AS toplam_randevu,
        (SELECT COUNT(*) FROM randevular r WHERE r.kullanici_id = k.id AND r.durum = 'completed') AS tamamlanan_randevu
        FROM kullanicilar k
        {$whereSql}
        ORDER BY k.olusturulma_tarihi DESC";

$customers = [];
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

include __DIR__ . '/../includes/header.php';
?>

                                               
<?php if ($viewCustomer): ?>
    <div class="admin-panel" style="border:2px solid var(--admin-accent);background:#FCFBF9;margin-bottom:24px;">
        <div class="panel-header" style="background:#FFFBF5;">
            <h2>
                <i class="fa-solid fa-user"></i>
                <span>Müşteri Profili: <strong><?= e($viewCustomer['ad_soyad']) ?></strong></span>
            </h2>
            <a href="/admin/pages/customers.php" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-xmark"></i> Kapat
            </a>
        </div>
        <div class="panel-body padded">
            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:20px;margin-bottom:20px;">
                <div>
                    <label style="font-size:11px;font-weight:700;color:var(--admin-text-muted);text-transform:uppercase;">Ad Soyad</label>
                    <div style="font-size:16px;font-weight:700;color:var(--admin-text);"><?= e($viewCustomer['ad_soyad']) ?></div>
                </div>
                <div>
                    <label style="font-size:11px;font-weight:700;color:var(--admin-text-muted);text-transform:uppercase;">Telefon</label>
                    <div><a href="tel:<?= e($viewCustomer['telefon']) ?>" style="color:var(--admin-accent);font-weight:700;font-size:15px;"><?= e($viewCustomer['telefon']) ?></a></div>
                </div>
                <div>
                    <label style="font-size:11px;font-weight:700;color:var(--admin-text-muted);text-transform:uppercase;">E-posta</label>
                    <div><a href="mailto:<?= e($viewCustomer['eposta']) ?>" style="color:var(--admin-info);font-weight:600;font-size:14px;"><?= e($viewCustomer['eposta']) ?></a></div>
                </div>
                <div>
                    <label style="font-size:11px;font-weight:700;color:var(--admin-text-muted);text-transform:uppercase;">Kayıt Tarihi</label>
                    <div style="font-size:14px;"><?= date('d.m.Y H:i', strtotime($viewCustomer['olusturulma_tarihi'])) ?></div>
                </div>
            </div>

            <div style="margin-top:20px;border-top:1px solid #E2E8F0;padding-top:18px;">
                <h4 style="font-size:15px;font-weight:700;color:var(--admin-text);margin-bottom:12px;">
                    <i class="fa-solid fa-calendar-days" style="color:var(--admin-accent);margin-right:6px;"></i>
                    Randevu Geçmişi (<?= count($customerAppointments) ?> Randevu)
                </h4>

                <?php if (empty($customerAppointments)): ?>
                    <p style="font-size:13px;color:var(--admin-text-muted);margin:0;">Bu müşteriye ait henüz bir randevu kaydı bulunmuyor.</p>
                <?php else: ?>
                    <div style="overflow-x:auto;">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>İşletme</th>
                                    <th>Hizmet</th>
                                    <th>Personel</th>
                                    <th>Randevu Zamanı</th>
                                    <th>Durum</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($customerAppointments as $apt): ?>
                                    <tr>
                                        <td><strong><?= e($apt['isletme_adi'] ?? '-') ?></strong></td>
                                        <td><?= e($apt['hizmet_adi'] ?? '-') ?> <?= $apt['fiyat'] ? '('.number_format($apt['fiyat'], 2).' ₺)' : '' ?></td>
                                        <td><?= e($apt['calisan_adi'] ?? 'Fark etmez') ?></td>
                                        <td><?= date('d.m.Y H:i', strtotime($apt['randevu_zamani'])) ?></td>
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
                                                'cancelled' => 'İptal Edildi',
                                                'rejected' => 'Reddedildi',
                                                default => 'Bekliyor'
                                            };
                                            ?>
                                            <span class="status-badge <?= $badgeClass ?>"><?= $stLabel ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

                    
<div class="admin-panel" style="margin-bottom:20px;">
    <div style="padding:16px 20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
        <div style="font-size:14px;color:var(--admin-text-muted);">
            Toplam <strong><?= count($customers) ?></strong> kayıtlı müşteri listeleniyor.
        </div>
        <form method="GET" action="" style="display:flex;gap:8px;">
            <div style="position:relative;">
                <input type="text" name="q" value="<?= e($search) ?>" placeholder="Müşteri adı, telefon, e-posta..." class="form-control" style="padding-left:36px;width:260px;height:38px;">
                <i class="fa-solid fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94A3B8;font-size:13px;"></i>
            </div>
            <button type="submit" class="btn btn-secondary btn-sm" style="height:38px;">Ara</button>
            <?php if (!empty($search)): ?>
                <a href="/admin/pages/customers.php" class="btn btn-secondary btn-sm" style="height:38px;color:#DC2626;" title="Aramayı Temizle">
                    <i class="fa-solid fa-xmark"></i>
                </a>
            <?php endif; ?>
        </form>
    </div>
</div>

                                  
<div class="admin-panel">
    <div class="panel-header">
        <h2>
            <i class="fa-solid fa-users"></i>
            <span>Müşteriler</span>
        </h2>
    </div>
    <div class="panel-body">
        <?php if (empty($customers)): ?>
            <div style="padding:60px 20px;text-align:center;color:var(--admin-text-muted);">
                <i class="fa-solid fa-user-xmark" style="font-size:40px;color:#CBD5E1;margin-bottom:14px;display:block;"></i>
                <h4 style="color:var(--admin-text);margin-bottom:6px;">Müşteri Bulunamadı</h4>
                <p style="font-size:13px;">Arama kriterlerine uygun müşteri kaydı bulunmuyor.</p>
            </div>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Müşteri Ad Soyad</th>
                            <th>Telefon</th>
                            <th>E-posta</th>
                            <th>Toplam Randevu</th>
                            <th>Tamamlanan</th>
                            <th>Kayıt Tarihi</th>
                            <th style="text-align:right;">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $c): ?>
                            <tr>
                                <td>
                                    <div style="font-weight:700;color:var(--admin-text);"><?= e($c['ad_soyad']) ?></div>
                                </td>
                                <td>
                                    <a href="tel:<?= e($c['telefon']) ?>" style="font-weight:600;color:var(--admin-accent);">
                                        <?= e($c['telefon']) ?>
                                    </a>
                                </td>
                                <td>
                                    <a href="mailto:<?= e($c['eposta']) ?>" style="color:var(--admin-info);">
                                        <?= e($c['eposta']) ?>
                                    </a>
                                </td>
                                <td><strong><?= $c['toplam_randevu'] ?></strong> Randevu</td>
                                <td>
                                    <span style="color:var(--admin-success);font-weight:600;"><?= $c['tamamlanan_randevu'] ?></span>
                                </td>
                                <td><?= date('d.m.Y H:i', strtotime($c['olusturulma_tarihi'])) ?></td>
                                <td style="text-align:right;">
                                    <a href="?id=<?= $c['id'] ?><?= !empty($search) ? '&q=' . urlencode($search) : '' ?>" class="btn btn-secondary btn-sm" title="Randevu Geçmişi">
                                        <i class="fa-solid fa-clock-rotate-left"></i> Randevu Geçmişi
                                    </a>
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
