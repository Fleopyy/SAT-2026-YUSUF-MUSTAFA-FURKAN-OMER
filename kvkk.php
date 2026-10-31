<?php
require_once __DIR__ . '/data/privacy.php';
require_once __DIR__ . '/data/database.php';
require_once __DIR__ . '/data/app_url.php';

$s = [];
try {
    $settingsStmt = $pdo->query("SELECT anahtar, deger FROM site_ayarlari");
    while ($row = $settingsStmt->fetch(PDO::FETCH_ASSOC)) {
        $s[$row['anahtar']] = $row['deger'];
    }
} catch (Throwable $e) {
}

$siteBaslik = $s['site_baslik'] ?? 'BiTıraş | Premium Berber & Erkek Kuaförü Randevu Sistemi';
$contactEmail = $s['iletisim_eposta'] ?? 'destek@bitiras.com';
$contactPhone = $s['iletisim_telefon'] ?? '0505 508 5158';
$lastUpdate = '30 Ekim 2026';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KVKK Aydınlatma Metni - BiTıraş</title>
    <meta name="description" content="BiTıraş 6698 sayılı Kişisel Verilerin Korunması Kanunu (KVKK) uyarınca kişisel verilerin işlenmesine ilişkin aydınlatma metni.">
    <?php if (hasFunctionalConsent()): ?><link rel="preconnect" href="https://fonts.googleapis.com"><?php endif; ?>
    <?php if (hasFunctionalConsent()): ?><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><?php endif; ?>
    <?php if (hasFunctionalConsent()): ?><link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"><?php endif; ?>
    <?php if (hasFunctionalConsent()): ?><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"><?php endif; ?>
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
        .info-callout { background: #F0FDF4; border-left: 4px solid #10B981; padding: 16px 20px; border-radius: 0 12px 12px 0; margin: 20px 0; font-size: 14px; color: #065F46; }
        .warn-callout { background: #FFFBEB; border-left: 4px solid #F59E0B; padding: 16px 20px; border-radius: 0 12px 12px 0; margin: 20px 0; font-size: 14px; color: #92400E; }
    </style>
</head>
<body>

    <!-- ÜST GEZİNME ÇUBUĞU -->
    <header class="bg-dark py-3 border-bottom border-secondary">
        <div class="container d-flex justify-content-between align-items-center">
            <a href="/" class="d-flex align-items-center gap-2 text-decoration-none">
                <div class="logo-icon"><i class="fa-solid fa-scissors rotate-90"></i></div>
                <span class="fs-4 fw-extrabold text-white">BiTıraş<span style="color: var(--accent);">.com</span></span>
            </a>
            <div class="d-flex gap-2">
                <a href="/" class="btn btn-outline-light btn-sm rounded-3 px-3">
                    <i class="fa-solid fa-arrow-left me-1"></i> Ana Sayfa
                </a>
            </div>
        </div>
    </header>

    <!-- SAYFA BAŞLIĞI -->
    <div class="legal-header">
        <div class="container">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge" style="background:rgba(217,119,6,0.2);color:#F59E0B;border:1px solid rgba(217,119,6,0.3);font-size:12px;font-weight:700;">
                    <i class="fa-solid fa-shield-halved me-1"></i> 6698 Sayılı KVKK Uyumlu
                </span>
                <span class="text-white-50 text-xs">Son Güncelleme: <?= $lastUpdate ?></span>
            </div>
            <h1 class="fs-2 fw-extrabold text-white mb-2">Kişisel Verilerin Korunması ve Aydınlatma Metni</h1>
            <p class="text-white-50 mb-0" style="max-width: 760px; font-size: 15px;">
                BiTıraş.com olarak kişisel verilerinizin güvenliğine ve gizliliğine en yüksek önemi veriyoruz. 6698 sayılı Kanun uyarınca verilerinizin toplanma, işlenme ve saklanma süreçlerini şeffaflıkla paylaşıyoruz.
            </p>
        </div>
    </div>

    <!-- İÇERİK ALANI -->
    <main class="container py-5">
        <div class="row g-4">
            <!-- SOL MENÜ -->
            <div class="col-lg-3">
                <div class="legal-nav-card shadow-sm">
                    <div class="text-xs fw-extrabold text-muted text-uppercase mb-3 px-2">Hukuki Belgeler</div>
                    <nav class="d-flex flex-column gap-1">
                        <a href="/kvkk.php" class="legal-nav-link active">
                            <i class="fa-solid fa-shield-halved"></i> KVKK Aydınlatma
                        </a>
                        <a href="/gizlilik-politikasi.php" class="legal-nav-link">
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

            <!-- SAĞ İÇERİK -->
            <div class="col-lg-9">
                <article class="legal-card">
                    <div class="info-callout">
                        <strong>Önemli Bilgilendirme:</strong> Bu aydınlatma metni, 6698 sayılı Kişisel Verilerin Korunması Kanunu ("KVKK") m. 10 ile Aydınlatma Yükümlülüğünün Yerine Getirilmesinde Uyulacak Usul ve Esaslar Hakkında Tebliğ kapsamında veri sorumlusu sıfatıyla <strong>BiTıraş.com</strong> tarafından hazırlanmıştır.
                    </div>

                    <h2 class="legal-section-title"><i class="fa-solid fa-building"></i> 1. Veri Sorumlusunun Kimliği</h2>
                    <p class="legal-text">
                        Kişisel verileriniz, veri sorumlusu sıfatıyla Türkiye Cumhuriyeti kanunlarına uygun olarak faaliyet gösteren <strong>BiTıraş.com Platformu</strong> ("BiTıraş" veya "Şirket") tarafından aşağıda açıklanan kapsamda işlenmektedir.
                    </p>
                    <ul class="legal-text">
                        <li><strong>Platform Adı:</strong> BiTıraş Online Berber & Kuaför Randevu Sistemi</li>
                        <li><strong>İletişim E-Posta:</strong> <?= htmlspecialchars($contactEmail, ENT_QUOTES, 'UTF-8') ?></li>
                        <li><strong>Destek Telefonu:</strong> <?= htmlspecialchars($contactPhone, ENT_QUOTES, 'UTF-8') ?></li>
                        <li><strong>Web Sitesi:</strong> <?= htmlspecialchars(getSiteBaseUrl(), ENT_QUOTES, 'UTF-8') ?></li>
                    </ul>

                    <h2 class="legal-section-title"><i class="fa-solid fa-database"></i> 2. İşlenen Kişisel Verileriniz ve Toplama Amaçları</h2>
                    <p class="legal-text">
                        BiTıraş üzerinden müşteri veya berber işletmesi olarak gerçekleştirdiğiniz işlemlerde toplanan veri kategorileri ve amaçları aşağıda tabloda özetlenmiştir:
                    </p>

                    <div class="table-responsive">
                        <table class="legal-table">
                            <thead>
                                <tr>
                                    <th>Veri Kategorisi</th>
                                    <th>İşlenen Kişisel Veriler</th>
                                    <th>İşleme Amacı</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Kimlik Verisi</strong></td>
                                    <td>Ad, Soyad</td>
                                    <td>Kullanıcı kaydı oluşturma, randevu sahibinin doğrulanması, işletmede hizmetin doğru kişiye sunulması</td>
                                </tr>
                                <tr>
                                    <td><strong>İletişim Verisi</strong></td>
                                    <td>Telefon numarası, e-posta adresi, şehir (il/ilçe)</td>
                                    <td>Randevu onay ve iptal bildirimlerinin SMS/E-posta ile iletilmesi, hesap doğrulama kodları gönderimi, müşteri desteği</td>
                                </tr>
                                <tr>
                                    <td><strong>İşletme & Konum Verisi</strong></td>
                                    <td>İşletme adı, yetkili adı, açık adres, mahalle, cadde/sokak, bina no, harita enlem/boylam koordinatları, Google Place ID</td>
                                    <td>İşletmelerin haritada listelenmesi, müşterilere mesafe ve yol tarifi sunulması, salon doğrulama süreçleri</td>
                                </tr>
                                <tr>
                                    <td><strong>Randevu & İşlem Verisi</strong></td>
                                    <td>Randevu tarihi, seçilen hizmet, seçilen çalışan, randevu durumu, müşteri notu, hizmet fiyatı</td>
                                    <td>Online randevu organizasyonunun sağlanması, berber koltuk/çalışma takviminin senkronize edilmesi</td>
                                </tr>
                                <tr>
                                    <td><strong>Değerlendirme & Yorum</strong></td>
                                    <td>Verilen puan (1-5 yıldız), randevu deneyimi yorumları</td>
                                    <td>Hizmet kalitesinin ölçülmesi, vitrin ve en yüksek puanlı salonların objektif olarak sıralanması</td>
                                </tr>
                                <tr>
                                    <td><strong>İşlem Güvenliği</strong></td>
                                    <td>Bcrypt şifre hash'i, IP adresi, port, oturum tokenları, başarısız giriş denemeleri, 2FA kodları, güvenilir cihaz kimliği</td>
                                    <td>Hesap güvenliğinin sağlanması, kaba kuvvet (brute-force) saldırılarının engellenmesi, 5651 sayılı Kanun log yükümlülüğü</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <h2 class="legal-section-title"><i class="fa-solid fa-scale-balanced"></i> 3. Kişisel Veri İşlemenin Hukuki Sebepleri</h2>
                    <p class="legal-text">
                        Kişisel verileriniz, 6698 sayılı Kanun'un 5. maddesinin 2. fıkrasında belirtilen aşağıdaki hukuki sebeplere dayanılarak açık rıza aranmaksızın işlenmektedir:
                    </p>
                    <ul class="legal-text">
                        <li><strong>Sözleşmenin Kurulması ve İfası (KVKK m. 5/2-c):</strong> Müşteri ile kuaför salonu arasındaki randevu akdinin kurulması, kullanıcı hesabı oluşturulması ve hizmet takibinin yapılması.</li>
                        <li><strong>Hukuki Yükümlülüğün Yerine Getirilmesi (KVKK m. 5/2-ç):</strong> 5651 sayılı İnternet Ortamında Yapılan Yayınların Düzenlenmesi Hakkında Kanun uyarınca trafik loglarının tutulması ve adli makamların yasal taleplerine yanıt verilmesi.</li>
                        <li><strong>Bir Hakkın Tesisi, Kullanılması veya Korunması (KVKK m. 5/2-e):</strong> Olası tüketici şikayetleri ve hukuki uyuşmazlıklarda delil saklanması.</li>
                        <li><strong>Meşru Menfaat (KVKK m. 5/2-f):</strong> Temel hak ve özgürlüklerinize zarar vermemek kaydıyla sistem güvenliğinin artırılması, kötüye kullanımın önlenmesi ve hizmet kalitesinin geliştirilmesi.</li>
                    </ul>
                    <div class="warn-callout">
                        <strong>Ticari İletişim & Pazarlama (Açık Rıza):</strong> Kampanya, indirim ve yeni hizmet bildirimleri yalnızca kayıt esnasında veya sonrasında isteğe bağlı olarak verdiğiniz <strong>Açık Rıza</strong> ve 6563 sayılı Elektronik Ticaretin Düzenlenmesi Hakkında Kanun uyarınca iletilir. Dilediğiniz zaman bu izni geri alabilirsiniz.
                    </div>

                    <h2 class="legal-section-title"><i class="fa-solid fa-share-nodes"></i> 4. Kişisel Verilerin Aktarılması</h2>
                    <p class="legal-text">
                        Toplanan kişisel verileriniz, kanunun 8. ve 9. maddelerine uygun olarak yalnızca aşağıdaki taraflara ve belirtilen amaçlarla aktarılmaktadır:
                    </p>
                    <ul class="legal-text">
                        <li><strong>İlgili Berber ve Kuaför Salonları:</strong> Randevunuzun eksiksiz yerine getirilmesi amacıyla adınız, telefon numaranız, randevu zamanı ve seçtiğiniz hizmet kalemleri randevu oluşturduğunuz işletmeyle paylaşılır.</li>
                        <li><strong>Hizmet ve Altyapı Sağlayıcılar:</strong> E-posta ve SMS gönderim altyapısı sağlayan teknik hizmet ortakları (yalnızca bildirim iletimi amacıyla).</li>
                        <li><strong>Harita Servisi Sağlayıcıları:</strong> Dükkan konumu gösterimi ve yol tarifi için Google Maps ve Leaflet altyapısı.</li>
                        <li><strong>Yetkili Kamu Kurum ve Kuruluşları:</strong> Yasal zorunluluklar çerçevesinde mahkemeler, savcılıklar ve kolluk kuvvetleri.</li>
                    </ul>

                    <h2 class="legal-section-title"><i class="fa-solid fa-clock"></i> 5. Veri Saklama Süreleri</h2>
                    <p class="legal-text">
                        Verileriniz, işleme amaçlarının gerektirdiği süreler ve ilgili mevzuatta öngörülen zamanaşımı süreleri boyunca saklanır:
                    </p>
                    <ul class="legal-text">
                        <li><strong>Üyelik ve Randevu Kayıtları:</strong> Türk Ticaret Kanunu ve Türk Borçlar Kanunu uyarınca sözleşmenin sona ermesinden itibaren 10 yıl.</li>
                        <li><strong>Erişim ve Trafik Logları (5651 Sayılı Kanun):</strong> 2 yıl süreyle zaman damgalı olarak.</li>
                        <li><strong>İletişim ve E-Posta Gönderim Günlükleri:</strong> 1 yıl.</li>
                    </ul>

                    <h2 class="legal-section-title"><i class="fa-solid fa-user-check"></i> 6. İlgili Kişi Olarak Haklarınız (KVKK Madde 11)</h2>
                    <p class="legal-text">
                        KVKK'nın 11. maddesi uyarınca veri sahibi olarak aşağıdaki haklara sahipsiniz:
                    </p>
                    <ul class="legal-text">
                        <li>Kişisel verilerinizin işlenip işlenmediğini öğrenme,</li>
                        <li>İşlenmişse buna ilişkin bilgi talep etme,</li>
                        <li>İşlenme amacını ve amacına uygun kullanılıp kullanılmadığını öğrenme,</li>
                        <li>Yurt içinde veya yurt dışında verilerin aktarıldığı üçüncü kişileri bilme,</li>
                        <li>Verilerin eksik veya yanlış işlenmiş olması hâlinde düzeltilmesini isteme,</li>
                        <li>KVKK m. 7 çerçevesinde silinmesini veya yok edilmesini isteme,</li>
                        <li>İşlenen verilerin münhasıran otomatik sistemler vasıtasıyla analiz edilmesi suretiyle aleyhinize bir sonucun ortaya çıkmasına itiraz etme,</li>
                        <li>Kanuna aykırı veri işleme sebebiyle zarara uğramanız hâlinde zararın giderilmesini talep etme.</li>
                    </ul>

                    <h2 class="legal-section-title"><i class="fa-solid fa-envelope-open-text"></i> 7. Başvuru ve İletişim</h2>
                    <p class="legal-text">
                        Yukarıda belirtilen haklarınızı kullanmak için sistemde kayıtlı e-posta adresinizden <strong><?= htmlspecialchars($contactEmail, ENT_QUOTES, 'UTF-8') ?></strong> adresine e-posta göndererek veya yazılı dilekçe ile başvurabilirsiniz. Başvurunuz en geç 30 (otuz) gün içerisinde ücretsiz olarak sonuçlandırılacaktır.
                    </p>
                </article>
            </div>
        </div>
    </main>

    <!-- FOOTER -->
    <footer class="bg-dark text-secondary py-5 border-top border-secondary mt-5">
        <div class="container">
            <div class="row g-4 mb-4">
                <div class="col-lg-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="logo-icon"><i class="fa-solid fa-scissors rotate-90"></i></div>
                        <span class="fs-4 fw-extrabold text-white">BiTıraş<span style="color: var(--accent);">.com</span></span>
                    </div>
                    <p class="text-secondary text-sm" style="max-width: 320px;">
                        Türkiye'nin en seçkin berber ve erkek kuaförlerinden online randevu almanın en kolay yolu.
                    </p>
                </div>
                <div class="col-6 col-lg-2">
                    <h5 class="text-white text-uppercase fs-6 fw-bold mb-3">Hızlı Menü</h5>
                    <ul class="list-unstyled text-sm space-y-2">
                        <li><a href="/" class="text-secondary text-decoration-none">Ana Sayfa</a></li>
                        <li><a href="/#barbers" class="text-secondary text-decoration-none">Salonlar</a></li>
                        <li><a href="/isletme/giris.php" class="text-secondary text-decoration-none">Berber Girişi</a></li>
                    </ul>
                </div>
                <div class="col-6 col-lg-3">
                    <h5 class="text-white text-uppercase fs-6 fw-bold mb-3">Hukuki & Gizlilik</h5>
                    <ul class="list-unstyled text-sm space-y-2">
                        <li><a href="/kvkk.php" class="text-warning text-decoration-none fw-bold">KVKK Aydınlatma Metni</a></li>
                        <li><a href="/gizlilik-politikasi.php" class="text-secondary text-decoration-none">Gizlilik Politikası</a></li>
                        <li><a href="/cerez-politikasi.php" class="text-secondary text-decoration-none">Çerez Politikası</a></li>
                        <li><a href="/kullanim-kosullari.php" class="text-secondary text-decoration-none">Kullanım Koşulları</a></li>
                        <li><a href="javascript:openCookieSettings()" class="text-secondary text-decoration-none"><i class="fa-solid fa-cookie me-1"></i>Çerez Tercihleri</a></li>
                    </ul>
                </div>
                <div class="col-lg-3">
                    <h5 class="text-white text-uppercase fs-6 fw-bold mb-3">İletişim</h5>
                    <p class="text-secondary text-sm mb-1">E-posta: <?= htmlspecialchars($contactEmail, ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="text-secondary text-sm mb-0">Telefon: <?= htmlspecialchars($contactPhone, ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </div>
            <div class="pt-4 border-top border-secondary d-flex flex-column flex-sm-row justify-content-between align-items-center text-xs">
                <p class="mb-2 mb-sm-0">© 2026 BiTıraş.com - Tüm Hakları Saklıdır.</p>
                <div class="fw-bold" style="color: var(--accent);"><i class="fa-solid fa-code me-1"></i> BiTıraş Hukuk & Güvenlik</div>
            </div>
        </div>
    </footer>

    <?php include __DIR__ . '/includes/cookie_banner.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

