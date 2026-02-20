<?php

declare(strict_types=1);

final class MailService
{
    public static function sendNewPostNotification(string $subject, string $message): void
    {
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/plain; charset=UTF-8',
            'From: noreply@localhost',
        ];

        try {
            @mail(ADMIN_NOTIFICATION_EMAIL, $subject, $message, implode("\r\n", $headers));
        } catch (Throwable $exception) {
            error_log('Mail send failed: ' . $exception->getMessage());
        }
    }
}
