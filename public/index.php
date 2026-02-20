<?php

declare(strict_types=1);

$installedInfo = isset($_GET['installed']) ? 'System bereits installiert.' : '';
?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>PHRYSO Redaktionssystem</title>
  <style>
    body{font-family:Arial,sans-serif;background:#f5f7fb;margin:0}
    main{max-width:760px;margin:60px auto;background:#fff;padding:24px;border-radius:10px;box-shadow:0 8px 20px rgba(0,0,0,.06)}
    .info{background:#eff6ff;border:1px solid #bfdbfe;color:#1e3a8a;padding:10px;border-radius:6px;margin-bottom:14px}
  </style>
</head>
<body>
  <main>
    <?php if ($installedInfo !== ''): ?><div class="info"><?= htmlspecialchars($installedInfo, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    <h1>PHRYSO Redaktionssystem</h1>
    <p>Das System ist bereit. Bitte mit dem Admin-Account anmelden.</p>
  </main>
</body>
</html>
