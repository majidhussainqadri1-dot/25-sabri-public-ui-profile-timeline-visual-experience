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
        'native_status' => 'publish', 'content_type' => 'post', 'language' => 'ur-PK',
        'review_state' => 'published',
    ];
    new \Sabri\PublicExperience\Normalized_Timeline_Item($base);
    $bad = [
        ['provider_id' => 'Owner-Provider'],
        ['native_object_type' => 'Post Type'],
        ['native_object_id' => ' 1'],
        ['content_type' => 'POST'],
        ['native_status' => 'Published '],
        ['review_state' => 'Published'],
        ['language' => 'ur_PK'],
        ['language' => 'en--US'],
    ];
    foreach ($bad as $override) {
        try {
            new \Sabri\PublicExperience\Normalized_Timeline_Item(array_merge($base, $override));
            fwrite(STDERR, 'FAILED: Timeline identity/language alias was accepted: ' . json_encode($override) . "\n");
            exit(1);
        } catch (\InvalidArgumentException) {}
    }
    echo "PASS: Review 49 exact timeline identity and language integrity\n";
}
