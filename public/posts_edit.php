<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_auth();

$pdo = Database::getConnection();
$postId = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

$stmt = $pdo->prepare('SELECT p.*, i.nummer FROM posts p JOIN issues i ON i.id = p.issue_id WHERE p.id = :id');
$stmt->execute(['id' => $postId]);
$post = $stmt->fetch();

if (!$post) {
    http_response_code(404);
    echo 'Beitrag nicht gefunden';
    exit;
}

if (!Auth::isAdmin() && (int) $post['autor_id'] !== (int) $_SESSION['user_id']) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
        $error = 'Ungültiges CSRF-Token.';
    } else {
        $title = trim((string) ($_POST['title'] ?? ''));
        $content = trim((string) ($_POST['content'] ?? ''));
        $smallImageCount = max(0, (int) ($_POST['small_image_count'] ?? 0));
        $hasCoverImage = isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK;

        if ($title === '' || $content === '') {
            $error = 'Titel und Inhalt sind Pflichtfelder.';
        } else {
            try {
                PostService::update($pdo, $postId, $title, $content, $hasCoverImage, $smallImageCount);

                $uploadDir = __DIR__ . '/uploads/heft_' . (int) $post['nummer'] . '/beitrag_' . $postId;
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

                redirect('/dashboard.php');
            } catch (Throwable $exception) {
                $error = $exception->getMessage();
            }
        }
    }
}
?>
<!doctype html>
<html lang="de">
<head><meta charset="UTF-8"><title>Beitrag bearbeiten</title></head>
<body>
<h1>Beitrag bearbeiten</h1>
<p><a href="/dashboard.php">Zurück</a></p>
<?php if ($error !== ''): ?><p style="color:red"><?= e($error) ?></p><?php endif; ?>
<form method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>">
    <input type="hidden" name="id" value="<?= (int) $post['id'] ?>">
    <label>Titel <input type="text" name="title" value="<?= e((string) $post['title']) ?>" required></label><br>
    <label>Inhalt<br><textarea name="content" rows="10" cols="80" required><?= e((string) $post['content']) ?></textarea></label><br>
    <label>Titelbild aktualisieren <input type="file" name="cover_image" accept="image/*"></label><br>
    <label>Kleine Bilder (mehrere) <input type="file" name="images[]" multiple accept="image/*"></label><br>
    <label>Anzahl kleine Bilder (für Kalkulation) <input type="number" min="0" name="small_image_count" value="0"></label><br>
    <button type="submit">Aktualisieren</button>
</form>
</body>
</html>
