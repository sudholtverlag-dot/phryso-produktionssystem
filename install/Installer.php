<?php

declare(strict_types=1);

final class Installer
{
    private const INSTALLED_FILE = __DIR__ . '/../config/.installed';
    private const SQL_FILE = __DIR__ . '/../sql/install.sql';
    private const CONFIG_FILE = __DIR__ . '/../config/local.php';

    public static function startSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public static function isInstalled(): bool
    {
        return is_file(self::INSTALLED_FILE);
    }

    public static function redirectIfInstalled(): void
    {
        if (!self::isInstalled()) {
            return;
        }

        header('Location: /public/index.php?installed=1');
        exit;
    }

    public static function createCsrfToken(): string
    {
        self::startSession();

        if (empty($_SESSION['install_csrf_token'])) {
            $_SESSION['install_csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['install_csrf_token'];
    }

    public static function validateCsrfToken(?string $token): bool
    {
        self::startSession();

        if (empty($_SESSION['install_csrf_token']) || $token === null) {
            return false;
        }

        return hash_equals($_SESSION['install_csrf_token'], $token);
    }

    /**
     * @return array<string, string>
     */
    public static function validateInput(array $data): array
    {
        $errors = [];

        $requiredFields = [
            'db_host',
            'db_name',
            'db_user',
            'db_pass',
            'smtp_host',
            'smtp_user',
            'smtp_pass',
            'smtp_port',
            'admin_notification_email',
            'admin_username',
            'admin_password',
            'admin_password_confirm',
        ];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || trim((string) $data[$field]) === '') {
                $errors[$field] = 'Dieses Feld darf nicht leer sein.';
            }
        }

        if (!isset($errors['admin_password']) && strlen((string) $data['admin_password']) < 8) {
            $errors['admin_password'] = 'Das Passwort muss mindestens 8 Zeichen lang sein.';
        }

        if (
            !isset($errors['admin_password_confirm'])
            && (string) $data['admin_password'] !== (string) $data['admin_password_confirm']
        ) {
            $errors['admin_password_confirm'] = 'Die Passwort-Bestätigung stimmt nicht überein.';
        }

        if (
            !isset($errors['admin_notification_email'])
            && filter_var((string) $data['admin_notification_email'], FILTER_VALIDATE_EMAIL) === false
        ) {
            $errors['admin_notification_email'] = 'Bitte eine gültige E-Mail-Adresse eingeben.';
        }

        if (
            !isset($errors['admin_username'])
            && preg_match('/^[a-zA-Z0-9]+$/', (string) $data['admin_username']) !== 1
        ) {
            $errors['admin_username'] = 'Der Username darf nur alphanumerische Zeichen enthalten.';
        }

        if (!isset($errors['smtp_port']) && filter_var((string) $data['smtp_port'], FILTER_VALIDATE_INT) === false) {
            $errors['smtp_port'] = 'SMTP-Port muss eine Zahl sein.';
        }

        return $errors;
    }

