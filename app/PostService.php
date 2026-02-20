<?php

declare(strict_types=1);

final class PostService
{
    public static function countWords(string $text): int
    {
        $trimmed = trim(strip_tags($text));
        if ($trimmed === '') {
            return 0;
        }

        return count(preg_split('/\s+/u', $trimmed) ?: []);
    }

    public static function create(
        PDO $pdo,
        int $issueId,
        int $authorId,
        string $title,
        string $content,
        bool $hasCoverImage,
        int $smallImageCount
    ): int {
        $wordCount = self::countWords($content);
        $pageCount = PageCalculator::calculate($wordCount, $hasCoverImage, $smallImageCount);

        $stmt = $pdo->prepare('INSERT INTO posts (issue_id, autor_id, title, content, word_count, page_count, created_at, updated_at) VALUES (:issue_id, :autor_id, :title, :content, :word_count, :page_count, NOW(), NOW())');
        $stmt->execute([
            'issue_id' => $issueId,
            'autor_id' => $authorId,
            'title' => $title,
            'content' => $content,
            'word_count' => $wordCount,
            'page_count' => $pageCount,
        ]);

        return (int) $pdo->lastInsertId();
    }

    public static function update(
        PDO $pdo,
        int $postId,
        string $title,
        string $content,
        bool $hasCoverImage,
        int $smallImageCount
    ): void {
        $wordCount = self::countWords($content);
        $pageCount = PageCalculator::calculate($wordCount, $hasCoverImage, $smallImageCount);

        $stmt = $pdo->prepare('UPDATE posts SET title = :title, content = :content, word_count = :word_count, page_count = :page_count, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            'id' => $postId,
            'title' => $title,
            'content' => $content,
            'word_count' => $wordCount,
            'page_count' => $pageCount,
        ]);
    }
}
