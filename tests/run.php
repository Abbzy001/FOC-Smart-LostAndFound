<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Services/MatchEngine.php';

use App\Services\MatchEngine;

$checks = 0;
$assert = static function (bool $condition, string $message) use (&$checks): void {
    $checks++;
    if (!$condition) {
        throw new RuntimeException("Test failed: {$message}");
    }
};

$base = [
    'category_id' => 1,
    'venue_id' => 1,
    'building' => 'Computing',
    'event_date' => '2026-11-20',
    'description' => 'Black laptop with a silver sticker',
];

$assert(MatchEngine::score($base, $base) === 100.0, 'identical details score 100');
$differentCategory = $base;
$differentCategory['category_id'] = 2;
$assert(MatchEngine::score($base, $differentCategory) === 70.0, 'category weight is 30 percent');
$sameBuilding = $base;
$sameBuilding['venue_id'] = 2;
$assert(MatchEngine::locationScore($base, $sameBuilding) === 0.5, 'same building location score is 0.5');
$differentBuilding = $sameBuilding;
$differentBuilding['building'] = 'Library';
$assert(MatchEngine::locationScore($base, $differentBuilding) === 0.0, 'different building location score is zero');
$fourteenDays = $base;
$fourteenDays['event_date'] = '2026-11-06';
$assert(MatchEngine::score($base, $fourteenDays) === 85.0, 'fourteen-day temporal decay contributes zero');
$assert(MatchEngine::textScore('  Blue   bottle ', 'blue bottle') === 1.0, 'text comparison normalizes case and spacing');
$assert(MatchEngine::textScore('', 'blue bottle') === 0.0, 'empty text does not produce a match');
$assert(MatchEngine::DISPLAY_THRESHOLD === 50.0, 'match display threshold is 50 percent');

fwrite(STDOUT, "Passed {$checks} match-engine checks.\n");
