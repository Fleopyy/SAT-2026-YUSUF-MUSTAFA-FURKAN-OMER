<?php
// Pure configuration loader; never opens a network connection or outputs secrets.
function mailSettings(?array $local = null, ?callable $environment = null): array
{
    if ($local === null) {
        $path = __DIR__ . '/mail.local.php';
        $local = is_file($path) ? require $path : [];
        if (!is_array($local)) {
            throw new RuntimeException('Invalid mail configuration.');
        }
    }

    $environment = $environment ?? static fn(string $name) => getenv($name);

    $map = [
        'host'       => ['SMTP_HOST', 'smtp.gmail.com'],
        'port'       => ['SMTP_PORT', 587],
        'secure'     => ['SMTP_SECURE', 'tls'],
        'username'   => ['SMTP_USER', 'bitirasofficial@gmail.com'],
        'password'   => ['SMTP_PASSWORD', ''],
        'from_email' => ['SMTP_FROM_EMAIL', 'bitirasofficial@gmail.com'],
        'from_name'  => ['SMTP_FROM_NAME', 'BİTİRAS'],
    ];

    $settings = [
        'auth'             => true,
        'verify_peer'      => true,
        'verify_peer_name' => true,
    ];

    foreach ($map as $key => [$envVar, $default]) {
        $val = $environment($envVar);
        if ($val === false && $key === 'username') {
            $val = $environment('SMTP_USERNAME');
        }
        if ($val === false && $key === 'password') {
            $val = $environment('SMTP_PASS');
        }

        if ($val !== false && $val !== null) {
            $settings[$key] = $val;
        } elseif (array_key_exists($key, $local)) {
            $settings[$key] = $local[$key];
        } else {
            $settings[$key] = $default;
        }
    }

    // Type coercion & validation
    $settings['port'] = (int)$settings['port'];
    if ($settings['port'] < 1 || $settings['port'] > 65535) {
        throw new RuntimeException('Invalid SMTP port.');
    }

    $settings['secure'] = strtolower((string)$settings['secure']);
    if (!in_array($settings['secure'], ['tls', 'ssl', 'starttls'], true)) {
        throw new RuntimeException('Invalid SMTP encryption.');
    }
    if ($settings['secure'] === 'starttls') {
        $settings['secure'] = 'tls';
    }

    foreach (['host', 'username', 'from_email', 'from_name'] as $field) {
        $str = (string)($settings[$field] ?? '');
        if (preg_match('/[\r\n\x00]/', $str)) {
            throw new RuntimeException('Header injection detected in mail configuration.');
        }
    }

    if (!filter_var($settings['from_email'], FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Invalid from_email in mail configuration.');
    }

    return $settings;
}

require_once __DIR__ . '/app_url.php';
