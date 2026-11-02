<?php
header('Content-Type: text/html; charset=utf-8');
require_once __DIR__ . '/../data/privacy.php';
require_once __DIR__ . '/_common.php';
require_once dirname(__DIR__) . '/data/maps_config.php';

$currentBusinessPage = 'profile';
$pageTitle = 'İşletme Profilim';
$errors = [];

// Load maps configuration
$mapsConfig = mapsSettings();
$googleMapsApiKey = $mapsConfig['google_maps_api_key'];
$hasGoogleMapsKey = $mapsConfig['has_api_key'];

// Load Turkey districts dataset
$turkeyDistricts = [];
$districtsJsonPath = dirname(__DIR__) . '/data/turkey_districts.json';
if (file_exists($districtsJsonPath)) {
    $decodedDistricts = json_decode(file_get_contents($districtsJsonPath), true);
    if (!empty($decodedDistricts['districts']) && is_array($decodedDistricts['districts'])) {
        $turkeyDistricts = $decodedDistricts['districts'];
    }
}
$allCities = array_keys($turkeyDistricts);
sort($allCities, SORT_LOCALE_STRING);

// Fetch complete and fresh business record
$stmt = $pdo->prepare('SELECT * FROM isletmeler WHERE id = ?');
$stmt->execute([$businessId]);
$freshBusiness = $stmt->fetch(PDO::FETCH_ASSOC);
if ($freshBusiness) {
    $business = array_merge($business, $freshBusiness);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    businessRequirePost();

    $fields = [
        'isletme_adi'    => trim((string)($_POST['isletme_adi']    ?? '')),
        'isletme_sahibi' => trim((string)($_POST['isletme_sahibi'] ?? '')),
        'telefon'        => trim((string)($_POST['telefon']        ?? '')),
        'eposta'         => trim((string)($_POST['eposta']         ?? '')),
        'sehir'          => trim((string)($_POST['sehir']          ?? '')),
        'ilce'           => trim((string)($_POST['ilce']           ?? '')),
        'mahalle'        => trim((string)($_POST['mahalle']        ?? '')),
        'cadde_sokak'    => trim((string)($_POST['cadde_sokak']    ?? '')),
        'bina_no'        => trim((string)($_POST['bina_no']        ?? '')),
        'daire_no'       => trim((string)($_POST['daire_no']       ?? '')),
        'posta_kodu'     => trim((string)($_POST['posta_kodu']     ?? '')),
        'tam_adres'      => trim((string)($_POST['tam_adres']      ?? '')),
        'aciklama'       => trim((string)($_POST['aciklama']       ?? '')),
        'latitude'       => trim((string)($_POST['latitude']       ?? '')),
        'longitude'      => trim((string)($_POST['longitude']      ?? '')),
        'place_id'       => trim((string)($_POST['place_id']       ?? '')),
        'gece_acik'      => (isset($_POST['gece_acik']) && $_POST['gece_acik'] === '1') ? 1 : 0,
    ];

    foreach (['isletme_adi', 'isletme_sahibi', 'telefon', 'eposta', 'sehir', 'ilce'] as $required) {
        if ($fields[$required] === '') {
            $errors[] = 'Zorunlu alanları doldurun (İşletme Adı, Yetkili Kişi, Telefon, E-Posta, İl, İlçe).';
            break;
        }
    }

    if (!filter_var($fields['eposta'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Geçerli bir e-posta adresi girin.';
    }

    if (mb_strlen($fields['aciklama'], 'UTF-8') > 2000) {
        $errors[] = 'İşletme tanıtımı en fazla 2000 karakter olabilir.';
    }

    // Address change detection: Check if core address text changed
    $oldAddressNorm = mb_strtolower(trim(
        ($business['sehir'] ?? '') . ' ' .
        ($business['ilce'] ?? '') . ' ' .
        ($business['mahalle'] ?? '') . ' ' .
        ($business['cadde_sokak'] ?? '') . ' ' .
        ($business['bina_no'] ?? '') . ' ' .
        ($business['tam_adres'] ?? '')
    ), 'UTF-8');

    $newAddressNorm = mb_strtolower(trim(
        $fields['sehir'] . ' ' .
        $fields['ilce'] . ' ' .
        $fields['mahalle'] . ' ' .
        $fields['cadde_sokak'] . ' ' .
        $fields['bina_no'] . ' ' .
        $fields['tam_adres']
    ), 'UTF-8');

    $oldAddressNorm = (string)preg_replace('/\s+/', ' ', $oldAddressNorm);
    $newAddressNorm = (string)preg_replace('/\s+/', ' ', $newAddressNorm);
    $addressChanged = ($oldAddressNorm !== '' && $oldAddressNorm !== $newAddressNorm);
    $locationReconfirmed = (($_POST['location_reconfirmed'] ?? '0') === '1');

    // Coordinate validation
    $lat = null;
    $lng = null;
    $locationVerifiedAt = $business['location_verified_at'] ?? null;
    $prevLat = isset($business['latitude']) && $business['latitude'] !== null ? (float)$business['latitude'] : null;
    $prevLng = isset($business['longitude']) && $business['longitude'] !== null ? (float)$business['longitude'] : null;

    if ($fields['latitude'] !== '' && $fields['longitude'] !== '') {
        if (!is_numeric($fields['latitude']) || !is_numeric($fields['longitude'])) {
            $errors[] = 'Enlem ve boylam sayısal formatta olmalıdır.';
        } else {
            $latCandidate = (float)$fields['latitude'];
            $lngCandidate = (float)$fields['longitude'];
            if (!isValidCoordinate($latCandidate, $lngCandidate)) {
                $errors[] = 'Geçersiz koordinat aralığı: Enlem -90 ile 90, boylam -180 ile 180 arasında olmalıdır.';
            } else {
                $coordsChanged = ($prevLat === null || $prevLng === null || abs($prevLat - $latCandidate) > 0.00001 || abs($prevLng - $lngCandidate) > 0.00001);

                if ($addressChanged && !$coordsChanged && !$locationReconfirmed) {
                    // Critical rule: Address changed, but coordinates were NOT re-verified for new address!
                    // Automatically invalidate old coordinates and reset location_verified_at to NULL.
                    $lat = null;
                    $lng = null;
                    $fields['place_id'] = '';
                    $locationVerifiedAt = null;
                } else {
                    $lat = $latCandidate;
                    $lng = $lngCandidate;
                    if ($coordsChanged || $locationReconfirmed) {
                        $locationVerifiedAt = date('Y-m-d H:i:s');
                    }
                }
            }
        }
    } else {
        $lat = null;
        $lng = null;
        $locationVerifiedAt = null;
    }

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM isletmeler WHERE id <> ? AND (telefon = ? OR eposta = ?)');
        $stmt->execute([$businessId, $fields['telefon'], $fields['eposta']]);
        if ((int)$stmt->fetchColumn() > 0) {
            $errors[] = 'Bu telefon veya e-posta başka bir işletme tarafından kullanılıyor.';
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare('UPDATE isletmeler SET 
            isletme_adi = ?, 
            isletme_sahibi = ?, 
            telefon = ?, 
            eposta = ?, 
            sehir = ?, 
            ilce = ?, 
            mahalle = ?, 
            cadde_sokak = ?, 
            bina_no = ?, 
            daire_no = ?, 
            posta_kodu = ?, 
            tam_adres = ?, 
            aciklama = ?,
            gece_acik = ?,
            latitude = ?,
            longitude = ?,
            place_id = ?,
            location_verified_at = ?
            WHERE id = ?');

        $stmt->execute([
            $fields['isletme_adi'],
            $fields['isletme_sahibi'],
            $fields['telefon'],
            $fields['eposta'],
            $fields['sehir'],
            $fields['ilce'],
            $fields['mahalle'] !== '' ? $fields['mahalle'] : null,
            $fields['cadde_sokak'] !== '' ? $fields['cadde_sokak'] : null,
            $fields['bina_no'] !== '' ? $fields['bina_no'] : null,
            $fields['daire_no'] !== '' ? $fields['daire_no'] : null,
            $fields['posta_kodu'] !== '' ? $fields['posta_kodu'] : null,
            $fields['tam_adres'] !== '' ? $fields['tam_adres'] : null,
            $fields['aciklama'] !== '' ? $fields['aciklama'] : null,
            $fields['gece_acik'],
            $lat !== null ? number_format($lat, 8, '.', '') : null,
            $lng !== null ? number_format($lng, 8, '.', '') : null,
            $fields['place_id'] !== '' ? $fields['place_id'] : null,
            $locationVerifiedAt,
            $businessId,
        ]);

        businessFlash('success', 'İşletme profiliniz ve adres bilgileriniz başarıyla kaydedildi.');
        businessRedirect('profil.php');
    }

    $business = array_merge($business, $fields);
}

include __DIR__ . '/_header.php';
?>

<style>
.section-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 14px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}
.section-title {
    font-size: 16px;
    font-weight: 700;
    color: #1E293B;
    margin: 0 0 16px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}
.section-title i {
    color: var(--accent, #F97316);
}
.section-subtitle {
    font-size: 13px;
    color: #64748B;
    margin: -10px 0 18px 0;
    line-height: 1.5;
}
.map-canvas-container {
    width: 100%;
    height: 380px;
    border-radius: 12px;
    border: 1.5px solid #CBD5E1;
    overflow: hidden;
    position: relative;
    background: #F8FAFC;
}
.map-toolbar {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
    margin-bottom: 14px;
}
.location-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}
.location-badge.verified {
    background: #ECFDF5;
    color: #065F46;
    border: 1px solid #A7F3D0;
}
.location-badge.unverified {
    background: #FFFBEB;
    color: #B45309;
    border: 1px solid #FDE68A;
}
.map-helper-box {
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    border-radius: 10px;
    padding: 12px 14px;
    font-size: 12px;
    color: #475569;
    margin-top: 12px;
    line-height: 1.5;
}
.map-helper-box strong {
    color: #1E293B;
}
.btn-auto-addr {
    background: #F1F5F9;
    border: 1px solid #CBD5E1;
    color: #334155;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all .2s;
}
.btn-auto-addr:hover {
    background: #E2E8F0;
    color: #0F172A;
}
.preview-editable {
    cursor: pointer;
    border-radius: 8px;
    transition: background .15s, outline .15s;
    outline: 2px solid transparent;
    position: relative;
}
.preview-editable:hover {
    background: #F8FAFC;
    outline: 2px dashed #CBD5E1;
}
.preview-editable:hover::after {
    content: '\f303';
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
    font-size: 11px;
    color: #94A3B8;
    position: absolute;
    top: 6px;
    right: 8px;
}
.preview-editable.focused {
    outline: 2px solid var(--accent, #F97316);
    background: #FFF7ED;
}
@media (max-width: 991px) {
    .profile-grid {
        grid-template-columns: 1fr !important;
    }
}
</style>

<!-- Leaflet CSS for map rendering & fallback -->
<?php if (hasFunctionalConsent()): ?><link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" /><?php endif; ?>

<section class="admin-panel">
    <div class="panel-header">
        <h2><i class="fa-solid fa-store"></i><span>İşletme Bilgileri & Konum Yönetimi</span></h2>
    </div>
    <div class="panel-body padded">
        <?php foreach ($errors as $error): ?>
            <div class="business-notice error"><?= businessEscape($error) ?></div>
        <?php endforeach; ?>

        <div class="profile-grid" style="display:grid;grid-template-columns:1fr 340px;gap:32px;align-items:start;">

            <!-- ── FORM ── -->
            <form method="POST" class="admin-form" id="businessProfileForm">
                <input type="hidden" name="csrf_token" id="csrfToken" value="<?= businessEscape(generateCsrfToken()) ?>">
                <input type="hidden" name="latitude" id="inputLatitude" value="<?= businessEscape($business['latitude'] ?? '') ?>">
                <input type="hidden" name="longitude" id="inputLongitude" value="<?= businessEscape($business['longitude'] ?? '') ?>">
                <input type="hidden" name="place_id" id="inputPlaceId" value="<?= businessEscape($business['place_id'] ?? '') ?>">
                <input type="hidden" name="location_reconfirmed" id="inputLocationReconfirmed" value="0">

                <!-- BÖLÜM 1: GENEL BİLGİLER -->
                <div class="section-card">
                    <h3 class="section-title"><i class="fa-solid fa-id-card"></i>Genel İşletme Bilgileri</h3>
                    <div class="business-form-grid">
                        <div class="form-group">
                            <label for="isletme_adi">İşletme Adı <span style="color:#EF4444">*</span></label>
                            <input id="isletme_adi" name="isletme_adi" value="<?= businessEscape($business['isletme_adi'] ?? '') ?>" placeholder="Örn: Stil Erkek Kuaförü" required>
                        </div>
                        <div class="form-group">
                            <label for="isletme_sahibi">Yetkili Kişi <span style="color:#EF4444">*</span></label>
                            <input id="isletme_sahibi" name="isletme_sahibi" value="<?= businessEscape($business['isletme_sahibi'] ?? '') ?>" placeholder="Örn: Mehmet Usta" required>
                        </div>
                        <div class="form-group">
                            <label for="telefon">Telefon <span style="color:#EF4444">*</span></label>
                            <input id="telefon" name="telefon" value="<?= businessEscape($business['telefon'] ?? '') ?>" placeholder="05XX XXX XX XX" required>
                        </div>
                        <div class="form-group">
                            <label for="eposta">E-Posta <span style="color:#EF4444">*</span></label>
                            <input id="eposta" name="eposta" type="email" value="<?= businessEscape($business['eposta'] ?? '') ?>" placeholder="isletme@mail.com" required>
                        </div>
                        <div class="form-group" style="grid-column:1/-1">
                            <label for="aciklama">İşletme Tanıtımı</label>
                            <textarea id="aciklama" name="aciklama" maxlength="2000" rows="4" placeholder="İşletmenizi tanıtın: sunduğunuz hizmetler, deneyiminiz, öne çıkan özellikleriniz..."><?= businessEscape($business['aciklama'] ?? '') ?></textarea>
                            <small id="descriptionCount" style="color:#64748B;"></small>
                        </div>
                        <div class="gece-acik-card" style="grid-column: 1 / -1; background: #F8FAFC; border: 1.5px solid #E2E8F0; border-radius: 12px; padding: 18px 22px; display: flex !important; flex-direction: row !important; align-items: center !important; justify-content: space-between !important; gap: 20px; margin-top: 10px;">
                            <div style="flex: 1; min-width: 0;">
                                <div style="font-size: 15px; font-weight: 700; color: #1E293B; margin-bottom: 4px; display: flex; align-items: center; gap: 8px;">
                                    <i class="fa-solid fa-moon" style="color: #6366F1; font-size: 18px;"></i>
                                    <span>Gece Açık İşletme</span>
                                    <span id="geceAcikStatusBadge" class="badge" style="<?= !empty($business['gece_acik']) ? 'background:#DCFCE7;color:#166534;border:1px solid #86EFAC;' : 'background:#F1F5F9;color:#64748B;border:1px solid #CBD5E1;' ?>font-size:11px;font-weight:700;padding:3px 9px;border-radius:12px;margin-left:6px;">
                                        <?= !empty($business['gece_acik']) ? 'Aktif' : 'Kapalı' ?>
                                    </span>
                                </div>
                                <div style="font-size: 13px; color: #64748B; line-height: 1.45;">
                                    Salonunuz gece veya geç saatlerde hizmet veriyorsa bu özelliği aktif edin. Ana sayfada "Gece Açık" filtresinde öncelikli olarak listelenirsiniz.
                                </div>
                            </div>
                            <div style="flex-shrink: 0; display: flex; align-items: center; gap: 10px;">
                                <label class="switch-ui" style="position: relative; display: inline-block; width: 44px; height: 24px; margin: 0; cursor: pointer;" title="Gece Açık Durumunu Değiştir">
                                    <input type="checkbox" id="gece_acik" name="gece_acik" value="1" <?= !empty($business['gece_acik']) ? 'checked' : '' ?> onchange="toggleGeceAcik(this)">
                                    <span class="slider-ui"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- BÖLÜM 2: İŞLETME ADRESİ VE KONUMU -->
                <div class="section-card">
                    <h3 class="section-title"><i class="fa-solid fa-map-location-dot"></i>İşletme Adresi ve Konumu</h3>
                    <p class="section-subtitle">
                        Müşterilerinizin işletmenizi kolayca bulabilmesi için adresinizi girin ve harita üzerinde dükkanınızın tam kapı konumunu işaretleyin.
                    </p>

                    <div class="business-form-grid">
                        <div class="form-group">
                            <label for="sehir">İl <span style="color:#EF4444">*</span></label>
                            <select id="sehir" name="sehir" required>
                                <option value="">İl Seçin</option>
                                <?php foreach ($allCities as $city): ?>
                                    <option value="<?= businessEscape($city) ?>" <?= ($business['sehir'] ?? '') === $city ? 'selected' : '' ?>>
                                        <?= businessEscape($city) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="ilce">İlçe <span style="color:#EF4444">*</span></label>
                            <select id="ilce" name="ilce" required>
                                <option value="">Önce İl Seçin</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="mahalle">Mahalle</label>
                            <input id="mahalle" name="mahalle" value="<?= businessEscape($business['mahalle'] ?? '') ?>" placeholder="Örn: Taşdelen Mah.">
                        </div>

                        <div class="form-group">
                            <label for="cadde_sokak">Cadde / Sokak</label>
                            <input id="cadde_sokak" name="cadde_sokak" value="<?= businessEscape($business['cadde_sokak'] ?? '') ?>" placeholder="Örn: Bulvar Sokak">
                        </div>

                        <div class="form-group">
                            <label for="bina_no">Bina No</label>
                            <input id="bina_no" name="bina_no" value="<?= businessEscape($business['bina_no'] ?? '') ?>" placeholder="Örn: 1-3B veya No: 12">
                        </div>

                        <div class="form-group">
                            <label for="daire_no">Daire No <small style="color:#94A3B8;">(isteğe bağlı)</small></label>
                            <input id="daire_no" name="daire_no" value="<?= businessEscape($business['daire_no'] ?? '') ?>" placeholder="Örn: D: 2">
                        </div>

                        <div class="form-group">
                            <label for="posta_kodu">Posta Kodu <small style="color:#94A3B8;">(isteğe bağlı)</small></label>
                            <input id="posta_kodu" name="posta_kodu" value="<?= businessEscape($business['posta_kodu'] ?? '') ?>" placeholder="Örn: 34788">
                        </div>

                        <div class="form-group" style="grid-column:1/-1">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                                <label for="tam_adres" style="margin:0;">Açık Adres</label>
                                <button type="button" class="btn-auto-addr" id="btnAutoGenerateAddress">
                                    <i class="fa-solid fa-wand-magic-sparkles"></i> Adres Alanlarından Otomatik Doldur
                                </button>
                            </div>
                            <textarea id="tam_adres" name="tam_adres" rows="2" placeholder="Cadde/Sokak, Bina No, Kat, Daire, Tarif"><?= businessEscape($business['tam_adres'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <!-- HARİTA ENTEGRASYONU -->
                    <div style="margin-top:24px;border-top:1px solid #E2E8F0;padding-top:20px;">
                        <div class="map-toolbar">
                            <button type="button" class="btn-admin primary" id="btnSearchAddressOnMap">
                                <i class="fa-solid fa-magnifying-glass-location"></i> Adresi Haritada Göster
                            </button>

                            <button type="button" class="btn-admin" id="btnSaveLocationOnly" style="background:#059669;color:#fff;border-color:#059669;display:none;">
                                <i class="fa-solid fa-check"></i> Konumu Kaydet
                            </button>

                            <div id="locationStatusBadge" class="location-badge <?= !empty($business['latitude']) && !empty($business['longitude']) ? 'verified' : 'unverified' ?>">
                                <?php if (!empty($business['latitude']) && !empty($business['longitude'])): ?>
                                    <i class="fa-solid fa-circle-check"></i>
                                    <span>Konum Kayıtlı <?= !empty($business['location_verified_at']) ? '(' . date('d.m.Y', strtotime($business['location_verified_at'])) . ')' : '' ?></span>
                                <?php else: ?>
                                    <i class="fa-solid fa-circle-exclamation"></i>
                                    <span>Henüz haritada konum kaydedilmedi</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div id="mapNotificationArea" style="margin-bottom:12px;display:none;"></div>

                        <!-- Harita Tuvali -->
                        <div id="businessMap" class="map-canvas-container">
                            <?php if (!hasFunctionalConsent()): ?>
                            <div class="d-flex flex-column align-items-center justify-content-center h-100 p-4 text-center">
                                <div style="width:48px;height:48px;border-radius:50%;background:rgba(194,136,64,0.12);color:var(--accent,#C28840);display:flex;align-items:center;justify-content:center;font-size:20px;margin-bottom:10px;">
                                    <i class="fa-solid fa-map-location-dot"></i>
                                </div>
                                <div class="fw-bold text-dark mb-1" style="font-size:14px;">Harita Konum Seçici Korumalı</div>
                                <p class="text-muted text-xs mb-3" style="max-width:300px;font-size:12px;line-height:1.4;">
                                    Haritayı görüntülemek ve dükkan konumunuzu işaretlemek için işlevsel çerez izni gereklidir.
                                </p>
                                <button type="button" class="btn btn-warning btn-sm px-3 py-1 fw-bold text-dark rounded-3" style="font-size:12px;" onclick="openCookieSettings()">
                                    <i class="fa-solid fa-sliders me-1"></i> Çerez Tercihlerini Yönet
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="map-helper-box">
                            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
                                <div>
                                    <i class="fa-solid fa-location-crosshairs" style="color:var(--accent,#F97316);margin-right:4px;"></i>
                                    <strong>Koordinatlar:</strong>
                                    <span id="coordDisplay">
                                        <?= !empty($business['latitude']) && !empty($business['longitude']) ? businessEscape($business['latitude'] . ', ' . $business['longitude']) : 'Belirlenmedi' ?>
                                    </span>
                                </div>
                                <div id="verifiedNotice" style="color:#059669;font-weight:600;">
                                    <?= !empty($business['location_verified_at']) ? 'Son doğrulama: ' . date('d.m.Y H:i', strtotime($business['location_verified_at'])) : '' ?>
                                </div>
                            </div>
                            <div style="margin-top:6px;color:#64748B;">
                                <i class="fa-solid fa-hand-pointer"></i> <strong>Kullanım:</strong>
                                "Adresi Haritada Göster" ile adrese yaklaşın. Kırmızı işaretçiyi farenizle veya dokunarak dükkanınızın tam üzerine sürükleyip bırakabilirsiniz. Konumu belirledikten sonra "Konumu Kaydet" butonuna veya aşağıdaki ana kaydet butonuna basın.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="business-actions" style="margin-top:20px;">
                    <button class="btn-admin primary" type="submit" id="btnSubmitFullForm">
                        <i class="fa-solid fa-floppy-disk"></i> Değişiklikleri Kaydet
                    </button>
                    <a class="btn-admin outline" href="/isletme/fotograflar.php">
                        <i class="fa-solid fa-images"></i> Fotoğrafları Yönet
                    </a>
                </div>
            </form>

            <!-- ── CANLI ÖNİZLEME ── -->
            <div style="position:sticky;top:24px;">
                <p style="font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#94A3B8;margin:0 0 10px;">
                    <i class="fa-solid fa-eye" style="margin-right:5px;"></i>Müşteri Görünümü — Canlı Önizleme
                </p>
                <div style="border:1px solid #E2E8F0;border-radius:14px;padding:20px 22px;background:#fff;box-shadow:0 2px 12px rgba(0,0,0,.06);">
                    <h2 style="font-size:15px;font-weight:700;color:#1E293B;margin:0 0 10px;">
                        İşletme hakkında
                    </h2>

                    <p id="previewDesc"
                       class="preview-editable"
                       title="Düzenlemek için tıkla"
                       data-target="aciklama"
                       style="font-size:13px;white-space:pre-wrap;margin:0 0 14px;min-height:32px;line-height:1.6;color:#374151;padding:6px 28px 6px 6px;">
                        <?= businessEscape($business['aciklama'] ?? '') ?>
                    </p>

                    <div style="border-top:1px solid #F1F5F9;padding-top:12px;font-size:13px;color:#374151;">
                        <p class="preview-editable" data-target="tam_adres"
                           title="Düzenlemek için tıkla"
                           style="margin:0 0 6px;padding:5px 28px 5px 6px;">
                            <strong>Adres:</strong>
                            <span id="previewAddrText"><?= businessEscape(trim(
                                implode(', ', array_filter([
                                    $business['tam_adres'] ?? '',
                                    $business['ilce']      ?? '',
                                    $business['sehir']     ?? '',
                                ]))
                            )) ?></span>
                        </p>

                        <p class="preview-editable" data-target="telefon"
                           title="Düzenlemek için tıkla"
                           style="margin:0 0 8px;padding:5px 28px 5px 6px;">
                            <strong>Telefon:</strong>
                            <span id="previewPhoneText" style="color:#F97316;">
                                <?= businessEscape($business['telefon'] ?? '') ?>
                            </span>
                        </p>

                        <!-- Konum & Yol Tarifi Önizleme Rozeti -->
                        <div style="margin-top:10px;padding-top:10px;border-top:1px dashed #E2E8F0;">
                            <div style="font-size:12px;display:flex;align-items:center;gap:6px;color:#0F766E;">
                                <i class="fa-solid fa-location-dot"></i>
                                <span id="previewLocationStatus">
                                    <?= !empty($business['latitude']) && !empty($business['longitude']) ? 'Harita konumu doğrulanmış' : 'Harita konumu henüz girilmedi' ?>
                                </span>
                            </div>
                            <div style="margin-top:8px;">
                                <span style="display:inline-block;padding:4px 8px;border-radius:6px;background:#F1F5F9;font-size:11px;color:#475569;">
                                    <i class="fa-solid fa-diamond-turn-right" style="color:#2563EB;"></i> Google Maps Yol Tarifi Aktif
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <p style="font-size:11px;color:#94A3B8;margin:10px 0 0;text-align:center;">
                    <i class="fa-solid fa-pencil" style="margin-right:4px;"></i>
                    Düzenlemek için üzerine tıklayın
                </p>
            </div>

        </div><!-- /grid -->
    </div>
</section>

<!-- Leaflet JS for responsive interactive map & fallback -->
<?php if (hasFunctionalConsent()): ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<?php if ($hasGoogleMapsKey && hasFunctionalConsent()): ?>
<!-- Google Maps API loaded safely via server configuration -->
<script src="https://maps.googleapis.com/maps/api/js?key=<?= urlencode($googleMapsApiKey) ?>&libraries=places&language=tr&region=TR&callback=initGoogleMap" async defer></script>
<?php endif; ?>
<?php endif; ?>

<script>
(function () {
    /* ── Şehir & İlçe Veri Yapısı Entegrasyonu ── */
    var turkeyDistricts = <?= json_encode($turkeyDistricts, JSON_UNESCAPED_UNICODE) ?>;
    var fSehir = document.getElementById('sehir');
    var fIlce  = document.getElementById('ilce');
    var savedIlce = <?= json_encode($business['ilce'] ?? '', JSON_UNESCAPED_UNICODE) ?>;

    function populateDistricts(city, preselected) {
        fIlce.innerHTML = '<option value="">İlçe Seçin</option>';
        if (!city || !turkeyDistricts[city]) {
            fIlce.disabled = true;
            return;
        }
        fIlce.disabled = false;
        var list = turkeyDistricts[city];
        for (var i = 0; i < list.length; i++) {
            var opt = document.createElement('option');
            opt.value = list[i];
            opt.textContent = list[i];
            if (list[i] === preselected) {
                opt.selected = true;
            }
            fIlce.appendChild(opt);
        }
    }

    if (fSehir) {
        fSehir.addEventListener('change', function () {
            populateDistricts(this.value, '');
            syncAddr();
        });
        if (fSehir.value) {
            populateDistricts(fSehir.value, savedIlce);
        }
    }

    if (fIlce) {
        fIlce.addEventListener('change', function () {
            syncAddr();
        });
    }

    /* ── Form Elemanları ve Önizleme ── */
    var fDesc    = document.getElementById('aciklama');
    var fAddr    = document.getElementById('tam_adres');
    var fMahalle = document.getElementById('mahalle');
    var fCadde   = document.getElementById('cadde_sokak');
    var fBina    = document.getElementById('bina_no');
    var fDaire   = document.getElementById('daire_no');
    var fPosta   = document.getElementById('posta_kodu');
    var fPhone   = document.getElementById('telefon');

    var previewDesc  = document.getElementById('previewDesc');
    var previewAddr  = document.getElementById('previewAddrText');
    var previewPhone = document.getElementById('previewPhoneText');
    var previewLoc   = document.getElementById('previewLocationStatus');
    var counter      = document.getElementById('descriptionCount');

    function buildStructuredAddress() {
        var parts = [];
        var m = fMahalle ? fMahalle.value.trim() : '';
        var c = fCadde   ? fCadde.value.trim() : '';
        var b = fBina    ? fBina.value.trim() : '';
        var d = fDaire   ? fDaire.value.trim() : '';
        var p = fPosta   ? fPosta.value.trim() : '';
        var i = fIlce    ? fIlce.value.trim() : '';
        var s = fSehir   ? fSehir.value.trim() : '';

        if (m) parts.push(m.toLowerCase().indexOf('mah') !== -1 ? m : m + ' Mah.');
        if (c) parts.push(c.toLowerCase().indexOf('sok') !== -1 || c.toLowerCase().indexOf('cad') !== -1 ? c : c + ' Sokak');
        if (b) parts.push(b.toLowerCase().indexOf('no') !== -1 ? b : 'No: ' + b);
        if (d) parts.push(d.toLowerCase().indexOf('d:') !== -1 || d.toLowerCase().indexOf('daire') !== -1 ? d : 'Daire: ' + d);
        if (p) parts.push(p);
        if (i) parts.push(i);
        if (s) parts.push(s);

        return parts.join(', ');
    }

    function buildAddr() {
        if (fAddr && fAddr.value.trim()) {
            var full = fAddr.value.trim();
            var extra = [];
            if (fIlce && fIlce.value && full.indexOf(fIlce.value) === -1) extra.push(fIlce.value);
            if (fSehir && fSehir.value && full.indexOf(fSehir.value) === -1) extra.push(fSehir.value);
            return extra.length ? full + ', ' + extra.join(', ') : full;
        }
        return buildStructuredAddress();
    }

    function syncDesc() {
        var val = fDesc.value.trim();
        previewDesc.textContent  = val || 'İşletme tanıtımı henüz eklenmemiş.';
        previewDesc.style.color  = val ? '#374151' : '#CBD5E1';
        previewDesc.style.fontStyle = val ? 'normal' : 'italic';

        var rem = 2000 - fDesc.value.length;
        counter.textContent  = rem + ' karakter kaldı';
        counter.style.color  = rem < 100 ? '#EF4444' : '#64748B';
    }

    function syncAddr()  { previewAddr.textContent  = buildAddr() || '—'; }
    function syncPhone() { previewPhone.textContent = fPhone ? fPhone.value.trim() : ''; }

    var inputReconfirmed = document.getElementById('inputLocationReconfirmed');
    function onAddressChanged() {
        if (inputReconfirmed) inputReconfirmed.value = '0';
        if (statusBadge) {
            statusBadge.className = 'location-badge unverified';
            statusBadge.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> <span>Adres değiştirildi (Harita onayı bekliyor)</span>';
        }
        if (btnSaveLocation) {
            btnSaveLocation.style.display = 'inline-flex';
            btnSaveLocation.innerHTML = '<i class="fa-solid fa-check"></i> Yeni Konumu Onayla';
        }
        if (previewLoc) {
            previewLoc.textContent = 'Adres değiştirildi (Harita onayı bekliyor)';
        }
    }

    if (fDesc)  fDesc.addEventListener('input', syncDesc);
    if (fAddr)  { fAddr.addEventListener('input', syncAddr); fAddr.addEventListener('input', onAddressChanged); }
    if (fPhone) fPhone.addEventListener('input', syncPhone);
    [fMahalle, fCadde, fBina, fDaire, fPosta].forEach(function (el) {
        if (el) {
            el.addEventListener('input', syncAddr);
            el.addEventListener('input', onAddressChanged);
        }
    });

    // Otomatik adres oluştur butonu
    var btnAuto = document.getElementById('btnAutoGenerateAddress');
    if (btnAuto) {
        btnAuto.addEventListener('click', function () {
            var generated = buildStructuredAddress();
            if (generated && fAddr) {
                fAddr.value = generated;
                syncAddr();
                onAddressChanged();
                showNotice('Adres alanları birleştirilerek açık adres oluşturuldu. Yeni adres için harita konumunu kontrol edip onaylayın.', 'info');
            } else {
                showNotice('Lütfen önce il, ilçe, mahalle veya cadde/sokak alanlarını doldurun.', 'error');
            }
        });
    }

    syncDesc();
    syncAddr();
    syncPhone();

    /* ── Önizleme Tıklama Odaklanması ── */
    document.querySelectorAll('.preview-editable').forEach(function (el) {
        el.addEventListener('click', function () {
            var targetId = el.getAttribute('data-target');
            var target   = document.getElementById(targetId);
            if (!target) return;
            target.scrollIntoView({ behavior: 'smooth', block: 'center' });
            target.focus();
            el.classList.add('focused');
            target.style.transition = 'box-shadow .2s';
            target.style.boxShadow  = '0 0 0 3px rgba(249,115,22,.3)';
            setTimeout(function () {
                el.classList.remove('focused');
                target.style.boxShadow = '';
            }, 1800);
        });
    });

    /* ── Harita Yönetimi (Google Maps & Leaflet Çift Mod) ── */
    var hasGoogleMaps = <?= $hasGoogleMapsKey ? 'true' : 'false' ?>;
    var inputLat = document.getElementById('inputLatitude');
    var inputLng = document.getElementById('inputLongitude');
    var inputPlaceId = document.getElementById('inputPlaceId');
    var coordDisplay = document.getElementById('coordDisplay');
    var statusBadge = document.getElementById('locationStatusBadge');
    var btnSaveLocation = document.getElementById('btnSaveLocationOnly');
    var noticeArea = document.getElementById('mapNotificationArea');
    var csrfToken = document.getElementById('csrfToken').value;

    var initialLat = <?= !empty($business['latitude']) ? (float)$business['latitude'] : 41.0082 ?>;
    var initialLng = <?= !empty($business['longitude']) ? (float)$business['longitude'] : 28.9784 ?>;
    var hasInitialCoords = <?= !empty($business['latitude']) && !empty($business['longitude']) ? 'true' : 'false' ?>;
    var initialZoom = hasInitialCoords ? 16 : 12;

    var leafletMap = null;
    var leafletMarker = null;
    var googleMap = null;
    var googleMarker = null;
    var googleGeocoder = null;

    function showNotice(msg, type) {
        noticeArea.style.display = 'block';
        noticeArea.innerHTML = '<div class="business-notice ' + (type || 'info') + '" style="margin:0 0 12px 0;">' + msg + '</div>';
    }

    function updateCoordinates(lat, lng, confirmed) {
        lat = parseFloat(lat);
        lng = parseFloat(lng);
        if (isNaN(lat) || isNaN(lng)) return;

        inputLat.value = lat.toFixed(8);
        inputLng.value = lng.toFixed(8);
        if (inputReconfirmed) inputReconfirmed.value = '1';
        coordDisplay.textContent = lat.toFixed(6) + ', ' + lng.toFixed(6);

        if (confirmed) {
            statusBadge.className = 'location-badge verified';
            statusBadge.innerHTML = '<i class="fa-solid fa-circle-check"></i> <span>Konum Kayıtlı (Doğrulandı)</span>';
            btnSaveLocation.style.display = 'none';
            if (previewLoc) previewLoc.textContent = 'Harita konumu doğrulanmış';
        } else {
            statusBadge.className = 'location-badge unverified';
            statusBadge.innerHTML = '<i class="fa-solid fa-clock"></i> <span>Konum İşaretlendi (Kaydetmeyi unutmayın)</span>';
            btnSaveLocation.style.display = 'inline-flex';
            btnSaveLocation.innerHTML = '<i class="fa-solid fa-check"></i> Konumu Kaydet';
            if (previewLoc) previewLoc.textContent = 'Konum seçildi (Kaydetmeyi unutmayın)';
        }
    }

    /* ── Leaflet Harita Başlatıcı ── */
    function initLeaflet() {
        if (typeof L === 'undefined') return;
        if (leafletMap) return;
        var container = document.getElementById('businessMap');
        if (!container) return;

        leafletMap = L.map('businessMap', {
            center: [initialLat, initialLng],
            zoom: initialZoom,
            scrollWheelZoom: true
        });

        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap'
        }).addTo(leafletMap);

        // Marker
        var markerOptions = { draggable: true };
        leafletMarker = L.marker([initialLat, initialLng], markerOptions).addTo(leafletMap);
        leafletMarker.bindPopup('<strong>' + (document.getElementById('isletme_adi').value || 'İşletmeniz') + '</strong><br>Konumu değiştirmek için sürükleyin.').openPopup();

        leafletMarker.on('dragend', function (e) {
            var pos = leafletMarker.getLatLng();
            updateCoordinates(pos.lat, pos.lng, false);
            showNotice('İşaretçi yeni konuma taşındı. Konumu geçerli kılmak için "Konumu Kaydet" butonuna basın.', 'info');
        });

        leafletMap.on('click', function (e) {
            leafletMarker.setLatLng(e.latlng);
            updateCoordinates(e.latlng.lat, e.latlng.lng, false);
            showNotice('İşaretçi tıklanan noktaya yerleştirildi. Konumu onaylamak için "Konumu Kaydet" butonuna basın.', 'info');
        });

    }

    /* ── Google Maps Harita Başlatıcı ── */
    window.initGoogleMap = function () {
        var container = document.getElementById('businessMap');
        if (!container || typeof google === 'undefined' || !google.maps) return;

        try {
            googleMap = new google.maps.Map(container, {
                center: { lat: initialLat, lng: initialLng },
                zoom: initialZoom,
                mapTypeControl: true,
                streetViewControl: true,
                fullscreenControl: true
            });

            googleGeocoder = new google.maps.Geocoder();

            googleMarker = new google.maps.Marker({
                position: { lat: initialLat, lng: initialLng },
                map: googleMap,
                draggable: true,
                title: 'İşletme Konumu',
                animation: google.maps.Animation.DROP
            });

            var infowindow = new google.maps.InfoWindow({
                content: '<strong>' + (document.getElementById('isletme_adi').value || 'İşletmeniz') + '</strong><br>Konumu ayarlamak için sürükleyin.'
            });
            infowindow.open(googleMap, googleMarker);

            googleMarker.addListener('dragend', function () {
                var pos = googleMarker.getPosition();
                updateCoordinates(pos.lat(), pos.lng(), false);
                showNotice('İşaretçi yeni konuma taşındı. Konumu doğrulamak için "Konumu Kaydet" butonuna basın.', 'info');
            });

            googleMap.addListener('click', function (e) {
                googleMarker.setPosition(e.latLng);
                updateCoordinates(e.latLng.lat(), e.latLng.lng(), false);
                showNotice('İşaretçi tıklanan noktaya taşındı. Konumu doğrulamak için "Konumu Kaydet" butonuna basın.', 'info');
            });
        } catch (e) {
            console.warn('Google Maps yüklenirken hata, Leaflet başlatılıyor:', e);
            initLeaflet();
        }
    };

    // Google Maps authentication failure handler
    window.gm_authFailure = function () {
        console.warn('Google Maps API kimlik doğrulaması başarısız oldu. Leaflet harita desteğine geçiliyor.');
        showNotice('Google Maps API anahtarı geçersiz veya kısıtlı. Harita yedek açık kaynak motoruyla kullanıma devam ediyor.', 'error');
        initLeaflet();
    };

    // If Google Maps API is not configured, load Leaflet immediately
    if (!hasGoogleMaps) {
        document.addEventListener('DOMContentLoaded', initLeaflet);
    }

    /* ── Gece Açık Durumu Anında Kaydetme (AJAX) ── */
    window.toggleGeceAcik = function(checkbox) {
        var isChecked = checkbox.checked ? 1 : 0;
        var badge = document.getElementById('geceAcikStatusBadge');
        if (badge) {
            badge.textContent = isChecked ? 'Aktif' : 'Kapalı';
            badge.style.background = isChecked ? '#DCFCE7' : '#F1F5F9';
            badge.style.color = isChecked ? '#166534' : '#64748B';
            badge.style.border = isChecked ? '1px solid #86EFAC' : '1px solid #CBD5E1';
        }

        var csrfEl = document.getElementById('csrfToken');
        var csrfToken = csrfEl ? csrfEl.value : '';
        var formData = new FormData();
        formData.append('csrf_token', csrfToken);
        formData.append('gece_acik', isChecked);

        fetch('/isletme/gece_durum_kaydet.php', {
            method: 'POST',
            body: formData
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                if (typeof showNotice === 'function') {
                    showNotice(isChecked ? 'İşletmeniz Gece Açık olarak ayarlandı.' : 'Gece Açık özelliği kapatıldı.', 'success');
                }
            } else {
                checkbox.checked = !isChecked;
                if (badge) {
                    badge.textContent = !isChecked ? 'Aktif' : 'Kapalı';
                    badge.style.background = !isChecked ? '#DCFCE7' : '#F1F5F9';
                    badge.style.color = !isChecked ? '#166534' : '#64748B';
                    badge.style.border = !isChecked ? '1px solid #86EFAC' : '1px solid #CBD5E1';
                }
                if (typeof showNotice === 'function') {
                    showNotice(data.message || 'Durum kaydedilemedi.', 'error');
                }
            }
        })
        .catch(function(err) {
            console.error('Gece acik guncelleme hatasi:', err);
        });
    };

    /* ── Adresi Haritada Göster (Geocoding) ── */
    var btnSearch = document.getElementById('btnSearchAddressOnMap');
    if (btnSearch) {
        btnSearch.addEventListener('click', function () {
            var c = fCadde   ? fCadde.value.trim() : '';
            var m = fMahalle ? fMahalle.value.trim() : '';
            var b = fBina    ? fBina.value.trim() : '';
            var i = fIlce    ? fIlce.value.trim() : '';
            var s = fSehir   ? fSehir.value.trim() : '';
            var openAddr = fAddr ? fAddr.value.trim() : '';

            // Akıllı aday sorgu listesi oluştur (Açık adres en yüksek önceliğe sahiptir)
            var candidateQueries = [];

            if (openAddr) {
                // 1. Kullanıcının açık adres kutusuna yazdığı tam metin
                candidateQueries.push(openAddr);
                candidateQueries.push(openAddr + ', Türkiye');

                // Açık adres metninde formdaki ilçe ve il zaten geçiyor mu kontrol et
                var lowerAddr = openAddr.toLocaleLowerCase('tr-TR');
                var containsCity = s && lowerAddr.indexOf(s.toLocaleLowerCase('tr-TR')) !== -1;
                var containsDistrict = i && lowerAddr.indexOf(i.toLocaleLowerCase('tr-TR')) !== -1;

                if (!containsCity && s) {
                    var extraLoc = [i && !containsDistrict ? i : '', s, 'Türkiye'].filter(Boolean).join(', ');
                    candidateQueries.push(openAddr + ', ' + extraLoc);
                }
            }

            // 2. Yapısal alanlar (cadde, mahalle, bina no, ilçe, il)
            var structuredParts = [];
            if (c) structuredParts.push(c);
            if (m) structuredParts.push(m);
            if (b) structuredParts.push('No ' + b);
            if (i) structuredParts.push(i);
            if (s) structuredParts.push(s);
            if (structuredParts.length > 0) {
                structuredParts.push('Türkiye');
                candidateQueries.push(structuredParts.filter(Boolean).join(', '));
            }

            // 3. Bölge düzeyinde yedek kombinasyonlar (mahalle, ilçe, il)
            if (m && i && s) candidateQueries.push([m, i, s, 'Türkiye'].filter(Boolean).join(', '));
            if (i && s)      candidateQueries.push([i, s, 'Türkiye'].filter(Boolean).join(', '));
            if (s)           candidateQueries.push([s, 'Türkiye'].filter(Boolean).join(', '));

            // Tekrarları temizle
            candidateQueries = candidateQueries.filter(function (item, pos, self) {
                return item && item.trim().length > 1 && self.indexOf(item) === pos;
            });

            if (candidateQueries.length === 0) {
                showNotice('Lütfen önce açık adres veya il, ilçe ve cadde/mahalle bilgisi girin.', 'error');
                return;
            }

            btnSearch.disabled = true;
            btnSearch.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Aranıyor...';

            function applyFoundLocation(lat, lng, queryIdx, placeId) {
                btnSearch.disabled = false;
                btnSearch.innerHTML = '<i class="fa-solid fa-magnifying-glass-location"></i> Adresi Haritada Göster';

                if (googleMap && googleMarker) {
                    var gLatLng = new google.maps.LatLng(lat, lng);
                    googleMap.setCenter(gLatLng);
                    googleMap.setZoom(17);
                    googleMarker.setPosition(gLatLng);
                    if (placeId && inputPlaceId) {
                        inputPlaceId.value = placeId;
                    }
                } else if (leafletMap && leafletMarker) {
                    leafletMap.setView([lat, lng], 17);
                    leafletMarker.setLatLng([lat, lng]);
                }

                updateCoordinates(lat, lng, false);

                var isDirectOpenAddrMatch = openAddr && queryIdx < 2;
                var successNote = isDirectOpenAddrMatch
                    ? 'Açık adres haritada bulundu! İşaretçi yazılan konuma yerleştirildi. Konumu onaylamak için "Konumu Kaydet" butonuna basın.'
                    : (queryIdx === 0
                        ? 'Adres haritada bulundu! Lütfen işaretçiyi kontrol edin, gerekiyorsa tam dükkan konumuna sürükleyin ve "Konumu Kaydet" butonuna basın.'
                        : 'Adres bölge/mahalle düzeyinde haritada bulundu. Lütfen kırmızı işaretçiyi dükkanınızın tam kapısına sürükleyin ve "Konumu Kaydet" butonuna basın.');

                showNotice(successNote, 'success');

                // Harita alanını görünür alana kaydır
                var mapBox = document.getElementById('businessMap');
                if (mapBox) {
                    mapBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }

            function runNominatimSearch(index) {
                if (index >= candidateQueries.length) {
                    btnSearch.disabled = false;
                    btnSearch.innerHTML = '<i class="fa-solid fa-magnifying-glass-location"></i> Adresi Haritada Göster';
                    showNotice('Girilen detaylı adres haritada doğrudan bulunamadı. Lütfen ilçe merkezinden haritayı kaydırarak dükkanınızın tam üzerine tıklayın.', 'error');
                    return;
                }

                var q = candidateQueries[index];
                var url = 'https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(q);
                if (!window.BitirasCookie || !BitirasCookie.hasConsent('functional')) { showNotice('Harita icin cerez tercihlerinden islevsel izni acin.', 'info'); return; }
                fetch(url, { headers: { 'Accept': 'application/json' } })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (data && data.length > 0) {
                            var target = data[0];
                            var newLat = parseFloat(target.lat);
                            var newLng = parseFloat(target.lon);
                            applyFoundLocation(newLat, newLng, index, '');
                        } else {
                            runNominatimSearch(index + 1);
                        }
                    })
                    .catch(function () {
                        runNominatimSearch(index + 1);
                    });
            }

            if (googleGeocoder && googleMap && googleMarker) {
                function runGoogleSearch(idx) {
                    if (idx >= candidateQueries.length) {
                        runNominatimSearch(0);
                        return;
                    }
                    var q = candidateQueries[idx];
                    googleGeocoder.geocode({ address: q }, function (results, status) {
                        if (status === 'OK' && results && results[0]) {
                            var loc = results[0].geometry.location;
                            applyFoundLocation(loc.lat(), loc.lng(), idx, results[0].place_id || '');
                        } else {
                            runGoogleSearch(idx + 1);
                        }
                    });
                }
                runGoogleSearch(0);
            } else {
                runNominatimSearch(0);
            }
        });
    }

    /* ── Açık Adreste Enter Tuşuna Basıldığında Haritada Göster ── */
    if (fAddr && btnSearch) {
        fAddr.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                btnSearch.click();
            }
        });
    }

    /* ── Yalnızca Konumu Kaydet (AJAX Endpoint) ── */
    if (btnSaveLocation) {
        btnSaveLocation.addEventListener('click', function () {
            var lat = inputLat.value.trim();
            var lng = inputLng.value.trim();
            var placeId = inputPlaceId ? inputPlaceId.value.trim() : '';

            if (!lat || !lng) {
                showNotice('Lütfen önce harita üzerinde bir konum işaretleyin.', 'error');
                return;
            }

            btnSaveLocation.disabled = true;
            btnSaveLocation.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Kaydediliyor...';

            var formData = new FormData();
            formData.append('csrf_token', csrfToken);
            formData.append('latitude', lat);
            formData.append('longitude', lng);
            formData.append('place_id', placeId);

            fetch('/isletme/konum_kaydet.php', {
                method: 'POST',
                body: formData,
                headers: { 'Accept': 'application/json' }
            })
            .then(function (res) { return res.json(); })
            .then(function (result) {
                btnSaveLocation.disabled = false;
                btnSaveLocation.innerHTML = '<i class="fa-solid fa-check"></i> Konumu Kaydet';

                if (result.success) {
                    updateCoordinates(result.latitude, result.longitude, true);
                    var vNotice = document.getElementById('verifiedNotice');
                    if (vNotice && result.location_verified_at) {
                        vNotice.textContent = 'Son doğrulama: ' + result.location_verified_at;
                    }
                    showNotice('Harita konumu başarıyla doğrulandı ve veritabanına kaydedildi!', 'success');
                } else {
                    showNotice(result.message || 'Konum kaydedilemedi.', 'error');
                }
            })
            .catch(function () {
                btnSaveLocation.disabled = false;
                btnSaveLocation.innerHTML = '<i class="fa-solid fa-check"></i> Konumu Kaydet';
                showNotice('Sunucu ile iletişim kurulurken bir hata oluştu.', 'error');
            });
        });
    }
})();
</script>

<?php include __DIR__ . '/_footer.php'; ?>
