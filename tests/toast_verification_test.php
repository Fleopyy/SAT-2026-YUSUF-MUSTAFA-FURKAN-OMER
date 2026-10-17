<?php
   
                                                           
   

$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_METHOD'] = 'GET';

session_start();

                                                                                                         
$_SESSION['mesaj'] = "İşletme kaydınız başarıyla oluşturuldu! Hoş geldiniz.";
$_SESSION['mesaj_turu'] = "success";

ob_start();
include __DIR__ . '/../index.php';
$htmlSuccess = ob_get_clean();

$hasSuccessToast = strpos($htmlSuccess, 'bitiras-toast-container') !== false &&
                   strpos($htmlSuccess, 'toast-success') !== false &&
                   strpos($htmlSuccess, 'fa-circle-check') !== false &&
                   strpos($htmlSuccess, 'İşlem Başarılı') !== false &&
                   strpos($htmlSuccess, 'İşletme kaydınız başarıyla oluşturuldu! Hoş geldiniz.') !== false &&
                   strpos($htmlSuccess, 'alert-dismissible') === false;

                                                                             
$_SESSION['mesaj'] = "Hatalı e-posta veya şifre girdiniz!";
$_SESSION['mesaj_turu'] = "danger";

ob_start();
include __DIR__ . '/../index.php';
$htmlDanger = ob_get_clean();

$hasDangerToast = strpos($htmlDanger, 'bitiras-toast-container') !== false &&
                  strpos($htmlDanger, 'toast-danger') !== false &&
                  strpos($htmlDanger, 'fa-circle-xmark') !== false &&
                  strpos($htmlDanger, 'İşlem Başarısız') !== false &&
                  strpos($htmlDanger, 'Hatalı e-posta veya şifre girdiniz!') !== false &&
                  strpos($htmlDanger, 'alert-dismissible') === false;

echo "====================================================================\n";
echo "BiTıraş.com TOAST POP-UP BİLDİRİM DOĞRULAMA TESTİ\n";
echo "====================================================================\n\n";

if ($hasSuccessToast) {
    echo "✅ [GEÇTİ] Başarı Toast'ı Sağ Üstte Pop-up Olarak Render Edildi (Eski full-width alert kaldırıldı).\n";
} else {
    echo "❌ [BAŞARISIZ] Başarı Toast'ı beklendiği gibi oluşturulamadı.\n";
}

if ($hasDangerToast) {
    echo "✅ [GEÇTİ] Hata Toast'ı Sağ Üstte Pop-up Olarak Render Edildi (Eski full-width alert kaldırıldı).\n";
} else {
    echo "❌ [BAŞARISIZ] Hata Toast'ı beklendiği gibi oluşturulamadı.\n";
}

$cssContent = file_get_contents(__DIR__ . '/../style.css');
$hasCssRules = strpos($cssContent, '.bitiras-toast-container') !== false &&
               strpos($cssContent, 'position: fixed') !== false &&
               strpos($cssContent, 'toastSlideIn') !== false;

if ($hasCssRules) {
    echo "✅ [GEÇTİ] style.css İçerisinde Toast Pozisyonlama, Animasyon ve Renk Kuralları Mevcut.\n";
} else {
    echo "❌ [BAŞARISIZ] style.css dosyası gerekli CSS kurallarını içermiyor.\n";
}

if ($hasSuccessToast && $hasDangerToast && $hasCssRules) {
    echo "\nSONUÇ: BİLDİRİM BALONCUĞU TESTİ KUSURSUZ ŞEKİLDE GEÇTİ! 🎉\n";
} else {
    echo "\nSONUÇ: BAZI TESTLER BAŞARISIZ OLDU!\n";
}
echo "====================================================================\n";
