# A?ama 4A ? dosya y?kleme g?venli?i

## Kapsam ve de?i?iklikler
- T?m PHP/JS kaynaklar?nda y?kleme API ve multipart aramas? yap?ld?. ?? giri? bulundu: isletme/fotograflar.php (galeri), isletme/calisanlar.php (?al??an ekleme/d?zenleme), musteri/hesabim.php (profil). Bunlar iki ortak y?kleme fonksiyonuna ba?lan?yor.
- data/image_upload.php eklendi; isletme/_common.php ve musteri/_common.php yaln?zca y?kleme fonksiyonlar? ve require sat?rlar? bak?m?ndan de?i?tirildi.
- Ger?ek HTTP y?klemesi, disk ?zerindeki boyut (i?letme 2 MB, m??teri 5 MB), tek uzant?, uzant?/MIME/g?rsel ba?l??? e?le?mesi, piksel s?n?r? ve GD ile ??z?mleme do?rulan?yor. JPG/JPEG, PNG ve WEBP kabul ediliyor. ?stemcinin Content-Type alan?na g?venilmiyor.
- ?ift uzant? ve yol s?zdizimi reddediliyor. G?rseller yeniden kodlan?yor; ?zg?n metaveri ve eklenmi? kod i?eri?i saklanm?yor. Adlar 160 bit rastgele de?er ve do?rulanan uzant?dan olu?uyor; ?zel olu?turma modu mevcut dosyan?n ?zerine yazm?yor.
- Hedef dizinler sabit, kullan?c? kimlikleri pozitif; sembolik ba?lant?/junction hedefleri reddediliyor.
- uploads/.htaccess eklendi; uploads/isletme/.htaccess ve uploads/musteri/.htaccess g??lendirildi. Statik handler, PHP engine off, CGI/SSI/MultiViews kapatma, PATH_INFO kapatma, b?y?k/k???k harf duyars?z tehlikeli ve ?ift uzant? engeli, gizli dosya engeli ve nosniff eklendi.
- Mevcut JPG/PNG/WEBP dosyalar? de?i?tirilmedi. Di?er mevcut g?rsel dosyalar? ta??nmad? veya silinmedi.
- SEC-15: data/owned_media.php ve silme ?a?r?lar? de?i?tirilmedi.

## ?al??t?r?lan kontroller
- De?i?en ?? PHP dosyas?nda php -l: ba?ar?l?.
- Apache httpd.exe -t: Syntax OK. Bu komut .htaccess dosyalar?n? HTTP iste?i gibi y?klemez.
- tests/stage3d_security_test.php: 31 kontrol ba?ar?l?; symlink olu?turma testi izin nedeniyle atland?.
- GD, normal PHP yap?land?rmas?nda kapal?. php -d extension=gd ile uzant?n?n y?klenebildi?i do?ruland?.
- tests/stage4a_upload_test.py: izole multipart HTTP testi haz?rland? ve ?al??t?r?ld?; ba?lant? WinError 10013 nedeniyle ba?ar?s?z oldu. Testler ge?ti olarak de?erlendirilmedi.
- Apache ba?latma denemesi g?nl?k dosyas? eri?im izni nedeniyle ba?ar?s?z oldu. Mevcut g?rsele localhost HTTP iste?i de yan?t alamad? (curl 000).

## Kalan i?ler / i?letim ko?ullar?
- ?NEML?: Aktif Apache PHP yap?land?rmas?nda GD etkinle?tirilmelidir. Bu ?al??ma C:/xampp/htdocs d???ndaki php.ini dosyas?n? de?i?tirmedi. GD yokken yeni y?klemeler g?venli bi?imde reddedilir; mevcut g?rseller dosya olarak kal?r.
- A? ve Apache g?nl?k izinleri uygun ortamda ger?ek Apache HTTP do?rulamas? yap?lmal?d?r: normal g?rseller 200 ve uygun MIME, ?al??t?r?labilir/?ift uzant?/gizli dosyalar 403, PATH_INFO engeli; galeri, ?al??an ve m??teri oturumlar?yla kabul/red y?kleme ak??lar?.
- Mevcut g?rsellerin taray?c?da g?r?nmesi HTTP ?zerinden do?rulanamad?.
- Test beti?i PHP yerle?ik sunucusunu ve s?re? i?in GD se?ene?ini kullan?r; Apache kurallar?n? veya ?retim oturumlar?n? do?rulamaz.

Canl? y?klemeler/veritaban? verileri silinmedi; migration ?al??t?r?lmad?. Tasar?m ve randevu sistemi de?i?tirilmedi.
