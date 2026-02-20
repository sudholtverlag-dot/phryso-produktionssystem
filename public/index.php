<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/migration_manager.php';

$pdo = get_pdo($config);
ensure_system_meta_table($pdo);
ensure_migration_table($pdo);

start_session_if_needed();
$message = null;
$error = null;

if (isset($_POST['logout'])) {
    logout_user();
    header('Location: /public/index.php');
    exit;
}

if (isset($_POST['login'])) {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    try {
        $isLoggedIn = login_user($pdo, $username, $password);
        if ($isLoggedIn) {
            $message = 'Login erfolgreich.';
        } else {
            $error = 'Ungültige Anmeldedaten.';
        }
    } catch (Throwable $throwable) {
        $error = 'Login nicht möglich. Datenbank eventuell noch nicht migriert: ' . $throwable->getMessage();
    }
}

$user = current_user();
$dbVersion = get_db_version($pdo);
$updateRequired = version_update_required($pdo);
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>PHRYSO Login</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 2rem; background: #f6f8fb; }
        .card { background: white; padding: 1.5rem; border-radius: 8px; max-width: 560px; box-shadow: 0 2px 6px rgba(0,0,0,.08); }
        .alert { padding: .8rem; border-radius: 6px; margin: .8rem 0; }
        .success { background: #e7f7ed; color: #1f6b38; }
        .error { background: #fde8e8; color: #9b1c1c; }
        .warning { background: #fff4db; color: #8a5a00; }
        input { padding: .5rem; margin: .3rem 0; width: 100%; }
        button { padding: .6rem 1rem; border: 0; background: #0062cc; color: white; border-radius: 5px; cursor: pointer; }
        .meta { margin-top: 1rem; font-size: .92rem; color: #334155; }
    </style>
</head>
<body>
<div class="card">
    <h1>PHRYSO-System</h1>

    <?php if ($message !== null): ?>
        <div class="alert success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if ($error !== null): ?>
        <div class="alert error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if ($user === null): ?>
        <form method="post">
            <label for="username">Benutzername</label>
            <input id="username" type="text" name="username" required>

            <label for="password">Passwort</label>
            <input id="password" type="password" name="password" required>

            <button type="submit" name="login" value="1">Einloggen</button>
        </form>
    <?php else: ?>
        <p>Angemeldet als <strong><?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?></strong>.</p>

        <?php if ($user['is_admin'] && $updateRequired): ?>
            <div class="alert warning">
                Neue Systemversion erkannt: <strong><?= htmlspecialchars(PHRYSO_VERSION, ENT_QUOTES, 'UTF-8') ?></strong>.
                Aktuelle Datenbankversion: <strong><?= htmlspecialchars($dbVersion, ENT_QUOTES, 'UTF-8') ?></strong>.
                <div style="margin-top: .6rem;">
                    <a href="/update/index.php"><button type="button">System aktualisieren</button></a>
                </div>
            </div>
        <?php endif; ?>

        <form method="post">
            <button type="submit" name="logout" value="1">Logout</button>
        </form>
    <?php endif; ?>

    <div class="meta">
        Systemversion: <?= htmlspecialchars(PHRYSO_VERSION, ENT_QUOTES, 'UTF-8') ?> |
        DB-Version: <?= htmlspecialchars($dbVersion, ENT_QUOTES, 'UTF-8') ?>
    </div>
</div>
</body>
</html>
