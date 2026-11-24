<?php

declare(strict_types=1);

namespace App\Services;

final class MatchEngine
{
    public const DISPLAY_THRESHOLD = 50.0;

    public static function score(array $reported, array $candidate): float
    {
        $category = (int) $reported['category_id'] === (int) $candidate['category_id'] ? 1.0 : 0.0;
        $location = self::locationScore($reported, $candidate);
        $daysApart = abs(
            (new \DateTimeImmutable((string) $reported['event_date']))->diff(
                new \DateTimeImmutable((string) $candidate['event_date'])
            )->days
        );
        $time = max(0.0, 1.0 - ($daysApart / 14.0));
        $text = self::textScore((string) $reported['description'], (string) $candidate['description']);

        return round(($category * 30.0) + ($location * 25.0) + ($time * 15.0) + ($text * 30.0), 2);
    }

    public static function locationScore(array $first, array $second): float
    {
        if ((int) $first['venue_id'] === (int) $second['venue_id']) {
            return 1.0;
        }

        return isset($first['building'], $second['building'])
            && strcasecmp(trim((string) $first['building']), trim((string) $second['building'])) === 0
            ? 0.5
            : 0.0;
    }

    public static function textScore(string $first, string $second): float
    {
        $normalize = static function (string $text): string {
            $text = strtolower(trim($text));
            $text = preg_replace('/\s+/u', ' ', $text);
            return is_string($text) ? $text : strtolower(trim($text));
        };

        $first = $normalize($first);
        $second = $normalize($second);
        if ($first === '' || $second === '') {
            return 0.0;
        }
        if ($first === $second) {
            return 1.0;
        }

        if (strlen($first) <= 255 && strlen($second) <= 255) {
            $distance = levenshtein($first, $second);
            return max(0.0, 1.0 - ($distance / max(strlen($first), strlen($second))));
        }

        similar_text(substr($first, 0, 2000), substr($second, 0, 2000), $percentage);
        return $percentage / 100.0;
    }
}
