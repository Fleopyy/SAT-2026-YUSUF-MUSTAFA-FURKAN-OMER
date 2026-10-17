<?php
   
                                                                 
                                                                                                     
   

require_once dirname(__DIR__) . '/admin/includes/auth.php';

                                            
$manager = requireManager();
$currentPage = 'dashboard';
$pageTitle = 'Yönetici Paneli';
$pageSubtitle = 'Yetkileriniz kapsamındaki yönetim modülleri ve canlı veriler';

$managerId = (int)$manager['id'];

                                  
$stmt = $pdo->prepare("SELECT izin_anahtari FROM yonetici_izinleri WHERE yonetici_id = ?");
$stmt->execute([$managerId]);
$myPermissions = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
$allPermissionsCatalog = getAllPermissions();

                                                                              
$allowedBusinessIds = getManagerAllowedBusinessIds($managerId);

                                                             
$stats = [];

                                                             
if (canCurrentUser('businesses.view')) {
    if ($allowedBusinessIds === null) {
        $stats['pending_apps'] = (int)$pdo->query("SELECT COUNT(*) FROM isletmeler WHERE durum = 'pending'")->fetchColumn();
        $stats['active_businesses'] = (int)$pdo->query("SELECT COUNT(*) FROM isletmeler WHERE durum = 'active'")->fetchColumn();
    } elseif (!empty($allowedBusinessIds)) {
        $inClause = implode(',', array_map('intval', $allowedBusinessIds));
        $stats['pending_apps'] = (int)$pdo->query("SELECT COUNT(*) FROM isletmeler WHERE durum = 'pending' AND id IN ($inClause)")->fetchColumn();
        $stats['active_businesses'] = (int)$pdo->query("SELECT COUNT(*) FROM isletmeler WHERE durum = 'active' AND id IN ($inClause)")->fetchColumn();
    } else {
        $stats['pending_apps'] = 0;
        $stats['active_businesses'] = 0;
    }
}

                                                             
if (canCurrentUser('appointments.view')) {
    if ($allowedBusinessIds === null) {
        $stats['today_appointments'] = (int)$pdo->query("SELECT COUNT(*) FROM randevular WHERE DATE(randevu_zamani) = CURDATE()")->fetchColumn();
        $stats['total_appointments'] = (int)$pdo->query("SELECT COUNT(*) FROM randevular")->fetchColumn();
    } elseif (!empty($allowedBusinessIds)) {
        $inClause = implode(',', array_map('intval', $allowedBusinessIds));
        $stats['today_appointments'] = (int)$pdo->query("SELECT COUNT(*) FROM randevular WHERE DATE(randevu_zamani) = CURDATE() AND isletme_id IN ($inClause)")->fetchColumn();
        $stats['total_appointments'] = (int)$pdo->query("SELECT COUNT(*) FROM randevular WHERE isletme_id IN ($inClause)")->fetchColumn();
    } else {
        $stats['today_appointments'] = 0;
        $stats['total_appointments'] = 0;
    }
}

                                                        
if (canCurrentUser('users.view')) {
    $stats['total_customers'] = (int)$pdo->query("SELECT COUNT(*) FROM kullanicilar")->fetchColumn();
}

                                                               
if (canCurrentUser('reviews.view')) {
    $stats['total_reviews'] = (int)$pdo->query("SELECT COUNT(*) FROM degerlendirmeler")->fetchColumn();
}

                                                                       
$pendingApplications = [];
if (canCurrentUser('businesses.view')) {
    if ($allowedBusinessIds === null) {
        $stmt = $pdo->query("SELECT id, isletme_adi, isletme_sahibi, telefon, eposta, sehir, ilce, olusturulma_tarihi 
                             FROM isletmeler WHERE durum = 'pending' ORDER BY olusturulma_tarihi DESC LIMIT 5");
        $pendingApplications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif (!empty($allowedBusinessIds)) {
        $inClause = implode(',', array_map('intval', $allowedBusinessIds));
        $stmt = $pdo->query("SELECT id, isletme_adi, isletme_sahibi, telefon, eposta, sehir, ilce, olusturulma_tarihi 
                             FROM isletmeler WHERE durum = 'pending' AND id IN ($inClause) ORDER BY olusturulma_tarihi DESC LIMIT 5");
        $pendingApplications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

                                                                   
$recentAppointments = [];
if (canCurrentUser('appointments.view')) {
    if ($allowedBusinessIds === null) {
        $stmt = $pdo->query("SELECT r.id, r.randevu_zamani, r.durum, 
                                    k.ad_soyad AS musteri_adi, k.telefon AS musteri_telefon,
                                    i.isletme_adi
                             FROM randevular r
                             LEFT JOIN kullanicilar k ON r.kullanici_id = k.id
                             LEFT JOIN isletmeler i ON r.isletme_id = i.id
                             ORDER BY r.randevu_zamani DESC LIMIT 5");
        $recentAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif (!empty($allowedBusinessIds)) {
        $inClause = implode(',', array_map('intval', $allowedBusinessIds));
        $stmt = $pdo->query("SELECT r.id, r.randevu_zamani, r.durum, 
                                    k.ad_soyad AS musteri_adi, k.telefon AS musteri_telefon,
                                    i.isletme_adi
                             FROM randevular r
                             LEFT JOIN kullanicilar k ON r.kullanici_id = k.id
                             LEFT JOIN isletmeler i ON r.isletme_id = i.id
                             WHERE r.isletme_id IN ($inClause)
                             ORDER BY r.randevu_zamani DESC LIMIT 5");
        $recentAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

include dirname(__DIR__) . '/admin/includes/header.php';
?>

                                            
<div style="background:linear-gradient(135deg, #1E293B 0%, #0F172A 100%);border:1px solid #334155;border-radius:16px;padding:24px 28px;margin-bottom:28px;color:#fff;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:20px;">
    <div>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
            <span class="status-badge" style="background:rgba(194,136,64,0.2);color:#E5A955;border:1px solid rgba(194,136,64,0.4);font-size:11px;">Yetkili Personel</span>
            <span style="font-size:12px;color:#94A3B8;">Kapsam: <strong><?= $manager['kapsam_turu'] === 'tum_isletmeler' ? 'Tüm İşletmeler' : count($allowedBusinessIds ?? []) . ' Seçili İşletme' ?></strong></span>
        </div>
        <h2 style="font-size:22px;font-weight:800;color:#F8FAFC;margin:0 0 4px;">Hoş Geldiniz, <?= e($manager['ad_soyad']) ?></h2>
        <p style="font-size:13px;color:#94A3B8;margin:0;">Sistemde toplam <strong><?= count($myPermissions) ?></strong> aktif modül izniniz tanımlanmıştır.</p>
    </div>
    <div style="display:flex;gap:10px;">
        <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('myPermsModal').style.display='flex';" style="background:rgba(255,255,255,0.08);color:#fff;border-color:rgba(255,255,255,0.15);">
            <i class="fa-solid fa-shield-halved" style="color:#E5A955;"></i> İzinlerimi Görüntüle
        </button>
    </div>
</div>

                                                                
<div class="stat-cards">
    <?php if (canCurrentUser('businesses.view')): ?>
        <div class="stat-card" onclick="window.location.href='/admin/pages/applications.php'">
            <div class="stat-icon warning">
                <i class="fa-solid fa-clock"></i>
            </div>
            <div class="stat-value"><?= number_format($stats['pending_apps'] ?? 0) ?></div>
            <div class="stat-label">Bekleyen Başvuru</div>
            <?php if (($stats['pending_apps'] ?? 0) > 0): ?>
                <span class="status-badge pending" style="margin-top:8px;font-size:11px;">İnceleme Bekliyor</span>
            <?php endif; ?>
        </div>

        <div class="stat-card" onclick="window.location.href='/admin/pages/businesses.php'">
            <div class="stat-icon success">
                <i class="fa-solid fa-store"></i>
            </div>
            <div class="stat-value"><?= number_format($stats['active_businesses'] ?? 0) ?></div>
            <div class="stat-label">Aktif Berber / Salon</div>
        </div>
    <?php endif; ?>

    <?php if (canCurrentUser('users.view')): ?>
        <div class="stat-card" onclick="window.location.href='/admin/pages/customers.php'">
            <div class="stat-icon info">
                <i class="fa-solid fa-users"></i>
            </div>
            <div class="stat-value"><?= number_format($stats['total_customers'] ?? 0) ?></div>
            <div class="stat-label">Kayıtlı Müşteri</div>
        </div>
    <?php endif; ?>

    <?php if (canCurrentUser('appointments.view')): ?>
        <div class="stat-card" onclick="window.location.href='/admin/pages/appointments.php'">
            <div class="stat-icon accent">
                <i class="fa-solid fa-calendar-check"></i>
            </div>
            <div class="stat-value"><?= number_format($stats['today_appointments'] ?? 0) ?></div>
            <div class="stat-label">Bugünkü Randevular</div>
            <small style="color:var(--admin-text-muted);font-size:11px;">Toplam: <?= number_format($stats['total_appointments'] ?? 0) ?></small>
        </div>
    <?php endif; ?>
</div>

                                                                             
<div class="admin-panel" style="margin-bottom:24px;">
    <div class="panel-header">
        <h2><i class="fa-solid fa-bolt"></i><span>Yetkili İşlemler</span></h2>
    </div>
    <div class="panel-body padded">
        <div style="display:flex;flex-wrap:wrap;gap:12px;">
            <?php if (canCurrentUser('businesses.view')): ?>
                <a href="/admin/pages/applications.php" class="btn btn-secondary">
                    <i class="fa-solid fa-file-circle-check" style="color:var(--admin-warning);"></i>
                    <span>Başvuru İnceleme</span>
                </a>
            <?php endif; ?>

            <?php if (canCurrentUser('businesses.view')): ?>
                <a href="/admin/pages/businesses.php" class="btn btn-secondary">
                    <i class="fa-solid fa-store" style="color:var(--admin-success);"></i>
                    <span>İşletme Listesi</span>
                </a>
            <?php endif; ?>

            <?php if (canCurrentUser('appointments.view')): ?>
                <a href="/admin/pages/appointments.php" class="btn btn-secondary">
                    <i class="fa-solid fa-calendar-days" style="color:var(--admin-info);"></i>
                    <span>Randevu Takibi</span>
                </a>
            <?php endif; ?>

            <?php if (canCurrentUser('content.edit')): ?>
                <a href="/admin/pages/content.php" class="btn btn-secondary">
                    <i class="fa-solid fa-pen-to-square" style="color:var(--admin-accent);"></i>
                    <span>İçerik Düzenleme</span>
                </a>
            <?php endif; ?>

            <?php if (canCurrentUser('reports.view')): ?>
                <a href="/admin/pages/reports.php" class="btn btn-secondary">
                    <i class="fa-solid fa-chart-pie" style="color:var(--admin-accent);"></i>
                    <span>Raporlar & Analiz</span>
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (canCurrentUser('businesses.view') && !empty($pendingApplications)): ?>
                                                                      
    <div class="admin-panel" style="margin-bottom:24px;">
        <div class="panel-header">
            <h2>
                <i class="fa-solid fa-file-circle-check"></i>
                <span>İnceleme Bekleyen İşletme Başvuruları</span>
            </h2>
            <a href="/admin/pages/applications.php" class="btn btn-secondary btn-sm">Tümünü Gör</a>
        </div>
        <div class="panel-body">
            <div style="overflow-x:auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>İşletme Adı</th>
                            <th>Sahibi</th>
                            <th>Konum</th>
                            <th>Telefon</th>
                            <th>Tarih</th>
                            <th style="text-align:right;">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingApplications as $app): ?>
                            <tr>
                                <td><strong><?= e($app['isletme_adi']) ?></strong></td>
                                <td><?= e($app['isletme_sahibi']) ?></td>
                                <td><?= e($app['sehir']) ?> / <?= e($app['ilce']) ?></td>
                                <td><a href="tel:<?= e($app['telefon']) ?>"><?= e($app['telefon']) ?></a></td>
                                <td><?= date('d.m.Y H:i', strtotime($app['olusturulma_tarihi'])) ?></td>
                                <td style="text-align:right;">
                                    <a href="/admin/pages/applications.php?detail=<?= $app['id'] ?>" class="btn btn-primary btn-sm">İncele</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (canCurrentUser('appointments.view') && !empty($recentAppointments)): ?>
                                                     
    <div class="admin-panel" style="margin-bottom:24px;">
        <div class="panel-header">
            <h2><i class="fa-solid fa-calendar-check"></i><span>Son Randevular</span></h2>
            <a href="/admin/pages/appointments.php" class="btn btn-secondary btn-sm">Tüm Randevular</a>
        </div>
        <div class="panel-body">
            <div style="overflow-x:auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Müşteri</th>
                            <th>İşletme</th>
                            <th>Zaman</th>
                            <th>Durum</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentAppointments as $apt): ?>
                            <tr>
                                <td><strong><?= e($apt['musteri_adi'] ?? 'Kayıtsız') ?></strong></td>
                                <td><?= e($apt['isletme_adi'] ?? '-') ?></td>
                                <td><?= date('d.m.Y H:i', strtotime($apt['randevu_zamani'])) ?></td>
                                <td><span class="status-badge <?= $apt['durum'] === 'confirmed' ? 'active' : ($apt['durum'] === 'cancelled' ? 'suspended' : 'pending') ?>"><?= e($apt['durum']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

                                 
<div id="myPermsModal" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,0.6);z-index:999;align-items:center;justify-content:center;padding:20px;">
    <div style="background:#fff;border-radius:16px;width:100%;max-width:540px;box-shadow:0 25px 60px rgba(0,0,0,0.3);overflow:hidden;">
        <div style="padding:20px 24px;border-bottom:1px solid #E2E8F0;display:flex;justify-content:space-between;align-items:center;">
            <h3 style="font-size:16px;font-weight:800;color:#0F172A;margin:0;">Aktif Yönetici İzinleriniz</h3>
            <button type="button" onclick="document.getElementById('myPermsModal').style.display='none';" style="background:none;border:none;font-size:22px;cursor:pointer;color:#64748B;">&times;</button>
        </div>
        <div style="padding:24px;max-height:400px;overflow-y:auto;">
            <?php if (empty($myPermissions)): ?>
                <p style="color:#64748B;font-size:13px;">Henüz adınıza atanmış bir izin bulunmuyor. Sistem yöneticiniz ile iletişime geçin.</p>
            <?php else: ?>
                <div style="display:grid;grid-template-columns:1fr;gap:8px;">
                    <?php foreach ($myPermissions as $permKey): ?>
                        <div style="display:flex;align-items:center;gap:10px;padding:8px 12px;background:#F8FAFC;border-radius:8px;border:1px solid #E2E8F0;font-size:13px;">
                            <i class="fa-solid fa-circle-check" style="color:var(--admin-success);"></i>
                            <span style="font-weight:600;color:#1E293B;"><?= e($allPermissionsCatalog[$permKey]['label'] ?? $permKey) ?></span>
                            <code style="margin-left:auto;font-size:10px;color:#64748B;"><?= e($permKey) ?></code>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/admin/includes/footer.php'; ?>
