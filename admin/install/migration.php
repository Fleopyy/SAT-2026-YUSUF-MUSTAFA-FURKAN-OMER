<?php
   
                                          
                                                                                   
                                                            
   
                               
   

require_once __DIR__ . '/../../data/database.php';

$errors = [];
$success = [];

function runMigration($pdo, $sql, $description, &$success, &$errors) {
    try {
        $pdo->exec($sql);
        $success[] = $description;
    } catch (PDOException $e) {
        $errors[] = "$description — HATA: " . $e->getMessage();
    }
}

echo "BiTıraş.com — Veritabanı Migration Başlıyor...\n";
echo str_repeat("=", 60) . "\n\n";

                                                 
runMigration($pdo,
    "ALTER TABLE `isletmeler` MODIFY COLUMN `durum` ENUM('pending','active','suspended','rejected') NOT NULL DEFAULT 'pending'",
    "isletmeler.durum alanına 'rejected' durumu eklendi",
    $success, $errors
);

                                                  
$alterColumns = [
    ["isletmeler", "onaylayan_admin_id", "INT(11) NULL DEFAULT NULL AFTER `durum`"],
    ["isletmeler", "onay_tarihi", "DATETIME NULL DEFAULT NULL AFTER `onaylayan_admin_id`"],
    ["isletmeler", "red_tarihi", "DATETIME NULL DEFAULT NULL AFTER `onay_tarihi`"],
    ["isletmeler", "red_gerekce", "TEXT NULL DEFAULT NULL AFTER `red_tarihi`"],
    ["isletmeler", "guncelleme_tarihi", "TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER `olusturulma_tarihi`"],
];

