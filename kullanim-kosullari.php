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
$contactPhone = $s['iletisim_telefon'] ?? '0505 508 5158';
$lastUpdate = '30 Ekim 2026';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kullanım Koşulları - BiTıraş</title>
    <meta name="description" content="BiTıraş platformu kullanım şartları, müşteri ve berber işletmesi hak ve yükümlülükleri.">
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
                    <i class="fa-solid fa-file-contract me-1"></i> Kullanıcı Sözleşmesi
                </span>
                <span class="text-white-50 text-xs">Son Güncelleme: <?= $lastUpdate ?></span>
            </div>
            <h1 class="fs-2 fw-extrabold text-white mb-2">Kullanım Koşulları</h1>
            <p class="text-white-50 mb-0" style="max-width: 760px; font-size: 15px;">
                BiTıraş platformunu ziyaret eden, müşteri hesabı açan veya salon kaydı oluşturan tüm tarafların hak, sorumluluk ve kuralları.
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
                        <a href="/cerez-politikasi.php" class="legal-nav-link">
                            <i class="fa-solid fa-cookie-bite"></i> Çerez Politikası
                        </a>
                        <a href="/kullanim-kosullari.php" class="legal-nav-link active">
                            <i class="fa-solid fa-file-contract"></i> Kullanım Koşulları
                        </a>
                    </nav>
                    <hr class="my-3 text-muted">
                    <button type="button" class="btn btn-outline-dark btn-sm w-100 text-xs py-2" onclick="BitirasCookie.openSettings()">
                        <i class="fa-solid fa-sliders me-1"></i> Çerez Tercihlerini Yönet
                    </button>
                </div>
            </div>

            <div class="col-lg-9">
                <article class="legal-card">
                    <h2 class="legal-section-title"><i class="fa-solid fa-handshake"></i> 1. Taraflar ve Hizmet Kapsamı</h2>
                    <p class="legal-text">
                        İşbu Kullanım Koşulları, <strong>BiTıraş.com</strong> ("Platform") ile siteye erişim sağlayan ziyaretçiler, randevu oluşturan müşteriler ve salon hizmeti sunan berber işletmeleri arasındaki karşılıklı şartları belirler.
                    </p>
                    <p class="legal-text">
                        BiTıraş, bağımsız berber ve erkek kuaförleri ile müşterileri buluşturan dijital bir randevu ve aracılık platformudur. Sunulan berberlik, saç/sakal kesimi, bakım vb. hizmetlerin doğrudan sağlayıcısı ilgili işletmelerdir.
                    </p>

                    <h2 class="legal-section-title"><i class="fa-solid fa-user"></i> 2. Müşteri Hak ve Yükümlülükleri</h2>
                    <ul class="legal-text">
                        <li>Müşteri, kayıt ve randevu oluşturma sırasında girdiği iletişim bilgilerinin (ad soyad, telefon, e-posta) doğruluğunu taahhüt eder.</li>
                        <li>Alınan randevulara zamanında gidilmesi esastır. Katılım sağlanamayacak randevuların sistem üzerinden makul süre önce iptal edilmesi veya değiştirilmesi gerekmektedir.</li>
                        <li>Kullanıcılar gerçeğe aykırı, asılsız veya kötü niyetli yorum/puanlama yapamaz; hakaret içeren değerlendirmeler sistem moderasyonunca kaldırılır.</li>
                    </ul>

                    <h2 class="legal-section-title"><i class="fa-solid fa-shop"></i> 3. Berber İşletmesi Hak ve Yükümlülükleri</h2>
                    <ul class="legal-text">
                        <li>İşletme, platformda sunduğu hizmetlerin, fiyatların ve çalışma saatlerinin güncel ve doğru olduğunu taahhüt eder.</li>
                        <li>Onaylanan randevular için koltuk ve personel hazırlığını zamanında sağlamak işletmenin sorumluluğundadır.</li>
                        <li>İşletmeler, kendilerine iletilen müşteri kişisel verilerini (telefon, ad soyad) yalnızca randevunun ifası amacıyla kullanabilir; izinsiz üçüncü şahıslara aktaramaz veya reklam amacıyla kullanamaz.</li>
                    </ul>

                    <h2 class="legal-section-title"><i class="fa-solid fa-ban"></i> 4. Platformun Yetkileri ve Sorumluluk Sınırı</h2>
                    <p class="legal-text">
                        BiTıraş, platformun 7/24 kesintisiz çalışması için gerekli teknik tedbirleri alır; ancak sunucu bakımları, mücbir sebepler veya internet altyapı arızalarından kaynaklı geçici kesintilerden sorumlu tutulamaz.
                    </p>
                    <p class="legal-text">
                        Kullanıcılar veya işletmeler tarafından gerçekleştirilen kurallara aykırı, şüpheli veya kötüye kullanım teşkil eden hesaplar, BiTıraş yönetimi tarafından askıya alınabilir veya sonlandırılabilir.
                    </p>

                    <h2 class="legal-section-title"><i class="fa-solid fa-gavel"></i> 5. Uyuşmazlıkların Çözümü</h2>
                    <p class="legal-text">
                        İşbu sözleşmeden doğabilecek uyuşmazlıklarda Türk Hukuku uygulanır ve Tüketici Hakem Heyetleri ile İstanbul (Çağlayan) Mahkemeleri ve İcra Daireleri yetkilidir.
                    </p>

                    <h2 class="legal-section-title"><i class="fa-solid fa-envelope"></i> 6. İletişim Bilgileri</h2>
                    <p class="legal-text">
                        Sorularınız ve talepleriniz için <strong><?= htmlspecialchars($contactEmail, ENT_QUOTES, 'UTF-8') ?></strong> e-posta adresi veya <strong><?= htmlspecialchars($contactPhone, ENT_QUOTES, 'UTF-8') ?></strong> numaralı destek hattı üzerinden bize ulaşabilirsiniz.
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
                        <li><a href="/cerez-politikasi.php" class="text-secondary text-decoration-none">Çerez Politikası</a></li>
                        <li><a href="/kullanim-kosullari.php" class="text-warning text-decoration-none fw-bold">Kullanım Koşulları</a></li>
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

