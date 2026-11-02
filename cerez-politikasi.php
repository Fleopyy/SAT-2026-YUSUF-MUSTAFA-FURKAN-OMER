<?php
require_once __DIR__ . '/data/privacy.php';
require_once __DIR__ . '/data/database.php';

$s = [];
try {
    $settingsStmt = $pdo->query("SELECT anahtar, deger FROM site_ayarlari");
    while ($row = $settingsStmt->fetch(PDO::FETCH_ASSOC)) {
        $s[$row['anahtar']] = $row['deger'];
    }
} catch (Throwable $e) {}

$contactEmail = $s['iletisim_eposta'] ?? 'destek@bitiras.com';
$lastUpdate = '30 Ekim 2026';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Çerez (Cookie) Politikası - BiTıraş</title>
    <meta name="description" content="BiTıraş çerez politikası, kullanılan çerez türleri, kullanım amaçları ve çerez yönetimi rehberi.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/style.css">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #F8FAFC; color: #1E293B; }
        .legal-header { background: #0F172A; color: #fff; padding: 48px 0 36px; border-bottom: 1px solid #1E293B; }
        .legal-card { background: #fff; border-radius: 20px; border: 1px solid #E2E8F0; padding: 40px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); }
        .legal-nav-card { background: #fff; border-radius: 16px; border: 1px solid #E2E8F0; padding: 20px; position: sticky; top: 24px; }
        .legal-nav-link { display: flex; align-items: center; gap: 10px; padding: 12px 16px; border-radius: 10px; color: #475569; font-weight: 600; font-size: 14px; text-decoration: none; transition: all .2s; }
        .legal-nav-link:hover { background: #F1F5F9; color: #0F172A; }
        .legal-nav-link.active { background: #FFFBEB; color: #D97706; font-weight: 700; border-left: 3px solid #D97706; }
        .legal-section-title { font-size: 18px; font-weight: 800; color: #0F172A; margin: 32px 0 14px; padding-bottom: 8px; border-bottom: 1.5px solid #F1F5F9; display: flex; align-items: center; gap: 10px; }
        .legal-section-title i { color: #D97706; font-size: 16px; }
        .legal-text { font-size: 14.5px; line-height: 1.75; color: #475569; margin-bottom: 16px; }
        .legal-table { width: 100%; font-size: 13.5px; border-collapse: separate; border-spacing: 0; border: 1px solid #E2E8F0; border-radius: 10px; overflow: hidden; margin: 20px 0; }
        .legal-table th { background: #F8FAFC; font-weight: 700; color: #0F172A; padding: 12px 16px; border-bottom: 1px solid #E2E8F0; }
        .legal-table td { padding: 12px 16px; border-bottom: 1px solid #F1F5F9; color: #334155; }
        .legal-table tr:last-child td { border-bottom: none; }
    </style>
</head>
<body>

    <header class="bg-dark py-3 border-bottom border-secondary">
        <div class="container d-flex justify-content-between align-items-center">
            <a href="/" class="d-flex align-items-center gap-2 text-decoration-none">
                <div class="logo-icon"><i class="fa-solid fa-scissors rotate-90"></i></div>
                <span class="fs-4 fw-extrabold text-white">BiTıraş<span style="color: var(--accent);">.com</span></span>
            </a>
            <a href="/" class="btn btn-outline-light btn-sm rounded-3 px-3">
                <i class="fa-solid fa-arrow-left me-1"></i> Ana Sayfa
            </a>
        </div>
    </header>

    <div class="legal-header">
        <div class="container">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge" style="background:rgba(217,119,6,0.2);color:#F59E0B;border:1px solid rgba(217,119,6,0.3);font-size:12px;font-weight:700;">
                    <i class="fa-solid fa-cookie-bite me-1"></i> Şeffaf Çerez Yönetimi
                </span>
                <span class="text-white-50 text-xs">Son Güncelleme: <?= $lastUpdate ?></span>
            </div>
            <h1 class="fs-2 fw-extrabold text-white mb-2">Çerez (Cookie) Politikası</h1>
            <p class="text-white-50 mb-0" style="max-width: 760px; font-size: 15px;">
                BiTıraş web sitesinde kullanılan çerez türleri, kullanım amaçları ve çerez tercihlerinizi nasıl yönetebileceğiniz hakkında kapsamlı rehber.
            </p>
        </div>
    </div>

    <main class="container py-5">
        <div class="row g-4">
            <div class="col-lg-3">
                <div class="legal-nav-card shadow-sm">
                    <div class="text-xs fw-extrabold text-muted text-uppercase mb-3 px-2">Hukuki Belgeler</div>
                    <nav class="d-flex flex-column gap-1">
                        <a href="/kvkk.php" class="legal-nav-link">
                            <i class="fa-solid fa-shield-halved"></i> KVKK Aydınlatma
                        </a>
                        <a href="/gizlilik-politikasi.php" class="legal-nav-link">
                            <i class="fa-solid fa-user-shield"></i> Gizlilik Politikası
                        </a>
                        <a href="/cerez-politikasi.php" class="legal-nav-link active">
                            <i class="fa-solid fa-cookie-bite"></i> Çerez Politikası
                        </a>
                        <a href="/kullanim-kosullari.php" class="legal-nav-link">
                            <i class="fa-solid fa-file-contract"></i> Kullanım Koşulları
                        </a>
                    </nav>
                    <hr class="my-3 text-muted">
                    <button type="button" class="btn btn-warning btn-sm w-100 text-xs py-2 fw-bold text-dark" onclick="BitirasCookie.openSettings()">
                        <i class="fa-solid fa-sliders me-1"></i> Çerez Tercihlerini Yönet
                    </button>
                </div>
            </div>

            <div class="col-lg-9">
                <article class="legal-card">
                    <h2 class="legal-section-title"><i class="fa-solid fa-question-circle"></i> 1. Çerez (Cookie) Nedir?</h2>
                    <p class="legal-text">
                        Çerezler, bir web sitesini ziyaret ettiğinizde tarayıcınız aracılığıyla cihazınıza (bilgisayar, tablet, akıllı telefon) kaydedilen küçük metin dosyalarıdır. Çerezler web sitesinin kullanıcıyı tanımasını, oturumun açık tutulmasını, güvenliğin sağlanmasını ve tercihlerin hatırlanmasını mümkün kılar.
                    </p>

                    <h2 class="legal-section-title"><i class="fa-solid fa-layer-group"></i> 2. Platformumuzda Kullanılan Çerez Türleri</h2>
                    <p class="legal-text">
                        BiTıraş platformunda kullanılan çerezler işlev ve amaçlarına göre 4 ana kategoride toplanmıştır:
                    </p>

                    <div class="table-responsive">
                        <table class="legal-table">
                            <thead>
                                <tr>
                                    <th>Kategori</th>
                                    <th>Çerez Adı</th>
                                    <th>Süre</th>
                                    <th>Kullanım Amacı</th>
                                    <th>Zorunlu mu?</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Zorunlu Çerezler</strong></td>
                                    <td><code>PHPSESSID</code></td>
                                    <td>Oturum Boyunca</td>
                                    <td>Kullanıcının güvenli oturumunun devam etmesini ve randevu akışını sağlar.</td>
                                    <td><span class="badge bg-success">Evet</span></td>
                                </tr>
                                <tr>
                                    <td><strong>Güvenlik Çerezi</strong></td>
                                    <td><code>csrf_token</code></td>
                                    <td>Oturum Boyunca</td>
                                    <td>Siteler arası istek sahteciliği (CSRF) saldırılarını engeller.</td>
                                    <td><span class="badge bg-success">Evet</span></td>
                                </tr>
                                <tr>
                                    <td><strong>Çerez İzni</strong></td>
                                    <td><code>bitiras_cookie_consent</code></td>
                                    <td>180 Gün</td>
                                    <td>Çerez onay panelindeki tercihlerinizi (kabul/ret) saklar.</td>
                                    <td><span class="badge bg-success">Evet</span></td>
                                </tr>
                                <tr>
                                    <td><strong>İşlevsel Çerezler</strong></td>
                                    <td><code>bitiras_filter_pref</code></td>
                                    <td>30 Gün</td>
                                    <td>Seçtiğiniz şehir, ilçe ve salon filtrelerini (VIP, Gece Açık) hatırlar.</td>
                                    <td><span class="badge bg-secondary">İsteğe Bağlı</span></td>
                                </tr>
                                <tr>
                                    <td><strong>Analitik Çerezler</strong></td>
                                    <td><code>_bitiras_analytics</code></td>
                                    <td>90 Gün</td>
                                    <td>Sayfa görüntülenme ve performans metriklerini anonim olarak ölçer.</td>
                                    <td><span class="badge bg-secondary">İsteğe Bağlı</span></td>
                                </tr>
                                <tr>
                                    <td><strong>Pazarlama Çerezleri</strong></td>
                                    <td><code>_bitiras_mkt</code></td>
                                    <td>30 Gün</td>
                                    <td>Öne çıkan salon duyuruları ve kampanya ilgisini ölçümler.</td>
                                    <td><span class="badge bg-secondary">İsteğe Bağlı</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <h2 class="legal-section-title"><i class="fa-solid fa-sliders"></i> 3. Çerez Tercihlerini Nasıl Değiştirebilirsiniz?</h2>
                    <p class="legal-text">
                        BiTıraş, kullanıcılarına çerez tercihleri üzerinde tam kontrol sunar:
                    </p>
                    <ul class="legal-text">
                        <li><strong>Platform İçi Çerez Paneli:</strong> Sayfanın altında yer alan <button type="button" class="btn btn-link p-0 text-warning fw-bold text-decoration-underline" onclick="BitirasCookie.openSettings()">Çerez Tercihlerini Yönet</button> butonuna tıklayarak izinlerinizi dilediğiniz an açabilir veya kapatabilirsiniz.</li>
                        <li><strong>Tarayıcı Ayarları Üzerinden:</strong> Tarayıcınızın (Google Chrome, Mozilla Firefox, Safari, Edge) "Gizlilik ve Güvenlik" ayarları bölümünden kayıtlı çerezleri silebilir veya çerez kabulünü tamamen engelleyebilirsiniz. (Zorunlu çerezlerin engellenmesi durumunda randevu alma ve giriş yapma fonksiyonları çalışmayabilir).</li>
                    </ul>

                    <h2 class="legal-section-title"><i class="fa-solid fa-envelope"></i> 4. İletişim</h2>
                    <p class="legal-text">
                        Çerez politikamız ile ilgili tüm sorularınız için <strong><?= htmlspecialchars($contactEmail, ENT_QUOTES, 'UTF-8') ?></strong> adresinden bizimle iletişime geçebilirsiniz.
                    </p>
                </article>
            </div>
        </div>
    </main>

    <footer class="bg-dark text-secondary py-5 border-top border-secondary mt-5">
        <div class="container">
            <div class="row g-4 mb-4">
                <div class="col-lg-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="logo-icon"><i class="fa-solid fa-scissors rotate-90"></i></div>
                        <span class="fs-4 fw-extrabold text-white">BiTıraş<span style="color: var(--accent);">.com</span></span>
                    </div>
                    <p class="text-secondary text-sm">Online berber randevu yönetim platformu.</p>
                </div>
                <div class="col-6 col-lg-3">
                    <h5 class="text-white text-uppercase fs-6 fw-bold mb-3">Hukuki Belgeler</h5>
                    <ul class="list-unstyled text-sm space-y-2">
                        <li><a href="/kvkk.php" class="text-secondary text-decoration-none">KVKK Aydınlatma Metni</a></li>
                        <li><a href="/gizlilik-politikasi.php" class="text-secondary text-decoration-none">Gizlilik Politikası</a></li>
                        <li><a href="/cerez-politikasi.php" class="text-warning text-decoration-none fw-bold">Çerez Politikası</a></li>
                        <li><a href="/kullanim-kosullari.php" class="text-secondary text-decoration-none">Kullanım Koşulları</a></li>
                        <li><a href="javascript:openCookieSettings()" class="text-secondary text-decoration-none"><i class="fa-solid fa-cookie me-1"></i>Çerez Tercihleri</a></li>
                    </ul>
                </div>
                <div class="col-lg-5">
                    <h5 class="text-white text-uppercase fs-6 fw-bold mb-3">Destek</h5>
                    <p class="text-secondary text-sm mb-1">E-posta: <?= htmlspecialchars($contactEmail, ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </div>
            <div class="pt-4 border-top border-secondary d-flex flex-column flex-sm-row justify-content-between align-items-center text-xs">
                <p class="mb-0">© 2026 BiTıraş.com - Tüm Hakları Saklıdır.</p>
            </div>
        </div>
    </footer>

    <?php include __DIR__ . '/includes/cookie_banner.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

