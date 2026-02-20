<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::validate($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    echo 'Ungültige Anfrage';
    exit;
}

$postId = (int) ($_POST['id'] ?? 0);
$pdo = Database::getConnection();
$stmt = $pdo->prepare('SELECT autor_id FROM posts WHERE id = :id');
$stmt->execute(['id' => $postId]);
$post = $stmt->fetch();

if (!$post) {
    redirect('/dashboard.php');
}

if (!Auth::isAdmin() && (int) $post['autor_id'] !== (int) $_SESSION['user_id']) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

$del = $pdo->prepare('DELETE FROM posts WHERE id = :id');
$del->execute(['id' => $postId]);

redirect('/dashboard.php');
