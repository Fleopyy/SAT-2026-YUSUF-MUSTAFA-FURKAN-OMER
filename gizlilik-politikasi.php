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
    <title>Gizlilik Politikası - BiTıraş</title>
    <meta name="description" content="BiTıraş gizlilik politikası, veri güvenliği standartları ve kullanıcı hesap koruma ilkeleri.">
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
        .info-callout { background: #EFF6FF; border-left: 4px solid #3B82F6; padding: 16px 20px; border-radius: 0 12px 12px 0; margin: 20px 0; font-size: 14px; color: #1E40AF; }
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
                <span class="badge" style="background:rgba(59,130,246,0.2);color:#60A5FA;border:1px solid rgba(59,130,246,0.3);font-size:12px;font-weight:700;">
                    <i class="fa-solid fa-lock me-1"></i> Güvenlik & Gizlilik
                </span>
                <span class="text-white-50 text-xs">Son Güncelleme: <?= $lastUpdate ?></span>
            </div>
            <h1 class="fs-2 fw-extrabold text-white mb-2">Gizlilik Politikası</h1>
            <p class="text-white-50 mb-0" style="max-width: 760px; font-size: 15px;">
                BiTıraş platformunu kullanırken bilgilerinizin nasıl korunduğunu, teknik güvenlik önlemlerimizi ve gizlilik ilkelerimizi inceleyin.
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
                        <a href="/gizlilik-politikasi.php" class="legal-nav-link active">
                            <i class="fa-solid fa-user-shield"></i> Gizlilik Politikası
                        </a>
                        <a href="/cerez-politikasi.php" class="legal-nav-link">
                            <i class="fa-solid fa-cookie-bite"></i> Çerez Politikası
                        </a>
                        <a href="/kullanim-kosullari.php" class="legal-nav-link">
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
                    <div class="info-callout">
                        <strong>Önemli İlke:</strong> BiTıraş, kullanıcılarının verilerini üçüncü şahıslara veya reklam şirketlerine asla satmaz, kiralamaz ya da ticari meta olarak kullandırmaz.
                    </div>

                    <h2 class="legal-section-title"><i class="fa-solid fa-shield-virus"></i> 1. Veri Güvenliği Standartlarımız</h2>
                    <p class="legal-text">
                        BiTıraş, kullanıcı ve salon verilerinin gizliliğini korumak amacıyla endüstri standardı güvenlik önlemlerini uygulamaktadır:
                    </p>
                    <ul class="legal-text">
                        <li><strong>Kriptografik Parola Güvenliği:</strong> Kullanıcı ve işletme şifreleri veritabanında asla açık metin olarak tutulmaz; endüstri standardı <code>bcrypt</code> tuzlu (salted) karma algoritmalarıyla şifrelenir.</li>
                        <li><strong>SSL / TLS Şifreleme:</strong> Tüm ağ trafiği modern 256-bit SSL/TLS şifreleme protokolü üzerinden güvenle taşınır.</li>
                        <li><strong>Kaba Kuvvet (Brute-Force) Koruması:</strong> Şüpheli veya peş peşe hatalı giriş denemeleri IP bazlı olarak sınırlandırılır ve geçici süreyle askıya alınır.</li>
                        <li><strong>İki Aşamalı Doğrulama (2FA):</strong> Şüpheli cihazlardan veya farklı konumlardan yapılan işletme girişlerinde e-posta/TOTP tabanlı iki adımlı güvenlik zorunlu kılınır.</li>
                        <li><strong>CSRF & XSS Koruması:</strong> Form gönderimlerinde güvenli CSRF token doğrulaması ve girdi filtrelemesi uygulanır.</li>
                    </ul>

                    <h2 class="legal-section-title"><i class="fa-solid fa-credit-card"></i> 2. Ödeme Bilgileri ve Güvenlik</h2>
                    <p class="legal-text">
                        BiTıraş, bir randevu ve rezervasyon platformudur. Sistemimiz üzerinden doğrudan kredi kartı tahsilatı yapılmamakta olup; randevu hizmet bedelleri berber salonunda nakit veya POS cihazı üzerinden doğrudan işletmeye ödenmektedir. Dolayısıyla platformumuzda kredi kartı veya banka hesap bilgisi toplanmaz, işlenmez ve saklanmaz.
                    </p>

                    <h2 class="legal-section-title"><i class="fa-solid fa-user-lock"></i> 3. Hesap Güvenliği ve Kullanıcı Sorumluluğu</h2>
                    <p class="legal-text">
                        Kullanıcılar ve berber işletmeleri, kendi hesap şifrelerinin gizliliğini korumakla yükümlüdür. Şifrenizin en az 10 karakterden oluşması, tahmin edilmesi zor harf, rakam ve sembol kombinasyonları içermesi sistem tarafından zorunlu tutulmaktadır. Hesabınızda şüpheli bir işlem fark ettiğinizde derhal şifrenizi sıfırlamalı ve BiTıraş destek ekibiyle iletişime geçmelisiniz.
                    </p>

                    <h2 class="legal-section-title"><i class="fa-solid fa-map-location"></i> 4. Konum ve Harita Entegrasyonları</h2>
                    <p class="legal-text">
                        İşletmelerin harita üzerinde gösterilmesi ve kullanıcılara en yakın salonların listelenmesi amacıyla harita koordinatları işlenir. Müşterilerin anlık GPS konumları izinsiz olarak kaydedilmez; yalnızca arama esnasında seçtikleri il/ilçe bilgisi filtreleme amacıyla kullanılır.
                    </p>

                    <h2 class="legal-section-title"><i class="fa-solid fa-triangle-exclamation"></i> 5. Güvenlik İhlali Bildirim Prosedürü</h2>
                    <p class="legal-text">
                        Herhangi bir veri ihlali veya yetkisiz erişim şüphesi tespit edildiğinde, KVKK m. 12 uyarınca durum derhal Kişisel Verileri Koruma Kurulu'na ve etkilenen kullanıcılara en geç 72 saat içerisinde bildirilir ve gerekli teknik tedbirler derhal uygulanır.
                    </p>

                    <h2 class="legal-section-title"><i class="fa-solid fa-headset"></i> 6. İletişim</h2>
                    <p class="legal-text">
                        Gizlilik politikamız ve veri güvenliği uygulamalarımız hakkında sorularınız için <strong><?= htmlspecialchars($contactEmail, ENT_QUOTES, 'UTF-8') ?></strong> adresinden veri koruma sorumlumuza ulaşabilirsiniz.
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
                        <li><a href="/gizlilik-politikasi.php" class="text-warning text-decoration-none fw-bold">Gizlilik Politikası</a></li>
                        <li><a href="/cerez-politikasi.php" class="text-secondary text-decoration-none">Çerez Politikası</a></li>
                        <li><a href="/kullanim-kosullari.php" class="text-secondary text-decoration-none">Kullanım Koşulları</a></li>
                        <li><a href="javascript:openCookieSettings()" class="text-secondary text-decoration-none"><i class="fa-solid fa-cookie me-1"></i>Çerez Tercihleri</a></li>
                    </ul>
                </div>
                <div class="col-lg-5">
                    <h5 class="text-white text-uppercase fs-6 fw-bold mb-3">Destek</h5>
                    <p class="text-secondary text-sm mb-1">E-posta: <?= htmlspecialchars($contactEmail, ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="text-secondary text-sm mb-0">Telefon: <?= htmlspecialchars($contactPhone, ENT_QUOTES, 'UTF-8') ?></p>
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

