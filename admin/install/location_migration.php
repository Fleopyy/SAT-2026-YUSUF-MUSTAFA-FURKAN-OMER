<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

require_once dirname(__DIR__, 2) . '/data/database.php';

echo "=== BiTıraş.com İşletme Konum ve Harita Migration Başlatılıyor ===\n\n";

try {
    $columnsToAdd = [
        'mahalle'              => "ALTER TABLE `isletmeler` ADD COLUMN `mahalle` VARCHAR(150) NULL DEFAULT NULL AFTER `ilce`",
        'cadde_sokak'          => "ALTER TABLE `isletmeler` ADD COLUMN `cadde_sokak` VARCHAR(150) NULL DEFAULT NULL AFTER `mahalle`",
        'bina_no'              => "ALTER TABLE `isletmeler` ADD COLUMN `bina_no` VARCHAR(30) NULL DEFAULT NULL AFTER `cadde_sokak`",
        'daire_no'             => "ALTER TABLE `isletmeler` ADD COLUMN `daire_no` VARCHAR(30) NULL DEFAULT NULL AFTER `bina_no`",
        'posta_kodu'           => "ALTER TABLE `isletmeler` ADD COLUMN `posta_kodu` VARCHAR(20) NULL DEFAULT NULL AFTER `daire_no`",
        'latitude'             => "ALTER TABLE `isletmeler` ADD COLUMN `latitude` DECIMAL(10, 8) NULL DEFAULT NULL AFTER `tam_adres`",
        'longitude'            => "ALTER TABLE `isletmeler` ADD COLUMN `longitude` DECIMAL(11, 8) NULL DEFAULT NULL AFTER `latitude`",
        'place_id'             => "ALTER TABLE `isletmeler` ADD COLUMN `place_id` VARCHAR(255) NULL DEFAULT NULL AFTER `longitude`",
        'location_verified_at' => "ALTER TABLE `isletmeler` ADD COLUMN `location_verified_at` DATETIME NULL DEFAULT NULL AFTER `place_id`",
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

    $indices = $pdo->query("SHOW INDEX FROM `isletmeler` WHERE Key_name = 'idx_isletme_coordinates'")->fetchAll();
    if (empty($indices)) {
        $pdo->exec("ALTER TABLE `isletmeler` ADD INDEX `idx_isletme_coordinates` (`latitude`, `longitude`)");
        echo "[+] 'idx_isletme_coordinates' indeksi eklendi.\n";
    }

    $stmt = $pdo->prepare("INSERT IGNORE INTO `site_ayarlari` (`anahtar`, `deger`, `tur`, `aciklama`) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        'google_maps_api_key',
        '',
        'text',
        'Google Maps JavaScript & Geocoding API Anahtarı'
    ]);
    echo "[+] 'google_maps_api_key' site ayarı eklendi / kontrol edildi.\n";

    echo "\n[BAŞARILI] İşletme Konum Migration başarıyla tamamlandı.\n";
} catch (Exception $e) {
    echo "[HATA] Migration sırasında hata: " . $e->getMessage() . "\n";
    exit(1);
}
