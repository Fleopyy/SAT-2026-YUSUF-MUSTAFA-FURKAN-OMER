<?php
require_once __DIR__ . '/data/privacy.php';
require_once __DIR__ . '/data/database.php';
require_once __DIR__ . '/admin/includes/auth.php';

$businessId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$businessId) {
    http_response_code(404);
    exit('İşletme bulunamadı.');
}
require_once __DIR__ . '/data/maps_config.php';

$stmt = $pdo->prepare("SELECT i.id, i.isletme_adi, i.isletme_sahibi, i.telefon, i.eposta, i.sehir, i.ilce,
                              i.mahalle, i.cadde_sokak, i.bina_no, i.daire_no, i.posta_kodu,
                              i.tam_adres, i.aciklama, i.latitude, i.longitude, i.place_id, i.location_verified_at,
                              i.is_vip, i.gece_acik,
                              cover.fotograf_yolu AS cover_photo, profile.fotograf_yolu AS profile_photo,
                              AVG(CASE WHEN d.degerlendiren_turu = 'customer' THEN d.puan END) AS average_rating,
                              COUNT(CASE WHEN d.degerlendiren_turu = 'customer' THEN d.id END) AS review_count
                       FROM isletmeler i
                       LEFT JOIN isletme_panel_medya m ON m.isletme_id = i.id
                       LEFT JOIN isletme_fotograflari cover ON cover.id = m.kapak_fotograf_id AND cover.isletme_id = i.id
                       LEFT JOIN isletme_fotograflari profile ON profile.id = m.profil_fotograf_id AND profile.isletme_id = i.id
                       LEFT JOIN randevular r ON r.isletme_id = i.id
                       LEFT JOIN degerlendirmeler d ON d.randevu_id = r.id
                       WHERE i.id = ? AND i.durum = 'active'
                       GROUP BY i.id");
$stmt->execute([$businessId]);
$business = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$business) {
    http_response_code(404);
    exit('İşletme bulunamadı.');
}

$displayAddress = trim((string)($business['tam_adres'] ?? ''));
if ($displayAddress === '') {
    $addrParts = array_filter([
        $business['mahalle'] ?? '',
        $business['cadde_sokak'] ?? '',
        !empty($business['bina_no']) ? 'No: ' . $business['bina_no'] : '',
        !empty($business['daire_no']) ? 'Daire: ' . $business['daire_no'] : '',
        $business['ilce'] ?? '',
        $business['sehir'] ?? '',
        $business['posta_kodu'] ?? '',
    ]);
    $displayAddress = implode(', ', $addrParts);
}

$hasCoordinates = false;
$lat = null;
$lng = null;
$googleMapsSearchUrl = '';
$googleMapsDirUrl = '';

if (!empty($business['location_verified_at']) && isset($business['latitude'], $business['longitude']) && is_numeric($business['latitude']) && is_numeric($business['longitude'])) {
    $latCandidate = (float)$business['latitude'];
    $lngCandidate = (float)$business['longitude'];
    if (isValidCoordinate($latCandidate, $lngCandidate)) {
        $lat = $latCandidate;
        $lng = $lngCandidate;
        $hasCoordinates = true;
        $formattedCoords = number_format($lat, 8, '.', '') . ',' . number_format($lng, 8, '.', '');
        $googleMapsSearchUrl = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($formattedCoords);
        $googleMapsDirUrl = 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode($formattedCoords);
    }
}

