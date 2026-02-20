<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

if (Auth::check()) {
    redirect('/dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
        $error = 'Ungültiges CSRF-Token.';
    } else {
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        $pdo = Database::getConnection();
        if (Auth::attemptLogin($pdo, $username, $password)) {
            redirect('/dashboard.php');
        }

        $error = 'Login fehlgeschlagen.';
    }
}
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
</head>
<body>
<h1>Produktionssystem Login</h1>
<?php if ($error !== ''): ?>
    <p style="color:red"><?= e($error) ?></p>
<?php endif; ?>
<form method="post">
    <input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>">
    <label>Username <input type="text" name="username" required></label><br>
    <label>Passwort <input type="password" name="password" required></label><br>
    <button type="submit">Einloggen</button>
</form>
</body>
</html>
