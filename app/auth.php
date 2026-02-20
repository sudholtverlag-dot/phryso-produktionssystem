<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function start_session_if_needed(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function current_user(): ?array
{
    start_session_if_needed();

    return $_SESSION['user'] ?? null;
}

function require_login(): void
{
    if (current_user() === null) {
        header('Location: /public/index.php');
        exit;
    }
}

function require_admin(): void
{
    $user = current_user();
    if ($user === null || empty($user['is_admin'])) {
        http_response_code(403);
        echo 'Zugriff verweigert. Nur Admins dürfen diese Aktion durchführen.';
        exit;
    }
}

function login_user(PDO $pdo, string $username, string $password): bool
{
    $statement = $pdo->prepare('SELECT id, username, password_hash, is_admin FROM users WHERE username = :username LIMIT 1');
    $statement->execute(['username' => $username]);
    $user = $statement->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    start_session_if_needed();
    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'username' => $user['username'],
        'is_admin' => (int) $user['is_admin'] === 1,
    ];

    return true;
}

function logout_user(): void
{
    start_session_if_needed();
    $_SESSION = [];
    session_destroy();
}
