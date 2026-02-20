<?php

declare(strict_types=1);

class Migration_002
{
    public function up(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS update_audit (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                message TEXT NOT NULL,
                created_at DATETIME NOT NULL
            )'
        );
    }
}
