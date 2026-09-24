<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/class-public-url.php';
require_once dirname(__DIR__) . '/includes/class-normalized-timeline-item.php';

use Sabri\PublicExperience\Normalized_Timeline_Item;

$base = [
    'provider_id' => 'file-21',
    'provider_version' => '1.0.0',
    'native_object_type' => 'publication',
    'native_object_id' => '1',
    'author_id' => 7,
    'public_profile_id' => 7,
    'title' => 'Public item',
    'safe_excerpt' => 'Excerpt',
    'canonical_url' => 'https://example.test/publication/1',
    'published_at' => '2026-08-03T10:20:30Z',
    'visibility_state' => 'public',
    'native_status' => 'published',
    'content_type' => 'post',
    'review_state' => 'published',
];

$valid = new Normalized_Timeline_Item($base + ['updated_at' => '2026-08-03T11:20:30+00:00']);
if ($valid->get('updated_at') !== '2026-08-03T11:20:30Z') {
    fwrite(STDERR, "Review 33 failed: valid update timestamp was not normalized.\n");
    exit(1);
}

$equal = new Normalized_Timeline_Item($base + ['updated_at' => '2026-08-03T10:20:30Z']);
if ($equal->get('updated_at') !== $equal->get('published_at')) {
    fwrite(STDERR, "Review 33 failed: equal publication/update timestamps were not accepted.\n");
    exit(1);
}

$rejected = false;
try {
    new Normalized_Timeline_Item($base + ['updated_at' => '2026-08-03T09:20:30Z']);
} catch (InvalidArgumentException) {
    $rejected = true;
}
if (! $rejected) {
    fwrite(STDERR, "Review 33 failed: update timestamp preceding publication was accepted.\n");
    exit(1);
}

echo "Review 33 timeline chronology checks passed.\n";
