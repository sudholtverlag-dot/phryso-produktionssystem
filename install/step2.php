<?php

declare(strict_types=1);

require_once __DIR__ . '/Installer.php';

Installer::startSession();
Installer::redirectIfInstalled();

$csrfToken = Installer::createCsrfToken();
$errors = $_SESSION['install_errors'] ?? [];
$old = $_SESSION['install_old'] ?? [];
unset($_SESSION['install_errors'], $_SESSION['install_old']);

function old(string $key, array $old): string
{
    return htmlspecialchars((string) ($old[$key] ?? ''), ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Installation - Schritt 2</title>
  <link rel="stylesheet" href="/install/styles.css">
</head>
<body>
  <main class="container">
    <div class="progress">
      <div class="step done">Schritt 1: Systemprüfung</div>
      <div class="step active">Schritt 2: Konfiguration</div>
      <div class="step">Abschluss</div>
    </div>

    <h1>System konfigurieren</h1>

    <?php if (isset($errors['general'])): ?>
      <div class="alert"><?= htmlspecialchars((string) $errors['general'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if (isset($errors['csrf'])): ?>
      <div class="alert"><?= htmlspecialchars((string) $errors['csrf'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="post" action="/install/Installer.php" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

      <h2>Datenbank</h2>
      <div class="grid">
        <label class="field"><span>DB Host</span><input name="db_host" value="<?= old('db_host', $old) ?>"><?php if (isset($errors['db_host'])): ?><span class="error"><?= htmlspecialchars((string) $errors['db_host'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?></label>
        <label class="field"><span>DB Name</span><input name="db_name" value="<?= old('db_name', $old) ?>"><?php if (isset($errors['db_name'])): ?><span class="error"><?= htmlspecialchars((string) $errors['db_name'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?></label>
        <label class="field"><span>DB User</span><input name="db_user" value="<?= old('db_user', $old) ?>"><?php if (isset($errors['db_user'])): ?><span class="error"><?= htmlspecialchars((string) $errors['db_user'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?></label>
        <label class="field"><span>DB Passwort</span><input type="password" name="db_pass"><?php if (isset($errors['db_pass'])): ?><span class="error"><?= htmlspecialchars((string) $errors['db_pass'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?></label>
      </div>

      <h2>SMTP</h2>
      <div class="grid">
        <label class="field"><span>SMTP Host</span><input name="smtp_host" value="<?= old('smtp_host', $old) ?>"><?php if (isset($errors['smtp_host'])): ?><span class="error"><?= htmlspecialchars((string) $errors['smtp_host'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?></label>
        <label class="field"><span>SMTP User</span><input name="smtp_user" value="<?= old('smtp_user', $old) ?>"><?php if (isset($errors['smtp_user'])): ?><span class="error"><?= htmlspecialchars((string) $errors['smtp_user'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?></label>
        <label class="field"><span>SMTP Passwort</span><input type="password" name="smtp_pass"><?php if (isset($errors['smtp_pass'])): ?><span class="error"><?= htmlspecialchars((string) $errors['smtp_pass'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?></label>
        <label class="field"><span>SMTP Port</span><input name="smtp_port" value="<?= old('smtp_port', $old) ?>"><?php if (isset($errors['smtp_port'])): ?><span class="error"><?= htmlspecialchars((string) $errors['smtp_port'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?></label>
      </div>

      <h2>System</h2>
      <div class="grid">
        <label class="field full"><span>Admin Benachrichtigungs-E-Mail</span><input name="admin_notification_email" value="<?= old('admin_notification_email', $old) ?>"><?php if (isset($errors['admin_notification_email'])): ?><span class="error"><?= htmlspecialchars((string) $errors['admin_notification_email'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?></label>
      </div>

      <h2>Admin Account</h2>
      <div class="grid">
        <label class="field"><span>Admin Username</span><input name="admin_username" value="<?= old('admin_username', $old) ?>"><?php if (isset($errors['admin_username'])): ?><span class="error"><?= htmlspecialchars((string) $errors['admin_username'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?></label>
        <label class="field"><span>Admin Passwort</span><input type="password" name="admin_password"><?php if (isset($errors['admin_password'])): ?><span class="error"><?= htmlspecialchars((string) $errors['admin_password'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?></label>
        <label class="field"><span>Passwort bestätigen</span><input type="password" name="admin_password_confirm"><?php if (isset($errors['admin_password_confirm'])): ?><span class="error"><?= htmlspecialchars((string) $errors['admin_password_confirm'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?></label>
      </div>

      <button class="btn" type="submit">Installation starten</button>
    </form>
  </main>
</body>
</html>
