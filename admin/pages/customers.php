<?php

require_once __DIR__ . '/../includes/auth.php';

$admin       = requirePermission('users.view');
$currentPage = 'customers';
$pageTitle   = 'Müşteriler';
$pageSubtitle= 'Sisteme kayıtlı müşterileri ve randevu geçmişlerini inceleyin';

$search     = trim($_GET['q']     ?? '');
$filterCity = trim($_GET['sehir'] ?? '');
$viewId     = (int)($_GET['id']   ?? 0);
$page       = max(1, (int)($_GET['sayfa'] ?? 1));
$perPage    = 50;
$offset     = ($page - 1) * $perPage;

$viewCustomer         = null;
$customerAppointments = [];

$scopeParams = [];
$appointmentScope = currentBusinessScopeSql('r.isletme_id', $scopeParams);
$customerScope = $appointmentScope === '1=1' ? '1=1' : "EXISTS (SELECT 1 FROM randevular r WHERE r.kullanici_id = k.id AND {$appointmentScope})";

if ($viewId > 0) {
    $stmt = $pdo->prepare("SELECT k.* FROM kullanicilar k WHERE k.id = ? AND {$customerScope}");
    $stmt->execute(array_merge([$viewId], $scopeParams));
    $viewCustomer = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($viewCustomer) {
        $stmt = $pdo->prepare("SELECT r.*, i.isletme_adi, i.sehir, i.ilce, h.hizmet_adi, h.fiyat, c.ad_soyad AS calisan_adi
                               FROM randevular r
                               LEFT JOIN isletmeler i ON r.isletme_id = i.id
                               LEFT JOIN hizmetler  h ON r.hizmet_id  = h.id
                               LEFT JOIN calisanlar c ON r.calisan_id = c.id
                               WHERE r.kullanici_id = ? AND {$appointmentScope}
                               ORDER BY r.randevu_zamani DESC");
        $stmt->execute(array_merge([$viewId], $scopeParams));
        $customerAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

$cityList = [];
try {
    $cityStmt = $pdo->prepare("SELECT DISTINCT k.sehir FROM kullanicilar k WHERE k.sehir <> '' AND {$customerScope} ORDER BY k.sehir");
    $cityStmt->execute($scopeParams);
    $cityList = $cityStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {}

$where  = [$customerScope];
$params = $scopeParams;

if (!empty($search)) {
    $where[]  = "(k.ad_soyad LIKE ? OR k.telefon LIKE ? OR k.eposta LIKE ?)";
    $like     = "%{$search}%";
    $params   = array_merge($params, [$like, $like, $like]);
}
if (!empty($filterCity)) {
    $where[]  = "k.sehir = ?";
    $params[] = $filterCity;
}

$whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

$totalCount = 0;
try {
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM kullanicilar k {$whereSql}");
    $countStmt->execute($params);
    $totalCount = (int)$countStmt->fetchColumn();
} catch (Exception $e) {}

$totalPages = max(1, (int)ceil($totalCount / $perPage));
if ($page > $totalPages) { $page = $totalPages; $offset = ($page - 1) * $perPage; }

$sql = "SELECT k.*,
        (SELECT COUNT(*) FROM randevular r WHERE r.kullanici_id = k.id AND {$appointmentScope}) AS toplam_randevu,
        (SELECT COUNT(*) FROM randevular r WHERE r.kullanici_id = k.id AND {$appointmentScope} AND r.durum = 'completed') AS tamamlanan_randevu
        FROM kullanicilar k
        {$whereSql}
        ORDER BY k.olusturulma_tarihi DESC
        LIMIT {$perPage} OFFSET {$offset}";

$customers = [];
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge($scopeParams, $scopeParams, $params));
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

function pageUrl(int $p, string $q, string $sehir): string {
    $args = ['sayfa' => $p];
    if ($q     !== '') $args['q']     = $q;
    if ($sehir !== '') $args['sehir'] = $sehir;
    return '/admin/pages/customers.php?' . http_build_query($args);
}

include __DIR__ . '/../includes/header.php';
?>

<?php if ($viewCustomer): ?>
<div class="admin-panel" style="border:2px solid var(--admin-accent);background:#FCFBF9;margin-bottom:24px;">
    <div class="panel-header" style="background:#FFFBF5;">
        <h2>
            <i class="fa-solid fa-user"></i>
            <span>Müşteri Profili: <strong><?= e($viewCustomer['ad_soyad']) ?></strong></span>
        </h2>
        <a href="/admin/pages/customers.php<?= !empty($search) ? '?q='.urlencode($search) : '' ?>" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-xmark"></i> Kapat
        </a>
    </div>
    <div class="panel-body padded">
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));gap:20px;margin-bottom:20px;">
            <div>
                <label style="font-size:11px;font-weight:700;color:var(--admin-text-muted);text-transform:uppercase;">Ad Soyad</label>
                <div style="font-size:16px;font-weight:700;color:var(--admin-text);"><?= e($viewCustomer['ad_soyad']) ?></div>
            </div>
            <div>
                <label style="font-size:11px;font-weight:700;color:var(--admin-text-muted);text-transform:uppercase;">Telefon</label>
                <div><a href="tel:<?= e($viewCustomer['telefon']) ?>" style="color:var(--admin-accent);font-weight:700;font-size:15px;"><?= e($viewCustomer['telefon']) ?></a></div>
            </div>
            <div>
                <label style="font-size:11px;font-weight:700;color:var(--admin-text-muted);text-transform:uppercase;">E-posta</label>
                <div><a href="mailto:<?= e($viewCustomer['eposta']) ?>" style="color:var(--admin-info);font-weight:600;font-size:14px;"><?= e($viewCustomer['eposta']) ?></a></div>
            </div>
            <?php if (!empty($viewCustomer['sehir'])): ?>
            <div>
                <label style="font-size:11px;font-weight:700;color:var(--admin-text-muted);text-transform:uppercase;">Şehir</label>
                <div style="font-size:14px;font-weight:600;">
                    <i class="fa-solid fa-location-dot" style="color:var(--admin-accent);margin-right:4px;"></i><?= e($viewCustomer['sehir']) ?>
                </div>
            </div>
            <?php endif; ?>
            <div>
                <label style="font-size:11px;font-weight:700;color:var(--admin-text-muted);text-transform:uppercase;">Kayıt Tarihi</label>
                <div style="font-size:14px;"><?= date('d.m.Y H:i', strtotime($viewCustomer['olusturulma_tarihi'])) ?></div>
            </div>
        </div>

        <div style="margin-top:20px;border-top:1px solid #E2E8F0;padding-top:18px;">
            <h4 style="font-size:15px;font-weight:700;color:var(--admin-text);margin-bottom:12px;">
                <i class="fa-solid fa-calendar-days" style="color:var(--admin-accent);margin-right:6px;"></i>
                Randevu Geçmişi (<?= count($customerAppointments) ?> Randevu)
            </h4>
            <?php if (empty($customerAppointments)): ?>
                <p style="font-size:13px;color:var(--admin-text-muted);margin:0;">Bu müşteriye ait henüz bir randevu kaydı bulunmuyor.</p>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>İşletme</th><th>Hizmet</th><th>Personel</th><th>Randevu Zamanı</th><th>Durum</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($customerAppointments as $apt): ?>
                            <tr>
                                <td><strong><?= e($apt['isletme_adi'] ?? '-') ?></strong></td>
                                <td><?= e($apt['hizmet_adi'] ?? '-') ?> <?= $apt['fiyat'] ? '('.number_format($apt['fiyat'],2).' ₺)' : '' ?></td>
                                <td><?= e($apt['calisan_adi'] ?? 'Fark etmez') ?></td>
                                <td><?= date('d.m.Y H:i', strtotime($apt['randevu_zamani'])) ?></td>
                                <td>
                                    <?php
                                    $st = $apt['durum'];
                                    $badgeClass = match($st) { 'confirmed'=>'active','completed'=>'active','cancelled'=>'suspended','rejected'=>'rejected',default=>'pending' };
                                    $stLabel    = match($st) { 'confirmed'=>'Onaylandı','completed'=>'Tamamlandı','cancelled'=>'İptal Edildi','rejected'=>'Reddedildi',default=>'Bekliyor' };
                                    ?>
                                    <span class="status-badge <?= $badgeClass ?>"><?= $stLabel ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ── Filtre & Arama ── -->
<div class="admin-panel" style="margin-bottom:20px;">
    <div style="padding:16px 20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">

        <!-- Bilgi satırı -->
        <div style="font-size:14px;color:var(--admin-text-muted);">
            Toplam <strong><?= $totalCount ?></strong> kayıtlı müşteri
            <?php if ($totalPages > 1): ?>
                &mdash; Sayfa <strong><?= $page ?></strong> / <strong><?= $totalPages ?></strong>
                &nbsp;(<?= $offset + 1 ?>–<?= min($offset + $perPage, $totalCount) ?>. kayıtlar)
            <?php endif; ?>
        </div>

        <!-- Filtre formu -->
        <form method="GET" action="" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">

            <!-- Metin arama -->
            <div style="position:relative;">
                <input type="text" name="q" value="<?= e($search) ?>"
                       placeholder="Müşteri adı, telefon, e-posta..."
                       class="form-control" style="padding-left:36px;width:240px;height:38px;">
                <i class="fa-solid fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94A3B8;font-size:13px;"></i>
            </div>

            <!-- Şehir filtresi -->
            <div style="position:relative;">
                <select name="sehir" class="form-control"
                        style="height:38px;padding-left:34px;min-width:160px;cursor:pointer;">
                    <option value="">Tüm Şehirler</option>
                    <?php foreach ($cityList as $city): ?>
                        <option value="<?= e($city) ?>" <?= $filterCity === $city ? 'selected' : '' ?>>
                            <?= e($city) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <i class="fa-solid fa-location-dot"
                   style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#94A3B8;font-size:13px;pointer-events:none;"></i>
            </div>

            <button type="submit" class="btn btn-primary btn-sm" style="height:38px;">
                <i class="fa-solid fa-filter"></i> Filtrele
            </button>

            <?php if (!empty($search) || !empty($filterCity)): ?>
                <a href="/admin/pages/customers.php" class="btn btn-secondary btn-sm"
                   style="height:38px;color:#DC2626;" title="Filtreyi Temizle">
                    <i class="fa-solid fa-xmark"></i>
                </a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- ── Müşteri Tablosu ── -->
<div class="admin-panel">
    <div class="panel-header">
        <h2>
            <i class="fa-solid fa-users"></i>
            <span>Müşteriler</span>
        </h2>
    </div>
    <div class="panel-body">
        <?php if (empty($customers)): ?>
            <div style="padding:60px 20px;text-align:center;color:var(--admin-text-muted);">
                <i class="fa-solid fa-user-xmark" style="font-size:40px;color:#CBD5E1;margin-bottom:14px;display:block;"></i>
                <h4 style="color:var(--admin-text);margin-bottom:6px;">Müşteri Bulunamadı</h4>
                <p style="font-size:13px;">Arama veya filtre kriterlerine uygun müşteri kaydı bulunmuyor.</p>
            </div>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Müşteri Ad Soyad</th>
                            <th>Telefon</th>
                            <th>E-posta</th>
                            <th>Şehir</th>
                            <th>Toplam Randevu</th>
                            <th>Tamamlanan</th>
                            <th>Kayıt Tarihi</th>
                            <th style="text-align:right;">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $idx => $c): ?>
                        <tr>
                            <td style="color:var(--admin-text-muted);font-size:12px;font-weight:600;">
                                <?= $offset + $idx + 1 ?>
                            </td>
                            <td>
                                <div style="font-weight:700;color:var(--admin-text);"><?= e($c['ad_soyad']) ?></div>
                            </td>
                            <td>
                                <a href="tel:<?= e($c['telefon']) ?>" style="font-weight:600;color:var(--admin-accent);">
                                    <?= e($c['telefon']) ?>
                                </a>
                            </td>
                            <td>
                                <a href="mailto:<?= e($c['eposta']) ?>" style="color:var(--admin-info);">
                                    <?= e($c['eposta']) ?>
                                </a>
                            </td>
                            <td>
                                <?php if (!empty($c['sehir'])): ?>
                                    <span style="display:inline-flex;align-items:center;gap:4px;font-weight:600;font-size:13px;">
                                        <i class="fa-solid fa-location-dot" style="color:var(--admin-accent);font-size:11px;"></i>
                                        <?= e($c['sehir']) ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color:#CBD5E1;font-size:12px;">—</span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= $c['toplam_randevu'] ?></strong> Randevu</td>
                            <td>
                                <span style="color:var(--admin-success);font-weight:600;"><?= $c['tamamlanan_randevu'] ?></span>
                            </td>
                            <td><?= date('d.m.Y H:i', strtotime($c['olusturulma_tarihi'])) ?></td>
                            <td style="text-align:right;">
                                <a href="?id=<?= $c['id'] ?><?= !empty($search) ? '&q='.urlencode($search) : '' ?><?= !empty($filterCity) ? '&sehir='.urlencode($filterCity) : '' ?><?= $page > 1 ? '&sayfa='.$page : '' ?>"
                                   class="btn btn-secondary btn-sm" title="Randevu Geçmişi">
                                    <i class="fa-solid fa-clock-rotate-left"></i> Randevu Geçmişi
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- ── Sayfalama ── -->
            <?php if ($totalPages > 1): ?>
            <div style="padding:20px;display:flex;align-items:center;justify-content:center;gap:6px;flex-wrap:wrap;border-top:1px solid #E2E8F0;">

                <!-- Önceki -->
                <?php if ($page > 1): ?>
                    <a href="<?= pageUrl($page - 1, $search, $filterCity) ?>"
                       style="display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:8px;border:1px solid #E2E8F0;background:#fff;color:var(--admin-text);font-size:13px;font-weight:600;text-decoration:none;"
                       onmouseover="this.style.background='#F8FAFC'" onmouseout="this.style.background='#fff'">
                        <i class="fa-solid fa-chevron-left" style="font-size:11px;"></i>
                    </a>
                <?php else: ?>
                    <span style="display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:8px;border:1px solid #E2E8F0;background:#F8FAFC;color:#CBD5E1;cursor:not-allowed;">
                        <i class="fa-solid fa-chevron-left" style="font-size:11px;"></i>
                    </span>
                <?php endif; ?>

                <!-- Sayfa numaraları (akıllı kısaltma) -->
                <?php
                $pagesToShow = [1];
                for ($i = max(2, $page - 2); $i <= min($totalPages - 1, $page + 2); $i++) {
                    $pagesToShow[] = $i;
                }
                if ($totalPages > 1) $pagesToShow[] = $totalPages;
                $pagesToShow = array_unique($pagesToShow);
                sort($pagesToShow);
                $prev = null;
                foreach ($pagesToShow as $pn):
                ?>
                    <?php if ($prev !== null && $pn - $prev > 1): ?>
                        <span style="color:#94A3B8;font-size:14px;padding:0 2px;">…</span>
                    <?php endif; ?>
                    <?php if ($pn === $page): ?>
                        <span style="display:inline-flex;align-items:center;justify-content:center;min-width:36px;height:36px;padding:0 8px;border-radius:8px;background:var(--admin-accent);color:#fff;font-size:13px;font-weight:700;">
                            <?= $pn ?>
                        </span>
                    <?php else: ?>
                        <a href="<?= pageUrl($pn, $search, $filterCity) ?>"
                           style="display:inline-flex;align-items:center;justify-content:center;min-width:36px;height:36px;padding:0 8px;border-radius:8px;border:1px solid #E2E8F0;background:#fff;color:var(--admin-text);font-size:13px;font-weight:600;text-decoration:none;"
                           onmouseover="this.style.background='#F8FAFC'" onmouseout="this.style.background='#fff'">
                            <?= $pn ?>
                        </a>
                    <?php endif; ?>
                <?php $prev = $pn; endforeach; ?>

                <!-- Sonraki -->
                <?php if ($page < $totalPages): ?>
                    <a href="<?= pageUrl($page + 1, $search, $filterCity) ?>"
                       style="display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:8px;border:1px solid #E2E8F0;background:#fff;color:var(--admin-text);font-size:13px;font-weight:600;text-decoration:none;"
                       onmouseover="this.style.background='#F8FAFC'" onmouseout="this.style.background='#fff'">
                        <i class="fa-solid fa-chevron-right" style="font-size:11px;"></i>
                    </a>
                <?php else: ?>
                    <span style="display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:8px;border:1px solid #E2E8F0;background:#F8FAFC;color:#CBD5E1;cursor:not-allowed;">
                        <i class="fa-solid fa-chevron-right" style="font-size:11px;"></i>
                    </span>
                <?php endif; ?>

                <!-- Hızlı sayfa atlama -->
                <span style="margin-left:10px;font-size:13px;color:var(--admin-text-muted);display:flex;align-items:center;gap:6px;">
                    Sayfaya git:
                    <input type="number" min="1" max="<?= $totalPages ?>" value="<?= $page ?>"
                           id="pageJumpInput"
                           style="width:56px;height:32px;border:1px solid #E2E8F0;border-radius:6px;text-align:center;font-size:13px;font-weight:600;"
                           onkeydown="if(event.key==='Enter'){var v=parseInt(this.value);if(v>=1&&v<=<?= $totalPages ?>){window.location='<?= addslashes(pageUrl(1, $search, $filterCity)) ?>'.replace('sayfa=1','sayfa='+v);}}">
                </span>
            </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
