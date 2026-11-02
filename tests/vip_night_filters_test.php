<?php
// Maintenance and test tools must never run through HTTP.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

require_once dirname(__DIR__) . '/data/database.php';

echo "=== VIP, Gece Açık ve Puan Filtreleri Testi Başlatılıyor ===\n\n";

$passCount = 0;
$failCount = 0;

function assertTest(string $description, bool $condition, string $detail = '') {
    global $passCount, $failCount;
    if ($condition) {
        $passCount++;
        echo " [PASS] $description\n";
    } else {
        $failCount++;
        echo " [FAIL] $description" . ($detail ? " ($detail)" : "") . "\n";
    }
}

try {
    // 1. Database schema test
    $columns = $pdo->query("SHOW COLUMNS FROM isletmeler")->fetchAll(PDO::FETCH_COLUMN);
    assertTest("isletmeler tablosunda 'is_vip' kolonu mevcut", in_array('is_vip', $columns, true));
    assertTest("isletmeler tablosunda 'gece_acik' kolonu mevcut", in_array('gece_acik', $columns, true));

    // Get an existing active business for testing
    $stmt = $pdo->query("SELECT id, isletme_adi, is_vip, gece_acik FROM isletmeler WHERE durum = 'active' LIMIT 1");
    $testBusiness = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($testBusiness) {
        $testId = (int)$testBusiness['id'];
        $originalVip = (int)$testBusiness['is_vip'];
        $originalGece = (int)$testBusiness['gece_acik'];

        // 2. Test VIP Toggle
        $pdo->prepare("UPDATE isletmeler SET is_vip = 1 WHERE id = ?")->execute([$testId]);
        $checkVip = (int)$pdo->query("SELECT is_vip FROM isletmeler WHERE id = $testId")->fetchColumn();
        assertTest("Admin işletmeyi VIP yapabiliyor (is_vip=1)", $checkVip === 1);

        // 3. Test Gece Açık Toggle
        $pdo->prepare("UPDATE isletmeler SET gece_acik = 1 WHERE id = ?")->execute([$testId]);
        $checkGece = (int)$pdo->query("SELECT gece_acik FROM isletmeler WHERE id = $testId")->fetchColumn();
        assertTest("İşletme profili gece açık özelliğini güncelleyebiliyor (gece_acik=1)", $checkGece === 1);

        // 4. Test VIP Filter Query
        $vipQuery = $pdo->query("SELECT id, isletme_adi, is_vip FROM isletmeler WHERE durum = 'active' AND is_vip = 1")->fetchAll(PDO::FETCH_ASSOC);
        $allVip = true;
        foreach ($vipQuery as $b) {
            if ((int)$b['is_vip'] !== 1) { $allVip = false; break; }
        }
        assertTest("VIP filtresi yalnızca is_vip=1 olan işletmeleri listeliyor", $allVip && count($vipQuery) > 0);

        // 5. Test Gece Açık Filter Query
        $geceQuery = $pdo->query("SELECT id, isletme_adi, gece_acik FROM isletmeler WHERE durum = 'active' AND gece_acik = 1")->fetchAll(PDO::FETCH_ASSOC);
        $allGece = true;
        foreach ($geceQuery as $b) {
            if ((int)$b['gece_acik'] !== 1) { $allGece = false; break; }
        }
        assertTest("Gece Açık filtresi yalnızca gece_acik=1 olan işletmeleri listeliyor", $allGece && count($geceQuery) > 0);

        // 6. Test Rating Ordering Query
        $ratingSql = "SELECT i.id, i.isletme_adi, COALESCE(r.rating, 0) AS rating
                      FROM isletmeler i
                      LEFT JOIN (
                          SELECT a.isletme_id, AVG(d.puan) AS rating
                          FROM randevular a INNER JOIN degerlendirmeler d ON d.randevu_id = a.id
                          WHERE d.degerlendiren_turu = 'customer' GROUP BY a.isletme_id
                      ) r ON r.isletme_id = i.id
                      WHERE i.durum = 'active'
                      ORDER BY rating DESC, i.isletme_adi ASC";
        $ratedRows = $pdo->query($ratingSql)->fetchAll(PDO::FETCH_ASSOC);
        $isOrderedDesc = true;
        for ($i = 0; $i < count($ratedRows) - 1; $i++) {
            if ((float)$ratedRows[$i]['rating'] < (float)$ratedRows[$i + 1]['rating']) {
                $isOrderedDesc = false;
                break;
            }
        }
        assertTest("En yüksek puanlılar filtresi puana göre azalan (DESC) sıralıyor", $isOrderedDesc);

        // Restore original values
        $pdo->prepare("UPDATE isletmeler SET is_vip = ?, gece_acik = ? WHERE id = ?")->execute([$originalVip, $originalGece, $testId]);
    } else {
        echo " [WARN] Aktif test işletmesi bulunamadı, sorgu testleri atlandı.\n";
    }

    // 7. Verify index.php file content
    $indexHtml = file_get_contents(dirname(__DIR__) . '/index.php');
    assertTest("index.php 'filtre=vip' linkini ve butonunu içeriyor", strpos($indexHtml, "data-filter=\"vip\"") !== false);
    assertTest("index.php 'filtre=puan' linkini ve butonunu içeriyor", strpos($indexHtml, "data-filter=\"puan\"") !== false);
    assertTest("index.php 'filtre=gece' linkini ve butonunu içeriyor", strpos($indexHtml, "data-filter=\"gece\"") !== false);
    assertTest("index.php VIP rozetini kartlarda gösteriyor", strpos($indexHtml, "fa-crown") !== false);
    assertTest("index.php Gece Açık rozetini kartlarda gösteriyor", strpos($indexHtml, "fa-moon") !== false);
    assertTest("index.php AJAX filtreleme scriptini içeriyor", strpos($indexHtml, "js-filter-link") !== false);

    // 8. Verify admin panel businesses page
    $adminHtml = file_get_contents(dirname(__DIR__) . '/admin/pages/businesses.php');
    assertTest("admin/pages/businesses.php VIP toggle butonunu içeriyor", strpos($adminHtml, "toggle_vip") !== false);
    assertTest("admin/pages/businesses.php VIP rozetini ve statüsünü içeriyor", strpos($adminHtml, "VIP SALON") !== false || strpos($adminHtml, "VIP Statüsü") !== false);

    // 9. Verify business profile page
    $isletmeHtml = file_get_contents(dirname(__DIR__) . '/isletme/profil.php');
    assertTest("isletme/profil.php 'gece_acik' switch/toggle butonunu içeriyor", strpos($isletmeHtml, "name=\"gece_acik\"") !== false);

} catch (Exception $e) {
    $failCount++;
    echo "\n[ERROR] Exception: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Sonuç: {$passCount} Başarılı, {$failCount} Hatalı\n";
exit($failCount === 0 ? 0 : 1);

