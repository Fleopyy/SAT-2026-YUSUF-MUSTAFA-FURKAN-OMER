<?php
// Maintenance and test tools must never run through HTTP.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

require_once dirname(__DIR__) . '/data/database.php';

echo "=== Gece Açık AJAX ve Profil Testi Başlatılıyor ===\n\n";

// 1. Get an active business
$stmt = $pdo->query("SELECT id, isletme_adi, gece_acik FROM isletmeler WHERE durum = 'active' LIMIT 1");
$biz = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$biz) {
    exit("HATA: Test için işletme bulunamadı.\n");
}

$id = (int)$biz['id'];
$initial = (int)$biz['gece_acik'];

// Test direct update like AJAX endpoint does
$newVal = $initial === 1 ? 0 : 1;
$pdo->prepare('UPDATE isletmeler SET gece_acik = ? WHERE id = ?')->execute([$newVal, $id]);
$updated = (int)$pdo->query("SELECT gece_acik FROM isletmeler WHERE id = $id")->fetchColumn();

if ($updated === $newVal) {
    echo "[PASS] Gece Açık durumu başarıyla $newVal olarak güncellendi.\n";
} else {
    echo "[FAIL] Güncelleme başarısız.\n";
    exit(1);
}

// Restore
$pdo->prepare('UPDATE isletmeler SET gece_acik = ? WHERE id = ?')->execute([$initial, $id]);
echo "[PASS] Başlangıç durumuna geri döndürüldü ($initial).\n";

// Verify HTML changes
$html = file_get_contents(dirname(__DIR__) . '/isletme/profil.php');
if (strpos($html, 'toggleGeceAcik') !== false && strpos($html, 'geceAcikStatusBadge') !== false) {
    echo "[PASS] profil.php içerisinde toggleGeceAcik ve geceAcikStatusBadge mevcut.\n";
} else {
    echo "[FAIL] profil.php içeriğinde gerekli JavaScript/HTML bulunamadı.\n";
    exit(1);
}

echo "\nTest başarıyla tamamlandı!\n";

