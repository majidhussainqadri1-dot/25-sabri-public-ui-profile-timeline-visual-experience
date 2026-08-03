<?php

declare(strict_types=1);

namespace {
    define('ABSPATH', __DIR__ . '/fixtures/');
    require_once dirname(__DIR__) . '/includes/class-public-url.php';
    require_once dirname(__DIR__) . '/includes/class-normalized-timeline-item.php';
    $base = [
        'provider_id' => 'owner-provider', 'provider_version' => '1.0.0',
        'native_object_type' => 'post', 'native_object_id' => '1',
        'author_id' => 7, 'public_profile_id' => 7, 'title' => 'Title',
        'safe_excerpt' => 'Excerpt', 'canonical_url' => 'https://example.test/post/1/',
        'published_at' => '2026-08-03T12:00:00Z', 'visibility_state' => 'public',
        'native_status' => 'publish', 'content_type' => 'post', 'language' => 'en-US',
        'review_state' => 'published',
    ];
    $without = new \Sabri\PublicExperience\Normalized_Timeline_Item($base);
    if ($without->get('updated_at') !== null) { fwrite(STDERR, "FAILED: Absent optional date was not null.\n"); exit(1); }
    try {
        new \Sabri\PublicExperience\Normalized_Timeline_Item(array_merge($base, ['updated_at' => 'tomorrow']));
        fwrite(STDERR, "FAILED: Invalid supplied optional date was silently dropped.\n"); exit(1);
    } catch (\InvalidArgumentException) {}
    echo "PASS: Review 50 invalid optional timeline date fails closed\n";
}
