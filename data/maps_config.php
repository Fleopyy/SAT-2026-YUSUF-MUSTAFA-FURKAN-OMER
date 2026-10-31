<?php

function mapsSettings(?array $local = null, ?callable $environment = null): array
{
    if ($local === null) {
        $path = __DIR__ . '/maps.local.php';
        $local = is_file($path) ? require $path : [];
        if (!is_array($local)) {
            $local = [];
        }
    }

    $environment = $environment ?? static fn(string $name) => getenv($name);

    $apiKey = $environment('GOOGLE_MAPS_API_KEY');
    if ($apiKey === false || $apiKey === null || $apiKey === '') {
        $apiKey = $local['google_maps_api_key'] ?? '';
    }

    if ($apiKey === '' && isset($GLOBALS['pdo'])) {
        try {
            $stmt = $GLOBALS['pdo']->prepare("SELECT deger FROM site_ayarlari WHERE anahtar = 'google_maps_api_key' LIMIT 1");
            $stmt->execute();
            $dbKey = $stmt->fetchColumn();
            if ($dbKey && is_string($dbKey)) {
                $apiKey = trim($dbKey);
            }
        } catch (Throwable) {
        }
    }

    $apiKey = is_string($apiKey) ? trim($apiKey) : '';

    return [
        'google_maps_api_key' => $apiKey,
        'has_api_key'         => $apiKey !== '',
        'default_lat'         => (float)($local['default_lat'] ?? 41.0082),
        'default_lng'         => (float)($local['default_lng'] ?? 28.9784),
        'default_zoom'        => (int)($local['default_zoom'] ?? 13),
    ];
}

function isValidCoordinate(?float $lat, ?float $lng): bool
{
    if ($lat === null || $lng === null) {
        return false;
    }
    if (is_nan($lat) || is_nan($lng) || is_infinite($lat) || is_infinite($lng)) {
        return false;
    }
    return ($lat >= -90.0 && $lat <= 90.0) && ($lng >= -180.0 && $lng <= 180.0);
}
