<?php

declare(strict_types=1);

require_once __DIR__ . '/Installer.php';

if (!Installer::isInstalled()) {
    header('Location: /install/index.php');
    exit;
}
?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Installation abgeschlossen</title>
  <link rel="stylesheet" href="/install/styles.css">
</head>
<body>
  <main class="container">
    <div class="progress">
      <div class="step done">Schritt 1: Systemprüfung</div>
      <div class="step done">Schritt 2: Konfiguration</div>
      <div class="step active">Abschluss</div>
    </div>

    <h1>Installation erfolgreich abgeschlossen.</h1>
    <a class="btn" href="/public/index.php">Zum Login</a>
  </main>
</body>
</html>
