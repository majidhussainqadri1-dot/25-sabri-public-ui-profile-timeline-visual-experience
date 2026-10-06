<?php

declare(strict_types=1);

$repository = file_get_contents(__DIR__ . '/../includes/class-profile-repository.php');
$native = file_get_contents(__DIR__ . '/../includes/class-native-integration.php');
if (! is_string($repository) || ! is_string($native)) {
    fwrite(STDERR, "Unable to read current File 03 media consumer source.\n");
    exit(1);
}

/*
 * Historical Review 21 originally required File 03 attachment IDs. Current
 * File 03 contract 1.4.0 deliberately publishes bounded public media
 * URL/alt/focal fields without attachment_id, so the governing invariant is
 * now owner-approved DTO consumption, not a private-ID requirement.
 */
foreach ([
    "profile_media(\$user_id, 'avatar')",
    "profile_media(\$user_id, 'cover')",
    "'avatar_alt' => \$avatar_alt",
    "monotonic_media_filter(",
] as $needle) {
    if (! str_contains($repository, $needle)) {
        fwrite(STDERR, "Missing current review-21 media authority marker: {$needle}\n");
        exit(1);
    }
}

foreach ([
    'public function profile_media(int $user_id, string $purpose): array',
    "Public_URL::sanitize_same_site(\$row['url'] ?? '', false)",
    "\$row['alt'] ?? \$row['alt_text'] ?? ''",
    "max(0.0, min(100.0, \$focal_x))",
    "max(0.0, min(100.0, \$focal_y))",
] as $needle) {
    if (! str_contains($native, $needle)) {
        fwrite(STDERR, "Missing current File 03 DTO media marker: {$needle}\n");
        exit(1);
    }
}

foreach ([
    "'_spd_profile_photo_id'",
    "'_spd_cover_photo_id'",
    'get_avatar_url',
] as $needle) {
    if (str_contains($repository, $needle)) {
        fwrite(STDERR, "Legacy/unowned media path remains: {$needle}\n");
        exit(1);
    }
}

echo "Review 21 current File 03 public-media DTO authority checks passed.\n";
