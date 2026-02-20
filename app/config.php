<?php

declare(strict_types=1);

const DB_DSN = 'mysql:host=127.0.0.1;port=3306;dbname=produktionssystem;charset=utf8mb4';
const DB_USER = 'root';
const DB_PASS = '';
const ADMIN_NOTIFICATION_EMAIL = 'admin@example.com';
const MAX_UPLOAD_BYTES = 10 * 1024 * 1024;
const ALLOWED_UPLOAD_MIME_TYPES = [
    'image/jpeg',
    'image/png',
    'image/webp',
    'image/gif',
];
