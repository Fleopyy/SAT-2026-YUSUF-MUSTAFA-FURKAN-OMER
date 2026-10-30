<?php
// BİTİRAS — Gmail SMTP, Doğrulama & Otomatik E-Posta Sistemi Veritabanı Güncellemesi (Migration)
// GÜVENLİK NOTU: Bu dosya yalnızca CLI üzerinden ve yetkili kullanıcı izniyle çalıştırılabilir.
// Kullanıcı onayı alınmadan otomatik çalıştırılmaz.

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Bu komut yalnızca CLI ortamında çalıştırılabilir.');
}

require_once __DIR__ . '/../../data/database.php';

$success = [];
$errors = [];

function runMigrationStep(PDO $pdo, string $sql, string $description, array &$success, array &$errors): void {
    try {
        $pdo->exec($sql);
        $success[] = $description;
    } catch (PDOException $e) {
        $errors[] = "$description — HATA: " . $e->getMessage();
    }
}

function columnExists(PDO $pdo, string $table, string $column): bool {
    $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
    $stmt->execute([$column]);
    return (bool)$stmt->fetch();
}

function tableExists(PDO $pdo, string $table): bool {
    $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
    $stmt->execute([$table]);
    return (bool)$stmt->fetch();
}

echo "=================================================================\n";
echo " BİTİRAS — E-Posta ve Hesap Güvenliği Veritabanı Migration\n";
echo "=================================================================\n\n";

// 1. E-POSTA KUYRUĞU İYİLEŞTİRMELERİ (İdempotency ve Tür Ayrımı)
if (!columnExists($pdo, 'eposta_kuyrugu', 'idempotency_key')) {
    runMigrationStep($pdo,
        "ALTER TABLE `eposta_kuyrugu` ADD COLUMN `idempotency_key` VARCHAR(100) NULL AFTER `durum`, ADD UNIQUE KEY `uk_idempotency` (`idempotency_key`)",
        "eposta_kuyrugu tablosuna 'idempotency_key' (tekilleştirme anahtarı) kolonu eklendi",
        $success, $errors
    );
} else {
    $success[] = "eposta_kuyrugu.idempotency_key zaten mevcut, atlandı.";
}

if (!columnExists($pdo, 'eposta_kuyrugu', 'tur')) {
    runMigrationStep($pdo,
        "ALTER TABLE `eposta_kuyrugu` ADD COLUMN `tur` VARCHAR(50) NULL AFTER `idempotency_key`",
        "eposta_kuyrugu tablosuna 'tur' kolonu eklendi",
        $success, $errors
    );
} else {
    $success[] = "eposta_kuyrugu.tur zaten mevcut, atlandı.";
}

