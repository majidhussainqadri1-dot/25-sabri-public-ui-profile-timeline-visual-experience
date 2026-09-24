<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$errors = [];

$required = [
    'includes/class-timeline-registry.php',
    'includes/class-timeline-service.php',
    'includes/class-section-registry.php',
    'includes/class-section-service.php',
    'tests/timeline-provider-metadata.php',
    'tests/section-provider-metadata.php',
];
foreach ($required as $path) {
    if (! is_file($root . '/' . $path)) {
        $errors[] = 'Missing immutable-provider artifact: ' . $path;
    }
}

$timeline_registry = file_get_contents($root . '/includes/class-timeline-registry.php') ?: '';
foreach ([
    'registered_metadata',
    "'object_id' => spl_object_id(\$provider)",
    'validated_metadata',
    "\$registered['object_id'] === \$current['object_id']",
    "hash_equals(\$registered['version'], \$current['version'])",
    "hash_equals(\$registered['maturity'], \$current['maturity'])",
] as $marker) {
    if (! str_contains($timeline_registry, $marker)) {
        $errors[] = 'Timeline provider immutability marker missing: ' . $marker;
    }
}

$timeline_service = file_get_contents($root . '/includes/class-timeline-service.php') ?: '';
foreach (['validated_metadata', "\$metadata['maturity'] === 'disabled'", "\$provider_version = \$metadata['version']"] as $marker) {
    if (! str_contains($timeline_service, $marker)) {
        $errors[] = 'Timeline atomic metadata consumption marker missing: ' . $marker;
    }
}
if (str_contains($timeline_service, '$provider->get_provider_version()') || str_contains($timeline_service, '$provider->get_maturity_level()')) {
    $errors[] = 'Timeline service must consume the atomic registry snapshot instead of re-reading mutable metadata.';
}

$section_registry = file_get_contents($root . '/includes/class-section-registry.php') ?: '';
foreach ([
    'registered_metadata',
    "'object_id' => spl_object_id(\$provider)",
    'validated_metadata',
    "\$registered['object_id'] === \$current['object_id']",
    "hash_equals(\$registered['version'], \$current['version'])",
    "hash_equals(\$registered['maturity'], \$current['maturity'])",
] as $marker) {
    if (! str_contains($section_registry, $marker)) {
        $errors[] = 'Section provider immutability marker missing: ' . $marker;
    }
}

$section_service = file_get_contents($root . '/includes/class-section-service.php') ?: '';
foreach (['validated_metadata', "\$metadata['maturity'] === 'disabled'", 'projection_key'] as $marker) {
    if (! str_contains($section_service, $marker)) {
        $errors[] = 'Section atomic metadata consumption marker missing: ' . $marker;
    }
}
if (str_contains($section_service, '$provider->get_maturity_level()') || str_contains($section_service, '$provider->owns_native_content()')) {
    $errors[] = 'Section service must consume the atomic registry snapshot instead of re-reading mutable metadata.';
}

if ($errors !== []) {
    fwrite(STDERR, "FAILED\n- " . implode("\n- ", $errors) . "\n");
    exit(1);
}

echo "PASS: concrete-object provider identity and atomic metadata package contracts\n";
