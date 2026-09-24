<?php

declare(strict_types=1);

$timeline = file_get_contents(__DIR__ . '/../includes/class-timeline-service.php');
$url_policy = file_get_contents(__DIR__ . '/../includes/class-public-url.php');
if (! is_string($timeline) || ! is_string($url_policy)) {
    fwrite(STDERR, "Unable to read timeline or URL-policy source.\n");
    exit(1);
}

$timeline_required = [
    'Public_URL::sanitize_same_site($url, false)',
    'Public_URL::canonical_same_site_identity($url, false)',
    'strcmp((string) $right->get(\'published_at\'), (string) $left->get(\'published_at\'))',
    '(string) $left->get(\'provider_id\')',
    '(string) $left->get(\'native_object_type\')',
    '(string) $left->get(\'native_object_id\')',
    'return $detected && $filtered;',
];
foreach ($timeline_required as $needle) {
    if (! str_contains($timeline, $needle)) {
        fwrite(STDERR, "Missing review-22 timeline marker: {$needle}\n");
        exit(1);
    }
}

$url_required = [
    '$port_number === $default_port ? \'\' : \':\' . $port_number',
    'strtolower(rtrim((string) ($parts[\'host\'] ?? \'\'), \'.\'))',
    '$path === \'/\' ? \'/\' : rtrim($path, \'/\')',
];
foreach ($url_required as $needle) {
    if (! str_contains($url_policy, $needle)) {
        fwrite(STDERR, "Missing review-22 shared URL marker: {$needle}\n");
        exit(1);
    }
}

if (str_contains($timeline, 'strtotime(')) {
    fwrite(STDERR, "Permissive timeline date parsing remains.\n");
    exit(1);
}

echo "Review 22 canonical timeline identity checks passed.\n";
