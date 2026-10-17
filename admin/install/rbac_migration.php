<?php
   
                                                       
                                                                                        
   

require_once dirname(__DIR__, 2) . '/data/database.php';

echo "=== BiTıraş.com RBAC Migration Başlatılıyor ===\n\n";

try {
                                                                                         
    $cols = $pdo->query("SHOW COLUMNS FROM yoneticiler LIKE 'kapsam_turu'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE yoneticiler ADD COLUMN kapsam_turu ENUM('tum_isletmeler', 'belirli_isletmeler') NOT NULL DEFAULT 'tum_isletmeler' AFTER rol");
        echo "[+] 'kapsam_turu' kolonu 'yoneticiler' tablosuna eklendi.\n";
    }

    $cols = $pdo->query("SHOW COLUMNS FROM yoneticiler LIKE 'yetki_versiyonu'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE yoneticiler ADD COLUMN yetki_versiyonu INT NOT NULL DEFAULT 1 AFTER kapsam_turu");
        echo "[+] 'yetki_versiyonu' kolonu 'yoneticiler' tablosuna eklendi.\n";
    }

                                            
    $pdo->exec("CREATE TABLE IF NOT EXISTS yonetici_isletme_kapsami (
        id INT AUTO_INCREMENT PRIMARY KEY,
        yonetici_id INT NOT NULL,
        isletme_id INT NOT NULL,
        olusturulma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_yonetici_isletme (yonetici_id, isletme_id),
        INDEX idx_scope_yonetici (yonetici_id),
        INDEX idx_scope_isletme (isletme_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "[+] 'yonetici_isletme_kapsami' tablosu hazır.\n";

    echo "\n[BAŞARILI] RBAC Migration başarıyla tamamlandı.\n";
} catch (Exception $e) {
    echo "[HATA] Migration sırasında hata: " . $e->getMessage() . "\n";
    exit(1);
}
