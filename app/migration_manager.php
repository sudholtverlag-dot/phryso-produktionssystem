<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function ensure_migration_table(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS schema_migrations (
            migration_id VARCHAR(50) PRIMARY KEY,
            executed_at DATETIME NOT NULL
        )'
    );
}

function discover_migrations(string $migrationsPath): array
{
    $files = glob($migrationsPath . '/*.php') ?: [];
    sort($files, SORT_NATURAL);

    $migrations = [];
    foreach ($files as $file) {
        $basename = basename($file, '.php');
        if (!preg_match('/^(\d{3})_/', $basename, $matches)) {
            continue;
        }

        $migrationId = $matches[1];
        $className = 'Migration_' . $migrationId;
        $migrations[] = [
            'id' => $migrationId,
            'file' => $file,
            'class' => $className,
            'name' => $basename,
        ];
    }

    return $migrations;
}

function get_executed_migrations(PDO $pdo): array
{
    ensure_migration_table($pdo);

    $statement = $pdo->query('SELECT migration_id FROM schema_migrations ORDER BY migration_id ASC');
    $rows = $statement->fetchAll(PDO::FETCH_COLUMN);

    return array_map('strval', $rows);
}

function get_pending_migrations(PDO $pdo, string $migrationsPath): array
{
    $discovered = discover_migrations($migrationsPath);
    $executed = array_flip(get_executed_migrations($pdo));

    return array_values(array_filter(
        $discovered,
        static fn(array $migration): bool => !isset($executed[$migration['id']])
    ));
}

function run_migration(PDO $pdo, array $migration): void
{
    require_once $migration['file'];

    if (!class_exists($migration['class'])) {
        throw new RuntimeException(sprintf('Migration class %s not found.', $migration['class']));
    }

    $instance = new $migration['class']();
    if (!method_exists($instance, 'up')) {
        throw new RuntimeException(sprintf('Migration %s has no up() method.', $migration['class']));
    }

    $instance->up($pdo);

    $statement = $pdo->prepare(
        'INSERT INTO schema_migrations (migration_id, executed_at) VALUES (:migration_id, :executed_at)'
    );
    $statement->execute([
        'migration_id' => $migration['id'],
        'executed_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
    ]);
}