$stmt = $pdo->prepare('SELECT id, hizmet_adi, fiyat, sure_dakika FROM hizmetler WHERE isletme_id = ? AND aktif_mi = 1 ORDER BY hizmet_adi');
$stmt->execute([$businessId]);
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt = $pdo->prepare('SELECT c.id, c.ad_soyad, d.uzmanlik, d.fotograf_yolu FROM calisanlar c LEFT JOIN calisan_detaylari d ON d.calisan_id = c.id WHERE c.isletme_id = ? AND c.aktif_mi = 1 ORDER BY c.ad_soyad');
$stmt->execute([$businessId]);
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
$employeeServices = [];
$stmt = $pdo->prepare('SELECT hc.calisan_id, hc.hizmet_id FROM hizmet_calisanlari hc
                       INNER JOIN calisanlar c ON c.id = hc.calisan_id
                       INNER JOIN hizmetler h ON h.id = hc.hizmet_id
                       WHERE c.isletme_id = ? AND c.aktif_mi = 1 AND h.aktif_mi = 1');
$stmt->execute([$businessId]);
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $assignment) {
    $employeeServices[(int)$assignment['calisan_id']][] = (int)$assignment['hizmet_id'];
}
$stmt = $pdo->prepare('SELECT haftanin_gunu, baslangic, bitis, mola_baslangic, mola_bitis FROM isletme_calisma_araliklari WHERE isletme_id = ? ORDER BY haftanin_gunu, baslangic');
$stmt->execute([$businessId]);
$businessHours = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $period) {
    $businessHours[(int)$period['haftanin_gunu']][] = $period;
}
$stmt = $pdo->prepare('SELECT tarih, kapali, baslangic, bitis, not_metni FROM isletme_ozel_calisma_gunleri WHERE isletme_id = ? AND tarih >= CURDATE() ORDER BY tarih LIMIT 30');
$stmt->execute([$businessId]);
$specialBusinessHours = $stmt->fetchAll(PDO::FETCH_ASSOC);
$ownerScheduleConfigured = !empty($businessHours);
foreach ($specialBusinessHours as $specialHours) {
    if (!(int)$specialHours['kapali']) {
        $ownerScheduleConfigured = true;
        break;
    }
}
$businessDayNames = [1 => 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Pazar'];
$stmt = $pdo->prepare("SELECT f.fotograf_yolu FROM isletme_fotograflari f
                       LEFT JOIN isletme_fotograf_siralama s ON s.fotograf_id = f.id AND s.isletme_id = f.isletme_id
                       WHERE f.isletme_id = ?
                       ORDER BY COALESCE(s.sira, f.id), f.id");
$stmt->execute([$businessId]);
$gallery = $stmt->fetchAll(PDO::FETCH_COLUMN);

$featuredPhotos = array_filter([$business['cover_photo'] ?? null, $business['profile_photo'] ?? null]);
foreach (array_reverse($featuredPhotos) as $featuredPhoto) {
    if (!in_array($featuredPhoto, $gallery, true)) {
        array_unshift($gallery, $featuredPhoto);
    }
}
$gallery = array_values(array_unique($gallery));
$stmt = $pdo->prepare("SELECT d.puan, d.yorum, d.olusturulma_tarihi, k.ad_soyad, a.cevap
                       FROM degerlendirmeler d INNER JOIN randevular r ON r.id = d.randevu_id
                       LEFT JOIN kullanicilar k ON k.id = d.degerlendiren_id
                       LEFT JOIN isletme_yorum_cevaplari a ON a.degerlendirme_id = d.id AND a.isletme_id = r.isletme_id
                       WHERE r.isletme_id = ? AND d.degerlendiren_turu = 'customer'
                       ORDER BY d.olusturulma_tarihi DESC LIMIT 20");
$stmt->execute([$businessId]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

$currentCustomer = (($_SESSION['user_role'] ?? '') === 'customer' && !empty($_SESSION['user_id']));
$errorMessage = $_SESSION['mesaj'] ?? null;
$messageType = $_SESSION['mesaj_turu'] ?? 'info';
unset($_SESSION['mesaj'], $_SESSION['mesaj_turu']);
?>
<!DOCTYPE html>
<html lang="tr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="index,follow"><title><?= htmlspecialchars($business['isletme_adi'], ENT_QUOTES, 'UTF-8') ?> — BiTıraş</title><?php if (hasFunctionalConsent()): ?><link rel="preconnect" href="https://fonts.googleapis.com"><?php endif; ?><?php if (hasFunctionalConsent()): ?><link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"><?php endif; ?><?php if (hasFunctionalConsent()): ?><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"><?php endif; ?><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="/style.css"><?php if (hasFunctionalConsent()): ?><link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""><?php endif; ?></head>
<body><main class="container py-4" style="max-width:1100px">
    <a href="/" class="btn btn-outline-dark mb-3">← Ana sayfa</a>
    <?php if ($errorMessage): ?><div class="alert alert-<?= $messageType === 'success' ? 'success' : 'warning' ?>"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    <div class="business-profile-layout">
        <div class="business-profile-left">
    <section class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div style="height:280px;background:#0F172A;position:relative">
            <?php if ($business['cover_photo']): ?><img src="/<?= htmlspecialchars($business['cover_photo'], ENT_QUOTES, 'UTF-8') ?>" alt="Kapak Fotoğrafı" class="js-zoomable-img" style="width:100%;height:100%;object-fit:cover" title="Fotoğrafı büyüt"><?php endif; ?>
            <div style="position:absolute;inset:0;background:linear-gradient(transparent,rgba(15,23,42,.8))"></div>
            <div style="position:absolute;left:24px;right:24px;bottom:20px;color:white;display:flex;align-items:end;gap:16px">
                <?php if ($business['profile_photo']): ?><img src="/<?= htmlspecialchars($business['profile_photo'], ENT_QUOTES, 'UTF-8') ?>" alt="Profil Fotoğrafı" class="js-zoomable-img" style="width:74px;height:74px;object-fit:cover;border-radius:16px;border:2px solid #fff" title="Fotoğrafı büyüt"><?php endif; ?>
                <div>
                    <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                        <h1 style="color:white;margin:0"><?= htmlspecialchars($business['isletme_adi'], ENT_QUOTES, 'UTF-8') ?></h1>
                        <?php if (!empty($business['is_vip'])): ?>
                            <span class="badge" style="background:linear-gradient(135deg, #F59E0B, #D97706);color:#fff;font-weight:700;font-size:12px;padding:5px 10px;border-radius:20px;box-shadow:0 2px 6px rgba(0,0,0,0.3);">
                                <i class="fa-solid fa-crown me-1 text-warning-light"></i>VIP Salon
                            </span>
                        <?php endif; ?>
                        <?php if (!empty($business['gece_acik'])): ?>
                            <span class="badge" style="background:linear-gradient(135deg, #4F46E5, #3730A3);color:#fff;font-weight:700;font-size:12px;padding:5px 10px;border-radius:20px;box-shadow:0 2px 6px rgba(0,0,0,0.3);">
                                <i class="fa-solid fa-moon me-1" style="color:#C7D2FE;"></i>Gece Açık
                            </span>
                        <?php endif; ?>
                    </div>
                    <div><?= htmlspecialchars($business['sehir'] . ' · ' . $business['ilce'], ENT_QUOTES, 'UTF-8') ?> · <?= $business['average_rating'] !== null ? number_format((float)$business['average_rating'],1) . ' ★ (' . (int)$business['review_count'] . ')' : 'Henüz yorum yok' ?></div>
                </div>
            </div>
        </div>
        <div class="card-body p-4"><h2 class="h5">İşletme hakkında</h2><p style="white-space:pre-wrap"><?= htmlspecialchars($business['aciklama'] ?? 'İşletme tanıtımı henüz eklenmemiş.', ENT_QUOTES, 'UTF-8') ?></p><p class="mb-0"><strong>Adres:</strong> <?= htmlspecialchars(trim(($business['tam_adres'] ?? '') . ', ' . $business['ilce'] . ', ' . $business['sehir'], ', '), ENT_QUOTES, 'UTF-8') ?><br><strong>Telefon:</strong> <a href="tel:<?= htmlspecialchars($business['telefon'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($business['telefon'], ENT_QUOTES, 'UTF-8') ?></a></p></div>
    </section>

    <?php if ($hasCoordinates || $displayAddress !== ''): ?>
    <section class="card border-0 shadow-sm rounded-4 p-4 mb-4" id="isletme-konumu">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <h2 class="h5 mb-0" style="color:#102A43;font-weight:700;">
                <i class="fa-solid fa-map-location-dot me-2" style="color:var(--accent,#F97316)"></i>İşletme Adresi ve Harita Konumu
            </h2>
            <?php if ($hasCoordinates): ?>
                <span class="badge" style="background:#ECFDF5;color:#065F46;border:1px solid #A7F3D0;padding:6px 12px;border-radius:20px;font-size:12px;font-weight:600;">
                    <i class="fa-solid fa-circle-check me-1"></i>Doğrulanmış Konum
                </span>
            <?php endif; ?>
        </div>

        <div class="p-3 rounded-3 mb-3" style="background:#F8FAFC;border:1px solid #E2E8F0;">
            <div class="d-flex align-items-start gap-3">
                <div style="width:40px;height:40px;border-radius:10px;background:#FFF;border:1px solid #E2E8F0;display:flex;align-items:center;justify-content:center;color:#F97316;flex-shrink:0;box-shadow:0 1px 3px rgba(0,0,0,0.05);font-size:16px;">
                    <i class="fa-solid fa-location-dot"></i>
                </div>
                <div style="flex:1;min-width:0;">
                    <strong style="display:block;color:#1E293B;font-size:14px;margin-bottom:2px;">Açık Adres</strong>
                    <p style="margin:0;color:#334155;font-size:13.5px;line-height:1.5;">
                        <?= htmlspecialchars($displayAddress ?: 'Adres belirtilmemiş.', ENT_QUOTES, 'UTF-8') ?>
                    </p>
                    <div style="margin-top:4px;color:#64748B;font-size:12px;">
                        <?= htmlspecialchars($business['ilce'] . ' / ' . $business['sehir'], ENT_QUOTES, 'UTF-8') ?>
                        <?php if (!empty($business['posta_kodu'])): ?> · Posta Kodu: <?= htmlspecialchars($business['posta_kodu'], ENT_QUOTES, 'UTF-8') ?><?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($hasCoordinates): ?>
            <div id="customerProfileMap" style="width:100%;height:320px;border-radius:12px;overflow:hidden;border:1.5px solid #CBD5E1;margin-bottom:16px;background:#F8FAFC;position:relative;"><?php if (!hasFunctionalConsent()): ?><button type="button" onclick="openCookieSettings()">Harita icin islevsel cerez iznini yonetin</button><?php endif; ?></div>

            <div class="d-flex gap-2 flex-wrap">
                <a href="<?= htmlspecialchars($googleMapsDirUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary d-inline-flex align-items-center gap-2" style="background:#075B99;border-color:#075B99;border-radius:10px;padding:9px 18px;font-size:13.5px;font-weight:600;">
                    <i class="fa-solid fa-diamond-turn-right"></i>
                    <span>Google Maps ile Yol Tarifi Al</span>
                </a>
                <a href="<?= htmlspecialchars($googleMapsSearchUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2" style="border-radius:10px;padding:9px 18px;font-size:13.5px;font-weight:600;">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    <span>Google Maps'te Aç</span>
                </a>
            </div>
        <?php elseif ($displayAddress !== ''): ?>
            <div>
                <a href="https://www.google.com/maps/search/?api=1&query=<?= rawurlencode($displayAddress . ', ' . $business['ilce'] . ', ' . $business['sehir']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2" style="border-radius:10px;padding:8px 16px;font-size:13px;">
                    <i class="fa-solid fa-magnifying-glass-location"></i>
                    <span>Google Haritalar'da Adresi Ara</span>
                </a>
            </div>
        <?php endif; ?>
    </section>
    <?php endif; ?>
    <style>
        .business-profile-layout { display:grid; grid-template-columns:minmax(0, 1.55fr) minmax(340px, .95fr); gap:24px; align-items:start; }
        .business-profile-left { min-width:0; }
        .booking-sticky { position:sticky; top:16px; max-height:calc(100vh - 32px); overflow-y:auto; scrollbar-width:thin; }
        .booking-card { overflow:hidden; }
        .booking-steps { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; padding:20px 22px 16px; background:#F8FAFC; border-bottom:1px solid #E2E8F0; }
        .booking-step { color:#94A3B8; font-size:11px; font-weight:700; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .booking-step::before { content:""; display:block; height:3px; border-radius:3px; background:#E2E8F0; margin-bottom:8px; }
        .booking-step.active,.booking-step.done { color:#0F766E; }
        .booking-step.active::before,.booking-step.done::before { background:#10B981; }
        .booking-step span { color:inherit; margin-right:4px; }
        .booking-content { padding:22px; min-height:420px; }
        .booking-content h2 { font-size:18px; font-weight:800; color:#102A43; margin-bottom:18px; }
        .booking-choice { display:flex; align-items:center; gap:14px; width:100%; padding:13px 15px; margin-bottom:9px; border:1px solid #DCE4EE; border-radius:12px; background:#fff; color:#102A43; text-align:left; transition:background .15s,border-color .15s,transform .15s; }
        .booking-choice:hover { border-color:#94A3B8; transform:translateY(-1px); }
        .booking-choice.selected { background:#075B99; border-color:#075B99; color:#fff; }
        .booking-choice-mark { flex:0 0 7px; height:42px; border-radius:8px; background:#0EA5E9; }
        .booking-choice-copy { min-width:0; flex:1; }
        .booking-choice-copy strong,.booking-choice-copy small { display:block; }
        .booking-choice-copy strong { font-size:14px; }
        .booking-choice-copy small { margin-top:3px; font-size:12px; opacity:.78; }
        .booking-selected-check { display:none; color:#34D399; font-size:18px; }
        .booking-choice.selected .booking-selected-check { display:block; }
        .booking-employee-avatar { width:42px; height:42px; border-radius:50%; object-fit:cover; background:#F1F5F9; }
        .booking-date-list { display:flex; gap:9px; overflow-x:auto; padding:2px 2px 12px; scroll-snap-type:x mandatory; }
        .booking-date { flex:0 0 67px; min-height:82px; border:1px solid #DCE4EE; border-radius:12px; background:#fff; color:#64748B; text-align:center; padding:9px 5px; scroll-snap-align:start; }
        .booking-date strong,.booking-date small { display:block; }
        .booking-date strong { color:#102A43; font-size:20px; line-height:1.2; }
        .booking-date small { font-size:10px; text-transform:uppercase; letter-spacing:.5px; }
        .booking-date.selected { color:#fff; background:#075B99; border-color:#075B99; box-shadow:0 5px 12px #075b9930; }
        .booking-date.selected strong { color:#fff; }
        .booking-time-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:8px; margin-top:8px; }
        .booking-time { position:relative; min-height:39px; border:1px solid #DCE4EE; border-radius:9px; background:#fff; color:#102A43; font-size:13px; }
        .booking-time::after { content:""; position:absolute; top:5px; right:5px; width:6px; height:6px; border-radius:50%; background:#10B981; }
        .booking-time:hover,.booking-time.selected { border-color:#075B99; background:#EFF6FF; }
        .booking-time.unavailable { color:#94A3B8; text-decoration:line-through; background:#F8FAFC; cursor:not-allowed; }
        .booking-time.unavailable::after { background:#F87171; }
        .booking-nav { display:flex; justify-content:space-between; gap:10px; margin-top:18px; }
        .booking-nav button { border:1px solid #DCE4EE; border-radius:9px; background:#fff; color:#475569; padding:9px 14px; font-size:13px; }
        .booking-nav button.primary { background:#075B99; border-color:#075B99; color:#fff; }
        .booking-nav button:disabled { opacity:.45; cursor:not-allowed; }
        .booking-message { color:#64748B; font-size:13px; }
        .business-hours-card .hours-row { display:flex; justify-content:space-between; gap:12px; border-bottom:1px solid #E2E8F0; padding:8px 0; font-size:13px; }
        @media (max-width: 991.98px) {
            .business-profile-layout { grid-template-columns:minmax(0,1fr); }
            .booking-sticky { position:static; max-height:none; overflow:visible; }
        }
        @media (max-width: 420px) {
            .booking-content { padding:18px 15px; }
            .booking-steps { padding:16px 14px 12px; gap:6px; }
            .booking-step { font-size:10px; }
            .booking-time-grid { grid-template-columns:repeat(3,minmax(0,1fr)); }
        }

        .gallery-item-wrapper {
            position: relative;
            overflow: hidden;
            border-radius: 12px;
            aspect-ratio: 4/3;
            background: #F1F5F9;
            cursor: pointer;
            border: 1px solid rgba(0,0,0,0.06);
            box-shadow: 0 2px 6px rgba(0,0,0,0.04);
            transition: transform .25s cubic-bezier(0.4, 0, 0.2, 1), box-shadow .25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .gallery-item-wrapper:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px -6px rgba(0,0,0,0.18);
        }
        .gallery-thumb-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform .4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .gallery-item-wrapper:hover .gallery-thumb-img {
            transform: scale(1.08);
        }
        .gallery-item-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(15,23,42,0.6) 0%, rgba(15,23,42,0.1) 60%, transparent 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity .25s ease;
        }
        .gallery-item-wrapper:hover .gallery-item-overlay {
            opacity: 1;
        }
        .gallery-zoom-icon {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: rgba(255,255,255,0.95);
            color: #0F172A;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.25);
            transform: scale(0.85);
            transition: transform .25s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .gallery-item-wrapper:hover .gallery-zoom-icon {
            transform: scale(1);
        }

        .gallery-lightbox {
            position: fixed;
            inset: 0;
            z-index: 100000;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            opacity: 0;
            visibility: hidden;
            transition: opacity .28s ease, visibility .28s ease;
        }
        .gallery-lightbox.active {
            opacity: 1;
            visibility: visible;
        }
        .lightbox-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, 0.94);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            z-index: 1;
        }
        .lightbox-topbar {
            position: relative;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 24px;
            color: #fff;
        }
        .lightbox-counter {
            font-size: 13.5px;
            font-weight: 600;
            letter-spacing: 0.5px;
            background: rgba(255,255,255,0.12);
            padding: 6px 14px;
            border-radius: 20px;
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255,255,255,0.15);
        }
        .lightbox-close-btn {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.18);
            color: #fff;
            font-size: 19px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background .2s, transform .2s;
        }
        .lightbox-close-btn:hover {
            background: #EF4444;
            border-color: #EF4444;
            transform: scale(1.08);
        }
        .lightbox-stage {
            position: relative;
            z-index: 10;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 18px;
            overflow: hidden;
            user-select: none;
        }
        .lightbox-nav-btn {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.22);
            color: #fff;
            font-size: 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all .2s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(8px);
            flex-shrink: 0;
            z-index: 12;
        }
        .lightbox-nav-btn:hover {
            background: rgba(255,255,255,0.28);
            border-color: rgba(255,255,255,0.4);
            transform: scale(1.1);
        }
        .lightbox-nav-btn:active {
            transform: scale(0.94);
        }
        .lightbox-image-container {
            position: relative;
            flex: 1;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            max-height: calc(100vh - 180px);
            padding: 0 14px;
        }
        .lightbox-main-img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            border-radius: 12px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.6);
            transition: opacity .22s ease, transform .22s cubic-bezier(0.4, 0, 0.2, 1);
            transform: scale(1);
            opacity: 1;
        }
        .lightbox-main-img.changing {
            opacity: 0.15;
            transform: scale(0.96);
        }
        .lightbox-thumbs-bar {
            position: relative;
            z-index: 10;
            display: flex;
            gap: 10px;
            justify-content: center;
            align-items: center;
            padding: 14px 20px 20px;
            overflow-x: auto;
            max-width: 100%;
            scrollbar-width: none;
        }
        .lightbox-thumbs-bar::-webkit-scrollbar { display: none; }
        .lightbox-thumb-btn {
            border: 2px solid transparent;
            border-radius: 8px;
            overflow: hidden;
            width: 64px;
            height: 48px;
            padding: 0;
            background: none;
            opacity: 0.45;
            cursor: pointer;
            transition: all .2s;
            flex-shrink: 0;
        }
        .lightbox-thumb-btn img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .lightbox-thumb-btn:hover {
            opacity: 0.85;
            transform: translateY(-2px);
        }
        .lightbox-thumb-btn.active {
            opacity: 1;
            border-color: #F97316;
            box-shadow: 0 0 14px rgba(249,115,22,0.7);
            transform: scale(1.1);
        }
        @media (max-width: 576px) {
            .lightbox-topbar { padding: 12px 16px; }
            .lightbox-stage { padding: 6px 8px; }
            .lightbox-nav-btn { width: 42px; height: 42px; font-size: 16px; }
            .lightbox-image-container { max-height: calc(100vh - 150px); padding: 0 6px; }
            .lightbox-thumb-btn { width: 50px; height: 38px; }
        }
    </style>
            <?php if ($gallery): ?>
            <section class="card border-0 shadow-sm rounded-4 p-4 mb-4" id="fotograf-galerisi">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h2 class="h5">Fotoğraf galerisi</h2>
                    <span class="badge" style="background:#F1F5F9;color:#475569;border:1px solid #E2E8F0;padding:6px 12px;border-radius:20px;font-size:12px;font-weight:600;">
                        <?= count($gallery) ?> Fotoğraf
                    </span>
                </div>
                <div class="row g-3" id="galleryGrid">
                    <?php foreach ($gallery as $index => $photo): ?>
                        <div class="col-6 col-md-3">
                            <div class="gallery-item-wrapper" data-gallery-index="<?= $index ?>" role="button" tabindex="0" aria-label="Fotoğrafı büyüt ve incele">
                                <img src="/<?= htmlspecialchars($photo, ENT_QUOTES, 'UTF-8') ?>" 
                                     alt="İşletme fotoğrafı" 
                                     loading="lazy"
                                     class="gallery-thumb-img">
                                <div class="gallery-item-overlay">
                                    <span class="gallery-zoom-icon">
                                        <i class="fa-solid fa-magnifying-glass-plus"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>
            <section class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                <h2 class="h5">Hizmetler ve fiyatlar</h2>
                <?php if (!$services): ?><p class="mb-0">Aktif hizmet bulunmuyor.</p><?php else: ?>
                    <div class="table-responsive"><table class="table mb-0"><thead><tr><th>Hizmet</th><th>Süre</th><th>Fiyat</th></tr></thead><tbody>
                    <?php foreach ($services as $service): ?><tr><td><?= htmlspecialchars($service['hizmet_adi'], ENT_QUOTES, 'UTF-8') ?></td><td><?= (int)$service['sure_dakika'] ?> dk</td><td><?= number_format((float)$service['fiyat'],2,',','.') ?> ₺</td></tr><?php endforeach; ?>
                    </tbody></table></div>
                <?php endif; ?>
            </section>
            <section class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                <h2 class="h5">Çalışanlar</h2>
                <div class="row g-3">
                    <div class="col-md-6"><div class="border rounded-3 p-3 h-100"><strong><?= htmlspecialchars($business['isletme_sahibi'], ENT_QUOTES, 'UTF-8') ?></strong><small class="d-block text-muted">İşletme sahibi</small></div></div>
                    <?php foreach ($employees as $employee): ?>
                        <div class="col-md-6"><div class="border rounded-3 p-3 h-100">
                            <?php if ($employee['fotograf_yolu']): ?><img src="/<?= htmlspecialchars($employee['fotograf_yolu'], ENT_QUOTES, 'UTF-8') ?>" alt="" style="width:48px;height:48px;object-fit:cover;border-radius:50%;float:right"><?php endif; ?>
                            <strong><?= htmlspecialchars($employee['ad_soyad'], ENT_QUOTES, 'UTF-8') ?></strong><small class="d-block text-muted"><?= htmlspecialchars($employee['uzmanlik'] ?? '', ENT_QUOTES, 'UTF-8') ?></small>
                        </div></div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php if ($ownerScheduleConfigured): ?>
            <section class="card border-0 shadow-sm rounded-4 p-4 mb-4 business-hours-card">
                <h2 class="h5"><i class="fa-regular fa-clock me-2"></i>Çalışma saatleri</h2>
                <?php foreach ($businessDayNames as $day => $dayName): ?>
                    <div class="hours-row">
                        <strong><?= htmlspecialchars($dayName, ENT_QUOTES, 'UTF-8') ?></strong>
                        <span class="text-end">
                            <?php if (empty($businessHours[$day])): ?>Kapalı<?php else: foreach ($businessHours[$day] as $period): ?>
                                <?= htmlspecialchars(substr($period['baslangic'], 0, 5) . '–' . substr($period['bitis'], 0, 5), ENT_QUOTES, 'UTF-8') ?>
                                <?php if ($period['mola_baslangic']): ?><small class="d-block text-muted">Mola <?= htmlspecialchars(substr($period['mola_baslangic'], 0, 5) . '–' . substr($period['mola_bitis'], 0, 5), ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
                            <?php endforeach; endif; ?>
                        </span>
                    </div>
                <?php endforeach; ?>
                <?php if ($specialBusinessHours): ?><div class="small mt-3"><strong>Özel günler</strong>
                    <?php foreach ($specialBusinessHours as $specialHours): ?><div class="hours-row"><span><?= date('d.m.Y', strtotime($specialHours['tarih'])) ?><?= $specialHours['not_metni'] ? ' · ' . htmlspecialchars($specialHours['not_metni'], ENT_QUOTES, 'UTF-8') : '' ?></span><span><?= (int)$specialHours['kapali'] ? 'Kapalı' : htmlspecialchars(substr($specialHours['baslangic'], 0, 5) . '–' . substr($specialHours['bitis'], 0, 5), ENT_QUOTES, 'UTF-8') ?></span></div><?php endforeach; ?>
                </div><?php endif; ?>
            </section>
        <?php endif; ?>
        </div>
        <aside class="booking-sticky">
        <section class="card border-0 shadow-sm rounded-4 booking-card">
            <div class="booking-steps" aria-label="Randevu adımları">
                <div class="booking-step active" data-booking-step-label="1"><span>01</span> Hizmet</div>
                <div class="booking-step" data-booking-step-label="2"><span>02</span> Personel</div>
                <div class="booking-step" data-booking-step-label="3"><span>03</span> Tarih</div>
                <div class="booking-step" data-booking-step-label="4"><span>04</span> Onay</div>
            </div>
            <div class="booking-content">
                <h2 id="bookingTitle">Hizmet seçin</h2>
                <?php if (!$currentCustomer): ?>
                    <p class="text-muted">Randevu oluşturmak için müşteri hesabınızla giriş yapın.</p><a href="/?login=customer" class="btn btn-primary w-100">Giriş yap</a>
                <?php elseif (!$services): ?>
                    <p class="text-muted">Bu işletme şu anda çevrimiçi randevu kabul etmiyor.</p>
                <?php else: ?>
                    <form method="POST" action="/randevu_olustur.php" id="bookingForm">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="business_id" value="<?= (int)$businessId ?>">
                        <input type="hidden" name="service_id" id="bookingService" required>
                        <input type="hidden" name="employee_id" id="bookingEmployee" required>
                        <input type="hidden" name="start" id="bookingStart" required>
                        <div data-booking-panel="1">
                            <?php foreach ($services as $service): ?>
                                <button type="button" class="booking-choice" data-service-id="<?= (int)$service['id'] ?>" data-service-name="<?= htmlspecialchars($service['hizmet_adi'], ENT_QUOTES, 'UTF-8') ?>" data-service-price="<?= number_format((float)$service['fiyat'],2,',','.') ?>" data-service-duration="<?= (int)$service['sure_dakika'] ?>">
                                    <span class="booking-choice-mark"></span><span class="booking-choice-copy"><strong><?= htmlspecialchars($service['hizmet_adi'], ENT_QUOTES, 'UTF-8') ?></strong><small><?= (int)$service['sure_dakika'] ?> dakika · <?= number_format((float)$service['fiyat'],2,',','.') ?> ₺</small></span><i class="fa-solid fa-check booking-selected-check"></i>
                                </button>
                            <?php endforeach; ?>
                        </div>
                        <div data-booking-panel="2" hidden>
                            <?php if ($ownerScheduleConfigured): ?>
                                <button type="button" class="booking-choice" data-employee-id="0" data-employee-name="<?= htmlspecialchars('İşletme sahibi — ' . $business['isletme_sahibi'], ENT_QUOTES, 'UTF-8') ?>">
                                    <img class="booking-employee-avatar" src="<?= $business['profile_photo'] ? '/' . htmlspecialchars($business['profile_photo'], ENT_QUOTES, 'UTF-8') : 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 40 40%22%3E%3Crect width=%2240%22 height=%2240%22 rx=%2220%22 fill=%22%23E2E8F0%22/%3E%3Ctext x=%2220%22 y=%2226%22 text-anchor=%22middle%22 font-size=%2218%22 fill=%22%23475569%22%3E%3F%3C/text%3E%3C/svg%3E' ?>" alt="">
                                    <span class="booking-choice-copy"><strong>İşletme sahibi — <?= htmlspecialchars($business['isletme_sahibi'], ENT_QUOTES, 'UTF-8') ?></strong><small>İşletme çalışma saatlerine göre</small></span><i class="fa-solid fa-check booking-selected-check"></i>
                                </button>
                            <?php endif; ?>
                            <?php foreach ($employees as $employee): ?>
                                <button type="button" class="booking-choice" data-employee-id="<?= (int)$employee['id'] ?>" data-service-ids="<?= htmlspecialchars(implode(',', $employeeServices[(int)$employee['id']] ?? []), ENT_QUOTES, 'UTF-8') ?>" data-employee-name="<?= htmlspecialchars($employee['ad_soyad'], ENT_QUOTES, 'UTF-8') ?>">
                                    <?php if ($employee['fotograf_yolu']): ?><img class="booking-employee-avatar" src="/<?= htmlspecialchars($employee['fotograf_yolu'], ENT_QUOTES, 'UTF-8') ?>" alt=""><?php else: ?><span class="booking-employee-avatar d-flex align-items-center justify-content-center"><i class="fa-solid fa-user"></i></span><?php endif; ?>
                                    <span class="booking-choice-copy"><strong><?= htmlspecialchars($employee['ad_soyad'], ENT_QUOTES, 'UTF-8') ?></strong><small><?= htmlspecialchars($employee['uzmanlik'] ?: 'Uygun personel', ENT_QUOTES, 'UTF-8') ?></small></span><i class="fa-solid fa-check booking-selected-check"></i>
                                </button>
                            <?php endforeach; ?>
                            <p class="booking-message" id="employeeMessage" hidden>Bu işletmede randevu için uygun personel bulunmuyor.</p>
                        </div>
                        <div data-booking-panel="3" hidden>
                            <div class="booking-date-list" id="bookingDates" aria-label="Tarih seçin"></div>
                            <p class="booking-message mt-2" id="bookingAvailabilityMessage" aria-live="polite">Uygun saatler yükleniyor...</p>
                            <div class="booking-time-grid" id="bookingTimes" aria-label="Saat seçin"></div>
                            <div class="booking-message mt-3">Gösterilen boş saatler seçtiğiniz personelin çalışma programına ve işletmenin çalışma saatlerine göre hesaplanır.</div>
                        </div>
                        <div data-booking-panel="4" hidden>
                            <div class="border rounded-3 p-3 mb-3">
                                <div class="small text-muted">Hizmet</div><strong id="bookingSummaryService"></strong>
                                <div class="small text-muted mt-3">Personel</div><strong id="bookingSummaryEmployee"></strong>
                                <div class="small text-muted mt-3">Tarih ve saat</div><strong id="bookingSummaryDate"></strong>
                            </div>
                            <label class="form-label" for="bookingNote">Not (isteğe bağlı)</label>
                            <textarea name="note" id="bookingNote" maxlength="1000" class="form-control" rows="3"></textarea>
                            <p class="booking-message mt-3">Randevu isteğiniz işletme tarafından onaylanana kadar beklemede kalır.</p>
                        </div>
                        <div class="booking-nav">
                            <button type="button" id="bookingBack" hidden>← Geri</button>
                            <button type="button" class="primary" id="bookingNext" disabled>Devam et →</button>
                            <button type="submit" class="primary" id="bookingSubmit" hidden>Randevu isteği gönder</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </section>
        </aside>
    </div>
    <section class="card border-0 shadow-sm rounded-4 p-4"><h2 class="h5">Müşteri yorumları</h2><?php if (!$reviews): ?><p>Henüz yorum yok.</p><?php else: foreach ($reviews as $review): ?><article class="border-bottom py-3"><strong><?= htmlspecialchars($review['ad_soyad'] ?? 'Müşteri', ENT_QUOTES, 'UTF-8') ?></strong> · <span style="color:#D97706"><?= str_repeat('★',(int)$review['puan']) ?></span><small class="float-end"><?= date('d.m.Y',strtotime($review['olusturulma_tarihi'])) ?></small><p class="mt-2"><?= htmlspecialchars($review['yorum'] ?? '', ENT_QUOTES, 'UTF-8') ?></p><?php if ($review['cevap']): ?><div class="border-start border-3 ps-3"><strong>İşletme yanıtı</strong><p><?= htmlspecialchars($review['cevap'], ENT_QUOTES, 'UTF-8') ?></p></div><?php endif; ?></article><?php endforeach; endif; ?></section>
</main>
<?php if ($currentCustomer && $services): ?>
<script>
(() => {
    const form = document.getElementById('bookingForm');
    if (!form) return;
    const panels = [...form.querySelectorAll('[data-booking-panel]')];
    const stepLabels = [...document.querySelectorAll('[data-booking-step-label]')];
    const title = document.getElementById('bookingTitle');
    const back = document.getElementById('bookingBack');
    const next = document.getElementById('bookingNext');
    const submit = document.getElementById('bookingSubmit');
    const serviceInput = document.getElementById('bookingService');
    const employeeInput = document.getElementById('bookingEmployee');
    const startInput = document.getElementById('bookingStart');
    const dateContainer = document.getElementById('bookingDates');
    const timeContainer = document.getElementById('bookingTimes');
    const availabilityMessage = document.getElementById('bookingAvailabilityMessage');
    const employeeMessage = document.getElementById('employeeMessage');
    const employeeChoices = [...form.querySelectorAll('[data-employee-id]')];
    const titles = {1:'Hizmet seçin',2:'Hangi personel ile ilgilenmek istersiniz?',3:'Tarih ve saatinizi seçin',4:'Randevunuzu onaylayın'};
    const dayNames = ['Paz','Pzt','Sal','Çar','Per','Cum','Cmt'];
    const monthNames = ['Oca','Şub','Mar','Nis','May','Haz','Tem','Ağu','Eyl','Eki','Kas','Ara'];
    const today = new Date();
    today.setHours(0,0,0,0);
    const dates = Array.from({length:30}, (_, offset) => {
        const date = new Date(today);
        date.setDate(today.getDate() + offset);
        return date;
    });
    let step = 1;
    let selectedDate = '';
    let selectedTime = '';
    let availabilityRequest = 0;

    function setStep(value) {
        step = value;
        panels.forEach(panel => panel.hidden = Number(panel.dataset.bookingPanel) !== step);
        stepLabels.forEach(label => {
            const labelStep = Number(label.dataset.bookingStepLabel);
            label.classList.toggle('active', labelStep === step);
            label.classList.toggle('done', labelStep < step);
        });
        title.textContent = titles[step];
        back.hidden = step === 1;
        next.hidden = step === 4;
        submit.hidden = step !== 4;
        next.textContent = 'Devam et →';
        updateNavigation();
    }

    function updateNavigation() {
        if (step === 1) next.disabled = !serviceInput.value;
        else if (step === 2) next.disabled = employeeInput.value === '';
        else if (step === 3) next.disabled = !startInput.value;
        else next.disabled = false;
    }

    function updateEmployeeChoices() {
        let visibleChoices = 0;
        employeeChoices.forEach(button => {
            const compatible = button.dataset.employeeId === '0'
                || button.dataset.serviceIds.split(',').includes(serviceInput.value);
            button.hidden = !compatible;
            button.classList.remove('selected');
            if (compatible) visibleChoices++;
        });
        employeeInput.value = '';
        if (employeeMessage) employeeMessage.hidden = visibleChoices > 0;
        updateNavigation();
    }

    function formatDate(date) {
        return date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2,'0') + '-' + String(date.getDate()).padStart(2,'0');
    }

    function renderDates() {
        dateContainer.replaceChildren();
        dates.forEach((date, index) => {
            const key = formatDate(date);
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'booking-date' + (key === selectedDate ? ' selected' : '');
            button.innerHTML = '<small>' + (index === 0 ? 'Bugün' : dayNames[date.getDay()]) + '</small><strong>' + date.getDate() + '</strong><small>' + monthNames[date.getMonth()] + '</small>';
            button.addEventListener('click', () => {
                selectedDate = key;
                selectedTime = '';
                startInput.value = '';
                renderDates();
                loadAvailability();
            });
            dateContainer.appendChild(button);
        });
    }

    async function loadAvailability() {
        const requestId = ++availabilityRequest;
        timeContainer.replaceChildren();
        availabilityMessage.textContent = 'Uygun saatler yükleniyor...';
        const params = new URLSearchParams({
            business_id: '<?= (int)$businessId ?>',
            employee_id: employeeInput.value,
            service_id: serviceInput.value,
            date: selectedDate
        });
        try {
            const response = await fetch('/isletme/musait-saatler.php?' + params.toString(), {headers:{'Accept':'application/json'}});
            const data = await response.json();
            if (requestId !== availabilityRequest) return;
            if (!response.ok) throw new Error(data.error || 'Saatler yüklenemedi.');
            if (!data.slots.length) {
                availabilityMessage.textContent = 'Bu tarihte uygun çalışma saati bulunmuyor.';
                updateNavigation();
                return;
            }
            availabilityMessage.textContent = data.slots.some(slot => slot.available)
                ? 'Uygun bir saat seçin.'
                : 'Seçilen tarihte uygun randevu saati kalmadı.';
            data.slots.forEach(slot => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'booking-time' + (slot.available ? '' : ' unavailable');
                button.textContent = slot.time;
                button.disabled = !slot.available;
                button.setAttribute('aria-label', slot.time + (slot.available ? ' müsait' : ' müsait değil'));
                button.addEventListener('click', () => {
                    selectedTime = slot.time;
                    timeContainer.querySelectorAll('.booking-time').forEach(item => item.classList.remove('selected'));
                    button.classList.add('selected');
                    startInput.value = selectedDate + 'T' + selectedTime;
                    updateNavigation();
                });
                timeContainer.appendChild(button);
            });
            updateNavigation();
        } catch (error) {
            if (requestId !== availabilityRequest) return;
            availabilityMessage.textContent = error.message || 'Saatler yüklenemedi. Lütfen tekrar deneyin.';
        }
    }

    form.querySelectorAll('[data-service-id]').forEach(button => button.addEventListener('click', () => {
        form.querySelectorAll('[data-service-id]').forEach(item => item.classList.remove('selected'));
        button.classList.add('selected');
        serviceInput.value = button.dataset.serviceId;
        startInput.value = '';
        selectedTime = '';
        updateEmployeeChoices();
    }));

    employeeChoices.forEach(button => button.addEventListener('click', () => {
        form.querySelectorAll('[data-employee-id]').forEach(item => item.classList.remove('selected'));
        button.classList.add('selected');
        employeeInput.value = button.dataset.employeeId;
        startInput.value = '';
        selectedTime = '';
        selectedDate = formatDate(dates[0]);
        if (employeeMessage) employeeMessage.hidden = true;
        renderDates();
        loadAvailability();
        updateNavigation();
    }));

    next.addEventListener('click', () => {
        if (step === 2 && !employeeInput.value) {
            if (employeeMessage) employeeMessage.hidden = false;
            return;
        }
        if (step === 3) {
            const service = form.querySelector('[data-service-id].selected');
            const employee = form.querySelector('[data-employee-id].selected');
            document.getElementById('bookingSummaryService').textContent = service.dataset.serviceName + ' · ' + service.dataset.serviceDuration + ' dk · ' + service.dataset.servicePrice + ' ₺';
            document.getElementById('bookingSummaryEmployee').textContent = employee.dataset.employeeName;
            const date = dates.find(item => formatDate(item) === selectedDate);
            document.getElementById('bookingSummaryDate').textContent = date.toLocaleDateString('tr-TR', {weekday:'long',day:'numeric',month:'long',year:'numeric'}) + ' · ' + selectedTime;
        }
        setStep(Math.min(step + 1, 4));
    });
    back.addEventListener('click', () => setStep(Math.max(step - 1, 1)));
    form.addEventListener('submit', event => {
        if (!serviceInput.value || employeeInput.value === '' || !startInput.value) {
            event.preventDefault();
            return;
        }
        submit.disabled = true;
        submit.textContent = 'Gönderiliyor...';
    });
    renderDates();
    setStep(1);
})();
</script>
<?php endif; ?>

<?php if ($hasCoordinates && hasFunctionalConsent()): ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
(function() {
    var lat = <?= number_format($lat, 8, '.', '') ?>;
    var lng = <?= number_format($lng, 8, '.', '') ?>;
    var businessName = <?= json_encode($business['isletme_adi'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var dirUrl = <?= json_encode($googleMapsDirUrl, JSON_UNESCAPED_UNICODE) ?>;
    var mapEl = document.getElementById('customerProfileMap');
    if (!mapEl) return;

    var map = L.map('customerProfileMap', {
        center: [lat, lng],
        zoom: 16,
        scrollWheelZoom: false
    });

    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    var marker = L.marker([lat, lng]).addTo(map);
    marker.bindPopup(
        '<div style="text-align:center;padding:4px;">' +
            '<strong style="font-size:13.5px;color:#102A43;">' + businessName.replace(/[&<>"']/g, function(c) { return '&#' + c.charCodeAt(0) + ';'; }) + '</strong><br>' +
            '<div style="margin-top:6px;">' +
                '<a href="' + dirUrl + '" target="_blank" rel="noopener noreferrer" style="display:inline-block;padding:4px 10px;border-radius:6px;background:#075B99;color:#fff;font-size:11.5px;font-weight:600;text-decoration:none;">' +
                    '<i class="fa-solid fa-diamond-turn-right me-1"></i> Yol Tarifi Al' +
                '</a>' +
            '</div>' +
        '</div>'
    ).openPopup();
})();
</script>
<?php endif; ?>

<!-- BİTIRAŞ FOTOĞRAF GALERİSİ LIGHTBOX MODAL -->
<div id="bitirasGalleryLightbox" class="gallery-lightbox" aria-hidden="true" role="dialog" aria-modal="true">
    <div class="lightbox-backdrop" id="lightboxBackdrop"></div>
    
    <!-- Üst Kontrol Çubuğu -->
    <div class="lightbox-topbar">
        <div class="lightbox-counter" id="lightboxCounter">1 / 1</div>
        <div class="lightbox-actions">
            <button type="button" class="lightbox-close-btn" id="lightboxCloseBtn" aria-label="Kapat (Esc)">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    </div>

    <!-- Ana Görsel Sahnesi -->
    <div class="lightbox-stage" id="lightboxStage">
        <button type="button" class="lightbox-nav-btn prev" id="lightboxPrevBtn" aria-label="Önceki Fotoğraf (Sol Ok)">
            <i class="fa-solid fa-chevron-left"></i>
        </button>

        <div class="lightbox-image-container" id="lightboxImgContainer">
            <img src="" alt="İşletme fotoğrafı" class="lightbox-main-img" id="lightboxMainImg">
        </div>

        <button type="button" class="lightbox-nav-btn next" id="lightboxNextBtn" aria-label="Sonraki Fotoğraf (Sağ Ok)">
            <i class="fa-solid fa-chevron-right"></i>
        </button>
    </div>

    <!-- Alt Küçük Resim Şeridi (Thumbnails) -->
    <div class="lightbox-thumbs-bar" id="lightboxThumbsBar"></div>
</div>

<script>
(function() {
    var galleryPhotos = <?= json_encode(array_values(array_map(function($p) { return '/' . ltrim($p, '/'); }, $gallery ?? [])), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    var lightbox = document.getElementById('bitirasGalleryLightbox');
    if (!lightbox) return;

    var mainImg = document.getElementById('lightboxMainImg');
    var counter = document.getElementById('lightboxCounter');
    var prevBtn = document.getElementById('lightboxPrevBtn');
    var nextBtn = document.getElementById('lightboxNextBtn');
    var closeBtn = document.getElementById('lightboxCloseBtn');
    var backdrop = document.getElementById('lightboxBackdrop');
    var thumbsBar = document.getElementById('lightboxThumbsBar');
    var stage = document.getElementById('lightboxStage');

    var currentList = (galleryPhotos && galleryPhotos.length) ? galleryPhotos.slice() : [];
    var currentIndex = 0;
    var isOpen = false;

    function buildThumbnails() {
        if (!thumbsBar) return;
        thumbsBar.innerHTML = '';
        if (currentList.length <= 1) {
            thumbsBar.style.display = 'none';
            return;
        }
        thumbsBar.style.display = 'flex';
        currentList.forEach(function(src, idx) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'lightbox-thumb-btn' + (idx === currentIndex ? ' active' : '');
            btn.setAttribute('aria-label', 'Fotoğraf ' + (idx + 1));
            var img = document.createElement('img');
            img.src = src;
            img.alt = '';
            btn.appendChild(img);
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                goToPhoto(idx);
            });
            thumbsBar.appendChild(btn);
        });
    }

    function updateView() {
        if (!currentList.length) return;
        if (currentIndex < 0) currentIndex = currentList.length - 1;
        if (currentIndex >= currentList.length) currentIndex = 0;

        var src = currentList[currentIndex];
        
        mainImg.classList.add('changing');
        setTimeout(function() {
            mainImg.src = src;
            mainImg.classList.remove('changing');
        }, 120);

        counter.textContent = (currentIndex + 1) + ' / ' + currentList.length;

        if (currentList.length <= 1) {
            prevBtn.style.display = 'none';
            nextBtn.style.display = 'none';
        } else {
            prevBtn.style.display = 'flex';
            nextBtn.style.display = 'flex';
        }

        if (thumbsBar) {
            var thumbBtns = thumbsBar.querySelectorAll('.lightbox-thumb-btn');
            thumbBtns.forEach(function(btn, idx) {
                if (idx === currentIndex) {
                    btn.classList.add('active');
                    btn.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
                } else {
                    btn.classList.remove('active');
                }
            });
        }

        if (currentList.length > 1) {
            var nextIdx = (currentIndex + 1) % currentList.length;
            var prevIdx = (currentIndex - 1 + currentList.length) % currentList.length;
            new Image().src = currentList[nextIdx];
            new Image().src = currentList[prevIdx];
        }
    }

    function openLightbox(index, customList) {
        if (customList && customList.length) {
            currentList = customList;
        } else {
            currentList = (galleryPhotos && galleryPhotos.length) ? galleryPhotos.slice() : [];
        }
        if (!currentList.length) return;

        currentIndex = index || 0;
        isOpen = true;
        lightbox.classList.add('active');
        lightbox.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';

        buildThumbnails();
        updateView();
    }

    function closeLightbox() {
        if (!isOpen) return;
        isOpen = false;
        lightbox.classList.remove('active');
        lightbox.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        setTimeout(function() {
            mainImg.src = '';
        }, 250);
    }

    function goToPhoto(idx) {
        currentIndex = idx;
        updateView();
    }

    function nextPhoto() {
        if (currentList.length <= 1) return;
        currentIndex = (currentIndex + 1) % currentList.length;
        updateView();
    }

    function prevPhoto() {
        if (currentList.length <= 1) return;
        currentIndex = (currentIndex - 1 + currentList.length) % currentList.length;
        updateView();
    }

    if (nextBtn) nextBtn.addEventListener('click', function(e) { e.stopPropagation(); nextPhoto(); });
    if (prevBtn) prevBtn.addEventListener('click', function(e) { e.stopPropagation(); prevPhoto(); });
    if (closeBtn) closeBtn.addEventListener('click', function(e) { e.stopPropagation(); closeLightbox(); });
    if (backdrop) backdrop.addEventListener('click', closeLightbox);

    window.addEventListener('keydown', function(e) {
        if (!isOpen) return;
        if (e.key === 'Escape') {
            closeLightbox();
        } else if (e.key === 'ArrowRight') {
            nextPhoto();
        } else if (e.key === 'ArrowLeft') {
            prevPhoto();
        }
    });

    var touchStartX = 0;
    var touchStartY = 0;
    stage.addEventListener('touchstart', function(e) {
        if (e.touches && e.touches.length === 1) {
            touchStartX = e.touches[0].clientX;
            touchStartY = e.touches[0].clientY;
        }
    }, { passive: true });

    stage.addEventListener('touchend', function(e) {
        if (e.changedTouches && e.changedTouches.length === 1) {
            var diffX = e.changedTouches[0].clientX - touchStartX;
            var diffY = e.changedTouches[0].clientY - touchStartY;
            if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > 40) {
                if (diffX < 0) {
                    nextPhoto();
                } else {
                    prevPhoto();
                }
            }
        }
    }, { passive: true });

    document.querySelectorAll('.gallery-item-wrapper').forEach(function(el) {
        el.addEventListener('click', function() {
            var idx = parseInt(el.getAttribute('data-gallery-index'), 10) || 0;
            openLightbox(idx, galleryPhotos);
        });
        el.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                el.click();
            }
        });
    });

    document.querySelectorAll('.js-zoomable-img').forEach(function(img) {
        img.style.cursor = 'pointer';
        img.addEventListener('click', function() {
            var src = img.getAttribute('src');
            if (!src) return;
            var targetIdx = galleryPhotos.indexOf(src);
            if (targetIdx !== -1) {
                openLightbox(targetIdx, galleryPhotos);
            } else {
                openLightbox(0, [src].concat(galleryPhotos));
            }
        });
    });
})();
</script>
<?php include __DIR__ . '/includes/cookie_banner.php'; ?>
</body></html>
