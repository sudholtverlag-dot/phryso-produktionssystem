<?php
declare(strict_types=1);

/**
 * Grundkonfiguration.
 * Umgebungsabhängige Zugangsdaten in local.php hinterlegen.
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');
date_default_timezone_set('Europe/Berlin');

require_once __DIR__ . '/local.php';