foreach ($alterColumns as $col) {
    $check = $pdo->query("SHOW COLUMNS FROM `{$col[0]}` LIKE '{$col[1]}'")->fetch();
    if (!$check) {
        runMigration($pdo,
            "ALTER TABLE `{$col[0]}` ADD COLUMN `{$col[1]}` {$col[2]}",
            "{$col[0]}.{$col[1]} kolonu eklendi",
            $success, $errors
        );
    } else {
        $success[] = "{$col[0]}.{$col[1]} zaten mevcut, atlandı";
    }
}

                                                          
runMigration($pdo, "
CREATE TABLE IF NOT EXISTS `yoneticiler` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `ad_soyad` VARCHAR(150) NOT NULL,
    `eposta` VARCHAR(190) NOT NULL,
    `sifre_hash` VARCHAR(255) NOT NULL,
    `rol` ENUM('admin','manager') NOT NULL DEFAULT 'manager',
    `aktif` TINYINT(1) NOT NULL DEFAULT 1,
    `son_giris` DATETIME NULL DEFAULT NULL,
    `olusturan_admin_id` INT(11) NULL DEFAULT NULL,
    `davet_token` VARCHAR(255) NULL DEFAULT NULL,
    `davet_token_bitis` DATETIME NULL DEFAULT NULL,
    `olusturulma_tarihi` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `guncelleme_tarihi` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_yoneticiler_eposta` (`eposta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    "yoneticiler tablosu oluşturuldu",
    $success, $errors
);

                                
runMigration($pdo, "
CREATE TABLE IF NOT EXISTS `yonetici_izinleri` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `yonetici_id` INT(11) NOT NULL,
    `izin_anahtari` VARCHAR(100) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_yonetici_izin` (`yonetici_id`, `izin_anahtari`),
    KEY `fk_yizin_yonetici` (`yonetici_id`),
    CONSTRAINT `fk_yizin_yonetici` FOREIGN KEY (`yonetici_id`) REFERENCES `yoneticiler` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    "yonetici_izinleri tablosu oluşturuldu",
    $success, $errors
);

                                      
runMigration($pdo, "
CREATE TABLE IF NOT EXISTS `islem_gunlukleri` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `yonetici_id` INT(11) NULL DEFAULT NULL,
    `islem_turu` VARCHAR(100) NOT NULL,
    `hedef_turu` VARCHAR(50) NULL DEFAULT NULL,
    `hedef_id` INT(11) NULL DEFAULT NULL,
    `detay` TEXT NULL DEFAULT NULL,
    `ip_adresi` VARCHAR(45) NULL DEFAULT NULL,
    `olusturulma_tarihi` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_islem_yonetici` (`yonetici_id`),
    KEY `idx_islem_tarih` (`olusturulma_tarihi`),
    KEY `idx_islem_turu` (`islem_turu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    "islem_gunlukleri tablosu oluşturuldu",
    $success, $errors
);

                         
runMigration($pdo, "
CREATE TABLE IF NOT EXISTS `bildirimler` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `hedef_yonetici_id` INT(11) NULL DEFAULT NULL,
    `baslik` VARCHAR(255) NOT NULL,
    `mesaj` TEXT NOT NULL,
    `tur` VARCHAR(50) NOT NULL DEFAULT 'info',
    `link` VARCHAR(255) NULL DEFAULT NULL,
    `okundu` TINYINT(1) NOT NULL DEFAULT 0,
    `olusturulma_tarihi` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_bildirim_hedef` (`hedef_yonetici_id`),
    KEY `idx_bildirim_okundu` (`okundu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    "bildirimler tablosu oluşturuldu",
    $success, $errors
);

                            
runMigration($pdo, "
CREATE TABLE IF NOT EXISTS `site_ayarlari` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `anahtar` VARCHAR(100) NOT NULL,
    `deger` LONGTEXT NULL DEFAULT NULL,
    `tur` VARCHAR(30) NOT NULL DEFAULT 'text',
    `aciklama` VARCHAR(255) NULL DEFAULT NULL,
    `guncelleme_tarihi` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_site_anahtar` (`anahtar`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    "site_ayarlari tablosu oluşturuldu",
    $success, $errors
);

                              
runMigration($pdo, "
CREATE TABLE IF NOT EXISTS `eposta_kuyrugu` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `alici_eposta` VARCHAR(190) NOT NULL,
    `alici_ad` VARCHAR(150) NULL DEFAULT NULL,
    `konu` VARCHAR(255) NOT NULL,
    `icerik` LONGTEXT NOT NULL,
    `durum` ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
    `deneme_sayisi` INT(11) NOT NULL DEFAULT 0,
    `hata_mesaji` TEXT NULL DEFAULT NULL,
    `gonderim_tarihi` DATETIME NULL DEFAULT NULL,
    `olusturulma_tarihi` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_eposta_durum` (`durum`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    "eposta_kuyrugu tablosu oluşturuldu",
    $success, $errors
);

                                      
runMigration($pdo, "
CREATE TABLE IF NOT EXISTS `giris_denemeleri` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `ip_adresi` VARCHAR(45) NOT NULL,
    `eposta` VARCHAR(190) NULL DEFAULT NULL,
    `basarili` TINYINT(1) NOT NULL DEFAULT 0,
    `olusturulma_tarihi` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_giris_ip` (`ip_adresi`),
    KEY `idx_giris_tarih` (`olusturulma_tarihi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    "giris_denemeleri tablosu oluşturuldu",
    $success, $errors
);

                                                        
$defaultSettings = [
    ['site_baslik', 'BiTıraş | Premium Berber & Erkek Kuaförü Randevu Sistemi', 'text', 'Site başlığı'],
    ['site_aciklama', "Türkiye'nin en seçkin berber ve erkek kuaförlerinden anında online randevu alın.", 'text', 'Meta açıklaması'],
    ['hero_baslik', 'Tarzını Keşfet, Sıra Beklemeden Randevunu Al', 'text', 'Hero bölümü ana başlık'],
    ['hero_aciklama', 'Şehrinizdeki en iyi erkek kuaförlerini ve VIP berber salonlarını karşılaştırın, dakikalar içinde boş koltuğunuzu ayırtın.', 'textarea', 'Hero bölümü açıklama'],
    ['duyuru_bandi', 'Sitemiz Yapım Aşamasındadır Yakında Görüşmek Üzere..', 'text', 'Üst duyuru bandı metni'],
    ['duyuru_aktif', '1', 'boolean', 'Duyuru bandı görünür mü?'],
    ['iletisim_eposta', 'destek@BiTıraş.com', 'text', 'İletişim e-posta adresi'],
    ['iletisim_telefon', '0505 508 5158', 'text', 'İletişim telefon numarası'],
    ['footer_metin', 'Sitemiz Yapım Aşamasındadır Yakında Görüşmek Üzere..', 'textarea', 'Footer açıklama metni'],
    ['footer_telif', '© 2026 BiTıraş.com - Tüm Hakları Saklıdır. (Sistem Analizi Proje Ödevi)', 'text', 'Footer telif metni'],
    ['smtp_host', '', 'text', 'SMTP sunucu adresi'],
    ['smtp_port', '587', 'text', 'SMTP port'],
    ['smtp_kullanici', '', 'text', 'SMTP kullanıcı adı'],
    ['smtp_sifre', '', 'password', 'SMTP şifre'],
    ['smtp_gonderen_eposta', '', 'text', 'Gönderen e-posta adresi'],
    ['smtp_gonderen_ad', 'BiTıraş.com', 'text', 'Gönderen adı'],
    ['smtp_sifreleme', 'tls', 'text', 'SMTP şifreleme (tls/ssl)'],
];

$insertSetting = $pdo->prepare("INSERT IGNORE INTO `site_ayarlari` (`anahtar`, `deger`, `tur`, `aciklama`) VALUES (?, ?, ?, ?)");
foreach ($defaultSettings as $s) {
    $insertSetting->execute($s);
}
$success[] = "Varsayılan site ayarları eklendi/güncellendi";

                      
echo "\n✅ BAŞARILI İŞLEMLER (" . count($success) . "):\n";
foreach ($success as $s) {
    echo "  ✓ $s\n";
}

if (!empty($errors)) {
    echo "\n❌ HATALAR (" . count($errors) . "):\n";
    foreach ($errors as $e) {
        echo "  ✗ $e\n";
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "Migration tamamlandı.\n";
