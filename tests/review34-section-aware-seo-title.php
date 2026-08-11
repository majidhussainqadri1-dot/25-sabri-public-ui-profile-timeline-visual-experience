<?php

declare(strict_types=1);

define('ABSPATH', __DIR__ . '/');
function sanitize_key(string $value): string { return trim((string) preg_replace('/[^a-z0-9_\-]/', '-', strtolower($value)), '-'); }
function wp_strip_all_tags(string $text): string { return strip_tags($text); }
require_once dirname(__DIR__) . '/includes/class-profile-renderer.php';

$reflection = new ReflectionClass(\Sabri\PublicExperience\Profile_Renderer::class);
$renderer = $reflection->newInstanceWithoutConstructor();
$method = $reflection->getMethod('page_title');
$method->setAccessible(true);
$profile = [
    'display_name' => '<b>Dr. Example</b>',
    'section_labels' => [
        'overview' => 'Overview',
        'timeline' => '<i>Timeline</i>',
        'knowledge' => 'Knowledge',
    ],
];

$cases = [
    ['overview', 'Dr. Example'],
    ['timeline', 'Timeline — Dr. Example'],
    ['knowledge', 'Knowledge — Dr. Example'],
    ['unknown', 'Dr. Example'],
];
foreach ($cases as [$section, $expected]) {
    $actual = $method->invoke($renderer, $profile, ['section' => $section]);
    if ($actual !== $expected) {
        fwrite(STDERR, "Review 34 failed for {$section}: expected {$expected}, got {$actual}.\n");
        exit(1);
    }
}

$source = file_get_contents(dirname(__DIR__) . '/includes/class-profile-renderer.php') ?: '';
foreach (["esc_attr(\$page_title)", "'name' => \$page_title"] as $marker) {
    if (! str_contains($source, $marker)) {
        fwrite(STDERR, "Review 34 SEO marker missing: {$marker}\n");
        exit(1);
    }
}

echo "Review 34 section-aware profile SEO title checks passed.\n";