    /**
     * @param array<string, string> $data
     */
    public static function performInstallation(array $data): void
    {
        if (!is_file(self::SQL_FILE)) {
            throw new RuntimeException('Die Datei /sql/install.sql wurde nicht gefunden.');
        }

        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $data['db_host'], $data['db_name']);
        $pdo = new PDO(
            $dsn,
            $data['db_user'],
            $data['db_pass'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );

        self::executeSqlFile($pdo, self::SQL_FILE);

        $insertAdmin = $pdo->prepare(
            'INSERT INTO users (username, password_hash, email, role, created_at) VALUES (:username, :password_hash, :email, :role, NOW())'
        );
        $insertAdmin->execute([
            ':username' => $data['admin_username'],
            ':password_hash' => password_hash($data['admin_password'], PASSWORD_DEFAULT),
            ':email' => $data['admin_notification_email'],
            ':role' => 'admin',
        ]);

        self::writeConfigFile($data);
        self::writeInstalledMarker();

        self::startSession();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    private static function executeSqlFile(PDO $pdo, string $sqlFile): void
    {
        $sql = (string) file_get_contents($sqlFile);
        $sql = preg_replace('/^\s*(--|#).*/m', '', $sql) ?? '';

        $statements = preg_split('/;\s*\n/', $sql) ?: [];

        foreach ($statements as $statement) {
            $trimmed = trim($statement);
            if ($trimmed === '') {
                continue;
            }

            $pdo->exec($trimmed);
        }
    }

    /**
     * @param array<string, string> $data
     */
    private static function writeConfigFile(array $data): void
    {
        $content = "<?php\n\n";
        $content .= "declare(strict_types=1);\n\n";
        $content .= "define('DB_HOST', '" . self::escapePhpValue($data['db_host']) . "');\n";
        $content .= "define('DB_NAME', '" . self::escapePhpValue($data['db_name']) . "');\n";
        $content .= "define('DB_USER', '" . self::escapePhpValue($data['db_user']) . "');\n";
        $content .= "define('DB_PASS', '" . self::escapePhpValue($data['db_pass']) . "');\n";
        $content .= "define('SMTP_HOST', '" . self::escapePhpValue($data['smtp_host']) . "');\n";
        $content .= "define('SMTP_USER', '" . self::escapePhpValue($data['smtp_user']) . "');\n";
        $content .= "define('SMTP_PASS', '" . self::escapePhpValue($data['smtp_pass']) . "');\n";
        $content .= "define('SMTP_PORT', '" . self::escapePhpValue($data['smtp_port']) . "');\n";
        $content .= "define('ADMIN_NOTIFICATION_EMAIL', '" . self::escapePhpValue($data['admin_notification_email']) . "');\n";

        if (file_put_contents(self::CONFIG_FILE, $content) === false) {
            throw new RuntimeException('Konfigurationsdatei konnte nicht geschrieben werden.');
        }
    }

    private static function writeInstalledMarker(): void
    {
        $markerContent = sprintf("Installed at %s\n", date(DATE_ATOM));

        if (file_put_contents(self::INSTALLED_FILE, $markerContent) === false) {
            throw new RuntimeException('Installationsmarker konnte nicht erstellt werden.');
        }
    }

    private static function escapePhpValue(string $value): string
    {
        return addcslashes($value, "\\'");
    }
}

if (basename(__FILE__) === basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''))) {
    require_once __DIR__ . '/Installer.php';

    Installer::startSession();
    Installer::redirectIfInstalled();

    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        http_response_code(405);
        echo 'Methode nicht erlaubt.';
        exit;
    }

    if (!Installer::validateCsrfToken($_POST['csrf_token'] ?? null)) {
        $_SESSION['install_errors'] = ['csrf' => 'Ungültiges CSRF-Token. Bitte Formular erneut senden.'];
        $_SESSION['install_old'] = $_POST;
        header('Location: /install/step2.php');
        exit;
    }

    $input = [
        'db_host' => trim((string) ($_POST['db_host'] ?? '')),
        'db_name' => trim((string) ($_POST['db_name'] ?? '')),
        'db_user' => trim((string) ($_POST['db_user'] ?? '')),
        'db_pass' => (string) ($_POST['db_pass'] ?? ''),
        'smtp_host' => trim((string) ($_POST['smtp_host'] ?? '')),
        'smtp_user' => trim((string) ($_POST['smtp_user'] ?? '')),
        'smtp_pass' => (string) ($_POST['smtp_pass'] ?? ''),
        'smtp_port' => trim((string) ($_POST['smtp_port'] ?? '')),
        'admin_notification_email' => trim((string) ($_POST['admin_notification_email'] ?? '')),
        'admin_username' => trim((string) ($_POST['admin_username'] ?? '')),
        'admin_password' => (string) ($_POST['admin_password'] ?? ''),
        'admin_password_confirm' => (string) ($_POST['admin_password_confirm'] ?? ''),
    ];

    $errors = Installer::validateInput($input);
    if ($errors !== []) {
        $_SESSION['install_errors'] = $errors;
        $_SESSION['install_old'] = $input;
        header('Location: /install/step2.php');
        exit;
    }

    try {
        Installer::performInstallation($input);
        header('Location: /install/finish.php');
        exit;
    } catch (Throwable $exception) {
        Installer::startSession();
        $_SESSION['install_errors'] = ['general' => $exception->getMessage()];
        $_SESSION['install_old'] = $input;
        header('Location: /install/step2.php');
        exit;
    }
}
