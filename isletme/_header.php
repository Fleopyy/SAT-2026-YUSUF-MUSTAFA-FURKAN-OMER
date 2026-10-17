<?php
$pageTitle = $pageTitle ?? 'İşletme Paneli';
$stmt = $pdo->prepare('SELECT COUNT(*) FROM isletme_bildirimleri WHERE isletme_id = ? AND okundu = 0');
$stmt->execute([$businessId]);
$unreadBusinessNotifications = (int)$stmt->fetchColumn();
$businessFlashMessage = $_SESSION['business_flash'] ?? null;
unset($_SESSION['business_flash']);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= businessEscape($pageTitle) ?> — BiTıraş</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/admin/assets/admin.css">
    <style>
        .business-content { max-width: 1380px; margin: 0 auto; }
        .business-form-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(230px,1fr)); gap:16px; }
        .business-inline-form { display:inline-flex; align-items:center; gap:6px; flex-wrap:wrap; }
        .business-form-grid .form-group { margin:0; }
        .business-gallery { display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr)); gap:16px; }
        .business-gallery > article { min-width:0; overflow:hidden; }
        .business-gallery img { display:block; width:100%; height:240px; object-fit:contain; object-position:center; background:#F8FAFC; border-radius:10px; }
        .business-actions { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
        .business-status { white-space:nowrap; }
        .business-notice { padding:14px 18px; border-radius:10px; margin-bottom:20px; }
        .business-notice.success { background:#ECFDF5; color:#065F46; border:1px solid #A7F3D0; }
        .business-notice.error { background:#FEF2F2; color:#991B1B; border:1px solid #FECACA; }
        .business-notice.info { background:#EFF6FF; color:#1E40AF; border:1px solid #BFDBFE; }
        .business-topbar-name { max-width:220px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        @media (max-width:768px) {
            .admin-header { padding:0 14px; }
            .admin-content { padding:16px; }
            .business-topbar-name { display:none; }
            .business-gallery { grid-template-columns:repeat(auto-fill,minmax(140px,1fr)); }
        }
    </style>
</head>
<body>
<div class="admin-layout">
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleBusinessSidebar()"></div>
    <aside class="admin-sidebar" id="businessSidebar">
        <div class="sidebar-brand">
            <div class="logo-icon"><i class="fa-solid fa-scissors" style="transform:rotate(90deg)"></i></div>
            <div class="brand-text"><h4>BiTıraş<span>.com</span></h4><small>İşletme Paneli</small></div>
        </div>
        <nav class="sidebar-nav">
            <div class="sidebar-section-label">İşletme Yönetimi</div>
            <?php
            $businessMenu = [
                ['index.php', 'overview', 'Genel Bakış', 'fa-gauge-high'],
                ['profil.php', 'profile', 'İşletme Profilim', 'fa-store'],
                ['fotograflar.php', 'photos', 'Fotoğraflarım', 'fa-images'],
                ['calisanlar.php', 'staff', 'Çalışanlarım', 'fa-users'],
                ['hizmetler.php', 'services', 'Hizmetler ve Fiyatlar', 'fa-scissors'],
                ['calisma-saatleri.php', 'hours', 'Çalışma Saatlerim', 'fa-clock'],
                ['randevular.php', 'appointments', 'Randevularım', 'fa-calendar-days'],
                ['yorumlar.php', 'reviews', 'Müşteri Yorumları', 'fa-star'],
                ['degerlendirmeler.php', 'ratings', 'Müşteri Değerlendirmeleri', 'fa-comment-dots'],
                ['bildirimler.php', 'notifications', 'Bildirimler', 'fa-bell'],
                ['ayarlar.php', 'settings', 'Hesap Ayarları', 'fa-gear'],
            ];
            foreach ($businessMenu as [$url, $key, $label, $icon]):
            ?>
                <div class="sidebar-nav-item">
                    <a class="sidebar-nav-link <?= ($currentBusinessPage ?? '') === $key ? 'active' : '' ?>" href="/isletme/<?= businessEscape($url) ?>">
                        <i class="fa-solid <?= businessEscape($icon) ?>"></i><span><?= businessEscape($label) ?></span>
                        <?php if ($key === 'notifications' && $unreadBusinessNotifications > 0): ?>
                            <span class="badge-count"><?= $unreadBusinessNotifications ?></span>
                        <?php endif; ?>
                    </a>
                </div>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="avatar"><?= businessEscape(mb_strtoupper(mb_substr($business['isletme_adi'], 0, 1, 'UTF-8'), 'UTF-8')) ?></div>
                <div class="user-info"><strong><?= businessEscape($business['isletme_adi']) ?></strong><small>Aktif işletme</small></div>
            </div>
            <form method="POST" action="/isletme/cikis.php" class="logout-form" style="margin-top:8px;">
                <input type="hidden" name="csrf_token" value="<?= businessEscape(generateCsrfToken()) ?>">
                <button type="submit" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i><span>Çıkış Yap</span></button>
            </form>
        </div>
    </aside>
    <div class="admin-main">
        <header class="admin-header">
            <div class="page-title">
                <button type="button" class="header-btn" onclick="toggleBusinessSidebar()" aria-label="Menüyü aç/kapat"><i class="fa-solid fa-bars"></i></button>
                <div><h1><?= businessEscape($pageTitle) ?></h1><span class="breadcrumb-text business-topbar-name"><?= businessEscape($business['isletme_adi']) ?> · <?= businessEscape($business['sehir']) ?></span></div>
            </div>
            <div class="header-actions">
                <a href="/isletme/bildirimler.php" class="header-btn" title="Bildirimler">
                    <i class="fa-regular fa-bell"></i>
                    <?php if ($unreadBusinessNotifications > 0): ?><span class="notification-dot"></span><?php endif; ?>
                </a>
                <a href="/" target="_blank" rel="noopener" class="header-btn" title="Siteyi görüntüle"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
            </div>
        </header>
        <main class="admin-content business-content">
            <?php if ($businessFlashMessage): ?>
                <div class="business-notice <?= businessEscape($businessFlashMessage['type']) ?>" role="status"><?= businessEscape($businessFlashMessage['message']) ?></div>
            <?php endif; ?>
