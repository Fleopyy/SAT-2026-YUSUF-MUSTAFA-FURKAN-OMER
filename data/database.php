<?php

ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
require_once __DIR__ . '/database_config.php';

try {
    $settings = databaseSettings();
    $host = $settings['host'];
    $username = $settings['username'];
    $password = $settings['password'];
    $dbname = $settings['dbname'];
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    date_default_timezone_set('Europe/Istanbul');
    $pdo->exec("SET time_zone = '+03:00'");
    $db = $pdo;
    unset($settings);
} catch (Throwable $e) {
    error_log('Database initialization failed. Check protected server configuration.');
    http_response_code(500);
    exit('Veritabani baglantisi kurulamadi. Lutfen daha sonra tekrar deneyin.');
}
