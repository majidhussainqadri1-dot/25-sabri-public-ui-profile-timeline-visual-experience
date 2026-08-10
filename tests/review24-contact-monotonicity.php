<?php

declare(strict_types=1);

$source = file_get_contents(__DIR__ . '/../includes/class-profile-repository.php');
if (! is_string($source)) { fwrite(STDERR, "Unable to read Profile_Repository source.\n"); exit(1); }

$required = [
    "foreach (['phone', 'whatsapp'] as \$field)",
    'self::canonical_contact($this->native->profile_contact($user_id, $field))',
    '$this->visibility->can_show_contact($user_id, $field)',
    '$visibility = array_fill_keys(array_keys($canonical), true);',
    "apply_filters('sabri_public_experience/public_contacts', \$visibility, \$user_id, \$canonical)",
    'foreach ($canonical as $field => $value)',
    'array_key_exists($field, $filtered) && $filtered[$field] === true',
    '$public[$field] = $value;',
];
foreach ($required as $needle) {
    if (! str_contains($source, $needle)) { fwrite(STDERR, "Missing review-24 contact monotonicity marker: {$needle}\n"); exit(1); }
}

$forbidden = [
    '$clinic[\'phone\']', '$clinic[\'whatsapp\']',
    '$founder[\'phone\'] ?? $this->native->profile_value',
    '$founder[\'whatsapp\'] ?? $this->native->profile_value',
    'return $this->sanitize_contacts($filtered, $user_id);',
];
foreach ($forbidden as $needle) {
    if (str_contains($source, $needle)) { fwrite(STDERR, "Forbidden contact substitution/legacy path remains: {$needle}\n"); exit(1); }
}

echo "Review 24 current File 03 contact monotonicity checks passed.\n";
