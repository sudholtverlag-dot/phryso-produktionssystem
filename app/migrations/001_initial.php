<?php

declare(strict_types=1);

class Migration_001
{
    public function up(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username VARCHAR(190) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                is_admin INTEGER NOT NULL DEFAULT 0
            )'
        );

        $adminStatement = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = :username');
        $adminStatement->execute(['username' => 'admin']);

        if ((int) $adminStatement->fetchColumn() === 0) {
            $insertAdmin = $pdo->prepare(
                'INSERT INTO users (username, password_hash, is_admin) VALUES (:username, :password_hash, :is_admin)'
            );
            $insertAdmin->execute([
                'username' => 'admin',
                'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
                'is_admin' => 1,
            ]);
        }
    }
}
