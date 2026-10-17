<?php
require_once __DIR__ . '/_common.php';
$currentBusinessPage = 'reviews';
$pageTitle = 'Müşteri Yorumları';
$errors = [];
$ratingFilter = filter_var($_GET['rating'] ?? null, FILTER_VALIDATE_INT);
$ratingFilter = $ratingFilter !== false && $ratingFilter >= 1 && $ratingFilter <= 5 ? $ratingFilter : 0;
$replyFilter = is_string($_GET['reply'] ?? null) && in_array($_GET['reply'], ['answered','unanswered','all'], true) ? $_GET['reply'] : 'all';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    businessRequirePost();
    try {
        $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
        $reviewId = filter_var($_POST['review_id'] ?? null, FILTER_VALIDATE_INT);
        $stmt = $pdo->prepare("SELECT d.id, d.randevu_id, d.degerlendiren_id, d.yorum, r.isletme_id
                               FROM degerlendirmeler d INNER JOIN randevular r ON r.id = d.randevu_id
                               WHERE d.id = ? AND r.isletme_id = ? AND d.degerlendiren_turu = 'customer'");
        $stmt->execute([$reviewId, $businessId]);
        $review = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$review) {
            throw new RuntimeException('Yorum bu işletmeye ait değil veya bulunamadı.');
        }
        if ($action === 'reply') {
            $reply = trim(is_string($_POST['reply'] ?? null) ? $_POST['reply'] : '');
            if ($reply === '' || mb_strlen($reply, 'UTF-8') > 2000) {
                throw new RuntimeException('Yanıt 1-2000 karakter arasında olmalıdır.');
            }
            $stmt = $pdo->prepare('SELECT id FROM isletme_yorum_cevaplari WHERE degerlendirme_id = ? AND isletme_id = ?');
            $stmt->execute([$reviewId, $businessId]);
            $replyId = $stmt->fetchColumn();
            if ($replyId) {
                $pdo->prepare('UPDATE isletme_yorum_cevaplari SET cevap = ? WHERE id = ? AND isletme_id = ?')
                    ->execute([$reply, $replyId, $businessId]);
            } else {
                $pdo->prepare('INSERT INTO isletme_yorum_cevaplari (degerlendirme_id, isletme_id, cevap) VALUES (?, ?, ?)')
                    ->execute([$reviewId, $businessId, $reply]);
                $customerNotice = $pdo->prepare('INSERT INTO kullanici_bildirimleri (kullanici_id, baslik, mesaj, tur, link) VALUES (?, ?, ?, ?, ?)');
                $customerNotice->execute([
                    (int)$review['degerlendiren_id'],
                    'İşletme yorumunuza yanıt verdi',
                    $business['isletme_adi'] . ' yorumunuza yanıt verdi.',
                    'review_reply',
                    '/musteri/hesabim.php',
                ]);
            }
            businessFlash('success', 'İşletme yanıtı kaydedildi. Müşteri bu yoruma yeni bir yanıt ekleyemez.');
        } elseif ($action === 'delete_reply') {
            $stmt = $pdo->prepare('DELETE FROM isletme_yorum_cevaplari WHERE degerlendirme_id = ? AND isletme_id = ?');
            $stmt->execute([$reviewId, $businessId]);
            businessFlash('success', 'İşletme yanıtı silindi. Müşterinin asıl yorumu korunur.');
        } elseif ($action === 'report') {
            $reason = trim(is_string($_POST['reason'] ?? null) ? $_POST['reason'] : '');
            if ($reason === '' || mb_strlen($reason, 'UTF-8') > 500) {
                throw new RuntimeException('Şikâyet nedeni 1-500 karakter olmalıdır.');
            }
            $pdo->prepare('INSERT INTO isletme_yorum_sikayetleri (degerlendirme_id, isletme_id, neden) VALUES (?, ?, ?)
                           ON DUPLICATE KEY UPDATE neden = VALUES(neden), durum = "pending"')
                ->execute([$reviewId, $businessId, $reason]);
            $pdo->prepare('INSERT INTO bildirimler (baslik, mesaj, tur, link) VALUES (?, ?, ?, ?)')
                ->execute(['İşletme yorumu bildirimi', $business['isletme_adi'] . ' bir müşteri yorumunun incelenmesini istedi.', 'review_report', '/admin/pages/customers.php']);
            businessFlash('success', 'Yorum moderatöre bildirildi.');
        } else {
            throw new RuntimeException('İşlem tanınmadı.');
        }
        businessRedirect('yorumlar.php');
    } catch (Throwable $e) {
        error_log('Business review action failed: ' . $e->getMessage());
        $errors[] = $e instanceof RuntimeException ? $e->getMessage() : 'Yorum işlemi tamamlanamadı.';
    }
}

