<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_admin();

$pdo = Database::getConnection();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
        $error = 'Ungültiges CSRF-Token.';
    } else {
        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'create') {
            $username = trim((string) ($_POST['username'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');
            $role = (string) ($_POST['role'] ?? 'redakteur');

            if ($username === '' || $password === '' || !in_array($role, ['admin', 'redakteur'], true)) {
                $error = 'Ungültige Eingabe.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('INSERT INTO users (username, password_hash, role) VALUES (:username, :password_hash, :role)');
                $stmt->execute(['username' => $username, 'password_hash' => $hash, 'role' => $role]);
                redirect('/users.php');
            }
        }

        if ($action === 'delete') {
            $deleteUserId = (int) ($_POST['user_id'] ?? 0);
            $targetStmt = $pdo->prepare('SELECT id, role FROM users WHERE id = :id');
            $targetStmt->execute(['id' => $deleteUserId]);
            $target = $targetStmt->fetch();

            if ($target) {
                if ($target['role'] === 'admin') {
                    $adminCount = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
                    if ($adminCount <= 1) {
                        $error = 'Der letzte Admin darf nicht gelöscht werden.';
                    }
                }

                if ($error === '') {
                    $del = $pdo->prepare('DELETE FROM users WHERE id = :id');
                    $del->execute(['id' => $deleteUserId]);
                    redirect('/users.php');
                }
            }
        }
    }
}

$users = $pdo->query('SELECT id, username, role FROM users ORDER BY username ASC')->fetchAll();
?>
<!doctype html>
<html lang="de">
<head><meta charset="UTF-8"><title>Benutzerverwaltung</title></head>
<body>
<h1>Benutzerverwaltung</h1>
<p><a href="/dashboard.php">Zurück</a></p>
<?php if ($error !== ''): ?><p style="color:red"><?= e($error) ?></p><?php endif; ?>

<h2>Neuen Benutzer erstellen</h2>
<form method="post">
    <input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>">
    <input type="hidden" name="action" value="create">
    <label>Username <input type="text" name="username" required></label><br>
    <label>Passwort <input type="password" name="password" required></label><br>
    <label>Rolle
        <select name="role">
            <option value="redakteur">redakteur</option>
            <option value="admin">admin</option>
        </select>
    </label><br>
    <button type="submit">Erstellen</button>
</form>

<h2>Benutzerliste</h2>
<table border="1" cellpadding="6">
    <tr><th>Username</th><th>Rolle</th><th>Aktion</th></tr>
    <?php foreach ($users as $user): ?>
        <tr>
            <td><?= e((string) $user['username']) ?></td>
            <td><?= e((string) $user['role']) ?></td>
            <td>
                <form method="post" onsubmit="return confirm('Wirklich löschen?')">
                    <input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
                    <button type="submit">Löschen</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
</body>
</html>
