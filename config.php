<?php
declare(strict_types=1);

// A shared host can supply the same settings in this private, ignored file.
$privatePath = __DIR__ . '/data/config.private.php';
$overrides = is_file($privatePath) ? require $privatePath : [];
$setting = static function (string $name, string $default = '') use ($overrides): string {
    $value = getenv('REUNITE_' . $name);
    return $value === false ? (string) ($overrides[$name] ?? $default) : $value;
};
$local = $setting('ENV') === 'local';
return [
    'local' => $local,
    'host' => $setting('DB_HOST', $local ? '127.0.0.1' : ''),
    'port' => (int) $setting('DB_PORT', '3306'),
    'database' => $setting('DB_NAME', $local ? 'reunite_local' : ''),
    'username' => $setting('DB_USER', $local ? 'root' : ''),
    'password' => $setting('DB_PASSWORD'),
    'admin_email' => $setting('ADMIN_EMAIL', $local ? 'admin@reunite.local' : ''),
    'admin_password' => $setting('ADMIN_PASSWORD'),
    'admin_credentials_file' => __DIR__ . '/data/local-admin.txt',
];
