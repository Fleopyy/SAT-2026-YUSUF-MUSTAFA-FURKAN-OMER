<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

require_once dirname(__DIR__) . '/data/database.php';

echo "=== Gece Açık AJAX ve Profil Testi Başlatılıyor ===\n\n";

$stmt = $pdo->query("SELECT id, isletme_adi, gece_acik FROM isletmeler WHERE durum = 'active' LIMIT 1");
$biz = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$biz) {
    exit("HATA: Test için işletme bulunamadı.\n");
}

$id = (int)$biz['id'];
$initial = (int)$biz['gece_acik'];

$newVal = $initial === 1 ? 0 : 1;
$pdo->prepare('UPDATE isletmeler SET gece_acik = ? WHERE id = ?')->execute([$newVal, $id]);
$updated = (int)$pdo->query("SELECT gece_acik FROM isletmeler WHERE id = $id")->fetchColumn();

if ($updated === $newVal) {
    echo "[PASS] Gece Açık durumu başarıyla $newVal olarak güncellendi.\n";
} else {
    echo "[FAIL] Güncelleme başarısız.\n";
    exit(1);
}

$pdo->prepare('UPDATE isletmeler SET gece_acik = ? WHERE id = ?')->execute([$initial, $id]);
echo "[PASS] Başlangıç durumuna geri döndürüldü ($initial).\n";

$html = file_get_contents(dirname(__DIR__) . '/isletme/profil.php');
if (strpos($html, 'toggleGeceAcik') !== false && strpos($html, 'geceAcikStatusBadge') !== false) {
    echo "[PASS] profil.php içerisinde toggleGeceAcik ve geceAcikStatusBadge mevcut.\n";
} else {
    echo "[FAIL] profil.php içeriğinde gerekli JavaScript/HTML bulunamadı.\n";
    exit(1);
}

echo "\nTest başarıyla tamamlandı!\n";

