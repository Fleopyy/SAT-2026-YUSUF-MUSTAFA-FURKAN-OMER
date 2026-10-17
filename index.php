<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/data/database.php';

$s = [];
try {
    $stmtSettings = $pdo->query("SELECT anahtar, deger FROM site_ayarlari");
    while ($row = $stmtSettings->fetch(PDO::FETCH_ASSOC)) {
        $s[$row['anahtar']] = $row['deger'];
    }
} catch (Exception $e) {
}

$flashMessage = $_SESSION['mesaj'] ?? null;
$flashMessageType = $_SESSION['mesaj_turu'] ?? 'info';
unset($_SESSION['mesaj'], $_SESSION['mesaj_turu']);

$authenticatedName = null;
$isAuthenticatedBarber = false;
$authenticatedCustomerPhoto = null;
if (($_SESSION['user_role'] ?? '') === 'customer' && !empty($_SESSION['user_id'])) {
    $authenticatedName = $_SESSION['user_name'] ?? null;
    $stmtCustomerPhoto = $pdo->prepare('SELECT fotograf_yolu FROM kullanici_portal_profilleri WHERE kullanici_id = ?');
    $stmtCustomerPhoto->execute([(int)$_SESSION['user_id']]);
    $authenticatedCustomerPhoto = $stmtCustomerPhoto->fetchColumn() ?: null;
} elseif (($_SESSION['user_role'] ?? '') === 'barber' && !empty($_SESSION['barber_id'])) {
    $authenticatedName = $_SESSION['barber_name'] ?? null;
    $isAuthenticatedBarber = true;
}

$totalBarbers = 0;
$totalAppointments = 0;
$avgRating = '—';
$featuredBusinesses = [];
$featuredServices = [];
$availableLocations = [];
$availableBusinessServices = [];
$locationDataset = json_decode(file_get_contents(__DIR__ . '/data/turkey_districts.json'), true, 512, JSON_THROW_ON_ERROR);
$turkeyDistricts = $locationDataset['districts'];
$searchPerformed = ($_GET['ara'] ?? '') === '1';
$searchCity = is_string($_GET['sehir'] ?? null) ? trim($_GET['sehir']) : '';
$searchDistrict = is_string($_GET['ilce'] ?? null) ? trim($_GET['ilce']) : '';
$searchTerm = is_string($_GET['hizmet'] ?? null) ? trim($_GET['hizmet']) : '';
$searchCity = mb_substr($searchCity, 0, 100, 'UTF-8');
$searchDistrict = mb_substr($searchDistrict, 0, 100, 'UTF-8');
$searchTerm = mb_substr($searchTerm, 0, 100, 'UTF-8');

$searchLocations = $turkeyDistricts;
$normalizeLocationName = static function (string $name): string {
    return strtr(mb_strtolower(trim($name), 'UTF-8'), [
        'ı' => 'i',
        'ş' => 's',
        'ğ' => 'g',
        'ü' => 'u',
        'ö' => 'o',
        'ç' => 'c',
    ]);
};
$knownCityKeys = [];
foreach (array_keys($searchLocations) as $city) {
    $knownCityKeys[$normalizeLocationName($city)] = $city;
}

