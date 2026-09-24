<?php

declare(strict_types=1);

define('ABSPATH', __DIR__ . '/');
function home_url(string $path = '/'): string { return 'https://example.test' . ($path === '' ? '/' : $path); }
function esc_url_raw(string $url, array $protocols = []): string { return $url; }
function wp_strip_all_tags(string $text, bool $remove_breaks = false): string { return strip_tags($text); }
require_once dirname(__DIR__) . '/includes/class-public-url.php';
require_once dirname(__DIR__) . '/includes/class-content-cards.php';

use Sabri\PublicExperience\Content_Cards;

$card = Content_Cards::normalize_public([
    'title' => 'Accessible public image',
    'url' => 'https://example.test/item',
    'image_url' => 'https://example.test/media/image.webp',
    'image_alt' => '',
]);
if (! is_array($card) || ($card['image_alt'] ?? '') !== 'Accessible public image') {
    fwrite(STDERR, "Review 31 failed: meaningful image did not inherit a bounded title alternative.\n");
    exit(1);
}

$explicit = Content_Cards::normalize_public([
    'title' => 'Card title',
    'image_url' => 'https://example.test/media/image.webp',
    'image_alt' => '<b>Specific alternative</b>',
]);
if (($explicit['image_alt'] ?? '') !== 'Specific alternative') {
    fwrite(STDERR, "Review 31 failed: explicit image alternative was not sanitized and retained.\n");
    exit(1);
}

$rejected = Content_Cards::normalize_public([
    'title' => 'Card title',
    'image_url' => 'https://evil.example/image.webp',
    'image_alt' => 'Must not survive without an image',
]);
if (($rejected['image_url'] ?? '') !== '' || ($rejected['image_alt'] ?? '') !== '') {
    fwrite(STDERR, "Review 31 failed: image alternative survived a rejected image URL.\n");
    exit(1);
}

echo "Review 31 content-card image alternative checks passed.\n";
