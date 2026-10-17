<?php
require_once __DIR__ . '/data/database.php';
require_once __DIR__ . '/admin/includes/auth.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$businessId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$businessId) {
    http_response_code(404);
    exit('İşletme bulunamadı.');
}
$stmt = $pdo->prepare("SELECT i.id, i.isletme_adi, i.isletme_sahibi, i.telefon, i.eposta, i.sehir, i.ilce,
                              i.tam_adres, i.aciklama, cover.fotograf_yolu AS cover_photo, profile.fotograf_yolu AS profile_photo,
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
                       LEFT JOIN isletme_panel_medya m ON m.isletme_id = f.isletme_id
                       WHERE f.isletme_id = ?
                         AND (m.profil_fotograf_id IS NULL OR m.profil_fotograf_id <> f.id)
                         AND (m.kapak_fotograf_id IS NULL OR m.kapak_fotograf_id <> f.id)
                       ORDER BY COALESCE(s.sira, f.id), f.id");
$stmt->execute([$businessId]);
$gallery = $stmt->fetchAll(PDO::FETCH_COLUMN);
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
<html lang="tr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="index,follow"><title><?= htmlspecialchars($business['isletme_adi'], ENT_QUOTES, 'UTF-8') ?> — BiTıraş</title><link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="/style.css"></head>
<body><main class="container py-4" style="max-width:1100px">
    <a href="/" class="btn btn-outline-dark mb-3">← Ana sayfa</a>
    <?php if ($errorMessage): ?><div class="alert alert-<?= $messageType === 'success' ? 'success' : 'warning' ?>"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    <div class="business-profile-layout">
        <div class="business-profile-left">
    <section class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div style="height:280px;background:#0F172A;position:relative">
            <?php if ($business['cover_photo']): ?><img src="/<?= htmlspecialchars($business['cover_photo'], ENT_QUOTES, 'UTF-8') ?>" alt="" style="width:100%;height:100%;object-fit:cover"><?php endif; ?>
            <div style="position:absolute;inset:0;background:linear-gradient(transparent,rgba(15,23,42,.8))"></div>
            <div style="position:absolute;left:24px;right:24px;bottom:20px;color:white;display:flex;align-items:end;gap:16px">
                <?php if ($business['profile_photo']): ?><img src="/<?= htmlspecialchars($business['profile_photo'], ENT_QUOTES, 'UTF-8') ?>" alt="" style="width:74px;height:74px;object-fit:cover;border-radius:16px;border:2px solid #fff"><?php endif; ?>
                <div><h1 style="color:white;margin:0"><?= htmlspecialchars($business['isletme_adi'], ENT_QUOTES, 'UTF-8') ?></h1><div><?= htmlspecialchars($business['sehir'] . ' · ' . $business['ilce'], ENT_QUOTES, 'UTF-8') ?> · <?= $business['average_rating'] !== null ? number_format((float)$business['average_rating'],1) . ' ★ (' . (int)$business['review_count'] . ')' : 'Henüz yorum yok' ?></div></div>
            </div>
        </div>
        <div class="card-body p-4"><h2 class="h5">İşletme hakkında</h2><p style="white-space:pre-wrap"><?= htmlspecialchars($business['aciklama'] ?? 'İşletme tanıtımı henüz eklenmemiş.', ENT_QUOTES, 'UTF-8') ?></p><p class="mb-0"><strong>Adres:</strong> <?= htmlspecialchars(trim(($business['tam_adres'] ?? '') . ', ' . $business['ilce'] . ', ' . $business['sehir'], ', '), ENT_QUOTES, 'UTF-8') ?><br><strong>Telefon:</strong> <a href="tel:<?= htmlspecialchars($business['telefon'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($business['telefon'], ENT_QUOTES, 'UTF-8') ?></a></p></div>
    </section>
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
    </style>
            <?php if ($gallery): ?><section class="card border-0 shadow-sm rounded-4 p-4 mb-4"><h2 class="h5">Fotoğraf galerisi</h2><div class="row g-3"><?php foreach ($gallery as $photo): ?><div class="col-6 col-md-3"><img src="/<?= htmlspecialchars($photo, ENT_QUOTES, 'UTF-8') ?>" alt="İşletme fotoğrafı" style="width:100%;aspect-ratio:4/3;object-fit:cover;border-radius:10px"></div><?php endforeach; ?></div></section><?php endif; ?>
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
</body></html>
