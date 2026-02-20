<?php

declare(strict_types=1);

$lockPath = dirname(__DIR__) . '/config/.installed';

if (!is_file($lockPath)) {
    header('Location: /install/index.php', true, 302);
    exit;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation abgeschlossen</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 2rem; }
        .card { max-width: 680px; border: 1px solid #ddd; border-radius: 8px; padding: 2rem; }
        a.button { display: inline-block; margin-top: 1rem; background: #005fcc; color: #fff; padding: 0.8rem 1.2rem; text-decoration: none; border-radius: 4px; }
    </style>
</head>
<body>
<div class="card">
    <h1>Installation erfolgreich</h1>
    <p>Das PHRYSO-System wurde erfolgreich installiert. Sie können sich jetzt im System anmelden.</p>
    <a class="button" href="/public/index.php">Zum Login</a>
</div>
</body>
</html>
