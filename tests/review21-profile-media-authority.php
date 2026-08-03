<?php

declare(strict_types=1);

$source = file_get_contents(__DIR__ . '/../includes/class-profile-repository.php');
if (! is_string($source)) {
    fwrite(STDERR, "Unable to read Profile_Repository source.\n");
    exit(1);
}

$required = [
    "MEDIA_OWNER_META = '_spd_media_owner_user_id'",
    "MEDIA_PURPOSE_META = '_spd_media_purpose'",
    "ALLOWED_IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/webp']",
    'MAX_IMAGE_PIXELS = 40000000',
    'get_post_type($attachment_id) !== \'attachment\'',
    '! wp_attachment_is_image($attachment_id)',
    'get_post_meta($attachment_id, self::MEDIA_OWNER_META, true)',
    'get_post_meta($attachment_id, self::MEDIA_PURPOSE_META, true)',
    'get_post_mime_type($attachment_id)',
    'wp_get_attachment_metadata($attachment_id)',
    '$width * $height > self::MAX_IMAGE_PIXELS',
    'owned_media_url($photo_id, $user_id, \'profile\', \'medium\')',
    'owned_media_url($cover_id, $user_id, \'cover\', \'large\')',
];

foreach ($required as $needle) {
    if (! str_contains($source, $needle)) {
        fwrite(STDERR, "Missing review-21 media authority marker: {$needle}\n");
        exit(1);
    }
}

$forbidden = [
    '$photo_id > 0 ? wp_get_attachment_image_url',
    '$cover_id > 0 ? wp_get_attachment_image_url',
];
foreach ($forbidden as $needle) {
    if (str_contains($source, $needle)) {
        fwrite(STDERR, "Unowned direct media projection remains: {$needle}\n");
        exit(1);
    }
}

echo "Review 21 File 03 media authority checks passed.\n";
