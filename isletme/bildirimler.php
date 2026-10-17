<?php
require_once __DIR__ . '/_common.php';
$currentBusinessPage = 'notifications';
$pageTitle = 'Bildirimler';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    businessRequirePost();
    $notificationId = filter_var($_POST['notification_id'] ?? null, FILTER_VALIDATE_INT);
    if ($notificationId) {
        $stmt = $pdo->prepare('UPDATE isletme_bildirimleri SET okundu = 1 WHERE id = ? AND isletme_id = ?');
        $stmt->execute([$notificationId, $businessId]);
    } else {
        $pdo->prepare('UPDATE isletme_bildirimleri SET okundu = 1 WHERE isletme_id = ?')->execute([$businessId]);
    }
    businessRedirect('bildirimler.php');
}

$stmt = $pdo->prepare('SELECT * FROM isletme_bildirimleri WHERE isletme_id = ? ORDER BY olusturulma_tarihi DESC LIMIT 200');
$stmt->execute([$businessId]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
include __DIR__ . '/_header.php';
?>
<section class="admin-panel">
    <div class="panel-header"><h2><i class="fa-solid fa-bell"></i><span>Bildirim merkezi</span></h2>
        <form method="POST"><input type="hidden" name="csrf_token" value="<?= businessEscape(generateCsrfToken()) ?>"><button class="btn-admin outline sm">Tümünü okundu işaretle</button></form>
    </div>
    <div class="panel-body padded">
        <?php if (!$notifications): ?><div class="empty-state"><i class="fa-regular fa-bell"></i><h4>Henüz bildirim yok</h4><p>Randevu ve değerlendirme bildirimleri burada görünecek.</p></div>
        <?php else: ?><div style="display:grid;gap:10px;">
            <?php foreach ($notifications as $notification): ?>
                <article style="display:flex;gap:14px;align-items:flex-start;padding:14px;border:1px solid #E2E8F0;border-radius:10px;background:<?= $notification['okundu'] ? '#fff' : '#FFFBEB' ?>;">
                    <i class="fa-solid <?= $notification['okundu'] ? 'fa-circle-check' : 'fa-circle-exclamation' ?>" style="color:<?= $notification['okundu'] ? '#059669' : '#D97706' ?>;margin-top:3px;"></i>
                    <div style="flex:1"><strong><?= businessEscape($notification['baslik']) ?></strong><p style="margin:4px 0;color:#64748B;font-size:13px;"><?= businessEscape($notification['mesaj']) ?></p><small style="color:#94A3B8;"><?= date('d.m.Y H:i',strtotime($notification['olusturulma_tarihi'])) ?></small>
                        <?php if ($notification['link'] && str_starts_with($notification['link'], '/isletme/')): ?><a style="display:block;margin-top:8px;color:#A66E2C;font-weight:700;font-size:12px;" href="<?= businessEscape($notification['link']) ?>">İlgili sayfaya git</a><?php endif; ?>
                    </div>
                    <?php if (!$notification['okundu']): ?><form method="POST"><input type="hidden" name="csrf_token" value="<?= businessEscape(generateCsrfToken()) ?>"><input type="hidden" name="notification_id" value="<?= (int)$notification['id'] ?>"><button class="btn-admin outline sm">Okundu</button></form><?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div><?php endif; ?>
    </div>
</section>
<?php include __DIR__ . '/_footer.php'; ?>
