<?php
   
                                                 
   

$admin = getAuthenticatedAdmin() ?? getAuthenticatedManager();
$adminName = $admin['ad_soyad'] ?? 'Yönetici';
$adminEmail = $admin['eposta'] ?? '';
$adminRole = $admin['rol'] ?? 'manager';
$roleBadge = $adminRole === 'admin' ? '<span class="status-badge active" style="font-size:11px;">Süper Admin</span>' : '<span class="status-badge" style="background:#EEF2F6;color:#475569;font-size:11px;">Yönetici</span>';
$adminInitials = mb_strtoupper(mb_substr($adminName, 0, 1, 'UTF-8'), 'UTF-8');

                              
$unreadCount = 0;
$latestNotifications = [];
try {
    $currentUserId = (int)($admin['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bildirimler WHERE okundu = 0 AND (hedef_yonetici_id IS NULL OR hedef_yonetici_id = ?)");
    $stmt->execute([$currentUserId]);
    $unreadCount = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT * FROM bildirimler WHERE (hedef_yonetici_id IS NULL OR hedef_yonetici_id = ?) ORDER BY olusturulma_tarihi DESC LIMIT 5");
    $stmt->execute([$currentUserId]);
    $latestNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
                                   
}

                 
$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Yönetim Paneli') ?> — BiTıraş.com</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/admin/assets/admin.css">
</head>
<body>

<div class="admin-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>
    
    <div class="admin-main">
        <header class="admin-header">
            <div class="page-title">
                <button type="button" class="header-btn" onclick="toggleSidebar()" style="display:inline-flex;margin-right:8px;" aria-label="Menü">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div>
                    <h1><?= e($pageTitle ?? 'Yönetim Paneli') ?></h1>
                    <?php if (!empty($pageSubtitle)): ?>
                        <span class="breadcrumb-text"><?= e($pageSubtitle) ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="header-actions">
                <a href="/" target="_blank" class="header-btn" title="Siteyi Görüntüle" style="width:auto;padding:0 14px;gap:8px;font-size:13px;font-weight:600;">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    <span>Siteyi Gör</span>
                </a>

                <div style="position:relative;" id="notificationsDropdown">
                    <button type="button" class="header-btn" onclick="toggleNotificationMenu()" title="Bildirimler">
                        <i class="fa-regular fa-bell"></i>
                        <?php if ($unreadCount > 0): ?>
                            <span class="notification-dot"></span>
                        <?php endif; ?>
                    </button>
                    
                    <div id="notificationsMenu" style="display:none;position:absolute;right:0;top:calc(100% + 10px);width:320px;background:#fff;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,0.12);border:1px solid #E2E8F0;z-index:200;overflow:hidden;">
                        <div style="padding:14px 18px;border-bottom:1px solid #E2E8F0;display:flex;justify-content:space-between;align-items:center;">
                            <strong style="font-size:13px;color:#0F172A;">Bildirimler</strong>
                            <span style="font-size:11px;color:#64748B;"><?= $unreadCount ?> okunmamış</span>
                        </div>
                        <div style="max-height:300px;overflow-y:auto;">
                            <?php if (empty($latestNotifications)): ?>
                                <div style="padding:20px;text-align:center;color:#64748B;font-size:13px;">Yeni bildirim bulunmuyor.</div>
                            <?php else: ?>
                                <?php foreach ($latestNotifications as $notif): ?>
                                    <div style="padding:12px 16px;border-bottom:1px solid #F1F5F9;background:<?= !$notif['okundu'] ? '#FDFBF7' : '#fff' ?>;">
                                        <div style="font-weight:700;font-size:13px;color:#0F172A;"><?= e($notif['baslik']) ?></div>
                                        <div style="font-size:12px;color:#64748B;margin:2px 0 4px;"><?= e($notif['mesaj']) ?></div>
                                        <small style="font-size:10px;color:#94A3B8;"><?= date('d.m.Y H:i', strtotime($notif['olusturulma_tarihi'])) ?></small>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div style="display:flex;align-items:center;gap:10px;padding-left:12px;border-left:1px solid #E2E8F0;">
                    <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg, #C28840, #A66E2C);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;">
                        <?= $adminInitials ?>
                    </div>
                    <div style="display:flex;flex-direction:column;line-height:1.2;">
                        <span style="font-size:13px;font-weight:700;color:#0F172A;"><?= e($adminName) ?></span>
                        <div style="margin-top:2px;"><?= $roleBadge ?></div>
                    </div>
                </div>
            </div>
        </header>

        <main class="admin-content">
            <?php if ($flashSuccess): ?>
                <div style="padding:14px 18px;background:#ECFDF5;border:1px solid #A7F3D0;color:#065F46;border-radius:10px;margin-bottom:20px;display:flex;align-items:center;gap:10px;font-size:14px;">
                    <i class="fa-solid fa-circle-check"></i>
                    <span style="flex:1;"><?= e($flashSuccess) ?></span>
                    <button type="button" onclick="this.parentElement.remove()" style="background:none;border:none;color:#065F46;font-size:18px;cursor:pointer;">&times;</button>
                </div>
            <?php endif; ?>

            <?php if ($flashError): ?>
                <div style="padding:14px 18px;background:#FEF2F2;border:1px solid #FECACA;color:#991B1B;border-radius:10px;margin-bottom:20px;display:flex;align-items:center;gap:10px;font-size:14px;">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span style="flex:1;"><?= e($flashError) ?></span>
                    <button type="button" onclick="this.parentElement.remove()" style="background:none;border:none;color:#991B1B;font-size:18px;cursor:pointer;">&times;</button>
                </div>
            <?php endif; ?>
