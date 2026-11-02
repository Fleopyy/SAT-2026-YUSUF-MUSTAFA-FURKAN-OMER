<?php
// Maintenance and test tools must never run through HTTP.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

require_once dirname(__DIR__, 2) . '/data/database.php';

echo "=== BiTıraş.com VIP & Gece Açık Migration Başlatılıyor ===\n\n";

try {
    $columnsToAdd = [
        'is_vip'     => "ALTER TABLE `isletmeler` ADD COLUMN `is_vip` TINYINT(1) NOT NULL DEFAULT 0 AFTER `durum`",
        'gece_acik'   => "ALTER TABLE `isletmeler` ADD COLUMN `gece_acik` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_vip`",
    ];

    foreach ($columnsToAdd as $colName => $alterSql) {
        $check = $pdo->query("SHOW COLUMNS FROM `isletmeler` LIKE '{$colName}'")->fetchAll();
        if (empty($check)) {
            $pdo->exec($alterSql);
            echo "[+] '{$colName}' kolonu 'isletmeler' tablosuna eklendi.\n";
        } else {
            echo "[.] '{$colName}' kolonu zaten mevcut.\n";
        }
    }

    // Add indexes for filtering performance
    $indices = [
        'idx_isletme_vip' => "ALTER TABLE `isletmeler` ADD INDEX `idx_isletme_vip` (`is_vip`)",
        'idx_isletme_gece' => "ALTER TABLE `isletmeler` ADD INDEX `idx_isletme_gece` (`gece_acik`)",
    ];

    foreach ($indices as $idxName => $indexSql) {
        $check = $pdo->query("SHOW INDEX FROM `isletmeler` WHERE Key_name = '{$idxName}'")->fetchAll();
        if (empty($check)) {
            $pdo->exec($indexSql);
            echo "[+] '{$idxName}' indeksi eklendi.\n";
        } else {
            echo "[.] '{$idxName}' indeksi zaten mevcut.\n";
        }
    }

    echo "\n[BAŞARILI] VIP ve Gece Açık Migration başarıyla tamamlandı.\n";
} catch (Exception $e) {
    echo "\n[HATA] Migration sırasında hata: " . $e->getMessage() . "\n";
    exit(1);
}

