<?php
// Deployment configuration only: never trust Host or forwarded request headers.
function getSiteBaseUrl(): string {
    $environment = getenv('APP_ENV');
    $environment = ($environment === false || $environment === '') ? 'development' : $environment;
    if (!in_array($environment, ['development', 'production'], true)) {
        throw new RuntimeException('APP_ENV must be development or production.');
    }
    $configured = getenv('APP_BASE_URL');
    if ($configured === false || $configured === '') {
        if ($environment === 'production') {
            throw new RuntimeException('APP_BASE_URL is required in production.');
        }
        // Local XAMPP requests keep working, even with a poisoned Host header.
        $loopback = ['127.0.0.1', '::1'];
        if ((in_array($_SERVER['SERVER_ADDR'] ?? '', $loopback, true)
            && in_array($_SERVER['REMOTE_ADDR'] ?? '', $loopback, true))
            || (PHP_SAPI === 'cli' && !isset($_SERVER['REMOTE_ADDR'], $_SERVER['SERVER_ADDR']))) {
            $port = (string)($_SERVER['SERVER_PORT'] ?? '80');
            if (!ctype_digit($port) || (int)$port < 1 || (int)$port > 65535) {
                throw new RuntimeException('Invalid local application port.');
            }
            // Derive the mount path from server filesystem configuration, not request headers/URI.
            $path = '';
            $documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
            $applicationRoot = str_replace('\\', '/', dirname(__DIR__));
            if ($documentRoot !== false && !empty($_SERVER['DOCUMENT_ROOT'])) {
                $documentRoot = rtrim(str_replace('\\', '/', $documentRoot), '/');
                if (strncasecmp($applicationRoot, $documentRoot . '/', strlen($documentRoot) + 1) === 0) {
                    $relative = substr($applicationRoot, strlen($documentRoot) + 1);
                    $path = '/' . implode('/', array_map('rawurlencode', explode('/', $relative)));
                } elseif (strcasecmp($applicationRoot, $documentRoot) !== 0) {
                    throw new RuntimeException('Set APP_BASE_URL explicitly for an aliased application directory.');
                }
            }
            return 'http://localhost' . ((int)$port === 80 ? '' : ':' . (int)$port) . $path;
        }
        throw new RuntimeException('APP_BASE_URL is required outside local development.');
    }
    $url = rtrim($configured, '/');
    $parts = parse_url($url);
    $local = in_array(strtolower($parts['host'] ?? ''), ['localhost', '127.0.0.1', '[::1]'], true);
    if (!filter_var($url, FILTER_VALIDATE_URL) || !$parts
        || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])
        || preg_match('/[\x00-\x20\x7f\\\\]/', $url)
        || (($parts['scheme'] ?? '') !== 'https' && !(($parts['scheme'] ?? '') === 'http' && $local && $environment === 'development'))
        || ($environment === 'production' && ($local || !str_contains($parts['host'] ?? '', '.')
            || filter_var(trim($parts['host'] ?? '', '[]'), FILTER_VALIDATE_IP) !== false))) {
        throw new RuntimeException('APP_BASE_URL must be a trusted HTTPS application URL (HTTP allowed only for localhost).');
    }
    return $url;
}