$where = ['r.isletme_id = ?', "d.degerlendiren_turu = 'customer'"];
$params = [$businessId];
if ($ratingFilter > 0) {
    $where[] = 'd.puan = ?';
    $params[] = $ratingFilter;
}
if ($replyFilter === 'answered') {
    $where[] = 'a.id IS NOT NULL';
} elseif ($replyFilter === 'unanswered') {
    $where[] = 'a.id IS NULL';
}
$stmt = $pdo->prepare("SELECT d.id, d.puan, d.yorum, d.olusturulma_tarihi, d.degerlendiren_id, k.ad_soyad AS musteri_adi,
                              a.cevap, a.guncelleme_tarihi AS cevap_guncelleme,
                              s.durum AS sikayet_durumu, s.neden AS sikayet_nedeni,
                              NULL AS profil_fotografi
                       FROM degerlendirmeler d
                       INNER JOIN randevular r ON r.id = d.randevu_id
                       LEFT JOIN kullanicilar k ON k.id = d.degerlendiren_id
                       LEFT JOIN isletme_yorum_cevaplari a ON a.degerlendirme_id = d.id AND a.isletme_id = r.isletme_id
                       LEFT JOIN isletme_yorum_sikayetleri s ON s.degerlendirme_id = d.id AND s.isletme_id = r.isletme_id
                       WHERE " . implode(' AND ', $where) . " ORDER BY d.olusturulma_tarihi DESC LIMIT 200");
$stmt->execute($params);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
include __DIR__ . '/_header.php';
?>
<?php foreach ($errors as $error): ?><div class="business-notice error"><?= businessEscape($error) ?></div><?php endforeach; ?>
<section class="admin-panel">
    <div class="panel-header"><h2><i class="fa-solid fa-filter"></i><span>Yorum filtreleri</span></h2></div>
    <div class="panel-body padded">
        <form method="GET" class="business-form-grid admin-form">
            <div class="form-group"><label>Puan</label><select name="rating"><option value="0">Tüm puanlar</option><?php for ($i=5;$i>=1;$i--): ?><option value="<?= $i ?>" <?= $ratingFilter === $i ? 'selected' : '' ?>><?= $i ?> yıldız</option><?php endfor; ?></select></div>
            <div class="form-group"><label>Yanıt durumu</label><select name="reply"><option value="all">Tümü</option><option value="answered" <?= $replyFilter === 'answered' ? 'selected' : '' ?>>Yanıtlanan</option><option value="unanswered" <?= $replyFilter === 'unanswered' ? 'selected' : '' ?>>Yanıtlanmayan</option></select></div>
            <div class="form-group" style="align-self:end"><button class="btn-admin primary">Filtrele</button></div>
        </form>
    </div>
</section>
<section class="admin-panel">
    <div class="panel-header"><h2><i class="fa-solid fa-star"></i><span>Müşteri yorumları</span></h2><span class="status-badge"><?= count($reviews) ?> yorum</span></div>
    <div class="panel-body padded">
        <?php if (!$reviews): ?><div class="empty-state"><i class="fa-regular fa-comment-dots"></i><h4>Yorum bulunamadı</h4></div><?php endif; ?>
        <?php foreach ($reviews as $review): ?>
            <article style="padding:18px 0;border-bottom:1px solid #E2E8F0;">
                <div style="display:flex;gap:12px;align-items:center;">
                    <?php if ($review['profil_fotografi']): ?><img src="/<?= businessEscape($review['profil_fotografi']) ?>" alt="" style="width:42px;height:42px;border-radius:50%;object-fit:cover;"><?php else: ?><div class="avatar"><?= businessEscape(mb_strtoupper(mb_substr($review['musteri_adi'] ?? 'M',0,1,'UTF-8'),'UTF-8')) ?></div><?php endif; ?>
                    <div><strong><?= businessEscape($review['musteri_adi'] ?? 'Müşteri') ?></strong><div class="review-stars" aria-label="<?= (int)$review['puan'] ?> yıldız"><?= str_repeat('★',(int)$review['puan']) ?><?= str_repeat('☆',5-(int)$review['puan']) ?></div></div>
                    <time style="margin-left:auto;color:#64748B;font-size:12px;"><?= date('d.m.Y H:i',strtotime($review['olusturulma_tarihi'])) ?></time>
                </div>
                <p style="margin:14px 0;white-space:pre-wrap;"><?= businessEscape($review['yorum'] ?? 'Yorum metni yok.') ?></p>
                <?php if ($review['sikayet_durumu']): ?><span class="status-badge pending">Moderasyon bildirildi: <?= businessEscape($review['sikayet_durumu']) ?></span><?php endif; ?>
                <?php if ($review['cevap']): ?><div style="padding:12px 14px;border-left:3px solid #C28840;background:#FDF8F3;margin:12px 0;"><strong>İşletme yanıtı</strong><p style="margin:6px 0 0;white-space:pre-wrap;"><?= businessEscape($review['cevap']) ?></p></div><?php endif; ?>
                <div class="business-actions">
                    <details><summary class="btn-admin outline sm"><?= $review['cevap'] ? 'Yanıtı düzenle' : 'Yanıtla' ?></summary>
                        <form method="POST" class="admin-form" style="min-width:280px;padding:12px;">
                            <input type="hidden" name="csrf_token" value="<?= businessEscape(generateCsrfToken()) ?>"><input type="hidden" name="action" value="reply"><input type="hidden" name="review_id" value="<?= (int)$review['id'] ?>">
                            <div class="form-group"><label>İşletme yanıtı</label><textarea name="reply" maxlength="2000" required><?= businessEscape($review['cevap'] ?? '') ?></textarea></div>
                            <button class="btn-admin primary sm">Yanıtı kaydet</button>
                        </form>
                    </details>
                    <?php if ($review['cevap']): ?><form method="POST" onsubmit="return confirm('İşletme yanıtı silinsin mi?')"><input type="hidden" name="csrf_token" value="<?= businessEscape(generateCsrfToken()) ?>"><input type="hidden" name="action" value="delete_reply"><input type="hidden" name="review_id" value="<?= (int)$review['id'] ?>"><button class="btn-admin danger sm">Yanıtı sil</button></form><?php endif; ?>
                    <details><summary class="btn-admin outline sm">Moderatöre bildir</summary>
                        <form method="POST" class="admin-form" style="min-width:260px;padding:12px;">
                            <input type="hidden" name="csrf_token" value="<?= businessEscape(generateCsrfToken()) ?>"><input type="hidden" name="action" value="report"><input type="hidden" name="review_id" value="<?= (int)$review['id'] ?>">
                            <div class="form-group"><label>Bildirim nedeni</label><textarea name="reason" maxlength="500" required></textarea></div><button class="btn-admin warning sm">Bildir</button>
                        </form>
                    </details>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php include __DIR__ . '/_footer.php'; ?>
