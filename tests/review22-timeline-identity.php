<?php

declare(strict_types=1);

$source = file_get_contents(__DIR__ . '/../includes/class-timeline-service.php');
if (! is_string($source)) {
    fwrite(STDERR, "Unable to read Timeline_Service source.\n");
    exit(1);
}

$required = [
    'Public_URL::sanitize_same_site($url, false)',
    '$port_number === $default_port ? \'\' : \':\' . $port_number',
    'strtolower(rtrim((string) ($parts[\'host\'] ?? \'\'), \'.\'))',
    'strcmp((string) $right->get(\'published_at\'), (string) $left->get(\'published_at\'))',
    '(string) $left->get(\'provider_id\')',
    '(string) $left->get(\'native_object_type\')',
    '(string) $left->get(\'native_object_id\')',
    'return $detected && $filtered;',
    '$path === \'/\' ? \'/\' : rtrim($path, \'/\')',
];
foreach ($required as $needle) {
    if (! str_contains($source, $needle)) {
        fwrite(STDERR, "Missing review-22 deterministic timeline marker: {$needle}\n");
        exit(1);
    }
}

if (str_contains($source, 'strtotime(')) {
    fwrite(STDERR, "Permissive timeline date parsing remains.\n");
    exit(1);
}

echo "Review 22 canonical timeline identity checks passed.\n";
