<?php

declare(strict_types=1);

$source = file_get_contents(__DIR__ . '/../includes/class-profile-repository.php');
if (! is_string($source)) { fwrite(STDERR, "Unable to read Profile_Repository source.\n"); exit(1); }

$required = [
    "MEDIA_OWNER_META = '_spd_media_owner_user_id'",
    "MEDIA_PURPOSE_META = '_spd_media_purpose'",
    "ALLOWED_IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/webp']",
    'MAX_IMAGE_PIXELS = 40000000',
    "profile_media_attachment_id(\$user_id, 'avatar')",
    "profile_media_attachment_id(\$user_id, 'cover')",
    "owned_media_url(\$photo_id, \$user_id, 'avatar', 'medium')",
    "owned_media_url(\$cover_id, \$user_id, 'cover', 'large')",
    "get_post_type(\$attachment_id) !== 'attachment'",
    '! wp_attachment_is_image($attachment_id)',
    'get_post_meta($attachment_id, self::MEDIA_OWNER_META, true)',
    'get_post_meta($attachment_id, self::MEDIA_PURPOSE_META, true)',
    'get_post_mime_type($attachment_id)',
    'wp_get_attachment_metadata($attachment_id)',
    '$width * $height > self::MAX_IMAGE_PIXELS',
];
foreach ($required as $needle) {
    if (! str_contains($source, $needle)) { fwrite(STDERR, "Missing review-21 media authority marker: {$needle}\n"); exit(1); }
}
foreach (["'_spd_profile_photo_id'", "'_spd_cover_photo_id'", "owned_media_url(\$photo_id, \$user_id, 'profile'", 'get_avatar_url', '$photo_id > 0 ? wp_get_attachment_image_url', '$cover_id > 0 ? wp_get_attachment_image_url'] as $needle) {
    if (str_contains($source, $needle)) { fwrite(STDERR, "Legacy/unowned media path remains: {$needle}\n"); exit(1); }
}

echo "Review 21 current File 03 public-media authority checks passed.\n";
