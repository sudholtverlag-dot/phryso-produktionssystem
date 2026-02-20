<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_auth();

$pdo = Database::getConnection();
$userId = (int) $_SESSION['user_id'];
$isAdmin = Auth::isAdmin();

$issues = $pdo->query('SELECT id, nummer, titel FROM issues ORDER BY nummer DESC')->fetchAll();

if ($isAdmin) {
    $postsStmt = $pdo->query('SELECT p.id, p.title, p.page_count, p.word_count, p.autor_id, p.issue_id, u.username, i.nummer FROM posts p JOIN users u ON u.id = p.autor_id JOIN issues i ON i.id = p.issue_id ORDER BY p.created_at DESC');
} else {
    $postsStmt = $pdo->prepare('SELECT p.id, p.title, p.page_count, p.word_count, p.autor_id, p.issue_id, u.username, i.nummer FROM posts p JOIN users u ON u.id = p.autor_id JOIN issues i ON i.id = p.issue_id WHERE p.autor_id = :autor_id ORDER BY p.created_at DESC');
    $postsStmt->execute(['autor_id' => $userId]);
}
$posts = $postsStmt->fetchAll();

$totalsStmt = $pdo->query('SELECT issue_id, SUM(page_count) AS total_pages FROM posts GROUP BY issue_id');
$totals = [];
foreach ($totalsStmt->fetchAll() as $row) {
    $totals[(int) $row['issue_id']] = (int) $row['total_pages'];
}

$unreadCount = NotificationService::unreadCount($pdo, $userId);
$notifications = NotificationService::latest($pdo, $userId, 10);

function status_color(int $pages): string
{
    if ($pages < 56) {
        return 'red';
    }
    if ($pages <= 68) {
        return 'green';
    }

    return 'orange';
}
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
</head>
<body>
<p>Angemeldet als <?= e((string) $_SESSION['username']) ?> (<?= e((string) $_SESSION['role']) ?>) | <a href="/logout.php">Logout</a></p>
<p><a href="/posts_create.php">Neuen Beitrag anlegen</a><?php if ($isAdmin): ?> | <a href="/users.php">Benutzerverwaltung</a><?php endif; ?></p>

<div>
    <strong>Benachrichtigungen (<?= $unreadCount ?> ungelesen)</strong>
    <ul>
        <?php foreach ($notifications as $notification): ?>
            <li>
                <?= $notification['is_read'] ? '✓' : '•' ?>
                <?= e($notification['message']) ?>
                <a href="/mark_notification.php?id=<?= (int) $notification['id'] ?>&redirect=<?= urlencode('/dashboard.php') ?>">Als gelesen markieren</a>
                <?php if ($notification['link'] !== ''): ?>
                    <a href="<?= e($notification['link']) ?>">Öffnen</a>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
</div>

<h2>Hefte</h2>
<table border="1" cellpadding="6">
    <tr><th>Nummer</th><th>Titel</th><th>Gesamtseiten</th><th>Status</th></tr>
    <?php foreach ($issues as $issue):
        $totalPages = $totals[(int) $issue['id']] ?? 0;
        $color = status_color($totalPages);
        ?>
        <tr>
            <td><?= (int) $issue['nummer'] ?></td>
            <td><?= e((string) $issue['titel']) ?></td>
            <td><?= $totalPages ?></td>
            <td style="color:<?= $color ?>"><?= e($color) ?></td>
        </tr>
    <?php endforeach; ?>
</table>

<h2>Beitragsliste</h2>
<table border="1" cellpadding="6">
    <tr><th>Titel</th><th>Heft</th><th>Autor</th><th>Wörter</th><th>Seiten</th><th>Aktionen</th></tr>
    <?php foreach ($posts as $post): ?>
        <tr>
            <td><?= e((string) $post['title']) ?></td>
            <td><?= (int) $post['nummer'] ?></td>
            <td><?= e((string) $post['username']) ?></td>
            <td><?= (int) $post['word_count'] ?></td>
            <td><?= (int) $post['page_count'] ?></td>
            <td>
                <a href="/posts_edit.php?id=<?= (int) $post['id'] ?>">Bearbeiten</a>
                <form method="post" action="/posts_delete.php" style="display:inline">
                    <input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>">
                    <input type="hidden" name="id" value="<?= (int) $post['id'] ?>">
                    <button type="submit">Löschen</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
</body>
</html>
