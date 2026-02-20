<?php

declare(strict_types=1);

const PHRYSO_VERSION = '1.0.0';

return [
    'db' => [
        'dsn' => getenv('PHRYSO_DB_DSN') ?: 'sqlite:' . __DIR__ . '/config/phryso.sqlite',
        'username' => getenv('PHRYSO_DB_USER') ?: null,
        'password' => getenv('PHRYSO_DB_PASS') ?: null,
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ],
    ],
    'update' => [
        'log_file' => __DIR__ . '/config/update.log',
        'lock_file' => __DIR__ . '/config/update.lock',
    ],
];
