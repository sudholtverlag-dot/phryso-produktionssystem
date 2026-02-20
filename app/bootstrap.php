<?php

declare(strict_types=1);

$config = require __DIR__ . '/../config.php';

function get_pdo(array $config): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dbConfig = $config['db'];
    $pdo = new PDO(
        $dbConfig['dsn'],
        $dbConfig['username'],
        $dbConfig['password'],
        $dbConfig['options']
    );

    return $pdo;
}

function ensure_system_meta_table(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS system_meta (
            meta_key VARCHAR(190) PRIMARY KEY,
            meta_value TEXT NOT NULL
        )'
    );
}

function get_db_version(PDO $pdo): string
{
    ensure_system_meta_table($pdo);

    $statement = $pdo->prepare('SELECT meta_value FROM system_meta WHERE meta_key = :meta_key');
    $statement->execute(['meta_key' => 'db_version']);
    $value = $statement->fetchColumn();

    if ($value === false) {
        set_db_version($pdo, '0.0.0');
        return '0.0.0';
    }

    return (string) $value;
}

function set_db_version(PDO $pdo, string $version): void
{
    ensure_system_meta_table($pdo);

    $statement = $pdo->prepare(
        'INSERT INTO system_meta (meta_key, meta_value) VALUES (:meta_key, :meta_value)
         ON CONFLICT(meta_key) DO UPDATE SET meta_value = excluded.meta_value'
    );

    $statement->execute([
        'meta_key' => 'db_version',
        'meta_value' => $version,
    ]);
}

function version_update_required(PDO $pdo): bool
{
    return version_compare(PHRYSO_VERSION, get_db_version($pdo), '>');
}
