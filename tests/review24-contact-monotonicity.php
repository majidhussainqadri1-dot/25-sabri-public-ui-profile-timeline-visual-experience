<?php

declare(strict_types=1);

$source = file_get_contents(__DIR__ . '/../includes/class-profile-repository.php');
if (! is_string($source)) {
    fwrite(STDERR, "Unable to read Profile_Repository source.\n");
    exit(1);
}

$required = [
    '$canonical = $this->sanitize_contacts($values, $user_id);',
    'apply_filters(\'sabri_public_experience/public_contacts\', $canonical, $user_id)',
    'foreach ($canonical as $field => $value)',
    'array_key_exists($field, $filtered) && (bool) $filtered[$field]',
    '$public[$field] = $value;',
    '$founder[\'phone\'] ?? $this->native->profile_value($user_id, \'phone\')',
    '$founder[\'whatsapp\'] ?? $this->native->profile_value($user_id, \'whatsapp\')',
];
foreach ($required as $needle) {
    if (! str_contains($source, $needle)) {
        fwrite(STDERR, "Missing review-24 contact monotonicity marker: {$needle}\n");
        exit(1);
    }
}

$forbidden = [
    '$clinic[\'phone\']',
    '$clinic[\'whatsapp\']',
    'return $this->sanitize_contacts($filtered, $user_id);',
];
foreach ($forbidden as $needle) {
    if (str_contains($source, $needle)) {
        fwrite(STDERR, "Forbidden contact substitution path remains: {$needle}\n");
        exit(1);
    }
}

echo "Review 24 monotonic public-contact checks passed.\n";
