<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_auth();

$pdo = Database::getConnection();
$issues = $pdo->query('SELECT id, nummer, titel FROM issues ORDER BY nummer DESC')->fetchAll();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
        $error = 'Ungültiges CSRF-Token.';
    } else {
        $issueId = (int) ($_POST['issue_id'] ?? 0);
        $title = trim((string) ($_POST['title'] ?? ''));
        $content = trim((string) ($_POST['content'] ?? ''));
        $smallImageCount = max(0, (int) ($_POST['small_image_count'] ?? 0));
        $hasCoverImage = isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK;

        if ($issueId <= 0 || $title === '' || $content === '') {
            $error = 'Bitte alle Pflichtfelder ausfüllen.';
        } else {
            $pdo->beginTransaction();
            try {
                $postId = PostService::create($pdo, $issueId, (int) $_SESSION['user_id'], $title, $content, $hasCoverImage, $smallImageCount);

                $issueStmt = $pdo->prepare('SELECT nummer FROM issues WHERE id = :id');
                $issueStmt->execute(['id' => $issueId]);
                $issueNumber = (int) $issueStmt->fetchColumn();
                $uploadDir = __DIR__ . '/uploads/heft_' . $issueNumber . '/beitrag_' . $postId;
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0775, true);
                }

                $finfo = new finfo(FILEINFO_MIME_TYPE);

                if ($hasCoverImage) {
                    if ($_FILES['cover_image']['size'] > MAX_UPLOAD_BYTES) {
                        throw new RuntimeException('Titelbild überschreitet 10MB.');
                    }
                    $mime = (string) $finfo->file($_FILES['cover_image']['tmp_name']);
                    if (!in_array($mime, ALLOWED_UPLOAD_MIME_TYPES, true)) {
                        throw new RuntimeException('Ungültiger MIME-Typ beim Titelbild.');
                    }
                    $coverName = basename((string) $_FILES['cover_image']['name']);
                    move_uploaded_file($_FILES['cover_image']['tmp_name'], $uploadDir . '/cover_' . $coverName);
                }

                if (isset($_FILES['images'])) {
                    foreach ($_FILES['images']['tmp_name'] as $index => $tmpName) {
                        if ($_FILES['images']['error'][$index] !== UPLOAD_ERR_OK) {
                            continue;
                        }
                        if ($_FILES['images']['size'][$index] > MAX_UPLOAD_BYTES) {
                            throw new RuntimeException('Ein Bild überschreitet 10MB.');
                        }
                        $mime = (string) $finfo->file($tmpName);
                        if (!in_array($mime, ALLOWED_UPLOAD_MIME_TYPES, true)) {
                            throw new RuntimeException('Ungültiger MIME-Typ bei Zusatzbild.');
                        }
                        $imageName = basename((string) $_FILES['images']['name'][$index]);
                        move_uploaded_file($tmpName, $uploadDir . '/img_' . $index . '_' . $imageName);
                    }
                }

                NotificationService::createForAdmins($pdo, 'Neuer Beitrag: ' . $title, '/posts_edit.php?id=' . $postId);
                MailService::sendNewPostNotification('Neuer Beitrag erstellt', "Beitrag '{$title}' wurde neu erstellt.");

                $pdo->commit();
                redirect('/dashboard.php');
            } catch (Throwable $exception) {
                $pdo->rollBack();
                $error = $exception->getMessage();
            }
        }
    }
}
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Beitrag anlegen</title>
</head>
<body>
<h1>Beitrag anlegen</h1>
<p><a href="/dashboard.php">Zurück</a></p>
<?php if ($error !== ''): ?><p style="color:red"><?= e($error) ?></p><?php endif; ?>
<form method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>">
    <label>Heft
        <select name="issue_id" required>
            <option value="">Bitte wählen</option>
            <?php foreach ($issues as $issue): ?>
                <option value="<?= (int) $issue['id'] ?>"><?= (int) $issue['nummer'] ?> - <?= e((string) $issue['titel']) ?></option>
            <?php endforeach; ?>
        </select>
    </label><br>
    <label>Titel <input type="text" name="title" required></label><br>
    <label>Inhalt<br><textarea name="content" rows="10" cols="80" required></textarea></label><br>
    <label>Titelbild <input type="file" name="cover_image" accept="image/*"></label><br>
    <label>Kleine Bilder (mehrere) <input type="file" name="images[]" multiple accept="image/*"></label><br>
    <label>Anzahl kleine Bilder (für Kalkulation) <input type="number" min="0" name="small_image_count" value="0"></label><br>
    <button type="submit">Speichern</button>
</form>
</body>
</html>
