<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/Installer.php';

$rootPath = dirname(__DIR__);
$installer = new Installer($rootPath);

if ($installer->isInstalled()) {
    header('Location: /public/index.php', true, 302);
    exit;
}

$checks = [
    'PHP >= 8.2' => version_compare(PHP_VERSION, '8.2.0', '>='),
    'PDO MySQL verfügbar' => extension_loaded('pdo_mysql'),
    '/config beschreibbar' => is_dir($rootPath . '/config') && is_writable($rootPath . '/config'),
    '/uploads beschreibbar' => is_dir($rootPath . '/uploads') && is_writable($rootPath . '/uploads'),
];

$allChecksPassed = !in_array(false, $checks, true);

if (!isset($_SESSION['install_csrf'])) {
    $_SESSION['install_csrf'] = bin2hex(random_bytes(32));
}

$errors = [];
$old = [
    'db_host' => 'localhost',
    'db_name' => '',
    'db_user' => '',
    'db_pass' => '',
    'smtp_host' => '',
    'smtp_user' => '',
    'smtp_pass' => '',
    'admin_notification_email' => '',
    'admin_username' => '',
    'admin_password' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = (string) ($_POST['csrf_token'] ?? '');
    if (!hash_equals((string) $_SESSION['install_csrf'], $csrfToken)) {
        $errors[] = 'Ungültiges CSRF-Token. Bitte laden Sie die Seite neu.';
    }

    foreach ($old as $field => $value) {
        $old[$field] = (string) ($_POST[$field] ?? '');
    }

    if ($allChecksPassed && $errors === []) {
        $result = $installer->run($old);
        if ($result['success'] === true) {
            unset($_SESSION['install_csrf']);
            header('Location: /install/finish.php', true, 302);
            exit;
        }
        $errors = array_merge($errors, $result['errors']);
    } elseif (!$allChecksPassed) {
        $errors[] = 'Systemcheck fehlgeschlagen. Beheben Sie zuerst alle roten Punkte.';
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHRYSO Installation</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 2rem; max-width: 960px; }
        .card { border: 1px solid #ddd; border-radius: 8px; padding: 1.5rem; margin-bottom: 1rem; }
        .ok { color: #0a7f2e; }
        .fail { color: #a40000; }
        label { display: block; margin-top: 1rem; font-weight: bold; }
        input { width: 100%; max-width: 500px; padding: 0.5rem; margin-top: 0.25rem; }
        button { margin-top: 1.5rem; padding: 0.75rem 1.2rem; cursor: pointer; }
        .errors { background: #ffe8e8; border: 1px solid #ffb3b3; padding: 1rem; border-radius: 6px; }
    </style>
</head>
<body>
<h1>PHRYSO Installationsassistent</h1>

<div class="card">
    <h2>Systemcheck</h2>
    <ul>
        <?php foreach ($checks as $label => $passed): ?>
            <li class="<?= $passed ? 'ok' : 'fail' ?>">
                <?= $passed ? '✔' : '✘' ?> <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
            </li>
        <?php endforeach; ?>
    </ul>
</div>

<?php if ($errors !== []): ?>
    <div class="errors card">
        <h2>Fehler</h2>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card">
    <h2>Installationsformular</h2>
    <form method="post" action="/install/index.php" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string) $_SESSION['install_csrf'], ENT_QUOTES, 'UTF-8') ?>">

        <label for="db_host">DB_HOST</label>
        <input id="db_host" name="db_host" value="<?= htmlspecialchars($old['db_host'], ENT_QUOTES, 'UTF-8') ?>" required>

        <label for="db_name">DB_NAME</label>
        <input id="db_name" name="db_name" value="<?= htmlspecialchars($old['db_name'], ENT_QUOTES, 'UTF-8') ?>" required>

        <label for="db_user">DB_USER</label>
        <input id="db_user" name="db_user" value="<?= htmlspecialchars($old['db_user'], ENT_QUOTES, 'UTF-8') ?>" required>

        <label for="db_pass">DB_PASS</label>
        <input id="db_pass" type="password" name="db_pass" value="<?= htmlspecialchars($old['db_pass'], ENT_QUOTES, 'UTF-8') ?>">

        <label for="smtp_host">SMTP_HOST</label>
        <input id="smtp_host" name="smtp_host" value="<?= htmlspecialchars($old['smtp_host'], ENT_QUOTES, 'UTF-8') ?>" required>

        <label for="smtp_user">SMTP_USER</label>
        <input id="smtp_user" name="smtp_user" value="<?= htmlspecialchars($old['smtp_user'], ENT_QUOTES, 'UTF-8') ?>" required>

        <label for="smtp_pass">SMTP_PASS</label>
        <input id="smtp_pass" type="password" name="smtp_pass" value="<?= htmlspecialchars($old['smtp_pass'], ENT_QUOTES, 'UTF-8') ?>" required>

        <label for="admin_notification_email">ADMIN_NOTIFICATION_EMAIL</label>
        <input id="admin_notification_email" type="email" name="admin_notification_email" value="<?= htmlspecialchars($old['admin_notification_email'], ENT_QUOTES, 'UTF-8') ?>" required>

        <label for="admin_username">Admin Username</label>
        <input id="admin_username" name="admin_username" value="<?= htmlspecialchars($old['admin_username'], ENT_QUOTES, 'UTF-8') ?>" required>

        <label for="admin_password">Admin Passwort</label>
        <input id="admin_password" type="password" name="admin_password" required>

        <button type="submit" <?= $allChecksPassed ? '' : 'disabled' ?>>Installation starten</button>
    </form>
</div>
</body>
</html>
