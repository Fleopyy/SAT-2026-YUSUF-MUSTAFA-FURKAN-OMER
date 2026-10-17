<?php
   
                                                                  
                                                                                    
   

require_once __DIR__ . '/includes/auth.php';

                                                                                                       
$admin = requireAdmin();
$currentPage = 'dashboard';
$pageTitle = 'Dashboard';
$pageSubtitle = 'Sistem Durumu ve Canlı İstatistikler';

                                     
$stats = [
    'pending_apps' => 0,
    'active_businesses' => 0,
    'total_customers' => 0,
    'today_appointments' => 0,
    'total_appointments' => 0,
];

try {
    $stats['pending_apps'] = (int)$pdo->query("SELECT COUNT(*) FROM isletmeler WHERE durum = 'pending'")->fetchColumn();
    $stats['active_businesses'] = (int)$pdo->query("SELECT COUNT(*) FROM isletmeler WHERE durum = 'active'")->fetchColumn();
    $stats['total_customers'] = (int)$pdo->query("SELECT COUNT(*) FROM kullanicilar")->fetchColumn();
    $stats['today_appointments'] = (int)$pdo->query("SELECT COUNT(*) FROM randevular WHERE DATE(randevu_zamani) = CURDATE()")->fetchColumn();
    $stats['total_appointments'] = (int)$pdo->query("SELECT COUNT(*) FROM randevular")->fetchColumn();
} catch (Exception $e) {}

                           
$pendingApplications = [];
try {
    $stmt = $pdo->query("SELECT id, isletme_adi, isletme_sahibi, telefon, eposta, sehir, ilce, olusturulma_tarihi 
                         FROM isletmeler 
                         WHERE durum = 'pending' 
                         ORDER BY olusturulma_tarihi DESC 
                         LIMIT 5");
    $pendingApplications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

                 
$recentAppointments = [];
try {
    $stmt = $pdo->query("SELECT r.id, r.randevu_zamani, r.durum, 
                                k.ad_soyad AS musteri_adi, k.telefon AS musteri_telefon,
                                i.isletme_adi
                         FROM randevular r
                         LEFT JOIN kullanicilar k ON r.kullanici_id = k.id
                         LEFT JOIN isletmeler i ON r.isletme_id = i.id
                         ORDER BY r.randevu_zamani DESC
                         LIMIT 5");
    $recentAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

                                 
$recentLogs = [];
try {
    $stmt = $pdo->query("SELECT l.id, l.islem_turu, l.detay, l.ip_adresi, l.olusturulma_tarihi,
                                y.ad_soyad AS yonetici_adi, y.rol AS yonetici_rol
                         FROM islem_gunlukleri l
                         LEFT JOIN yoneticiler y ON l.yonetici_id = y.id
                         ORDER BY l.olusturulma_tarihi DESC
                         LIMIT 6");
    $recentLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

include __DIR__ . '/includes/header.php';
?>

                              
<div class="stat-cards">
    <div class="stat-card" onclick="window.location.href='/admin/pages/applications.php'">
        <div class="stat-icon warning">
            <i class="fa-solid fa-clock"></i>
        </div>
        <div class="stat-value"><?= number_format($stats['pending_apps']) ?></div>
        <div class="stat-label">Bekleyen Başvuru</div>
        <?php if ($stats['pending_apps'] > 0): ?>
            <span class="status-badge pending" style="margin-top:8px;font-size:11px;">İnceleme Bekliyor</span>
        <?php endif; ?>
    </div>

    <div class="stat-card" onclick="window.location.href='/admin/pages/businesses.php'">
        <div class="stat-icon success">
            <i class="fa-solid fa-store"></i>
        </div>
        <div class="stat-value"><?= number_format($stats['active_businesses']) ?></div>
        <div class="stat-label">Aktif Berber / Salon</div>
    </div>

    <div class="stat-card" onclick="window.location.href='/admin/pages/customers.php'">
        <div class="stat-icon info">
            <i class="fa-solid fa-users"></i>
        </div>
        <div class="stat-value"><?= number_format($stats['total_customers']) ?></div>
        <div class="stat-label">Kayıtlı Müşteri</div>
    </div>

    <div class="stat-card" onclick="window.location.href='/admin/pages/appointments.php'">
        <div class="stat-icon accent">
            <i class="fa-solid fa-calendar-check"></i>
        </div>
        <div class="stat-value"><?= number_format($stats['today_appointments']) ?></div>
        <div class="stat-label">Bugünkü Randevular</div>
        <small style="color:var(--admin-text-muted);font-size:11px;">Toplam: <?= number_format($stats['total_appointments']) ?></small>
    </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:24px;margin-bottom:28px;">
                                         
    <div class="admin-panel" style="margin-bottom:0;">
        <div class="panel-header">
            <h2>
                <i class="fa-solid fa-file-circle-check"></i>
                <span>Onay Bekleyen İşletme Başvuruları</span>
                <?php if ($stats['pending_apps'] > 0): ?>
                    <span class="status-badge pending" style="font-size:12px;"><?= $stats['pending_apps'] ?> Yeni</span>
                <?php endif; ?>
            </h2>
            <a href="/admin/pages/applications.php" class="btn btn-secondary btn-sm">
                Tümünü Gör <i class="fa-solid fa-arrow-right" style="font-size:11px;margin-left:4px;"></i>
            </a>
        </div>
        <div class="panel-body">
            <?php if (empty($pendingApplications)): ?>
                <div style="padding:40px 20px;text-align:center;color:var(--admin-text-muted);">
                    <i class="fa-solid fa-check-circle" style="font-size:36px;color:var(--admin-success);margin-bottom:12px;display:block;"></i>
                    <p style="font-size:14px;font-weight:600;">Harika! İncelenmeyi bekleyen yeni başvuru bulunmuyor.</p>
                </div>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>İşletme Adı</th>
                                <th>Sahibi</th>
                                <th>Şehir / İlçe</th>
                                <th>Telefon</th>
                                <th>Başvuru Tarihi</th>
                                <th style="text-align:right;">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingApplications as $app): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight:700;color:var(--admin-text);"><?= e($app['isletme_adi']) ?></div>
                                        <small style="color:var(--admin-text-muted);"><?= e($app['eposta']) ?></small>
                                    </td>
                                    <td><?= e($app['isletme_sahibi']) ?></td>
                                    <td><?= e($app['sehir']) ?> / <?= e($app['ilce']) ?></td>
                                    <td>
                                        <a href="tel:<?= e($app['telefon']) ?>" style="color:var(--admin-accent);font-weight:600;">
                                            <?= e($app['telefon']) ?>
                                        </a>
                                    </td>
                                    <td><?= date('d.m.Y H:i', strtotime($app['olusturulma_tarihi'])) ?></td>
                                    <td style="text-align:right;">
                                        <a href="/admin/pages/applications.php?detail=<?= $app['id'] ?>" class="btn btn-primary btn-sm">
                                            İncele
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

                                      
    <div class="admin-panel" style="margin-bottom:0;">
        <div class="panel-header">
            <h2>
                <i class="fa-solid fa-bolt"></i>
                <span>Hızlı İşlemler</span>
            </h2>
        </div>
        <div class="panel-body padded">
            <div style="display:flex;flex-direction:column;gap:12px;">
                <a href="/admin/pages/applications.php" class="btn btn-secondary" style="justify-content:flex-start;padding:12px 16px;">
                    <i class="fa-solid fa-file-circle-check" style="color:var(--admin-warning);"></i>
                    <span>Başvuru Onay/Red İşlemleri</span>
                </a>

                <a href="/admin/pages/managers.php?action=create" class="btn btn-secondary" style="justify-content:flex-start;padding:12px 16px;">
                    <i class="fa-solid fa-user-plus" style="color:var(--admin-info);"></i>
                    <span>Yeni Yönetici Ekle</span>
                </a>

                <a href="/admin/pages/content.php" class="btn btn-secondary" style="justify-content:flex-start;padding:12px 16px;">
                    <i class="fa-solid fa-sliders" style="color:var(--admin-accent);"></i>
                    <span>Site Ayarları & SMTP Yapılandırma</span>
                </a>

                <a href="/admin/pages/appointments.php" class="btn btn-secondary" style="justify-content:flex-start;padding:12px 16px;">
                    <i class="fa-solid fa-calendar-days" style="color:var(--admin-success);"></i>
                    <span>Tüm Randevuları Listele</span>
                </a>
            </div>

                                         
            <div style="margin-top:24px;padding-top:18px;border-top:1px solid var(--admin-border);">
                <div style="font-size:12px;font-weight:700;color:var(--admin-text-muted);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:12px;">
                    Sistem Güvenlik & Durum
                </div>
                <div style="display:flex;justify-content:space-between;font-size:13px;padding:6px 0;">
                    <span style="color:var(--admin-text-muted);">Veritabanı:</span>
                    <span style="font-weight:700;color:var(--admin-success);"><i class="fa-solid fa-circle-check"></i> Bağlı (MySQL)</span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:13px;padding:6px 0;">
                    <span style="color:var(--admin-text-muted);">Admin Paneli:</span>
                    <span style="font-weight:700;color:var(--admin-accent);"><i class="fa-solid fa-shield-halved"></i> Yalıtılmış</span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:13px;padding:6px 0;">
                    <span style="color:var(--admin-text-muted);">Oturum Açan:</span>
                    <span style="font-weight:700;color:var(--admin-accent);"><?= e($admin['ad_soyad']) ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
                           
    <div class="admin-panel">
        <div class="panel-header">
            <h2>
                <i class="fa-solid fa-calendar-check"></i>
                <span>Son Randevular</span>
            </h2>
            <a href="/admin/pages/appointments.php" class="btn btn-secondary btn-sm">Tüm Randevular</a>
        </div>
        <div class="panel-body">
            <?php if (empty($recentAppointments)): ?>
                <div style="padding:32px;text-align:center;color:var(--admin-text-muted);font-size:13px;">
                    Henüz kayıtlı randevu bulunmuyor.
                </div>
            <?php else: ?>
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
                                    <td>
                                        <div style="font-weight:600;"><?= e($apt['musteri_adi'] ?? 'Kayıtsız') ?></div>
                                        <small style="color:var(--admin-text-muted);"><?= e($apt['musteri_telefon'] ?? '') ?></small>
                                    </td>
                                    <td><?= e($apt['isletme_adi'] ?? '-') ?></td>
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
                                        $label = match($st) {
                                            'confirmed' => 'Onaylandı',
                                            'completed' => 'Tamamlandı',
                                            'cancelled' => 'İptal',
                                            'rejected' => 'Reddedildi',
                                            default => 'Bekliyor'
                                        };
                                        ?>
                                        <span class="status-badge <?= $badgeClass ?>"><?= $label ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

                                                 
    <div class="admin-panel">
        <div class="panel-header">
            <h2>
                <i class="fa-solid fa-clock-rotate-left"></i>
                <span>Son Sistem İşlem Kayıtları</span>
            </h2>
            <a href="/admin/pages/logs.php" class="btn btn-secondary btn-sm">Tüm Kayıtlar</a>
        </div>
        <div class="panel-body">
            <?php if (empty($recentLogs)): ?>
                <div style="padding:32px;text-align:center;color:var(--admin-text-muted);font-size:13px;">
                    Henüz kayıtlı bir işlem günlüğü bulunmuyor.
                </div>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Yönetici</th>
                                <th>İşlem</th>
                                <th>Detay</th>
                                <th>Zaman</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentLogs as $log): ?>
                                <tr>
                                    <td>
                                        <strong><?= e($log['yonetici_adi'] ?? 'Sistem') ?></strong>
                                    </td>
                                    <td>
                                        <code style="background:#F1F5F9;padding:2px 6px;border-radius:4px;font-size:11px;"><?= e($log['islem_turu']) ?></code>
                                    </td>
                                    <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= e($log['detay'] ?? '') ?>">
                                        <?= e($log['detay'] ?? '-') ?>
                                    </td>
                                    <td><small style="color:var(--admin-text-muted);"><?= date('d.m.Y H:i', strtotime($log['olusturulma_tarihi'])) ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
