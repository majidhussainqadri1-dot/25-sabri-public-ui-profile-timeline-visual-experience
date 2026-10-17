<?php
declare(strict_types=1);

// File25 R1435-1474: prevent undefined class references in preview links.
$template = file_get_contents(dirname(__DIR__) . '/templates/public-profile.php');
if (! is_string($template)) {
    fwrite(STDERR, "Template missing\n");
    exit(1);
}
$bad = 'SabriPublicExperiencePublic_URL::sanitize_same_site';
$good = '\\Sabri\\PublicExperience\\Public_URL::sanitize_same_site';
$valid = ! str_contains($template, $bad)
    && substr_count($template, $good) >= 5
    && str_contains($template, 'spux-preview-toolbar')
    && str_contains($template, 'spux-completion-assistant');
if (! $valid) {
    fwrite(STDERR, "Preview URL class resolution regression\n");
    exit(1);
}
echo "PASS R1435-1474 preview URL class resolution\n";
