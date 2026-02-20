<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/migration_manager.php';

$pdo = get_pdo($config);
start_session_if_needed();
require_admin();

function write_update_log(string $logFile, string $message): void
{
    $timestamp = (new DateTimeImmutable())->format('Y-m-d H:i:s');
    file_put_contents($logFile, sprintf("[%s] %s\n", $timestamp, $message), FILE_APPEND | LOCK_EX);
}

$logFile = $config['update']['log_file'];
$lockFile = $config['update']['lock_file'];

$lockHandle = fopen($lockFile, 'c+');
if ($lockHandle === false) {
    http_response_code(500);
    echo 'Update-Lockdatei konnte nicht geöffnet werden.';
    exit;
}

if (!flock($lockHandle, LOCK_EX | LOCK_NB)) {
    http_response_code(423);
    echo 'Ein Update läuft bereits. Bitte später erneut versuchen.';
    fclose($lockHandle);
    exit;
}

$success = false;
$output = [];

try {
    if (!version_update_required($pdo)) {
        $output[] = 'Kein Update erforderlich: Datenbankversion ist aktuell.';
        write_update_log($logFile, 'Update übersprungen - keine Versionsdifferenz.');
        $success = true;
    } else {
        $pending = get_pending_migrations($pdo, __DIR__ . '/../app/migrations');
        if ($pending === []) {
            set_db_version($pdo, PHRYSO_VERSION);
            $output[] = 'Keine Migration offen. DB-Version wurde auf Systemversion gesetzt.';
            write_update_log($logFile, 'Keine Migrationen offen; db_version angeglichen.');
            $success = true;
        } else {
            write_update_log($logFile, sprintf('Update gestartet. %d Migration(en) ausstehend.', count($pending)));

            foreach ($pending as $migration) {
                $pdo->beginTransaction();
                try {
                    run_migration($pdo, $migration);
                    $pdo->commit();
                    $msg = sprintf('Migration %s erfolgreich ausgeführt.', $migration['name']);
                    $output[] = $msg;
                    write_update_log($logFile, $msg);
                } catch (Throwable $throwable) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }

                    $error = sprintf('Migration %s fehlgeschlagen: %s', $migration['name'], $throwable->getMessage());
                    write_update_log($logFile, $error);
                    throw new RuntimeException($error, 0, $throwable);
                }
            }

            $pdo->beginTransaction();
            try {
                set_db_version($pdo, PHRYSO_VERSION);
                $pdo->commit();
            } catch (Throwable $throwable) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $error = 'db_version konnte nicht aktualisiert werden: ' . $throwable->getMessage();
                write_update_log($logFile, $error);
                throw new RuntimeException($error, 0, $throwable);
            }

            $output[] = sprintf('Update abgeschlossen. Neue DB-Version: %s', PHRYSO_VERSION);
            write_update_log($logFile, sprintf('Update erfolgreich beendet. db_version=%s', PHRYSO_VERSION));
            $success = true;
        }
    }
} catch (Throwable $throwable) {
    $output[] = 'Update fehlgeschlagen: ' . $throwable->getMessage();
    write_update_log($logFile, 'Update FEHLER: ' . $throwable->getMessage());
} finally {
    flock($lockHandle, LOCK_UN);
    fclose($lockHandle);
    if (file_exists($lockFile)) {
        unlink($lockFile);
    }
}
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Update-Ergebnis</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 2rem; background: #f8fafc; }
        .panel { max-width: 780px; background: #fff; border-radius: 8px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .status { padding: .8rem; border-radius: 6px; margin: .8rem 0; }
        .ok { background: #e7f7ed; color: #166534; }
        .err { background: #fde8e8; color: #991b1b; }
        li { margin: .3rem 0; }
    </style>
</head>
<body>
<div class="panel">
    <h1>Update-Status</h1>
    <div class="status <?= $success ? 'ok' : 'err' ?>">
        <?= $success ? 'Update erfolgreich abgeschlossen.' : 'Update mit Fehler beendet.' ?>
    </div>
    <ul>
        <?php foreach ($output as $line): ?>
            <li><?= htmlspecialchars($line, ENT_QUOTES, 'UTF-8') ?></li>
        <?php endforeach; ?>
    </ul>

    <p><a href="/update/index.php">Zurück zum Dashboard</a></p>
    <p><a href="/public/index.php">Zurück zum Login</a></p>
</div>
</body>
</html>
