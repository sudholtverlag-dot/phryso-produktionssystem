<?php

declare(strict_types=1);

final class Auth
{
    public static function attemptLogin(PDO $pdo, string $username, string $password): bool
    {
        $stmt = $pdo->prepare('SELECT id, username, password_hash, role FROM users WHERE username = :username LIMIT 1');
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        return true;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public static function check(): bool
    {
        return isset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['role']);
    }

    public static function isAdmin(): bool
    {
        return self::check() && $_SESSION['role'] === 'admin';
    }
}
