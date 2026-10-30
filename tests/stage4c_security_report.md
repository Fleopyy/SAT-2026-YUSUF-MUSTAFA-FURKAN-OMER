# Aşama 4C — HTTP başlıkları, CSP, HTTPS ve cookie

## Değişiklikler

- Kök `.htaccess`: nosniff, strict-origin-when-cross-origin, SAMEORIGIN, Permissions-Policy ve yalnızca Content-Security-Policy-Report-Only. Apache'nin onsuccess tablosu temizlenip always tablosunda tek değer atanır; hata ve yönlendirme cevapları da kapsanır. Panel dosyalarının tekrar eden güvenlik başlıkları kaldırıldı. Upload dizinlerindeki mevcut nosniff savunması korundu; aynı always tablosunda set kullanması değer eklemez.
- Panel `.htaccess` dosyalarında cache, robots ve pragma için de aynı tekilleştirme düzeni uygulandı. `admin/includes/auth.php` içindeki ikinci Cache-Control satırı kaldırıldı; no-store korundu.
- CSP kaynak envanteri: yerel JS/CSS, Bootstrap 5.3.3 jsDelivr dosyaları, cdnjs Font Awesome, Google Fonts, yerel/data/blob görseller, yerel fetch ve formlar. object-src none; frame-ancestors self; form-action self. Genel unsafe-inline/unsafe-eval yok.
- Mevcut inline script, style, style öznitelikleri ve olay işleyicileri değiştirilmedi. Bunlar aday politikada ihlal üretir; Report-Only çalışmayı engellemez. Nonce/hash veya harici dosyaya taşıma tamamlanmadan CSP enforcing yapılmamalı. frame-ancestors da şu anda gözlem amaçlıdır; etkin çerçeve koruması X-Frame-Options SAMEORIGIN'dir.
- `data/session.php`: ortak oturum politikası veritabanından ayrıldı. HttpOnly, SameSite=Lax, host-only cookie, path=/, strict mode ve yalnız cookie ile oturum korundu. Secure yalnız doğrudan HTTPS=on/1 sinyalinde açılır; port 443 veya istemciden gelen X-Forwarded-Proto yeterli sayılmaz. `security.php` bunu yükler; berber.php içindeki gereksiz ikinci session_start kaldırıldı. Mevcut admin test yardımcısının oturum başlangıcı da ortak politikayı kullanır.
- Admin çıkış cookie silme işlemi mevcut scope/Secure/HttpOnly yanında SameSite değerini de taşır.
- HTTPS yönlendirmesi ve HSTS eklenmedi; localhost HTTP kullanılabilir. TLS sonlandıran proxy varsa güvenilir proxy yapılandırması ayrıca doğrulanmalı.
- Stage2 testinin eval içindeki __DIR__ çözümlemesi yeni ortak session dosyasına uyarlandı; gerçek DB yerine test double kullanmaya devam eder.

## Doğrulama

- Değişen PHP dosyalarının tamamı `php -l`: başarılı.
- Apache `httpd -t`: Syntax OK. Ayrıca kök/admin/barber/manager `.htaccess` dosyaları Directory bağlamında Include edilerek syntax kontrol edildi. Ana `-t` tek başına .htaccess dosyalarını okumaz; bu nedenle ek kontrol yapıldı. Bu, gerçek HTTP doğrulaması değildir.
- `stage4c_security_test.php`: 40 kontrol geçti; HTTP/TLS/port ve sahte forwarding durumlarında cookie bayrakları, strict mode, CSP ve başlık sahipliği. Oturum backend'i testte bellektedir; gerçek oturum dosyaları ve DB kullanılmaz.
- `stage2_security_test.php`: 47/47.
- `stage3b_security_test.php`: 87/87.
- `stage3c_security_test.php`: 232 kontrol; gerçek DB bağlantısı yok.
- `stage3d_security_test.php`: 31 kontrol; symlink oluşturma izin nedeniyle atlandı.
- `stage4b_security_test.php`: 21 kontrol. `php -n` ile 17 kontrol; GD metadata turu açıkça atlandı.
- `curl http://localhost/nonexistent-stage4c`: localhost:80 bağlantısı kurulamadı.
- `stage4a_upload_test.py`: WinError 10013 soket erişim hatası; HTTP yükleme testi geçmedi.

## Kalan riskler ve sınırlar

- Gerçek Apache yanıtlarında 200/302/403/404 başlık tekilliği, Set-Cookie, HTTPS Secure cookie, tarayıcı CSP ihlalleri ve CDN yüklemeleri HTTP üzerinden doğrulanamadı. Giriş/kayıt/admin/randevu ve oturumlu görsel yükleme uçtan uca test edilmedi; bozulmadıkları kesin olarak iddia edilmiyor. İş mantığı değiştirilmedi ve izole regresyonlar geçti.
- CSP Report-Only XSS engellemez. Inline içerik ihlallerinin temizlenmesi ve gerçek tarayıcı testleri enforcement öncesi gereklidir. Dinamik/veritabanı kaynaklı dış görsel URL'leri aday politikada ihlal üretebilir.
- Merkezi CSP rapor toplama endpoint'i kurulmadı; ihlaller tarayıcı geliştirici konsolundan incelenmeli. Otomatik sunucu tarafı ihlal telemetrisi yoktur. Üçüncü tarafa URL/veri raporlanmaz.
- Apache mod_headers ve AllowOverride FileInfo/All gereklidir; mevcut kurulumda mod_headers yüklü ve AllowOverride All görülmüştür. Apache dışında sunumda veya bu modül kapalıyken merkezi başlıklar uygulanmaz. Proxy/CDN'nin eklediği başlıklar ayrıca kontrol edilmelidir.
- HTTPS doğrulanmadı; HSTS etkin değil. Daha önce tarayıcıda kaydedilmiş HSTS bu değişiklikle temizlenmez. TLS proxy arkasında doğrudan HTTPS sinyali sağlanmadan Secure açılmaz.
- Aşama 1–4B işlevleri, CSRF/RBAC/parola kontrolleri, upload kontrolleri ve SEC-15 owned_media/silme kodu değiştirilmedi. Veritabanına bağlanılmadı, veri/şema/migration değişikliği yapılmadı. Mevcut görsellere dokunulmadı.

Kaynaklar: [Apache mod_headers](https://httpd.apache.org/docs/2.4/mod/mod_headers.html), [MDN CSP](https://developer.mozilla.org/en-US/docs/Web/HTTP/Guides/CSP).

4C kapsamındaki çalışma burada durduruldu; sonraki aşamaya geçilmedi.
