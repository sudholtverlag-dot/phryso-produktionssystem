<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_auth();

$notificationId = (int) ($_GET['id'] ?? 0);
$redirectPath = (string) ($_GET['redirect'] ?? '/dashboard.php');

if ($notificationId > 0) {
    $pdo = Database::getConnection();
    NotificationService::markRead($pdo, $notificationId, (int) $_SESSION['user_id']);
}

redirect($redirectPath);
