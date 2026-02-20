<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/migration_manager.php';

$pdo = get_pdo($config);
start_session_if_needed();
require_admin();

$pendingMigrations = get_pending_migrations($pdo, __DIR__ . '/../app/migrations');
$dbVersion = get_db_version($pdo);
$updateRequired = version_update_required($pdo);
$lockExists = file_exists($config['update']['lock_file']);
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>PHRYSO Update-Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 2rem; background: #f8fafc; }
        .panel { max-width: 780px; background: #fff; border-radius: 8px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .status { padding: .8rem; border-radius: 6px; margin: .5rem 0; }
        .ok { background: #e7f7ed; color: #166534; }
        .warn { background: #fff4db; color: #8a5a00; }
        .err { background: #fde8e8; color: #991b1b; }
        button { padding: .65rem 1.1rem; border-radius: 5px; border: 0; background: #0062cc; color: white; cursor: pointer; }
        ul { margin-top: .4rem; }
    </style>
</head>
<body>
<div class="panel">
    <h1>System-Update</h1>

    <div class="status <?= $updateRequired ? 'warn' : 'ok' ?>">
        App-Version: <strong><?= htmlspecialchars(PHRYSO_VERSION, ENT_QUOTES, 'UTF-8') ?></strong><br>
        DB-Version: <strong><?= htmlspecialchars($dbVersion, ENT_QUOTES, 'UTF-8') ?></strong>
    </div>

    <?php if ($lockExists): ?>
        <div class="status err">Ein Update läuft bereits (Lock aktiv). Bitte warten.</div>
    <?php endif; ?>

    <h2>Ausstehende Migrationen</h2>
    <?php if ($pendingMigrations === []): ?>
        <p>Keine offenen Migrationen.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($pendingMigrations as $migration): ?>
                <li><?= htmlspecialchars($migration['name'], ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php if ($updateRequired && !$lockExists): ?>
        <form method="post" action="/update/run.php">
            <button type="submit" name="run_update" value="1">Update starten</button>
        </form>
    <?php else: ?>
        <p>Kein Update erforderlich oder aktuell gesperrt.</p>
    <?php endif; ?>

    <p><a href="/public/index.php">Zurück zum Login</a></p>
</div>
</body>
</html>
