# Aşama 3D — SEC-15 / SEC-17

## Durum

SEC-15 için düzeltmeler uygulandı. SEC-17'nin mevcut kontrolsüz ağ/eposta kuyruğu yolu kapatıldı. SMTP politikasına bağlı çalışma beklemede; SEC-17 tamamen tamamlandı olarak değerlendirilmemeli. Aşama 4'e geçilmedi.

## Doğrulanan bulgular ve değişiklikler

- `admin/actions/media_action.php`: Veritabanı commit edildikten sonra `@unlink` ile doğrulanmamış yol siliniyordu. Ortak güvenli silme yordamına geçirildi. SEC-10 işletme kapsamı ve silme izni kontrolü korundu. DELETE sorgusuna işletme ve beklenen dosya yolu koşulları eklendi; etkilenen kayıt sayısı doğrulanıyor.
- `data/owned_media.php` (yeni): Yalnızca `uploads/isletme/{işletmeId}/` kabul edilir. Her yol bileşeni için realpath, sembolik bağlantı/yönlendirme ve son dizin sınırı kontrol edilir. Windows ayraçları ve harf duyarlılığı dikkate alınır. Traversal, UNC, sürücü/stream/protokol yolları, kontrol karakterleri, başka işletme, eksik dosya ve çoklu hard link reddedilir. Geçersiz yollar artık sessizce geçilmez.
- `isletme/_common.php`: İşletmenin dosya silme yardımcısı aynı kontrolleri kullanır.
- `isletme/fotograflar.php`: Commit sonrası dosya silme kaldırıldı. Dosya işlemi ve işletmeye/yola bağlı SQL silme aynı kurtarılabilir akışa alındı.
- `admin/actions/settings_action.php`: `settings.edit`, POST ve CSRF kontrolleri korunur. SMTP testi politika belirlenene kadar hata mesajıyla sonlanır; DNS/soket/eposta kuyruğu işlemi yapmaz. SMTP alanlarına yazma da bekletilir; mevcut parola dahil tüm SMTP ayarları korunur. Diğer site ayarları kaydedilebilir.
- `admin/pages/content.php`: `settings.view` ve düzenleme alanlarındaki `settings.edit` denetimleri korunur. SMTP alanları/testi devre dışıdır ve bekleme nedeni görünür. Teslimat başarısı iddia edilmez.
- `tests/stage3b_security_test.php`: Yeni yardımcı yüklenir. Yetkili isteğin bile sahte veritabanındaki mevcut olmayan/yükleme dizini dışındaki dosyayı silemeyeceği beklentisi güncellendi.
- `tests/stage3d_security_test.php` (yeni): Gerçek uygulama bootstrap/veritabanı olmadan geçici dosyalar, sahte veritabanı ve alt süreçte gerçek ayar handler'ı kullanılır.

## Dosya ve veritabanı tutarlılığı

Dosya önce aynı dizindeki rastgele kurtarma adına taşınır. SQL değişiklikleri uygulanır; dosyanın özgün yoldan silinebildiği commit öncesi kontrol edilir. SQL/commit hatasında transaction geri alınır ve kurtarma dosyası özgün yola getirilir. Başarılı commit sonrası kurtarma dosyası temizlenir. Silme/taşıma/kopyalama hataları bastırılmaz.

Commit sonrası kurtarma dosyası temizlenemezse başarılı işlem mesajı verilmez; hata ve kurtarma yolu loglanır. Böyle bir durumda veritabanında silinmiş kaydın rastgele adlı artık kopyası kalabilir. Dosya sistemi ve SQL arasında dağıtık transaction bulunmadığından ani süreç/işletim sistemi kesintileri, belirsiz commit sonucu, rollback veya geri yüklemenin de başarısız olması otomatik olarak bütünüyle çözülemez. Bu durumlar için yönetici müdahalesi gerekir; dayanıklı kurtarma günlüğü/migration eklenmedi.

## Test sonuçları

- PHP sözdizimi: değişen/yeni 8 PHP dosyası başarılı.
- `stage2_security_test.php`: 47/47.
- `stage3b_security_test.php`: 87/87.
- `stage3c_security_test.php`: 232 kontrol başarılı.
- `stage3d_security_test.php`: 31 kontrol başarılı.
- Toplam: 397 başarılı kontrol. Yetkisiz erişim ve geçersiz dosya için loglanan ret mesajları beklenen sonuçlardır.
- Eski yollar: göreli `uploads/isletme/7/gallery/photo.jpg`, başında `/` bulunan yol ve Windows ters ayraçlı eşdeğeri test edildi. İşletme dizinindeki eski doğrudan dosyalar da yordam tarafından kabul edilir. Gerçek müşteri dosyaları veya DB kayıtları taranmadığından tüm tarihsel kayıtların uyumluluğu doğrulanmadı. İşletme sahipliği yolundan belirlenemeyen eski ortak dizinlere istisna verilmedi.
- SQL callback ve commit hatalarında dosya içeriği geri yükleme ve geçici kopya temizliği doğrulandı.
- SMTP salt görüntüleme yetkisi reddi, düzenleme yetkisiyle testin bekletilmesi, geçersiz alıcıların yan etkisiz reddi ve boş/dolu parola gönderildiğinde SMTP yazımı yapılmaması doğrulandı.
- İlk 3D çalıştırması sistem geçici dizininde Windows rename erişim engeline takıldı. Test kendi rastgele çalışma alanı geçici dizinine taşındı; sonraki çalıştırmalar geçti ve test dosyaları temizlendi.
- Sembolik bağlantı oluşturma: `symlink(): Permission denied` nedeniyle çalışma zamanı testi ATLANDI. Kod kontrolleri incelendi; junction/hard link, yarış durumu ve gerçek disk/izin hataları kapsamlı olarak doğrulanmadı.
- Gerçek veritabanına yazan `rbac_verification_test.php`, `all_actions_integration_test.php` ve benzer uygulama entegrasyon testleri çalıştırılmadı. Migration çalıştırılmadı; gerçek veritabanına, müşteri kayıtlarına ve mevcut medya dosyalarına dokunulmadı. Ağ bağlantısı açılmadı.

## Bekleyen SMTP işi ve kalan sınırlar

İzinli SMTP sunucuları, kurum içi IP/CIDR istisnaları ve port/protokol politikası belirlenmeli. Bu politika varsayılmadı. Sunucu/port/protokol doğrulaması, tüm A/AAAA sonuçlarında loopback/özel/link-local/metadata engeli, doğrulanan IP'ye sabitlenmiş bağlantı ve asıl hostname ile TLS sertifika doğrulaması, ardından SMTP protokol testi henüz uygulanmadı. TCP bağlantısı veya SMTP kabulü tek başına son alıcıya teslimat kanıtı sayılmamalı. Bu işler tamamlanana kadar SMTP testi ve ayar değişiklikleri kapalı kalır; mevcut yapılandırma korunur.

PHP yol doğrulaması ve dosya işlemleri tek bir atomik `openat/no-follow` işlemi değildir. Kontrolden hemen önce yeniden doğrulama yapılır; sunucuda dosya sistemi erişimi olan bağımsız saldırganın eşzamanlı dizin değiştirmesi bütünüyle engellenmiş sayılmaz. Yükleme dizinleri yalnız uygulamanın güvenilen hesabınca yazılabilir olmalıdır. Gerçek dağıtım izinleri bu çalışmada değiştirilmedi/doğrulanmadı.

Çalışma alanında Git deposu bulunmadığından Git diff/status karşılaştırması yapılamadı. Yukarıda listelenen dosyalar ve bu rapor dışında dosya değişikliği yapılmadı.
