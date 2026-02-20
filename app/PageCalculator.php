<?php

declare(strict_types=1);

final class PageCalculator
{
    public static function calculate(int $wordCount, bool $hasCoverImage, int $smallImageCount): int
    {
        $pages = $wordCount / 900;

        if ($hasCoverImage) {
            $pages += 0.5;
        }

        if ($smallImageCount > 0) {
            $pages += $smallImageCount * 0.15;
        }

        return (int) ceil($pages);
    }
}
