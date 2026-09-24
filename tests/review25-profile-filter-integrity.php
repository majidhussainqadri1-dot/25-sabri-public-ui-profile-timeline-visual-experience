<?php

declare(strict_types=1);

$source = file_get_contents(__DIR__ . '/../includes/class-profile-repository.php');
if (! is_string($source)) {
    fwrite(STDERR, "Unable to read Profile_Repository source.\n");
    exit(1);
}

$required = [
    '$profile[\'display_name\'] = $profile_class === \'founder\'',
    ': $this->plain_text($default_name, 190);',
    '$this->monotonic_media_filter(',
    'hash_equals($canonical, $candidate) ? $canonical : \'\'',
    '$filtered[\'headline\'] ?? $profile[\'headline\']',
    '$filtered[\'bio\'] ?? $profile[\'bio\']',
];
foreach ($required as $needle) {
    if (! str_contains($source, $needle)) {
        fwrite(STDERR, "Missing review-25 immutable profile marker: {$needle}\n");
        exit(1);
    }
}

$forbidden = [
    '$filtered[\'display_name\']',
    "Public_URL::sanitize_same_site(\n            \$filtered['avatar_url']",
    "Public_URL::sanitize_same_site(\n            \$filtered['cover_url']",
];
foreach ($forbidden as $needle) {
    if (str_contains($source, $needle)) {
        fwrite(STDERR, "Profile filter can still replace canonical identity or media: {$needle}\n");
        exit(1);
    }
}

echo "Review 25 immutable identity and media checks passed.\n";
