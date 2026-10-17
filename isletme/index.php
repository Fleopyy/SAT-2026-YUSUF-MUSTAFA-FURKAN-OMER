<?php
require_once __DIR__ . '/_common.php';
$currentBusinessPage = 'overview';
$pageTitle = 'Genel Bakış';

$stmt = $pdo->prepare("SELECT COUNT(*) AS total,
                              COUNT(CASE WHEN DATE(randevu_zamani) = CURDATE() THEN 1 END) AS today,
                              COUNT(CASE WHEN durum = 'pending' THEN 1 END) AS pending,
                              COUNT(CASE WHEN durum = 'confirmed' THEN 1 END) AS confirmed,
                              COUNT(CASE WHEN durum = 'completed' THEN 1 END) AS completed
                       FROM randevular WHERE isletme_id = ?");
$stmt->execute([$businessId]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare('SELECT COUNT(*) FROM calisanlar WHERE isletme_id = ? AND aktif_mi = 1');
$stmt->execute([$businessId]);
$stats['staff'] = (int)$stmt->fetchColumn();
$stmt = $pdo->prepare('SELECT COUNT(*) FROM hizmetler WHERE isletme_id = ? AND aktif_mi = 1');
$stmt->execute([$businessId]);
$stats['services'] = (int)$stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT AVG(d.puan) AS average_rating, COUNT(d.id) AS review_count
                       FROM degerlendirmeler d INNER JOIN randevular r ON r.id = d.randevu_id
                       WHERE r.isletme_id = ? AND d.degerlendiren_turu = 'customer'");
$stmt->execute([$businessId]);
$rating = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT r.id, r.randevu_zamani, r.durum, k.ad_soyad AS customer_name, k.telefon,
                              h.hizmet_adi, h.fiyat, c.ad_soyad AS employee_name, x.fiyat_snapshot, x.bitis_zamani
                       FROM randevular r
                       LEFT JOIN kullanicilar k ON k.id = r.kullanici_id
                       LEFT JOIN hizmetler h ON h.id = r.hizmet_id
                       LEFT JOIN calisanlar c ON c.id = r.calisan_id
                       LEFT JOIN randevu_ek_bilgileri x ON x.randevu_id = r.id
                       WHERE r.isletme_id = ? AND r.durum IN ('pending', 'confirmed')
                       ORDER BY CASE WHEN r.randevu_zamani < NOW() THEN 0 ELSE 1 END, r.randevu_zamani LIMIT 5");
$stmt->execute([$businessId]);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT d.puan, d.yorum, d.olusturulma_tarihi, k.ad_soyad AS customer_name
                       FROM degerlendirmeler d
                       INNER JOIN randevular r ON r.id = d.randevu_id
                       LEFT JOIN kullanicilar k ON k.id = d.degerlendiren_id
                       WHERE r.isletme_id = ? AND d.degerlendiren_turu = 'customer'
                       ORDER BY d.olusturulma_tarihi DESC LIMIT 4");
$stmt->execute([$businessId]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare('SELECT baslik, mesaj, tur, olusturulma_tarihi, okundu FROM isletme_bildirimleri WHERE isletme_id = ? ORDER BY olusturulma_tarihi DESC LIMIT 5');
$stmt->execute([$businessId]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/_header.php';
?>
<section style="background:linear-gradient(135deg,#1E293B,#0F172A);border:1px solid #334155;border-radius:16px;padding:24px 28px;margin-bottom:24px;color:#fff;">
    <span class="status-badge active" style="background:rgba(5,150,105,.18);color:#6EE7B7;border:1px solid rgba(110,231,183,.3)">Aktif işletme</span>
    <h2 style="font-size:22px;color:#F8FAFC;margin:12px 0 4px;">Hoş geldiniz, <?= businessEscape($business['isletme_sahibi']) ?></h2>
    <p style="font-size:13px;color:#CBD5E1;margin:0;"><?= businessEscape($business['isletme_adi']) ?> · <?= businessEscape($business['sehir']) ?> / <?= businessEscape($business['ilce']) ?></p>
</section>
<div class="stat-cards">
    <?php foreach ([
        ['today','Bugünkü Randevu','fa-calendar-day','accent'],
        ['pending','Onay Bekleyen','fa-clock','warning'],
        ['confirmed','Onaylanmış Randevu','fa-calendar-check','info'],
        ['completed','Tamamlanan','fa-check-double','success'],
        ['staff','Aktif Çalışan','fa-users','warning'],
        ['services','Aktif Hizmet','fa-scissors','accent'],
    ] as [$key,$label,$icon,$color]): ?>
        <a class="stat-card" href="<?= in_array($key,['today','pending','confirmed','completed'],true) ? '/isletme/randevular.php?filter=' . ($key === 'today' ? 'today' : $key) : ($key === 'staff' ? '/isletme/calisanlar.php' : '/isletme/hizmetler.php') ?>">
            <div class="stat-icon <?= businessEscape($color) ?>"><i class="fa-solid <?= businessEscape($icon) ?>"></i></div>
            <div class="stat-value"><?= number_format((int)$stats[$key]) ?></div><div class="stat-label"><?= businessEscape($label) ?></div>
        </a>
    <?php endforeach; ?>
    <a class="stat-card" href="/isletme/yorumlar.php">
        <div class="stat-icon info"><i class="fa-solid fa-star"></i></div>
        <div class="stat-value"><?= $rating['average_rating'] !== null ? number_format((float)$rating['average_rating'],1) : '—' ?></div>
        <div class="stat-label">Ortalama müşteri puanı</div><small style="color:#64748B;font-size:11px;"><?= number_format((int)$rating['review_count']) ?> yorum</small>
    </a>
</div>
<section class="admin-panel" id="appointments">
    <div class="panel-header"><h2><i class="fa-solid fa-calendar-days"></i><span>Yaklaşan / işlem bekleyen randevular</span></h2><a class="btn-admin outline sm" href="/isletme/randevular.php">Tüm randevular</a></div>
    <div class="panel-body">
        <?php if (!$appointments): ?><div class="empty-state"><i class="fa-regular fa-calendar"></i><h4>İşlem bekleyen randevu yok</h4></div>
        <?php else: ?><div style="overflow-x:auto;"><table class="admin-table"><thead><tr><th>Müşteri</th><th>Hizmet</th><th>Çalışan</th><th>Tarih</th><th>Durum</th></tr></thead><tbody>
            <?php foreach ($appointments as $appointment): ?><tr>
                <td><strong><?= businessEscape($appointment['customer_name'] ?? 'Müşteri') ?></strong><small style="display:block"><?= businessEscape($appointment['telefon'] ?? '') ?></small></td>
                <td><?= businessEscape($appointment['hizmet_adi'] ?? 'Hizmet') ?><small style="display:block;color:#64748B"><?= number_format((float)($appointment['fiyat_snapshot'] ?? $appointment['fiyat'] ?? 0),2,',','.') ?> ₺</small></td>
                <td><?= businessEscape($appointment['employee_name'] ?? '—') ?></td>
                <td><?= date('d.m.Y H:i',strtotime($appointment['randevu_zamani'])) ?></td>
                <td><span class="status-badge <?= $appointment['durum'] === 'confirmed' ? 'active' : 'pending' ?>"><?= $appointment['durum'] === 'confirmed' ? 'Onaylandı' : 'Bekliyor' ?></span></td>
            </tr><?php endforeach; ?>
        </tbody></table></div><?php endif; ?>
    </div>
</section>
<div class="grid-2">
    <section class="admin-panel" id="reviews">
        <div class="panel-header"><h2><i class="fa-solid fa-star"></i><span>Son müşteri yorumları</span></h2><a class="btn-admin outline sm" href="/isletme/yorumlar.php">Tüm yorumlar</a></div>
        <div class="panel-body padded">
            <?php if (!$reviews): ?><div class="empty-state"><i class="fa-regular fa-comment-dots"></i><h4>Henüz yorum yok</h4></div>
            <?php else: foreach ($reviews as $review): ?><article style="padding:12px 0;border-bottom:1px solid #E2E8F0"><strong><?= businessEscape($review['customer_name'] ?? 'Müşteri') ?></strong> <span style="color:#D97706"><?= str_repeat('★',(int)$review['puan']) ?></span><small style="float:right;color:#64748B"><?= date('d.m.Y',strtotime($review['olusturulma_tarihi'])) ?></small><p style="margin:8px 0 0"><?= businessEscape($review['yorum'] ?? '') ?></p></article><?php endforeach; endif; ?>
        </div>
    </section>
    <section class="admin-panel" id="notifications">
        <div class="panel-header"><h2><i class="fa-solid fa-bell"></i><span>Son bildirimler</span><a class="btn-admin outline sm" href="/isletme/bildirimler.php">Tüm bildirimler</a></h2></div>
        <div class="panel-body padded">
            <?php if (!$notifications): ?><div class="empty-state"><i class="fa-regular fa-bell"></i><h4>Yeni bildirim yok</h4></div>
            <?php else: foreach ($notifications as $notification): ?><article style="padding:12px 0;border-bottom:1px solid #E2E8F0"><strong><?= businessEscape($notification['baslik']) ?></strong><?php if (!$notification['okundu']): ?> <span class="status-badge pending">Yeni</span><?php endif; ?><p style="margin:5px 0;color:#64748B;font-size:13px"><?= businessEscape($notification['mesaj']) ?></p><small style="color:#94A3B8"><?= date('d.m.Y H:i',strtotime($notification['olusturulma_tarihi'])) ?></small></article><?php endforeach; endif; ?>
        </div>
    </section>
</div>
<?php include __DIR__ . '/_footer.php'; ?>