try {
    $locationStmt = $pdo->query("SELECT DISTINCT sehir, ilce FROM isletmeler
                                 WHERE sehir <> '' AND ilce <> ''
                                 ORDER BY sehir, ilce");
    foreach ($locationStmt->fetchAll(PDO::FETCH_ASSOC) as $location) {
        $cityKey = $knownCityKeys[$normalizeLocationName($location['sehir'])] ?? $location['sehir'];
        $searchLocations[$cityKey][] = $location['ilce'];
    }
    foreach ($searchLocations as &$districts) {
        $districts = array_values(array_unique($districts));
    }
    unset($districts);
    $availableBusinessServices = $pdo->query("SELECT DISTINCT h.hizmet_adi
                                               FROM hizmetler h
                                               INNER JOIN isletmeler i ON i.id = h.isletme_id
                                               WHERE i.durum = 'active' AND h.aktif_mi = 1
                                               ORDER BY h.hizmet_adi")->fetchAll(PDO::FETCH_COLUMN);

    $stmt = $pdo->query("SELECT COUNT(*) FROM isletmeler WHERE durum = 'active'");
    $totalBarbers = (int) $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM randevular WHERE durum = 'completed'");
    $totalAppointments = (int) $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT AVG(d.puan) FROM degerlendirmeler d
                         INNER JOIN randevular r ON r.id = d.randevu_id
                         INNER JOIN isletmeler i ON i.id = r.isletme_id
                         WHERE d.degerlendiren_turu = 'customer' AND i.durum = 'active'");
    $avg = $stmt->fetchColumn();
    if ($avg !== false && $avg !== null) {
        $avgRating = number_format((float)$avg, 1);
    }
    $businessConditions = ["i.durum = 'active'"];
    $businessParams = [];
    if ($searchPerformed && $searchCity !== '') {
        $businessConditions[] = 'i.sehir = ?';
        $businessParams[] = $searchCity;
    }
    if ($searchPerformed && $searchDistrict !== '') {
        $businessConditions[] = 'i.ilce = ?';
        $businessParams[] = $searchDistrict;
    }
    if ($searchPerformed && $searchTerm !== '') {
        $businessConditions[] = 'EXISTS (
            SELECT 1 FROM hizmetler search_h
            WHERE search_h.isletme_id = i.id AND search_h.aktif_mi = 1 AND search_h.hizmet_adi LIKE ?
        )';
        $businessParams[] = '%' . $searchTerm . '%';
    }
    $businessSql = "SELECT i.id, i.isletme_adi, i.isletme_sahibi, i.sehir, i.ilce, i.tam_adres, i.aciklama,
                                COALESCE(r.rating, 0) AS rating, COALESCE(r.review_count, 0) AS review_count,
                                cover.fotograf_yolu AS cover_photo, profile.fotograf_yolu AS profile_photo
                         FROM isletmeler i
                         LEFT JOIN (
                             SELECT a.isletme_id, AVG(d.puan) AS rating, COUNT(d.id) AS review_count
                             FROM randevular a INNER JOIN degerlendirmeler d ON d.randevu_id = a.id
                             WHERE d.degerlendiren_turu = 'customer' GROUP BY a.isletme_id
                         ) r ON r.isletme_id = i.id
                         LEFT JOIN isletme_panel_medya m ON m.isletme_id = i.id
                         LEFT JOIN isletme_fotograflari cover ON cover.id = m.kapak_fotograf_id AND cover.isletme_id = i.id
                         LEFT JOIN isletme_fotograflari profile ON profile.id = m.profil_fotograf_id AND profile.isletme_id = i.id
                         WHERE " . implode(' AND ', $businessConditions) . "
                         ORDER BY rating DESC, i.isletme_adi";
    if (!$searchPerformed) {
        $businessSql .= ' LIMIT 12';
    }
    $stmt = $pdo->prepare($businessSql);
    $stmt->execute($businessParams);
    $featuredBusinesses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($featuredBusinesses) {
        $businessIds = array_column($featuredBusinesses, 'id');
        $placeholders = implode(',', array_fill(0, count($businessIds), '?'));
        $serviceStmt = $pdo->prepare("SELECT id, isletme_id, hizmet_adi, fiyat FROM hizmetler WHERE aktif_mi = 1 AND isletme_id IN ($placeholders) ORDER BY hizmet_adi");
        $serviceStmt->execute($businessIds);
        foreach ($serviceStmt->fetchAll(PDO::FETCH_ASSOC) as $service) {
            $featuredServices[(int)$service['isletme_id']][] = $service;
        }
    }
} catch (Throwable $e) {
    error_log('Public business listing could not be loaded: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($s['site_baslik'] ?? 'BiTıraş | Premium Berber & Erkek Kuaförü Randevu Sistemi', ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="description"
        content="<?= htmlspecialchars($s['site_aciklama'] ?? 'Türkiye\'nin en seçkin berber ve erkek kuaförlerinden anında online randevu alın. Sıra beklemeden tarzınızı keşfedin.', ENT_QUOTES, 'UTF-8') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">


    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
    <style>
    .bitiras-toast-container {
        position: fixed !important;
        top: 88px !important;
        right: 24px !important;
        z-index: 999999 !important;
        pointer-events: none !important;
        display: flex !important;
        flex-direction: column !important;
        gap: 12px !important;
    }
    .bitiras-toast {
        pointer-events: auto !important;
        position: relative !important;
        display: flex !important;
        align-items: flex-start !important;
        gap: 14px !important;
        min-width: 320px !important;
        max-width: 420px !important;
        padding: 16px 18px 16px 16px !important;
        background: #FFFFFF !important;
        border-radius: 16px !important;
        box-shadow: 0 20px 45px -10px rgba(15, 23, 42, 0.25), 0 6px 18px -3px rgba(15, 23, 42, 0.1) !important;
        border: 1px solid #E2E8F0 !important;
        overflow: hidden !important;
        animation: toastSlideIn 0.45s cubic-bezier(0.16, 1, 0.3, 1) forwards !important;
        transition: transform 0.25s ease, opacity 0.25s ease, box-shadow 0.25s ease !important;
        font-family: 'Plus Jakarta Sans', sans-serif !important;
    }
    .bitiras-toast:hover {
        box-shadow: 0 26px 54px -10px rgba(15, 23, 42, 0.3), 0 8px 22px -3px rgba(15, 23, 42, 0.14) !important;
        transform: translateY(-2px) !important;
    }
    .bitiras-toast.toast-hiding {
        animation: toastSlideOut 0.35s cubic-bezier(0.7, 0, 0.84, 0) forwards !important;
    }
    .toast-icon-wrapper {
        flex-shrink: 0 !important;
        width: 38px !important;
        height: 38px !important;
        border-radius: 12px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 17px !important;
    }
    .toast-content {
        flex: 1 !important;
        min-width: 0 !important;
        padding-top: 1px !important;
    }
    .toast-title {
        display: block !important;
        font-size: 13px !important;
        font-weight: 800 !important;
        letter-spacing: -0.2px !important;
        margin-bottom: 3px !important;
        line-height: 1.25 !important;
    }
    .toast-message {
        display: block !important;
        font-size: 13px !important;
        font-weight: 500 !important;
        line-height: 1.45 !important;
        color: #475569 !important;
        word-break: break-word !important;
    }
    .toast-close-btn {
        flex-shrink: 0 !important;
        background: transparent !important;
        border: none !important;
        color: #94A3B8 !important;
        cursor: pointer !important;
        width: 28px !important;
        height: 28px !important;
        border-radius: 8px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 14px !important;
        transition: all 0.2s ease !important;
        margin-top: -2px !important;
        margin-right: -4px !important;
        padding: 0 !important;
    }
    .toast-close-btn:hover {
        background: #F1F5F9 !important;
        color: #0F172A !important;
    }
    .toast-progress {
        position: absolute !important;
        bottom: 0 !important;
        left: 0 !important;
        height: 3px !important;
        width: 100% !important;
        background: rgba(0, 0, 0, 0.05) !important;
    }
    .toast-progress-bar {
        height: 100% !important;
        width: 100% !important;
        transform-origin: left !important;
        animation: toastProgress 5s linear forwards !important;
    }
    .bitiras-toast:hover .toast-progress-bar {
        animation-play-state: paused !important;
    }
    .bitiras-toast.toast-success { border-left: 4px solid #10B981 !important; }
    .bitiras-toast.toast-success .toast-icon-wrapper { background: #ECFDF5 !important; color: #059669 !important; }
    .bitiras-toast.toast-success .toast-title { color: #065F46 !important; }
    .bitiras-toast.toast-success .toast-progress-bar { background: #10B981 !important; }

    .bitiras-toast.toast-danger { border-left: 4px solid #EF4444 !important; }
    .bitiras-toast.toast-danger .toast-icon-wrapper { background: #FEF2F2 !important; color: #DC2626 !important; }
    .bitiras-toast.toast-danger .toast-title { color: #991B1B !important; }
    .bitiras-toast.toast-danger .toast-progress-bar { background: #EF4444 !important; }

    .bitiras-toast.toast-warning { border-left: 4px solid #F59E0B !important; }
    .bitiras-toast.toast-warning .toast-icon-wrapper { background: #FFFBEB !important; color: #D97706 !important; }
    .bitiras-toast.toast-warning .toast-title { color: #92400E !important; }
    .bitiras-toast.toast-warning .toast-progress-bar { background: #F59E0B !important; }

    .bitiras-toast.toast-info { border-left: 4px solid #3B82F6 !important; }
    .bitiras-toast.toast-info .toast-icon-wrapper { background: #EFF6FF !important; color: #2563EB !important; }
    .bitiras-toast.toast-info .toast-title { color: #1E40AF !important; }
    .bitiras-toast.toast-info .toast-progress-bar { background: #3B82F6 !important; }

    @keyframes toastSlideIn {
        0% { opacity: 0; transform: translateX(115%) scale(0.94); }
        70% { transform: translateX(-6px) scale(1.01); }
        100% { opacity: 1; transform: translateX(0) scale(1); }
    }
    @keyframes toastSlideOut {
        0% { opacity: 1; transform: translateX(0) scale(1); }
        100% { opacity: 0; transform: translateX(115%) scale(0.92); }
    }
    @keyframes toastProgress {
        from { transform: scaleX(1); }
        to { transform: scaleX(0); }
    }
    @media (max-width: 576px) {
        .bitiras-toast-container { top: 16px !important; right: 16px !important; left: 16px !important; }
        .bitiras-toast { min-width: 100% !important; max-width: 100% !important; }
    }
    </style>
</head>

<body>


    <?php if (($s['duyuru_aktif'] ?? '0') === '1' && !empty($s['duyuru_bandi'])): ?>
    <div class="bg-dark text-white text-center py-2 px-3 text-xs fw-semibold border-bottom border-secondary">
        <span class="pulse-dot me-2"></span>
        <span><?= htmlspecialchars($s['duyuru_bandi'], ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <?php endif; ?>


    <nav class="navbar navbar-expand-lg bg-white navbar-light sticky-top shadow-sm py-3">
        <div class="container">

            <a class="navbar-brand d-flex align-items-center gap-2" href="#">
                <div class="logo-icon">
                    <i class="fa-solid fa-scissors rotate-90"></i>
                </div>
                <div>
                    <span class="fs-4 fw-extrabold text-dark tracking-tight">BiTıraş<span
                            style="color: var(--accent);">.com</span></span>
                    <span class="d-block text-muted uppercase fw-bold"
                        style="font-size: 10px; letter-spacing: 0.5px; margin-top: -3px;">Kolay Berber Randevu
                        Sistemi</span>
                </div>
            </a>

            <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false"
                aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarContent">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0 gap-lg-4 font-semibold text-slate">
                    <li class="nav-item">
                        <a class="nav-link text-dark fw-bold" href="#hero">Ana Sayfa</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-dark fw-bold" href="#barbers">Popüler Berberler</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-dark fw-bold" href="#how-it-works">Nasıl Çalışır?</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-dark fw-bold" href="#for-business">İşletmeler İçin</a>
                    </li>
                </ul>

                <div class="d-flex align-items-center gap-2 pt-3 pt-lg-0">
                    <?php if ($authenticatedName !== null): ?>
                        <details class="account-menu">
                            <summary class="account-menu-trigger">
                                <?php if (($_SESSION['user_role'] ?? '') === 'customer' && $authenticatedCustomerPhoto): ?>
                                    <img class="account-menu-avatar" src="/<?= htmlspecialchars($authenticatedCustomerPhoto, ENT_QUOTES, 'UTF-8') ?>" alt="">
                                <?php else: ?>
                                    <i class="fa-solid fa-circle-user" aria-hidden="true"></i>
                                <?php endif; ?>
                                <span><?= htmlspecialchars($authenticatedName, ENT_QUOTES, 'UTF-8') ?></span>
                                <i class="fa-solid fa-chevron-down account-menu-chevron" aria-hidden="true"></i>
                            </summary>
                            <div class="account-menu-dropdown">
                                <?php if ($isAuthenticatedBarber): ?>
                                    <a class="account-menu-item" href="/isletme/">
                                        <i class="fa-solid fa-gauge-high" aria-hidden="true"></i>
                                        İşletme Paneline Git
                                    </a>
                                <?php elseif (($_SESSION['user_role'] ?? '') === 'customer' && !empty($_SESSION['user_id'])): ?>
                                    <a class="account-menu-item" href="/musteri/randevularim.php">
                                        <i class="fa-regular fa-calendar-check" aria-hidden="true"></i>
                                        Randevularım
                                    </a>
                                    <a class="account-menu-item" href="/musteri/hesabim.php">
                                        <i class="fa-regular fa-user" aria-hidden="true"></i>
                                        Hesabım
                                    </a>
                                <?php endif; ?>
                                <a class="account-menu-item account-menu-logout" href="login.php?action=logout">
                                    <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i>
                                    Çıkış Yap
                                </a>
                            </div>
                        </details>
                    <?php else: ?>
                        <button class="btn btn-outline-dark px-4 py-2" onclick="openAuthModal('login')">
                            <i class="fa-solid fa-right-to-bracket me-2" style="color: var(--accent);"></i>Giriş Yap
                        </button>
                        <button class="btn btn-primary px-4 py-2" onclick="openAuthModal('register')">
                            <i class="fa-solid fa-user-plus me-2"></i>Kayıt Ol
                        </button>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </nav>

    <?php if ($flashMessage !== null): ?>
        <?php
        $toastType = in_array($flashMessageType, ['success', 'danger', 'warning', 'info']) ? $flashMessageType : 'info';
        $toastIcon = match($toastType) {
            'success' => 'fa-circle-check',
            'danger' => 'fa-circle-xmark',
            'warning' => 'fa-triangle-exclamation',
            default => 'fa-circle-info'
        };
        $toastTitle = match($toastType) {
            'success' => 'İşlem Başarılı',
            'danger' => 'İşlem Başarısız',
            'warning' => 'Dikkat',
            default => 'Bilgilendirme'
        };
        ?>
        <div class="bitiras-toast-container" role="status" aria-live="polite">
            <div id="bitirasToast" class="bitiras-toast toast-<?= $toastType ?>" role="alert">
                <div class="toast-icon-wrapper">
                    <i class="fa-solid <?= $toastIcon ?>"></i>
                </div>
                <div class="toast-content">
                    <span class="toast-title"><?= $toastTitle ?></span>
                    <span class="toast-message"><?= htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <button type="button" class="toast-close-btn" onclick="dismissBitirasToast()" aria-label="Bildirimi Kapat">
                    <i class="fa-solid fa-xmark"></i>
                </button>
                <div class="toast-progress">
                    <div class="toast-progress-bar"></div>
                </div>
            </div>
        </div>
        <script>
            function dismissBitirasToast() {
                const toast = document.getElementById('bitirasToast');
                if (!toast) return;
                toast.classList.add('toast-hiding');
                setTimeout(() => {
                    const container = toast.closest('.bitiras-toast-container');
                    if (container) container.remove();
                    else toast.remove();
                }, 340);
            }

                                              
            (function() {
                let autoTimer = setTimeout(dismissBitirasToast, 5000);
                const toast = document.getElementById('bitirasToast');
                if (toast) {
                    toast.addEventListener('mouseenter', () => clearTimeout(autoTimer));
                    toast.addEventListener('mouseleave', () => {
                        autoTimer = setTimeout(dismissBitirasToast, 2500);
                    });
                }
            })();
        </script>
    <?php endif; ?>

    <main>

        <section id="hero" class="py-4 py-lg-5 bg-light border-bottom">

            <div class="hero-slider" role="region" aria-roledescription="Arka plan görseli kaydırıcı" aria-label="Berber salonu görselleri">
                <div class="hero-slide active" style="background-image: url('uploads/slide-1.jpg');" role="img" aria-label="Berber salonu görsel 1"></div>
                <div class="hero-slide" style="background-image: url('uploads/slide-2.jpg');" role="img" aria-label="Berber salonu görsel 2"></div>
                <div class="hero-slide" style="background-image: url('uploads/slide-3.jpg');" role="img" aria-label="Berber salonu görsel 3"></div>
                <div class="hero-slide" style="background-image: url('uploads/slide-4.jpg');" role="img" aria-label="Berber salonu görsel 4"></div>
                <div class="hero-slide" style="background-image: url('uploads/slide-5.jpg');" role="img" aria-label="Berber salonu görsel 5"></div>
                <div class="hero-slide" style="background-image: url('uploads/slide-6.jpg');" role="img" aria-label="Berber salonu görsel 6"></div>
                <div class="hero-slider-overlay"></div>
            </div>
            
            <div class="container">

                <div class="text-center mx-auto mb-4 mb-lg-5" style="max-width: 900px;">


                    <h1 class="display-4 fw-extrabold text-white tracking-tight mb-3" style="text-shadow: 0 2px 10px rgba(0,0,0,0.55);">
                        <?= htmlspecialchars($s['hero_baslik'] ?? 'Tarzını Keşfet, Sıra Beklemeden Randevunu Al', ENT_QUOTES, 'UTF-8') ?>
                    </h1>
                    <p class="lead text-white fw-medium mx-auto" style="max-width: 720px; opacity: 0.92; text-shadow: 0 1px 6px rgba(0,0,0,0.55);">
                        <?= htmlspecialchars($s['hero_aciklama'] ?? 'Şehrinizdeki en iyi erkek kuaförlerini ve VIP berber salonlarını karşılaştırın, dakikalar içinde boş koltuğunuzu ayırtın.', ENT_QUOTES, 'UTF-8') ?>
                    </p>
                </div>

                <div class="w-100">
                    <form id="heroSearchForm" method="GET" action="/index.php#barbers"
                        class="hero-search-form p-4 p-md-5 rounded-4 shadow-lg">
                        <input type="hidden" name="ara" value="1">
                        <div class="row g-3 align-items-end">


                            <div class="col-md-3">
                                <label for="searchCity" class="hero-search-label form-label text-uppercase fw-extrabold">
                                    <i class="fa-solid fa-location-dot me-1" style="color: var(--accent);"></i>
                                    Şehir
                                </label>
                                <div class="hero-search-autocomplete">
                                    <input id="searchCity" name="sehir" type="text" role="combobox" aria-autocomplete="list"
                                        aria-expanded="false" aria-controls="searchCityOptions" autocomplete="off"
                                        value="<?= htmlspecialchars($searchCity, ENT_QUOTES, 'UTF-8') ?>"
                                        placeholder="Tüm şehirler" class="form-control form-control-lg fs-6 fw-semibold">
                                    <div id="searchCityOptions" class="hero-search-options" role="listbox" hidden></div>
                                    <i class="fa-solid fa-chevron-down hero-search-chevron" aria-hidden="true"></i>
                                </div>
                            </div>


                            <div class="col-md-3">
                                <label for="searchDistrict" class="hero-search-label form-label text-uppercase fw-extrabold">
                                    <i class="fa-solid fa-map-pin me-1" style="color: var(--accent);"></i> İlçe
                                </label>
                                <div class="hero-search-autocomplete">
                                    <input id="searchDistrict" name="ilce" type="text" role="combobox" aria-autocomplete="list"
                                        aria-expanded="false" aria-controls="searchDistrictOptions" autocomplete="off"
                                        value="<?= htmlspecialchars($searchDistrict, ENT_QUOTES, 'UTF-8') ?>"
                                        placeholder="Tüm ilçeler" class="form-control form-control-lg fs-6 fw-semibold">
                                    <div id="searchDistrictOptions" class="hero-search-options" role="listbox" hidden></div>
                                    <i class="fa-solid fa-chevron-down hero-search-chevron" aria-hidden="true"></i>
                                </div>
                            </div>


                            <div class="col-md-4">
                                <label for="hizmetler" class="hero-search-label form-label text-uppercase fw-extrabold">
                                    <i class="fa-solid fa-scissors me-1" style="color: var(--accent);"></i> Hizmetler
                                </label>
                                <div class="hero-search-autocomplete">
                                    <input id="hizmetler" name="hizmet" type="text" role="combobox" aria-autocomplete="list"
                                        aria-expanded="false" aria-controls="businessServiceOptions" autocomplete="off"
                                    value="<?= htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8') ?>"
                                    placeholder="Tüm hizmetler"
                                    class="form-control form-control-lg fs-6 fw-semibold">
                                    <div id="businessServiceOptions" class="hero-search-options" role="listbox" hidden></div>
                                    <i class="fa-solid fa-chevron-down hero-search-chevron" aria-hidden="true"></i>
                                </div>
                            </div>


                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary btn-lg w-100 fs-6 py-3">
                                    <i class="fa-solid fa-magnifying-glass me-1"></i> Bul
                                </button>
                            </div>

                        </div>
                    </form>


                </div>

                <div class="row g-3 mt-4 mt-lg-5 w-100 mx-0">
                    <div class="col-6 col-md-4">
                        <div class="card border text-center p-3 rounded-4 shadow-sm h-100">
                            <div class="fs-2 fw-extrabold text-dark"><?php echo htmlspecialchars((string)$totalBarbers, ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                            <div class="text-muted fw-semibold" style="font-size: 12px;">Kayıtlı Berber Salonu</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="card border text-center p-3 rounded-4 shadow-sm h-100">
                            <div class="fs-2 fw-extrabold text-dark"><?php echo htmlspecialchars((string)$totalAppointments, ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                            <div class="text-muted fw-semibold" style="font-size: 12px;">Tamamlanan Randevu</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="card border text-center p-3 rounded-4 shadow-sm h-100">
                            <div class="fs-2 fw-extrabold text-dark"><?php echo htmlspecialchars((string)$avgRating, ENT_QUOTES, 'UTF-8'); ?> <span
                                    style="color: var(--accent); font-size: 16px;">/ 5.0</span></div>
                            <div class="text-muted fw-semibold" style="font-size: 12px;">Müşteri Puan Ortalaması
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </section>
        <section id="barbers" class="py-5 bg-white border-bottom">
            <div class="container py-4">

                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-5">
                    <div>
                        <div class="text-uppercase fw-extrabold mb-1"
                            style="color: var(--accent); font-size: 12px; letter-spacing: 1px;">
                            <span class="d-inline-block me-1 rounded-circle"
                                style="width: 8px; height: 8px; background-color: var(--accent);"></span> Vitrin
                            Salonlar
                        </div>
                        <h2 class="fs-2 fw-extrabold text-dark mb-0"><?= $searchPerformed ? 'Arama Sonuçları' : 'Öne Çıkan Berber Salonları' ?></h2>
                        <p class="text-muted mb-0" style="font-size: 14px;">
                            <?php if ($searchPerformed): ?>
                                <?= $searchCity !== '' ? htmlspecialchars($searchCity, ENT_QUOTES, 'UTF-8') : 'Tüm şehirler' ?>
                                <?= $searchDistrict !== '' ? ' · ' . htmlspecialchars($searchDistrict, ENT_QUOTES, 'UTF-8') : '' ?>
                                <?= $searchTerm !== '' ? ' · "' . htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8') . '"' : '' ?>
                                konum ve arama kriterlerine uygun işletmeler
                            <?php else: ?>
                                En yüksek puanlı ve müşteriler tarafından en çok tercih edilen kuaförler
                            <?php endif; ?>
                        </p>
                    </div>

                    <div class="d-flex gap-2 overflow-auto pt-3 pt-md-0">
                        <button class="btn btn-dark btn-sm rounded-3 px-3">Tümü</button>
                        <button class="btn btn-outline-dark btn-sm rounded-3 px-3">VIP Salonlar</button>
                        <button class="btn btn-outline-dark btn-sm rounded-3 px-3">En Yüksek Puanlılar</button>
                        <button class="btn btn-outline-dark btn-sm rounded-3 px-3">Gece Açık</button>
                    </div>
                </div>
                <div class="row g-4">
                    <?php if (!$featuredBusinesses): ?>
                        <div class="col-12"><div class="empty-state"><h4><?= $searchPerformed ? 'Bu konumda eşleşen berber bulunamadı' : 'Şu anda yayında işletme bulunmuyor' ?></h4><p><?= $searchPerformed ? 'Farklı bir şehir, ilçe veya hizmet seçip tekrar arayabilirsin.' : 'Yeni onaylanan işletmeler burada görüntülenecek.' ?></p></div></div>
                    <?php else: foreach ($featuredBusinesses as $featuredBusiness):
                        $imagePath = $featuredBusiness['cover_photo'] ?: $featuredBusiness['profile_photo'];
                        $businessServices = $featuredServices[(int)$featuredBusiness['id']] ?? [];
                    ?>
                        <div class="col-lg-4 col-md-6">
                            <article class="card h-100 border rounded-4 overflow-hidden card-hover shadow-sm">
                                <a href="/berber.php?id=<?= (int)$featuredBusiness['id'] ?>" class="text-decoration-none text-dark">
                                    <div class="position-relative" style="height:220px;background:#1E293B;">
                                        <?php if ($imagePath): ?><img src="/<?= htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($featuredBusiness['isletme_adi'], ENT_QUOTES, 'UTF-8') ?>" class="w-100 h-100 object-fit-cover"><?php else: ?><div class="w-100 h-100 d-flex align-items-center justify-content-center text-white"><i class="fa-solid fa-scissors fa-3x"></i></div><?php endif; ?>
                                        <span class="position-absolute top-0 end-0 m-3 badge badge-dark-custom"><i class="fa-solid fa-star me-1" style="color:var(--accent)"></i><?= $featuredBusiness['review_count'] ? number_format((float)$featuredBusiness['rating'],1) : 'Yeni' ?> <span class="text-muted fw-normal">(<?= (int)$featuredBusiness['review_count'] ?>)</span></span>
                                        <span class="position-absolute bottom-0 start-0 m-3 text-white fw-bold text-xs"><i class="fa-solid fa-location-dot me-1" style="color:var(--accent)"></i><?= htmlspecialchars($featuredBusiness['sehir'] . ', ' . $featuredBusiness['ilce'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <div class="card-body d-flex flex-column p-4">
                                        <h3 class="fs-5 fw-extrabold mb-1"><?= htmlspecialchars($featuredBusiness['isletme_adi'], ENT_QUOTES, 'UTF-8') ?></h3>
                                        <p class="text-muted text-xs mb-3"><?= htmlspecialchars($featuredBusiness['isletme_sahibi'], ENT_QUOTES, 'UTF-8') ?></p>
                                        <p class="text-muted small"><?= htmlspecialchars(mb_substr($featuredBusiness['aciklama'] ?? '',0,150,'UTF-8'), ENT_QUOTES, 'UTF-8') ?></p>
                                        <?php if ($businessServices): ?><div class="pt-3 border-top"><div class="text-uppercase text-muted fw-bold mb-2" style="font-size:11px">Aktif hizmetler</div><div class="d-flex flex-wrap gap-1"><?php foreach (array_slice($businessServices,0,3) as $service): ?><span class="badge bg-light text-dark p-2"><?= htmlspecialchars($service['hizmet_adi'], ENT_QUOTES, 'UTF-8') ?> · <?= number_format((float)$service['fiyat'],0,',','.') ?> ₺</span><?php endforeach; ?></div></div><?php endif; ?>
                                    </div>
                                </a>
                                <div class="px-4 pb-4"><a href="/berber.php?id=<?= (int)$featuredBusiness['id'] ?>" class="btn btn-outline-amber btn-sm">Profili incele <i class="fa-solid fa-arrow-right ms-1"></i></a></div>
                            </article>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </section>

        <section id="how-it-works" class="py-5 bg-light">
            <div class="container py-4">
                <div class="text-center mx-auto mb-5" style="max-width: 700px;">
                    <span class="text-uppercase fw-extrabold d-block mb-1"
                        style="color: var(--accent); font-size: 12px; letter-spacing: 1px;">Kolay ve Hızlı</span>
                    <h2 class="fs-2 fw-extrabold text-dark mb-2">3 Adımda Randevunu Tamamla</h2>
                    <p class="text-muted mb-0">Telefon açmadan, koltuk sırası beklemeden 30 saniyede randevunu ayarla.
                    </p>
                </div>

                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="card h-100 border text-center p-4 rounded-4 shadow-sm card-hover">
                            <div class="step-illustration-box mx-auto mb-4" style="width: 100px; height: 100px;">
                                <svg width="100" height="100" viewBox="0 0 120 120" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <rect width="120" height="120" rx="28" fill="#FDF5EC" />
                                    <ellipse cx="60" cy="102" rx="40" ry="5" fill="#EADCCB" />
                                    <rect x="80" y="32" width="16" height="58" rx="8" fill="#1E242B" />
                                    <rect x="83" y="38" width="10" height="46" rx="2" fill="#FFFFFF" />
                                    <path d="M83 44L93 40M83 54L93 50M83 64L93 60M83 74L93 70" stroke="#C28840"
                                        stroke-width="4.5" stroke-linecap="round" />
                                    <rect x="78" y="30" width="20" height="6" rx="3" fill="#1E242B" />
                                    <rect x="78" y="86" width="20" height="6" rx="3" fill="#1E242B" />
                                    <path d="M46 34V88H53V34H46Z" fill="#1E242B" />
                                    <path d="M53 38H61M53 44H61M53 50H61M53 56H61M53 62H61M53 68H61M53 74H61M53 80H61"
                                        stroke="#1E242B" stroke-width="2.5" stroke-linecap="round" />
                                    <circle cx="26" cy="82" r="7" stroke="#1E242B" stroke-width="4.5" fill="none" />
                                    <circle cx="44" cy="82" r="7" stroke="#1E242B" stroke-width="4.5" fill="none" />
                                    <path d="M26 75L48 35" stroke="#1E242B" stroke-width="4.5" stroke-linecap="round" />
                                    <path d="M44 75L22 35" stroke="#1E242B" stroke-width="4.5" stroke-linecap="round" />
                                    <circle cx="35" cy="55" r="3" fill="#C28840" />
                                    <path
                                        d="M60 44C51.1634 44 44 51.1634 44 60C44 72 60 90 60 90C60 90 76 72 76 60C76 51.1634 68.8366 44 60 44Z"
                                        fill="#C28840" stroke="#1E242B" stroke-width="3.5" stroke-linejoin="round" />
                                    <circle cx="60" cy="58" r="7.5" fill="#FFFFFF" />
                                    <path d="M56 58L59 61L64 55" stroke="#1E242B" stroke-width="2.5"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M22 42L16 40M20 50L14 52" stroke="#C28840" stroke-width="2.5"
                                        stroke-linecap="round" />
                                </svg>
                            </div>
                            <h3 class="fs-5 fw-bold text-dark mb-2">Berberini ve Hizmeti Seç</h3>
                            <p class="text-muted text-sm mb-0">Bulunduğun konuma en yakın berberleri incele, alacağın
                                hizmetleri ve kuaförünü seç.</p>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card h-100 border text-center p-4 rounded-4 shadow-sm card-hover">
                            <div class="step-illustration-box mx-auto mb-4" style="width: 100px; height: 100px;">
                                <svg width="100" height="100" viewBox="0 0 120 120" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <rect width="120" height="120" rx="28" fill="#FDF5EC" />
                                    <ellipse cx="60" cy="102" rx="42" ry="5" fill="#EADCCB" />
                                    <path
                                        d="M24 38C24 33.5817 27.5817 30 32 30H84C88.4183 30 92 33.5817 92 38V82C92 86.4183 88.4183 90 84 90H32C27.5817 90 24 86.4183 24 82V38Z"
                                        fill="#1E242B" />
                                    <rect x="30" y="42" width="56" height="42" rx="4" fill="#FFFFFF" />
                                    <rect x="42" y="24" width="6" height="12" rx="3" fill="#C28840" />
                                    <rect x="68" y="24" width="6" height="12" rx="3" fill="#C28840" />
                                    <rect x="66" y="50" width="8" height="8" rx="2" fill="#C28840" />
                                    <path d="M68 54L70 56L73 52" stroke="#FFFFFF" stroke-width="1.8"
                                        stroke-linecap="round" />
                                    <rect x="52" y="62" width="8" height="8" rx="2" fill="#C28840" />
                                    <path d="M54 66L56 68L59 64" stroke="#FFFFFF" stroke-width="1.8"
                                        stroke-linecap="round" />
                                    <rect x="38" y="50" width="8" height="8" rx="2" fill="#E2E8F0" />
                                    <rect x="52" y="50" width="8" height="8" rx="2" fill="#E2E8F0" />
                                    <rect x="38" y="62" width="8" height="8" rx="2" fill="#E2E8F0" />
                                    <circle cx="76" cy="74" r="21" fill="#FFFFFF" stroke="#1E242B" stroke-width="4.5" />
                                    <circle cx="76" cy="74" r="17" fill="none" stroke="#C28840" stroke-width="2.5" />
                                    <path d="M76 74V62M76 74L84 79" stroke="#1E242B" stroke-width="3"
                                        stroke-linecap="round" />
                                    <circle cx="76" cy="74" r="2.5" fill="#1E242B" />
                                    <path d="M22 28L18 24M28 22L26 16M98 56L104 54M96 46L102 42" stroke="#C28840"
                                        stroke-width="2.5" stroke-linecap="round" />
                                </svg>
                            </div>
                            <h3 class="fs-5 fw-bold text-dark mb-2">Tarih ve Saati Belirle</h3>
                            <p class="text-muted text-sm mb-0">Berberin canlı takviminden sana en uygun boş saat
                                dilimini tek tıkla işaretle.</p>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card h-100 border text-center p-4 rounded-4 shadow-sm card-hover">
                            <div class="step-illustration-box mx-auto mb-4" style="width: 100px; height: 100px;">
                                <svg width="100" height="100" viewBox="0 0 120 120" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <rect width="120" height="120" rx="28" fill="#FDF5EC" />
                                    <ellipse cx="60" cy="102" rx="40" ry="5" fill="#EADCCB" />
                                    <rect x="42" y="22" width="40" height="74" rx="10" fill="#1E242B" stroke="#1E242B"
                                        stroke-width="2" />
                                    <rect x="46" y="28" width="32" height="62" rx="6" fill="#2D3748" />
                                    <circle cx="62" cy="85" r="2" fill="#A0AEC0" />
                                    <circle cx="34" cy="58" r="16" fill="#C28840" stroke="#FFFFFF" stroke-width="2.5" />
                                    <path d="M26 58L31.5 63.5L42 53" stroke="#FFFFFF" stroke-width="3.5"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                    <rect x="58" y="26" width="30" height="22" rx="7" fill="#C28840" />
                                    <path d="M64 48L68 53L70 48H64Z" fill="#C28840" />
                                    <path d="M67 37L71 41L79 33" stroke="#FFFFFF" stroke-width="2.5"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M68 58H88V72C88 81 78 87 78 87C78 87 68 81 68 72V58Z" fill="#C28840"
                                        stroke="#FFFFFF" stroke-width="2" />
                                    <path d="M73 71L76.5 74.5L83 67" stroke="#FFFFFF" stroke-width="2.5"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M88 22C92 24 95 28 95 32M93 17C99 20 102 26 102 32" stroke="#C28840"
                                        stroke-width="2.5" stroke-linecap="round" />
                                    <path d="M18 36L14 34M24 28L22 23" stroke="#C28840" stroke-width="2.5"
                                        stroke-linecap="round" />
                                </svg>
                            </div>
                            <h3 class="fs-5 fw-bold text-dark mb-2">Randevunu Onayla</h3>
                            <p class="text-muted text-sm mb-0">SMS ile gelen doğrulama koduyla randevun anında berberin
                                ekranına düşsün.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="for-business" class="py-5 bg-dark text-white">
            <div class="container py-3">
                <div class="row align-items-center g-4">
                    <div class="col-lg-8">

                        <h2 class="fs-2 fw-extrabold text-white mb-2">Salonunuzu BiTıraş.com'a Katın, Müşteri
                            Doluluğunuzu Artırın!</h2>
                        <p class="text-secondary mb-0" style="max-width: 640px;">
                            Randevu telefonları yanıtlamaktan kurtulun. Müşterileriniz 7/24 online randevu alsın, koltuk
                            takviminizi akıllı panelimizle zahmetsizce yönetin.
                        </p>
                    </div>
                    <div class="col-lg-4 text-lg-end">
                        <button onclick="openAuthModal('register', 'barber')" class="btn btn-primary btn-lg px-4 py-3">
                            <i class="fa-solid fa-store me-2"></i>İşletme Hesabı Oluştur
                        </button>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="bg-dark text-secondary py-5 border-top border-secondary">
        <div class="container">
            <div class="row g-4 mb-4">
                <div class="col-lg-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="logo-icon"><i class="fa-solid fa-scissors rotate-90"></i></div>
                        <span class="fs-4 fw-extrabold text-white">BiTıraş<span
                                style="color: var(--accent);">.com</span></span>
                    </div>
                    <p class="text-secondary text-sm" style="max-width: 320px;">
                        <?= htmlspecialchars($s['footer_metin'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                    </p>
                </div>
                <div class="col-6 col-lg-2">
                    <h5 class="text-white text-uppercase fs-6 fw-bold mb-3">Hızlı Bağlantılar</h5>
                    <ul class="list-unstyled text-sm space-y-2">
                        <li><a href="#hero" class="text-secondary text-decoration-none">Ana Sayfa</a></li>
                        <li><a href="#barbers" class="text-secondary text-decoration-none">Vitrin Berberler</a></li>
                        <li><a href="#how-it-works" class="text-secondary text-decoration-none">Sistem Nasıl
                                Çalışır?</a></li>
                    </ul>
                </div>
                <div class="col-6 col-lg-2">
                    <h5 class="text-white text-uppercase fs-6 fw-bold mb-3">İşletmeler</h5>
                    <ul class="list-unstyled text-sm space-y-2">
                        <li><a href="/isletme/giris.php"
                                class="text-secondary text-decoration-none">Berber Giriş Paneli</a></li>
                        <li><a href="javascript:void(0)" onclick="openAuthModal('register', 'barber')"
                                class="text-secondary text-decoration-none">Salon Kaydı Oluştur</a></li>
                    </ul>
                </div>
                <div class="col-lg-4">
                    <h5 class="text-white text-uppercase fs-6 fw-bold mb-3">İletişim & Destek</h5>
                    <p class="text-secondary text-sm mb-1">E-posta: <?= htmlspecialchars($s['iletisim_eposta'] ?? 'destek@BiTıraş.com', ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="text-secondary text-sm mb-0">Telefon: <?= htmlspecialchars($s['iletisim_telefon'] ?? '0505 508 5158', ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </div>

            <div
                class="pt-4 border-top border-secondary d-flex flex-column flex-sm-row justify-content-between align-items-center text-xs">
                <p class="mb-2 mb-sm-0"><?= htmlspecialchars($s['footer_telif'] ?? '© 2026 BiTıraş.com - Tüm Hakları Saklıdır.', ENT_QUOTES, 'UTF-8') ?></p>
                <div class="fw-bold" style="color: var(--accent);">
                    <i class="fa-solid fa-code me-1"></i> Projected By SAT Team
                </div>
            </div>
        </div>
    </footer>
    <?php include 'login.php'; ?>

    <div id="toastContainer" class="toast-container-custom"></div>


    <div class="modal fade" id="dataPreviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow-lg p-4">
                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                    <div class="fw-bold text-dark fs-6 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-database" style="color: var(--accent);"></i> Backend Entegrasyon Veri
                        Paketi
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <p class="text-muted text-xs mb-2">
                    BackEnd Hazır Değil (FormData verileri yakalandı ve konsola aktarıldı):
                </p>

                <div class="bg-dark p-3 rounded-3 font-mono text-xs overflow-auto" style="color: var(--accent);">
                    <pre id="dataPreviewContent" class="mb-0">{}</pre>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    <button type="button" class="btn btn-dark btn-sm px-4" data-bs-dismiss="modal">Tamam,
                        Anlaşıldı</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>

    <script>window.businessSearchOptions = <?= json_encode([
        'cities' => array_keys($searchLocations),
        'locations' => $searchLocations,
        'services' => $availableBusinessServices,
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;</script>
    <script src="app.js"></script>
    <?php if (($_GET['login'] ?? '') === 'customer'): ?><script>document.addEventListener('DOMContentLoaded', () => openAuthModal('login', 'customer'));</script><?php endif; ?>
</body>

</html>