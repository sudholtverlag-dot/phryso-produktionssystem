<?php

declare(strict_types=1);

require_once __DIR__ . '/Installer.php';

Installer::startSession();
Installer::redirectIfInstalled();

$checks = [
    'PHP-Version >= 8.2' => version_compare(PHP_VERSION, '8.2.0', '>='),
    'PDO MySQL verfügbar' => extension_loaded('pdo_mysql'),
    '/config ist schreibbar' => is_writable(__DIR__ . '/../config'),
    '/uploads ist schreibbar' => is_writable(__DIR__ . '/../uploads'),
];

$allGood = !in_array(false, $checks, true);
?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Installation - Schritt 1</title>
  <link rel="stylesheet" href="/install/styles.css">
</head>
<body>
  <main class="container">
    <div class="progress">
      <div class="step active">Schritt 1: Systemprüfung</div>
      <div class="step">Schritt 2: Konfiguration</div>
      <div class="step">Abschluss</div>
    </div>

    <h1>Systemprüfung</h1>
    <ul class="status-list">
      <?php foreach ($checks as $label => $ok): ?>
        <li>
          <span><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
          <span class="<?= $ok ? 'ok' : 'bad' ?>"><?= $ok ? 'OK' : 'Fehler' ?></span>
        </li>
      <?php endforeach; ?>
    </ul>

    <a class="btn" href="/install/step2.php" <?= $allGood ? '' : 'disabled aria-disabled="true" onclick="return false;"' ?>>Weiter zur Konfiguration</a>
  </main>
</body>
</html>
