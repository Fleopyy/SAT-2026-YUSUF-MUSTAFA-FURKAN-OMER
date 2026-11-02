<?php
require_once __DIR__ . '/../data/database.php';
// Clean test-only login attempt rows created during test suites
$stmt = $pdo->prepare("DELETE FROM giris_denemeleri WHERE eposta LIKE '%bitiras.test%' OR eposta LIKE '%@example.%' OR eposta LIKE '%.test%' OR eposta LIKE '%.invalid'");
$stmt->execute();
$deleted = $stmt->rowCount();
echo "Deleted test attempt rows: $deleted\n";
echo "Remaining attempts: " . $pdo->query("SELECT COUNT(*) FROM giris_denemeleri")->fetchColumn() . "\n";
