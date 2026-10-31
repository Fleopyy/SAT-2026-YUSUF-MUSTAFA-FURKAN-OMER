<?php
function databaseSettings(?array $local = null, ?callable $environment = null): array
{
    if ($local === null) {
        $path = __DIR__ . '/database.local.php';
        $local = is_file($path) ? require $path : [];
        if (!is_array($local)) {
            throw new RuntimeException('Invalid database configuration.');
        }
    }
    $environment = $environment ?? static fn(string $name) => getenv($name);
    $settings = [];
    foreach (['host' => 'DB_HOST', 'username' => 'DB_USER', 'password' => 'DB_PASSWORD', 'dbname' => 'DB_NAME'] as $key => $variable) {
        $value = $environment($variable);
        $settings[$key] = $value !== false ? $value : ($local[$key] ?? null);
        if (!is_string($settings[$key]) || ($key !== 'password' && $settings[$key] === '')) {
            throw new RuntimeException('Incomplete database configuration.');
        }
    }
    foreach (['host', 'dbname'] as $key) {
        if (preg_match('/[;\x00-\x20\x7f]/', $settings[$key])) {
            throw new RuntimeException('Invalid database configuration.');
        }
    }
    return $settings;
}
