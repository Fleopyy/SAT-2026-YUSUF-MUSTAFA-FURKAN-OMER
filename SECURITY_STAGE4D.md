# Aşama 4D — veritabanı bağlantısı ve yetkileri

## Uygulanan düzeltmeler

- Bağlantı ayarları kaynak koddan `data/database.local.php` dosyasına taşındı. Mevcut kurulum değerleri korundu; bu raporda gizli değer yoktur. Dosya sürüm kontrolü dışında tutulur, dağıtımda ayrıca korunmalıdır.
- `DB_HOST`, `DB_USER`, `DB_PASSWORD`, `DB_NAME` süreç ortam değişkenleri yerel dosyadan önceliklidir. Eksik değişkenler yerel dosyadan tamamlanır; boş parola açıkça desteklenir. Yerel dosya yoksa dört değişken de sağlanmalıdır. `.env` otomatik yüklenmez.
- Eksik/geçersiz yapılandırma ve DSN parametre enjeksiyonu reddedilir. Bağlantı takma adları, utf8mb4 ve exception modu korundu. Prepared statement davranışı değiştirilmedi.
- Başlatma hatasında HTTP 500 ve genel mesaj döner; sürücü ayrıntısı bağlantı günlüğüne yazılmaz. Bağlantı yüklenirken PHP hata gösterimi kapatılır.
- PDOException, RuntimeException alt sınıfıdır. On kullanıcı hata yolunda PDOException ayrıntılarının ekrana aktarılması engellendi; uygulama doğrulama mesajları korunur.
- `data/.htaccess` tüm doğrudan HTTP erişimini reddeder. PHP include işlemleri etkilenmez. Kök `.htaccess`, gizli dosya adları ile SQL/veritabanı/yedek/günlük uzantılarını reddeder; önceki başlık kuralları korunur.

## Yetki incelemesi ve minimum yetkiler

Statik incelemede uygulama bağlantısının root hesabına dayandığı görüldü. Hesap ayrıcalıkları canlı olarak sorgulanmadı; fiili GRANT kapsamı doğrulanmış değildir. Root kullanımı yüksek risk olarak devam eder.

Normal web işlevleri için yalnız uygulama şemasındaki kullanılan tablolarda SELECT, INSERT, UPDATE, DELETE gerekir. Kayıt, randevu, kimlik doğrulama, giriş denemeleri, bildirim, e-posta kuyruğu ve işlem günlükleri bu kapsamdadır. İşlemler için ayrıca yönetim yetkisi gerekmez. Tablo bazında daha dar ayrım, tüm çağrıların rol bazında doğrulanmasından sonra yapılmalıdır.

Normal web hesabına global yetki, GRANT OPTION, FILE, PROCESS, SUPER, SYSTEM_USER, CREATE USER, CREATE, ALTER, DROP, INDEX, TRIGGER, EVENT, ROUTINE/EXECUTE veya yönetim yetkileri verilmemelidir. Kurulum/migration işlemleri ayrı bakım kimliği gerektirir; normal çalışma hesabının parçası değildir. Koddan erişilen şema metadatası için ek global yetki önerilmez.

## Ayrı onay planı — uygulanmadı

1. Yetkili bakım oturumunda gerçek hesap/host eşleşmesini ve mevcut yetkileri denetle; rapora parola veya kimlik doğrulama verisi aktarma.
2. Uygulama sunucusuyla sınırlı host tanımı ve yalnız ilgili şemada gerekli DML yetkileri olan ayrı uygulama hesabı hazırla. Hesap oluşturma, parola belirleme ve yetki işlemleri ayrı onaya tabidir.
3. Sırları tercihen web kökü dışındaki dağıtım ortamına taşı; işletim sistemi ACL'lerini yalnız uygulama ve yetkili işletim kullanıcılarına sınırla. Mevcut yerel dosya geçiş tamamlanana kadar gereklidir.
4. İzole bir kopyada yeni hesapla giriş, kayıt, randevu oluşturma/iptal ve yönetim akışlarını doğrula. Kesinti ve geri dönüş planıyla ortam değişkenlerine geç; ardından eski geniş erişimi daralt. GRANT/REVOKE komutları bu aşamada çalıştırılmadı.
5. Uzaktaki MySQL kullanılıyorsa sertifika doğrulamalı TLS yapılandırmasını dağıtım özelinde test ederek etkinleştir. Bu aşamada bağlantı protokolü değiştirilmedi.

## Doğrulama ve sınırlamalar

- PHP syntax: 86 dosya, sıfır hata.
- Aşama 4D: 38 kontrol; ayar önceliği, geçersiz DSN, sahte PDO ile bağlantı takma adları/charset/error modu ve genel hata yanıtı. Gerçek veritabanı bağlantısı yok.
- Önceki izole testler: Aşama 2 47/47, 3B 87/87, 3C 232, 3D 31, 4B 21, 4C 40 başarılı.
- 3D symlink kontrolü işletim sistemi izni nedeniyle atlandı.
- 4A izole HTTP testi soket erişimi WinError 10013 ile engellendi; başarılı sayılmadı. Apache üzerinden HTTP engelleme de bu ortamda doğrulanmadı. PHP yerleşik sunucusu `.htaccess` uygulamaz. Apache 2.4 ve AllowOverride/AuthConfig desteği dağıtımda doğrulanmalıdır.
- Canlı giriş/kayıt/randevu akışı kullanıcı kısıtı nedeniyle denenmedi. Veritabanına bağlanılmadı, parola değiştirilmedi, migration veya yetki komutu çalıştırılmadı.
- Yerel sır dosyası hâlâ web kökündedir; HTTP engeli dosya sistemi erişimi, yanlış sunucu yapılandırması veya eski yedeklerdeki sırları gidermez. `.gitignore` geçmişte izlenen sırları geçmişten kaldırmaz. Bu dizinde Git deposu bulunmadığından geçmiş denetlenemedi.
- Mevcut diğer sunucu günlükleri SQL ayrıntısı içerebilir; günlükleri yetkili işletim erişiminde tutmak gerekir. HTTP kullanıcı hata yolları düzeltildi.

## Değişen / eklenen dosyalar

`.htaccess`, `.gitignore`, `data/.htaccess`, `data/database.php`, `data/database_config.php`, `data/database.local.php`, `tests/stage4d_security_test.php`, `SECURITY_STAGE4D.md`.

Hata filtreleri: `randevu_olustur.php`, `isletme/calisanlar.php`, `isletme/calisma-saatleri.php`, `isletme/degerlendirmeler.php`, `isletme/fotograflar.php`, `isletme/hizmetler.php`, `isletme/randevular.php`, `isletme/yorumlar.php`, `musteri/hesabim.php`, `musteri/randevularim.php`.

Aşama 4D tamamlandı. Diğer aşamalara geçilmedi.
