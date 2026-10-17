<?php
   
                                    
                                           
                                                    
   

require_once __DIR__ . '/auth.php';

$sidebarItems = getAdminSidebarItems();
$currentUser = getAuthenticatedAdmin() ?? getAuthenticatedManager();
$adminName = $currentUser['ad_soyad'] ?? ($_SESSION['admin_name'] ?? $_SESSION['manager_name'] ?? 'Yönetici');
$adminRole = $currentUser['rol'] ?? ($_SESSION['admin_role'] ?? $_SESSION['manager_role'] ?? 'manager');
$adminInitials = mb_strtoupper(mb_substr($adminName, 0, 1, 'UTF-8'), 'UTF-8');
$roleLabel = $adminRole === 'admin' ? 'Süper Admin' : 'Yetkili Yönetici';
?>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-brand">
        <div class="logo-icon">
            <i class="fa-solid fa-scissors" style="transform:rotate(90deg)"></i>
        </div>
        <div class="brand-text">
            <h4>BiTıraş<span>.com</span></h4>
            <small>Yönetim Paneli</small>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <div class="sidebar-section-label">Ana Menü</div>
        
        <?php foreach ($sidebarItems as $item): ?>
            <?php 
            $isActive = ($currentPage ?? '') === $item['key'];
            $badgeCount = 0;
            if (!empty($item['badge_query'])) {
                try {
                    $badgeCount = (int)$pdo->query($item['badge_query'])->fetchColumn();
                } catch (Exception $e) {
                    $badgeCount = 0;
                }
            }
            ?>
            <div class="sidebar-nav-item">
                <a href="<?= $item['url'] ?>" class="sidebar-nav-link <?= $isActive ? 'active' : '' ?>">
                    <i class="<?= $item['icon'] ?>"></i>
                    <span><?= e($item['label']) ?></span>
                    <?php if ($badgeCount > 0): ?>
                        <span class="badge-count"><?= $badgeCount ?></span>
                    <?php endif; ?>
                </a>
            </div>
        <?php endforeach; ?>
    </nav>
    
    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="avatar"><?= $adminInitials ?></div>
            <div class="user-info">
                <strong><?= e($adminName) ?></strong>
                <small><?= e($roleLabel) ?></small>
            </div>
        </div>
        <div style="margin-top: 8px;">
            <form method="POST" action="/admin/actions/logout.php" class="logout-form">
                <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                <button type="submit" class="logout-btn">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span>Çıkış Yap</span>
                </button>
            </form>
        </div>
    </div>
</aside>
