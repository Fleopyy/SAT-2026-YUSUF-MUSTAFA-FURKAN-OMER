<?php
// Maintenance and test tools must never run through HTTP.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

require_once dirname(__DIR__, 2) . '/data/database.php';

echo "=== BiTıraş.com KVKK & Onay Sistemi Migration Başlatılıyor ===\n\n";

try {
    $columns = [
        ['kullanicilar', 'kvkk_onay_tarihi', "ALTER TABLE `kullanicilar` ADD COLUMN `kvkk_onay_tarihi` DATETIME NULL DEFAULT NULL AFTER `sifre_hash`"],
        ['kullanicilar', 'pazarlama_onay', "ALTER TABLE `kullanicilar` ADD COLUMN `pazarlama_onay` TINYINT(1) NOT NULL DEFAULT 0 AFTER `kvkk_onay_tarihi`"],
        ['isletmeler', 'kvkk_onay_tarihi', "ALTER TABLE `isletmeler` ADD COLUMN `kvkk_onay_tarihi` DATETIME NULL DEFAULT NULL AFTER `sifre_hash`"],
        ['isletmeler', 'pazarlama_onay', "ALTER TABLE `isletmeler` ADD COLUMN `pazarlama_onay` TINYINT(1) NOT NULL DEFAULT 0 AFTER `kvkk_onay_tarihi`"],
    ];

    foreach ($columns as [$tbl, $col, $sql]) {
        $check = $pdo->query("SHOW COLUMNS FROM `{$tbl}` LIKE '{$col}'")->fetchAll();
        if (empty($check)) {
            $pdo->exec($sql);
            echo "[+] '{$col}' kolonu '{$tbl}' tablosuna eklendi.\n";
        } else {
            echo "[.] '{$col}' kolonu '{$tbl}' tablosunda zaten mevcut.\n";
        }
    }

    echo "\n[BAŞARILI] KVKK migration başarıyla tamamlandı.\n";
} catch (Exception $e) {
    echo "\n[HATA] Migration sırasında hata: " . $e->getMessage() . "\n";
    exit(1);
}