// 2. MÜŞTERİ HESAPLARI GÜVENLİĞİ (E-posta doğrulama, Şifre sıfırlama, E-posta değişikliği)
$userCols = [
    ['eposta_dogrulandi', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER `sifre_hash`'],
    ['eposta_dogrulama_kodu_hash', 'VARCHAR(255) NULL AFTER `eposta_dogrulandi`'],
    ['eposta_dogrulama_son_tarih', 'DATETIME NULL AFTER `eposta_dogrulama_kodu_hash`'],
    ['eposta_dogrulama_deneme', 'INT NOT NULL DEFAULT 0 AFTER `eposta_dogrulama_son_tarih`'],
    ['sifre_sifirlama_token_hash', 'VARCHAR(255) NULL AFTER `eposta_dogrulama_deneme`'],
    ['sifre_sifirlama_son_tarih', 'DATETIME NULL AFTER `sifre_sifirlama_token_hash`'],
    ['sifre_sifirlama_deneme', 'INT NOT NULL DEFAULT 0 AFTER `sifre_sifirlama_son_tarih`'],
    ['yeni_eposta', 'VARCHAR(190) NULL AFTER `sifre_sifirlama_deneme`'],
    ['yeni_eposta_kodu_hash', 'VARCHAR(255) NULL AFTER `yeni_eposta`'],
    ['yeni_eposta_son_tarih', 'DATETIME NULL AFTER `yeni_eposta_kodu_hash`'],
    ['son_giris', 'DATETIME NULL AFTER `yeni_eposta_son_tarih`'],
];

foreach ($userCols as [$col, $def]) {
    if (!columnExists($pdo, 'kullanicilar', $col)) {
        runMigrationStep($pdo,
            "ALTER TABLE `kullanicilar` ADD COLUMN `$col` $def",
            "kullanicilar.$col kolonu eklendi",
            $success, $errors
        );
    } else {
        $success[] = "kullanicilar.$col zaten mevcut, atlandı.";
    }
}

// 3. ADMİN VE YÖNETİCİ HESAPLARI GÜVENLİĞİ (2FA TOTP ve E-posta Doğrulama)
$adminCols = [
    ['iki_adimli_tur', "ENUM('none','totp','email') NOT NULL DEFAULT 'none' AFTER `aktif`"],
    ['totp_gizli_anahtar', 'VARCHAR(255) NULL AFTER `iki_adimli_tur`'],
    ['kurtarma_kodlari_hash', 'TEXT NULL AFTER `totp_gizli_anahtar`'],
    ['eposta_2fa_kod_hash', 'VARCHAR(255) NULL AFTER `kurtarma_kodlari_hash`'],
    ['eposta_2fa_son_tarih', 'DATETIME NULL AFTER `eposta_2fa_kod_hash`'],
    ['eposta_2fa_deneme', 'INT NOT NULL DEFAULT 0 AFTER `eposta_2fa_son_tarih`'],
];

foreach ($adminCols as [$col, $def]) {
    if (!columnExists($pdo, 'yoneticiler', $col)) {
        runMigrationStep($pdo,
            "ALTER TABLE `yoneticiler` ADD COLUMN `$col` $def",
            "yoneticiler.$col kolonu eklendi",
            $success, $errors
        );
    } else {
        $success[] = "yoneticiler.$col zaten mevcut, atlandı.";
    }
}

// 4. İŞLETME HESAPLARI (Son giriş takibi ve risk bazlı doğrulama)
$barberCols = [
    ['son_giris', 'DATETIME NULL AFTER `durum`'],
    ['eposta_2fa_kod_hash', 'VARCHAR(255) NULL AFTER `son_giris`'],
    ['eposta_2fa_son_tarih', 'DATETIME NULL AFTER `eposta_2fa_kod_hash`'],
    ['eposta_2fa_deneme', 'INT NOT NULL DEFAULT 0 AFTER `eposta_2fa_son_tarih`'],
];

foreach ($barberCols as [$col, $def]) {
    if (!columnExists($pdo, 'isletmeler', $col)) {
        runMigrationStep($pdo,
            "ALTER TABLE `isletmeler` ADD COLUMN `$col` $def",
            "isletmeler.$col kolonu eklendi",
            $success, $errors
        );
    } else {
        $success[] = "isletmeler.$col zaten mevcut, atlandı.";
    }
}

// 5. GÜVENİLİR CİHAZLAR TABLOSU (Admin, İşletme ve Müşteri için cihaz tanıma)
if (!tableExists($pdo, 'guvenilir_cihazlar')) {
    $createDevicesSql = "CREATE TABLE `guvenilir_cihazlar` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `kullanici_turu` ENUM('yonetici','isletme','musteri') NOT NULL,
        `kullanici_id` INT(11) NOT NULL,
        `cihaz_belirtec_hash` VARCHAR(64) NOT NULL,
        `cihaz_adi` VARCHAR(255) NULL,
        `ip_adresi` VARCHAR(45) NOT NULL,
        `user_agent` VARCHAR(255) NULL,
        `son_kullanim` DATETIME NOT NULL,
        `son_gecerlilik` DATETIME NOT NULL,
        `olusturulma_tarihi` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_kullanici` (`kullanici_turu`, `kullanici_id`),
        KEY `idx_belirtec` (`cihaz_belirtec_hash`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    runMigrationStep($pdo, $createDevicesSql, "guvenilir_cihazlar tablosu oluşturuldu", $success, $errors);
} else {
    $success[] = "guvenilir_cihazlar tablosu zaten mevcut, atlandı.";
}

// 6. RANDEVU BİLDİRİM VE HATIRLATMA KAYITLARI TABLOSU (Tekilleştirme ve Gönderim Takibi)
if (!tableExists($pdo, 'randevu_bildirim_kayitlari')) {
    $createNotifLogSql = "CREATE TABLE `randevu_bildirim_kayitlari` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `randevu_id` INT(11) NOT NULL,
        `bildirim_turu` ENUM('onay','degisiklik','iptal','hatirlatma_24h','hatirlatma_2h','isletme_yaklasan') NOT NULL,
        `alici_eposta` VARCHAR(190) NOT NULL,
        `gonderim_zamani` DATETIME NOT NULL,
        `olusturulma_tarihi` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uk_randevu_bildirim` (`randevu_id`, `bildirim_turu`),
        KEY `idx_randevu` (`randevu_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    runMigrationStep($pdo, $createNotifLogSql, "randevu_bildirim_kayitlari tablosu oluşturuldu", $success, $errors);
} else {
    $success[] = "randevu_bildirim_kayitlari tablosu zaten mevcut, atlandı.";
}

echo "Migration Tamamlandı!\n";
echo "Başarılı İşlemler (" . count($success) . "):\n";
foreach ($success as $msg) {
    echo "  [OK] $msg\n";
}

if (!empty($errors)) {
    echo "\nHatalar (" . count($errors) . "):\n";
    foreach ($errors as $msg) {
        echo "  [HATA] $msg\n";
    }
}
