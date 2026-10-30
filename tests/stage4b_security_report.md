# A?ama 4B ? g?rsel kaynak ve kota g?venli?i

De?i?en dosyalar: `data/image_upload.php`, `tests/stage4b_security_test.php`, bu rapor.

- 4A ger?ek HTTP y?klemesi, dosya ad?, MIME/uzant?/ba?l?k e?le?mesi, rastgele ad, ?zel dosya olu?turma ve dizin y?nlendirme kontrolleri korundu. SEC-15 dosyalar? ve silme ?a?r?lar? de?i?tirilmedi.
- Girdi ?st s?n?r? mutlak 5 MiB; i?letmede mevcut 2 MiB, m??teride mevcut 5 MiB s?n?r? korundu. Yeniden kodlanan ??kt? da ayn? s?n?ra tabi; a??mda yaln?zca yeni dosya temizlenir.
- Mevcut 8.000 piksel kenar ve 16.000.000 toplam piksel s?n?r? korundu. Decode ?ncesi tahmin: 12 bayt/piksel + girdinin iki kat? + 16 MiB rezerv. Kullan?labilir PHP belle?i ve 256 MiB uygulama tavan?n?n k????? uygulan?r; s?n?rs?z memory_limit tavan? kald?rmaz. Tam dosya okumas? da decode ?ncesi do?rulan?r.
- ??letme ba??na galeri ve ?al??anlar birlikte 200 dosya / 100 MiB; m??teri ba??na 20 dosya / 20 MiB. Tarihsel ve sahipsiz dosyalar da say?l?r. Yeni y?kleme i?in azami ??kt? boyutu ?nceden rezerve edilir. Say?m en fazla 4096 girdi / 8 alt dizin derinli?iyle s?n?rl?; ba?lant?/y?nlendirme ve okunamayan dizinler g?venli hata ?retir.
- Sahip ba??na kal?c? `.quota-ID.lock` dosyas? kategori dizininde tutulur; bloklamayan ?zel kilit say?m, decode ve kay?t boyunca korunur. Kilit ba?lant?lar? ve ?ok ba?lant?l? dosyalar reddedilir. Kilit dosyalar? kald?r?lmaz; kota yar???n? ?nleyen kal?c? senkronizasyon dosyalar?d?r.
- Mevcut GD yeniden kodlamas? yeniden yaz?lmad?. JPEG EXIF APP1 ve ek veri; PNG/WEBP ek veri temizli?i CLI GD turuyla do?ruland?. GD yokken decode ?ncesi hata korundu.

## ?al??t?r?lan kontroller

- De?i?en iki PHP dosyas? `php -l`: ba?ar?l?.
- `php tests/stage4b_security_test.php`: 21 kontrol ba?ar?l?; bu ortamda normal CLI GD etkin.
- `php -n tests/stage4b_security_test.php`: 17 kontrol ba?ar?l?, GD metadata testleri a??k?a atland?. GD eksikli?i korumas? kaynak kontrol?yle do?ruland?; ger?ek multipart GD-kapal? ak??? ?al??t?r?lamad?.
- `php tests/stage3d_security_test.php`: 31 kontrol ba?ar?l?; symlink olu?turma izin nedeniyle atland?.
- `python tests/stage4a_upload_test.py`: WinError 10013 soket izni nedeniyle ba?ar?s?z; HTTP testleri ba?ar?l? say?lmad?.

## Kalan risk ve do?rulamalar

- Apache/oturumlu ger?ek y?kleme, ??kt? boyutu a??m? sonras? HTTP temizleme ve e?zamanl? HTTP kota yar??lar? bu ortamda do?rulanamad?. Kilit/kota entegrasyonu ayr?ca uygun ortamda denenmeli.
- Bellek tahmini konservatiftir; GD yerel bellek t?ketimi/CPU i?in kesin i?letim sistemi s?n?r? de?ildir. Sunucu genelinde disk, istek boyutu, i?lem s?resi ve trafik s?n?rlar? ayr?ca i?letim ayar? gerektirir; bu ?al??ma sunucu yap?land?rmas?n? de?i?tirmedi.
- Kota hesaplar? dosya sistemi temellidir; uygulama d??? yazan s?re?ler kilide uymaz. Sunucu dosya izinleri ?nemini korur. Eski/sahipsiz dosyalar kotay? doldurabilir; otomatik temizlik yap?lmad?. Azami dosya boyutu rezervi nedeniyle kalan alan k???k bir dosyaya yetse de y?kleme reddedilebilir.
- GD metadata kontrol? izole encoder/decode turudur; ger?ek HTTP kay?t ak??? de?ildir. PNG/WEBP ?zel metadata par?a ?e?itleri kapsaml? bi?imde denenmedi.

Mevcut g?rseller silinmedi/de?i?tirilmedi. Veritaban?, ?ema, migration, tasar?m, giri? ve randevu kodlar?na dokunulmad?. 4C/4D ?al??mas? yap?lmad?.
