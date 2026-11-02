<?php

// Keep uncaught SQL exceptions out of HTTP responses.
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');

if (!headers_sent() && PHP_SAPI !== 'cli') {
    header('Content-Type: text/html; charset=utf-8');
}

require_once __DIR__ . '/database_config.php';

try {
    $settings = databaseSettings();
    $host = $settings['host'];
    $username = $settings['username'];
    $password = $settings['password'];
    $dbname = $settings['dbname'];
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES utf8mb4");
    date_default_timezone_set('Europe/Istanbul');
    $pdo->exec("SET time_zone = '+03:00'");
    $db = $pdo;
    unset($settings);
} catch (Throwable $e) {
    error_log('Database initialization failed. Check protected server configuration.');
    http_response_code(500);
    exit('Veritabani baglantisi kurulamadi. Lutfen daha sonra tekrar deneyin.');
}
