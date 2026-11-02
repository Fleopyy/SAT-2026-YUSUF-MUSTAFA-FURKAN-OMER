<?php
require_once __DIR__ . '/../data/privacy.php';
$pageTitle = $pageTitle ?? 'İşletme Paneli';
$stmt = $pdo->prepare('SELECT COUNT(*) FROM isletme_bildirimleri WHERE isletme_id = ? AND okundu = 0');
$stmt->execute([$businessId]);
$unreadBusinessNotifications = (int)$stmt->fetchColumn();
$businessFlashMessage = $_SESSION['business_flash'] ?? null;
unset($_SESSION['business_flash']);

$businessProfilePhoto = $business['profil_fotografi'] ?? null;
if (empty($businessProfilePhoto) && !empty($businessId)) {
    try {
        $stmtPhoto = $pdo->prepare('SELECT f.fotograf_yolu 
                                    FROM isletme_panel_medya m 
                                    JOIN isletme_fotograflari f ON f.id = m.profil_fotograf_id AND f.isletme_id = m.isletme_id 
                                    WHERE m.isletme_id = ?');
        $stmtPhoto->execute([$businessId]);
        $businessProfilePhoto = $stmtPhoto->fetchColumn() ?: null;
        if (!$businessProfilePhoto) {
            $stmtFallback = $pdo->prepare('SELECT f.fotograf_yolu 
                                           FROM isletme_fotograflari f 
                                           LEFT JOIN isletme_fotograf_siralama s ON s.fotograf_id = f.id AND s.isletme_id = f.isletme_id 
                                           WHERE f.isletme_id = ? 
                                           ORDER BY s.sira ASC, f.id ASC LIMIT 1');
            $stmtFallback->execute([$businessId]);
            $businessProfilePhoto = $stmtFallback->fetchColumn() ?: null;
        }
    } catch (Throwable $e) {}
}
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
        :root {
            --btn-height: 44px;
            --btn-height-sm: 34px;
            --btn-height-lg: 48px;
            --input-height: 44px;
        }
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

        /* --- Unified Buttons & Inputs --- */
        .admin-form input:not([type="checkbox"]):not([type="radio"]):not([type="file"]),
        .admin-form select {
            height: var(--input-height);
            line-height: 1.4;
            padding: 0 14px;
            border: 1.5px solid var(--admin-border);
            border-radius: 10px;
            font-size: 13.5px;
            background: #FFFFFF;
            color: var(--admin-text);
            box-sizing: border-box;
            transition: var(--transition);
        }
        .admin-form input:focus,
        .admin-form select:focus,
        .admin-form textarea:focus {
            border-color: var(--admin-accent);
            box-shadow: 0 0 0 3px rgba(194,136,64,0.12);
        }

        .btn-admin {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            height: var(--btn-height);
            padding: 0 20px;
            border-radius: 10px;
            font-size: 13.5px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            box-sizing: border-box;
            text-decoration: none;
            border: 1.5px solid transparent;
            line-height: 1;
            white-space: nowrap;
        }
        .btn-admin.sm {
            height: var(--btn-height-sm);
            padding: 0 13px;
            font-size: 12px;
            font-weight: 700;
            border-radius: 8px;
            gap: 6px;
        }
        .btn-admin.lg {
            height: var(--btn-height-lg);
            padding: 0 24px;
            font-size: 14.5px;
            border-radius: 11px;
        }
        .btn-admin.primary {
            background: linear-gradient(135deg, #C28840 0%, #A96F28 100%);
            color: #FFFFFF;
            border-color: #9C6521;
            box-shadow: 0 2px 8px rgba(194, 136, 64, 0.22);
        }
        .btn-admin.primary:hover {
            background: linear-gradient(135deg, #B57B34 0%, #965F1D 100%);
            box-shadow: 0 4px 14px rgba(194, 136, 64, 0.35);
            transform: translateY(-1px);
            color: #FFFFFF;
        }
        .btn-admin.primary:active {
            transform: translateY(0);
            box-shadow: 0 1px 4px rgba(194, 136, 64, 0.2);
        }
        .btn-admin.outline {
            background: #FFFFFF;
            color: #334155;
            border-color: #CBD5E1;
        }
        .btn-admin.outline:hover {
            background: #FDF8F3;
            border-color: #C28840;
            color: #A96F28;
            transform: translateY(-1px);
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        }
        .btn-admin.success {
            background: #059669;
            color: #FFFFFF;
            border-color: #047857;
            box-shadow: 0 2px 6px rgba(5,150,105,0.18);
        }
        .btn-admin.success:hover {
            background: #047857;
            color: #FFFFFF;
            box-shadow: 0 4px 12px rgba(5,150,105,0.28);
            transform: translateY(-1px);
        }
        .btn-admin.danger {
            background: #EF4444;
            color: #FFFFFF;
            border-color: #DC2626;
            box-shadow: 0 2px 6px rgba(239,68,68,0.18);
        }
        .btn-admin.danger:hover {
            background: #DC2626;
            color: #FFFFFF;
            box-shadow: 0 4px 12px rgba(239,68,68,0.28);
            transform: translateY(-1px);
        }

        /* --- Filter Bars --- */
        .business-filter-form {
            display: flex;
            align-items: flex-end;
            gap: 14px;
            flex-wrap: wrap;
        }
        .business-filter-form .form-group {
            margin: 0;
            flex: 1 1 200px;
            min-width: 170px;
        }
        .business-filter-actions {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
            height: var(--btn-height);
        }
        .business-filter-actions .btn-admin {
            height: var(--btn-height);
        }

        /* --- Form Labels Height & Subtext Alignment --- */
        .admin-form .form-group {
            margin: 0;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
        }
        .admin-form .form-group > label:not(.service-checkbox-pill):not(.checkbox-label) {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 11.5px;
            font-weight: 800;
            color: var(--admin-text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
            min-height: 20px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .admin-form label .label-sub {
            font-size: 10.5px;
            font-weight: 600;
            text-transform: none;
            color: #94A3B8;
            margin-left: 4px;
        }

        /* --- Service Checkbox Pills (Close to button/checkbox) --- */
        .services-checkbox-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }
        .service-checkbox-pill {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: flex-start !important;
            gap: 8px !important;
            padding: 7px 14px !important;
            background: #FFFFFF !important;
            border: 1.5px solid #CBD5E1 !important;
            border-radius: 8px !important;
            font-size: 13px !important;
            font-weight: 600 !important;
            color: #334155 !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
            user-select: none !important;
            width: auto !important;
            margin: 0 !important;
            text-transform: none !important;
        }
        .service-checkbox-pill:hover {
            border-color: var(--admin-accent) !important;
            background: #FDF8F3 !important;
        }
        .service-checkbox-pill input[type="checkbox"] {
            width: 17px !important;
            height: 17px !important;
            margin: 0 !important;
            cursor: pointer !important;
            accent-color: var(--admin-accent) !important;
            flex-shrink: 0 !important;
        }
        .service-checkbox-pill:has(input:checked) {
            border-color: var(--admin-accent) !important;
            background: rgba(194,136,64,0.1) !important;
            color: #A96F28 !important;
            font-weight: 700 !important;
        }

        /* --- 4-Parameter Grid for Employee Add --- */
        .business-employee-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            align-items: flex-end;
        }
        @media (max-width: 1100px) {
            .business-employee-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        @media (max-width: 640px) {
            .business-employee-grid {
                grid-template-columns: 1fr;
            }
        }

        /* --- Custom Modern File Upload Box --- */
        .custom-file-box {
            position: relative;
            width: 100%;
            height: var(--input-height);
            box-sizing: border-box;
        }
        .custom-file-input {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
            z-index: 5;
            margin: 0 !important;
            padding: 0 !important;
        }
        .custom-file-trigger {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 12px;
            background: #FFFFFF;
            border: 1.5px solid var(--admin-border);
            border-radius: 10px;
            transition: var(--transition);
            box-sizing: border-box;
            user-select: none;
            overflow: hidden;
        }
        .custom-file-box:hover .custom-file-trigger,
        .custom-file-input:focus ~ .custom-file-trigger {
            border-color: var(--admin-accent);
            background: #FDF8F3;
            box-shadow: 0 0 0 3px rgba(194,136,64,0.1);
        }
        .file-trigger-info {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
            overflow: hidden;
        }
        .file-trigger-info .file-icon {
            font-size: 15px;
            color: var(--admin-accent);
            flex-shrink: 0;
        }
        .file-trigger-info .file-text {
            font-size: 13px;
            color: #64748B;
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .file-browse-btn {
            flex-shrink: 0;
            padding: 5px 12px;
            background: #F1F5F9;
            border: 1px solid #E2E8F0;
            border-radius: 7px;
            font-size: 11.5px;
            font-weight: 700;
            color: #475569;
            transition: var(--transition);
            margin-left: 8px;
            white-space: nowrap;
        }
        .custom-file-box:hover .file-browse-btn {
            background: #FFFFFF;
            border-color: var(--admin-accent);
            color: var(--admin-accent);
        }

        /* --- Photo Upload Row --- */
        .business-file-upload-row {
            display: flex;
            align-items: flex-end;
            gap: 14px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }
        .business-file-upload-row .form-group {
            margin: 0;
            flex: 1 1 360px;
            min-width: 260px;
        }
        .business-file-upload-row .upload-btn-wrap {
            flex-shrink: 0;
        }
        .business-file-upload-row .btn-admin {
            height: var(--btn-height);
            padding: 0 24px;
        }

        /* --- Çalışma Saatleri (Schedule) Styles --- */
        .schedule-hero-banner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: linear-gradient(135deg, #FAF7F2 0%, #F5EFEB 100%);
            border: 1px solid rgba(194,136,64,0.25);
            border-radius: 14px;
            padding: 16px 20px;
            margin-bottom: 22px;
            gap: 16px;
            flex-wrap: wrap;
        }
        .schedule-hero-banner .hero-info {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .schedule-hero-banner .hero-icon {
            width: 44px;
            height: 44px;
            min-width: 44px;
            border-radius: 12px;
            background: rgba(194,136,64,0.15);
            color: var(--admin-accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        .schedule-hero-banner .hero-text h4 {
            font-size: 14.5px;
            font-weight: 800;
            color: #0F172A;
            margin-bottom: 2px;
        }
        .schedule-hero-banner .hero-text p {
            font-size: 12.5px;
            color: #64748B;
            margin: 0;
        }
        .schedule-quick-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .schedule-days-container {
            display: flex;
            flex-direction: column;
            gap: 14px;
            margin-bottom: 24px;
        }
        .schedule-day-card {
            background: #FFFFFF;
            border: 1.5px solid #E2E8F0;
            border-radius: 14px;
            box-shadow: 0 1px 3px rgba(15,23,42,0.03);
            overflow: hidden;
            transition: all 0.25s ease;
        }
        .schedule-day-card:hover {
            border-color: #CBD5E1;
            box-shadow: 0 4px 12px rgba(15,23,42,0.06);
        }
        .schedule-day-card.is-closed {
            background: #F8FAFC;
            border-color: #E2E8F0;
        }
        .schedule-day-card.is-closed .schedule-card-body {
            display: none;
        }
        .schedule-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 20px;
            background: #FFFFFF;
            border-bottom: 1.5px solid transparent;
            transition: background 0.2s;
            user-select: none;
            gap: 12px;
            flex-wrap: wrap;
        }
        .schedule-day-card:not(.is-closed) .schedule-card-header {
            border-bottom-color: #F1F5F9;
        }
        .schedule-day-card.is-closed .schedule-card-header {
            background: #F8FAFC;
        }
        .schedule-header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .schedule-day-avatar {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: rgba(194,136,64,0.1);
            color: var(--admin-accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13.5px;
            font-weight: 800;
        }
        .schedule-day-card.is-closed .schedule-day-avatar {
            background: #E2E8F0;
            color: #64748B;
        }
        .schedule-day-name {
            font-size: 15px;
            font-weight: 800;
            color: #0F172A;
        }
        .schedule-status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11.5px;
            font-weight: 700;
            letter-spacing: 0.3px;
        }
        .schedule-status-pill.open {
            background: #ECFDF5;
            color: #059669;
            border: 1px solid #A7F3D0;
        }
        .schedule-status-pill.open::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #059669;
        }
        .schedule-status-pill.closed {
            background: #F1F5F9;
            color: #64748B;
            border: 1px solid #E2E8F0;
        }
        .schedule-status-pill.closed::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #94A3B8;
        }

        .schedule-toggle-wrapper {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .schedule-toggle-label {
            font-size: 12.5px;
            font-weight: 700;
            color: var(--admin-text-muted);
        }
        .switch-ui {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 24px;
            margin: 0;
            cursor: pointer;
        }
        .switch-ui input {
            opacity: 0;
            width: 0;
            height: 0;
            position: absolute;
        }
        .slider-ui {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: #CBD5E1;
            border-radius: 24px;
            transition: 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .slider-ui::before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            border-radius: 50%;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
            transition: 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .switch-ui input:checked + .slider-ui {
            background-color: #059669;
        }
        .switch-ui input:checked + .slider-ui::before {
            transform: translateX(20px);
        }

        .schedule-card-body {
            padding: 16px 20px;
        }
        .schedule-rows-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 12px;
        }
        .schedule-row-item {
            display: grid;
            grid-template-columns: minmax(260px, 1fr) minmax(260px, 1fr) auto;
            gap: 16px;
            align-items: center;
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            padding: 12px 16px;
            transition: all 0.2s ease;
        }
        .schedule-row-item:hover {
            background: #FFFFFF;
            border-color: #CBD5E1;
            box-shadow: 0 2px 8px rgba(15,23,42,0.04);
        }
        .schedule-col-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .schedule-col-label {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--admin-text-muted);
        }
        .schedule-col-label i {
            font-size: 12px;
            color: var(--admin-accent);
        }
        .schedule-col-label.break i {
            color: #F59E0B;
        }
        .schedule-inputs-inline {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .schedule-inputs-inline input[type="time"] {
            flex: 1;
            height: 40px !important;
            padding: 0 10px !important;
            border: 1.5px solid #CBD5E1 !important;
            border-radius: 8px !important;
            font-size: 13.5px !important;
            font-weight: 600 !important;
            background: #FFFFFF !important;
            color: #0F172A !important;
            text-align: center;
        }
        .schedule-inputs-inline input[type="time"]:focus {
            border-color: var(--admin-accent) !important;
            box-shadow: 0 0 0 3px rgba(194,136,64,0.12) !important;
        }
        .schedule-separator {
            color: #94A3B8;
            font-weight: 700;
            font-size: 13px;
        }
        .btn-remove-schedule-row {
            width: 38px;
            height: 38px;
            border-radius: 8px;
            border: 1px solid #F1F5F9;
            background: #FFFFFF;
            color: #94A3B8;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
        }
        .btn-remove-schedule-row:hover {
            background: #FEF2F2;
            color: #EF4444;
            border-color: #FECACA;
        }

        .schedule-save-banner {
            position: sticky;
            bottom: 20px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(8px);
            border: 1.5px solid #E2E8F0;
            border-radius: 14px;
            padding: 14px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 10px 30px rgba(15,23,42,0.12);
            gap: 16px;
            flex-wrap: wrap;
            z-index: 50;
            margin-top: 24px;
        }
        .schedule-save-banner .save-info {
            font-size: 13px;
            color: #64748B;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        @media (max-width: 768px) {
            .admin-header { padding:0 14px; }
            .admin-content { padding:16px; }
            .business-topbar-name { display:none; }
            .business-gallery { grid-template-columns:repeat(auto-fill,minmax(140px,1fr)); }
            .schedule-row-item {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            .btn-remove-schedule-row {
                align-self: flex-end;
            }
            .business-filter-actions {
                width: 100%;
            }
            .business-filter-actions .btn-admin {
                flex: 1;
            }
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
                <div class="avatar" style="overflow:hidden;padding:0;background:<?= !empty($businessProfilePhoto) ? '#1E293B' : 'linear-gradient(135deg, #C28840, #A66E2C)' ?>;">
                    <?php if (!empty($businessProfilePhoto)): ?>
                        <img src="/<?= businessEscape(ltrim($businessProfilePhoto, '/')) ?>" alt="<?= businessEscape($business['isletme_adi']) ?>" style="width:100%;height:100%;object-fit:cover;display:block;border-radius:inherit;" onerror="this.onerror=null;this.parentElement.innerHTML='<?= businessEscape(mb_strtoupper(mb_substr($business['isletme_adi'], 0, 1, 'UTF-8'), 'UTF-8')) ?>';">
                    <?php else: ?>
                        <?= businessEscape(mb_strtoupper(mb_substr($business['isletme_adi'], 0, 1, 'UTF-8'), 'UTF-8')) ?>
                    <?php endif; ?>
                </div>
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
