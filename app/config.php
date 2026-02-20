<?php

declare(strict_types=1);

$defaults = [
    'db' => [
        'host' => '127.0.0.1',
        'port' => '3306',
        'name' => 'produktionssystem',
        'user' => 'root',
        'password' => '',
    ],
    'admin_notification_email' => 'admin@example.com',
    'max_upload_bytes' => 10 * 1024 * 1024,
    'allowed_upload_mime_types' => [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
    ],
];

$localConfigPath = dirname(__DIR__) . '/config/local.php';
$localConfig = [];

if (is_file($localConfigPath)) {
    $loaded = require $localConfigPath;
    if (is_array($loaded)) {
        $localConfig = $loaded;
    }
}

$dbHost = (string) ($localConfig['db']['host'] ?? $defaults['db']['host']);
$dbPort = (string) ($localConfig['db']['port'] ?? $defaults['db']['port']);
$dbName = (string) ($localConfig['db']['name'] ?? $defaults['db']['name']);
$dbUser = (string) ($localConfig['db']['user'] ?? $defaults['db']['user']);
$dbPass = (string) ($localConfig['db']['password'] ?? $defaults['db']['password']);

define('DB_DSN', sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $dbHost, $dbPort, $dbName));
define('DB_USER', $dbUser);
define('DB_PASS', $dbPass);
define(
    'ADMIN_NOTIFICATION_EMAIL',
    (string) ($localConfig['admin_notification_email'] ?? $defaults['admin_notification_email'])
);
define('MAX_UPLOAD_BYTES', (int) ($localConfig['max_upload_bytes'] ?? $defaults['max_upload_bytes']));
define(
    'ALLOWED_UPLOAD_MIME_TYPES',
    is_array($localConfig['allowed_upload_mime_types'] ?? null)
        ? array_values($localConfig['allowed_upload_mime_types'])
        : $defaults['allowed_upload_mime_types']
);
