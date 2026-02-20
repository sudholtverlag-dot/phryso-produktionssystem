<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Csrf.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/PageCalculator.php';
require_once __DIR__ . '/PostService.php';
require_once __DIR__ . '/MailService.php';
require_once __DIR__ . '/NotificationService.php';

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function require_auth(): void
{
    if (!Auth::check()) {
        redirect('/index.php');
    }
}

function require_admin(): void
{
    require_auth();
    if (!Auth::isAdmin()) {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
}
