<?php

declare(strict_types=1);

define('ABSPATH', __DIR__ . '/');
function home_url(string $path = '/'): string { return 'https://example.test' . ($path === '' ? '/' : $path); }
function esc_url_raw(string $url, array $protocols = []): string { return $url; }
function wp_strip_all_tags(string $text, bool $remove_breaks = false): string { return strip_tags($text); }
require_once dirname(__DIR__) . '/includes/class-public-url.php';
require_once dirname(__DIR__) . '/includes/class-content-cards.php';
require_once dirname(__DIR__) . '/includes/class-section-service.php';

use Sabri\PublicExperience\Content_Cards;
use Sabri\PublicExperience\Section_Service;

$method = new ReflectionMethod(Section_Service::class, 'candidate_key');
$method->setAccessible(true);

$first_candidate = [
    'type' => 'article',
    'title' => 'Shared title',
    'published_at' => '2026-08-03',
    'excerpt' => 'First distinct public projection',
];
$second_candidate = $first_candidate;
$second_candidate['excerpt'] = 'Second distinct public projection';
$first_card = Content_Cards::normalize_public($first_candidate);
$second_card = Content_Cards::normalize_public($second_candidate);
if (! is_array($first_card) || ! is_array($second_card)) {
    fwrite(STDERR, "Review 38 failed: valid test cards were not normalized.\n");
    exit(1);
}
$key_one = $method->invoke(null, $first_candidate, $first_card);
$key_two = $method->invoke(null, $second_candidate, $second_card);
if ($key_one === '' || $key_two === '' || $key_one === $key_two) {
    fwrite(STDERR, "Review 38 failed: distinct title/date peers collapsed to one fallback identity.\n");
    exit(1);
}

$duplicate_card = Content_Cards::normalize_public($first_candidate);
if ($method->invoke(null, $first_candidate, $duplicate_card) !== $key_one) {
    fwrite(STDERR, "Review 38 failed: identical normalized public cards did not deduplicate.\n");
    exit(1);
}

$projection = str_repeat('a', 64);
$projected_one = $first_candidate + ['projection_key' => $projection];
$projected_two = $second_candidate + ['projection_key' => $projection];
if ($method->invoke(null, $projected_one, $first_card) !== $method->invoke(null, $projected_two, $second_card)) {
    fwrite(STDERR, "Review 38 failed: authoritative projection key did not remain the strongest identity.\n");
    exit(1);
}

$url_one = $first_candidate + ['url' => 'https://example.test/item/'];
$url_two = $second_candidate + ['url' => 'https://EXAMPLE.test:443/item'];
$url_card_one = Content_Cards::normalize_public($url_one);
$url_card_two = Content_Cards::normalize_public($url_two);
if ($method->invoke(null, $url_one, $url_card_one) !== $method->invoke(null, $url_two, $url_card_two)) {
    fwrite(STDERR, "Review 38 failed: canonical URL identity did not deduplicate equivalent destinations.\n");
    exit(1);
}

echo "Review 38 normalized public-card deduplication checks passed.\n";
