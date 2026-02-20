<?php

declare(strict_types=1);


if (basename((string) ($_SERVER['SCRIPT_NAME'] ?? '')) === 'Installer.php') {
    $lockPath = dirname(__DIR__) . '/config/.installed';
    if (is_file($lockPath)) {
        header('Location: /public/index.php', true, 302);
        exit;
    }

    header('Location: /install/index.php', true, 302);
    exit;
}

final class Installer
{
    private string $rootPath;
    private string $configPath;
    private string $uploadsPath;
    private string $installedLockPath;
    private string $sqlInstallPath;

    public function __construct(string $rootPath)
    {
        $this->rootPath = rtrim($rootPath, DIRECTORY_SEPARATOR);
        $this->configPath = $this->rootPath . DIRECTORY_SEPARATOR . 'config';
        $this->uploadsPath = $this->rootPath . DIRECTORY_SEPARATOR . 'uploads';
        $this->installedLockPath = $this->configPath . DIRECTORY_SEPARATOR . '.installed';
        $this->sqlInstallPath = $this->rootPath . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . 'install.sql';
    }

    public function isInstalled(): bool
    {
        return is_file($this->installedLockPath);
    }

    /**
     * @return array{success:bool,errors:array<int,string>}
     */
    public function run(array $input): array
    {
        $errors = $this->validateInput($input);

        if ($this->isInstalled()) {
            $errors[] = 'Die Installation ist bereits abgeschlossen.';
        }

        if (!is_dir($this->configPath) || !is_writable($this->configPath)) {
            $errors[] = 'Das Verzeichnis /config fehlt oder ist nicht beschreibbar.';
        }

        if (!is_dir($this->uploadsPath) || !is_writable($this->uploadsPath)) {
            $errors[] = 'Das Verzeichnis /uploads fehlt oder ist nicht beschreibbar.';
        }

        if (!is_file($this->sqlInstallPath) || !is_readable($this->sqlInstallPath)) {
            $errors[] = 'Die Datei /sql/install.sql fehlt oder ist nicht lesbar.';
        }

        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors];
        }

        $pdo = $this->createConnection($input, $errors);
        if (!$pdo instanceof PDO) {
            return ['success' => false, 'errors' => $errors];
        }

        $schemaSql = (string) file_get_contents($this->sqlInstallPath);

        try {
            $pdo->beginTransaction();

            $pdo->exec($schemaSql);

            $insert = $pdo->prepare(
                'INSERT INTO users (username, password_hash, role) VALUES (:username, :password_hash, :role)'
            );
            $insert->execute([
                ':username' => trim((string) $input['admin_username']),
                ':password_hash' => password_hash((string) $input['admin_password'], PASSWORD_DEFAULT),
                ':role' => 'admin',
            ]);

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = 'Datenbank-Installation fehlgeschlagen: ' . $e->getMessage();

            return ['success' => false, 'errors' => $errors];
        }

        $configWritten = $this->writeConfig($input, $errors);
        if (!$configWritten) {
            return ['success' => false, 'errors' => $errors];
        }

        if (@file_put_contents($this->installedLockPath, date('c') . PHP_EOL) === false) {
            $errors[] = 'Lock-Datei /config/.installed konnte nicht erstellt werden.';

            return ['success' => false, 'errors' => $errors];
        }

        return ['success' => true, 'errors' => []];
    }

    /**
     * @param array<int,string> $errors
     */
    private function createConnection(array $input, array &$errors): ?PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            trim((string) $input['db_host']),
            trim((string) $input['db_name'])
        );

        try {
            return new PDO(
                $dsn,
                (string) $input['db_user'],
                (string) $input['db_pass'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (Throwable $e) {
            $errors[] = 'Verbindung zur Datenbank fehlgeschlagen: ' . $e->getMessage();

            return null;
        }
    }

    /**
     * @param array<int,string> $errors
     */
    private function writeConfig(array $input, array &$errors): bool
    {
        $localPath = $this->configPath . DIRECTORY_SEPARATOR . 'local.php';

        $content = "<?php\n\ndeclare(strict_types=1);\n\nreturn [\n"
            . "    'db' => [\n"
            . "        'host' => " . $this->toPhpString($input['db_host']) . ",\n"
            . "        'name' => " . $this->toPhpString($input['db_name']) . ",\n"
            . "        'user' => " . $this->toPhpString($input['db_user']) . ",\n"
            . "        'password' => " . $this->toPhpString($input['db_pass']) . ",\n"
            . "    ],\n"
            . "    'smtp' => [\n"
            . "        'host' => " . $this->toPhpString($input['smtp_host']) . ",\n"
            . "        'user' => " . $this->toPhpString($input['smtp_user']) . ",\n"
            . "        'password' => " . $this->toPhpString($input['smtp_pass']) . ",\n"
            . "    ],\n"
            . "    'admin_notification_email' => " . $this->toPhpString($input['admin_notification_email']) . ",\n"
            . "];\n";

        if (@file_put_contents($localPath, $content) === false) {
            $errors[] = 'Konfigurationsdatei /config/local.php konnte nicht geschrieben werden.';

            return false;
        }

        return true;
    }

    private function toPhpString(mixed $value): string
    {
        return var_export((string) $value, true);
    }

    /**
     * @return array<int,string>
     */
    private function validateInput(array $input): array
    {
        $required = [
            'db_host' => 'DB_HOST',
            'db_name' => 'DB_NAME',
            'db_user' => 'DB_USER',
            'smtp_host' => 'SMTP_HOST',
            'smtp_user' => 'SMTP_USER',
            'smtp_pass' => 'SMTP_PASS',
            'admin_notification_email' => 'ADMIN_NOTIFICATION_EMAIL',
            'admin_username' => 'Admin Username',
            'admin_password' => 'Admin Passwort',
        ];

        $errors = [];

        foreach ($required as $key => $label) {
            if (!isset($input[$key]) || trim((string) $input[$key]) === '') {
                $errors[] = sprintf('%s ist erforderlich.', $label);
            }
        }

        if (isset($input['admin_notification_email'])
            && trim((string) $input['admin_notification_email']) !== ''
            && !filter_var((string) $input['admin_notification_email'], FILTER_VALIDATE_EMAIL)
        ) {
            $errors[] = 'ADMIN_NOTIFICATION_EMAIL ist keine gültige E-Mail-Adresse.';
        }

        if (isset($input['admin_password']) && strlen((string) $input['admin_password']) < 12) {
            $errors[] = 'Das Admin Passwort muss mindestens 12 Zeichen lang sein.';
        }

        return $errors;
    }
}
