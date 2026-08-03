<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/class-public-url.php';
require_once dirname(__DIR__) . '/includes/class-normalized-timeline-item.php';

use Sabri\PublicExperience\Normalized_Timeline_Item;

$item = new Normalized_Timeline_Item([
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
    'available_actions' => ['read', 'share', 'delete', 'admin-edit', 'READ', 'watch', 123],
]);

$actions = $item->get('available_actions');
if ($actions !== ['read', 'share', 'watch']) {
    fwrite(STDERR, 'Review 32 failed: unexpected public action projection: ' . json_encode($actions) . "\n");
    exit(1);
}
if (in_array('delete', $actions, true) || in_array('admin-edit', $actions, true)) {
    fwrite(STDERR, "Review 32 failed: an ungoverned or privileged action survived normalization.\n");
    exit(1);
}

echo "Review 32 timeline action allow-list checks passed.\n";
