<?php

declare(strict_types=1);

define('ABSPATH', __DIR__ . '/');
function __(string $text, string $domain = ''): string { return $text; }
function sanitize_key(string $value): string
{
    return trim((string) preg_replace('/[^a-z0-9_\-]/', '-', strtolower($value)), '-');
}
function sanitize_text_field(string $value): string { return trim(strip_tags($value)); }
function wp_unslash(string $value): string { return stripslashes($value); }
function apply_filters(string $hook, mixed $value, mixed ...$args): mixed
{
    if ($hook === 'sabri_public_experience/timeline_filters') {
        $value['video'] = 'Videos';
        $value['Bad Key'] = 'Rejected';
    }
    return $value;
}

require_once dirname(__DIR__) . '/includes/class-profile-renderer.php';

$reflection = new ReflectionClass(\Sabri\PublicExperience\Profile_Renderer::class);
$renderer = $reflection->newInstanceWithoutConstructor();
$filters_method = $reflection->getMethod('timeline_filters');
$filters_method->setAccessible(true);
$request_method = $reflection->getMethod('requested_timeline_content_type');
$request_method->setAccessible(true);

$filters = $filters_method->invoke($renderer, ['class' => 'doctor']);
if (! isset($filters[''], $filters['post'], $filters['video']) || isset($filters['bad-key'])) {
    fwrite(STDERR, "Review 28 failed: timeline filter allow-list normalization is incorrect.\n");
    exit(1);
}

foreach ([
    'post' => 'post',
    'video' => 'video',
    'unknown' => '',
    'POST' => '',
    'post<script>' => '',
    str_repeat('a', 65) => '',
] as $input => $expected) {
    $_GET['type'] = $input;
    $actual = $request_method->invoke($renderer, $filters);
    if ($actual !== $expected) {
        fwrite(STDERR, "Review 28 failed for timeline filter {$input}: expected {$expected}, got {$actual}.\n");
        exit(1);
    }
}

unset($_GET['type']);
if ($request_method->invoke($renderer, $filters) !== '') {
    fwrite(STDERR, "Review 28 failed: absent filter must resolve to All.\n");
    exit(1);
}

echo "Review 28 allow-listed timeline filter checks passed.\n";
