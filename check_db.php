<?php
require_once __DIR__ . '/data/database.php';

$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
echo "=== TABLES ===\n";
echo json_encode($tables, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

foreach ($tables as $table) {
    echo "=== $table ===\n";
    $cols = $pdo->query("DESCRIBE `$table`")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $col) {
        echo "  {$col['Field']} | {$col['Type']} | {$col['Null']} | {$col['Key']} | {$col['Default']} | {$col['Extra']}\n";
    }
    
    $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
    echo "  Row count: $count\n\n";
}
