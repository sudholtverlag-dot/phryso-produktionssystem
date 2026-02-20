<?php

declare(strict_types=1);

final class NotificationService
{
    public static function createForAdmins(PDO $pdo, string $message, string $link): void
    {
        $admins = $pdo->query("SELECT id FROM users WHERE role = 'admin'")->fetchAll();

        $stmt = $pdo->prepare('INSERT INTO notifications (user_id, message, link, is_read, created_at) VALUES (:user_id, :message, :link, 0, NOW())');
        foreach ($admins as $admin) {
            $stmt->execute([
                'user_id' => (int) $admin['id'],
                'message' => $message,
                'link' => $link,
            ]);
        }
    }

    public static function unreadCount(PDO $pdo, int $userId): int
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND is_read = 0');
        $stmt->execute(['user_id' => $userId]);

        return (int) $stmt->fetchColumn();
    }

    public static function latest(PDO $pdo, int $userId, int $limit = 10): array
    {
        $stmt = $pdo->prepare('SELECT id, message, link, is_read, created_at FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC LIMIT :limit');
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function markRead(PDO $pdo, int $notificationId, int $userId): void
    {
        $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :user_id');
        $stmt->execute(['id' => $notificationId, 'user_id' => $userId]);
    }
}
