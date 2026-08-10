<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$failures = [];
$check = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) {
        $failures[] = $message;
    }
};

$design = file_get_contents($root . '/includes/class-design-system.php');
$central = file_get_contents($root . '/includes/class-central-plan-2026-corrections.php');
$depsRaw = file_get_contents($root . '/config/staging-dependencies.json');

$check(is_string($design), 'Review 177: design-system source must be readable.');
$check(is_string($central), 'Review 177: central-plan correction source must be readable.');
$check(is_string($depsRaw), 'Review 179: staging dependency matrix must be readable.');

if (is_string($design)) {
    $check(str_contains($design, "'color-primary' => self::token('--sabri-visual-primary', '', '#087A4E')"), 'Review 177: File 25 canonical primary token must be Sabri Green #087A4E.');
    $check(! str_contains(strtolower($design), '#ff8a1f'), 'Review 177: historical orange primary fallback must be removed from the canonical File 25 design contract.');
    foreach (['--sabri-shell-primary', '--sabri-shell-text', '--sabri-shell-muted', '--sabri-shell-surface', '--sabri-shell-bg', '--sabri-shell-border', '--sabri-shell-focus', '--sabri-shell-success', '--sabri-shell-warning', '--sabri-shell-danger', '--sabri-shell-radius', '--sabri-shell-gap', '--sabri-shell-font-scale'] as $staleVisualInheritance) {
        $check(! str_contains($design, $staleVisualInheritance), 'Review 178: File 25 visual tokens must not inherit competing File 20 visual token ' . $staleVisualInheritance . '.');
    }
    $check(str_contains($design, "'content-wide' => self::token('--sabri-visual-content-wide', '--sabri-shell-max-width', '100rem')"), 'Review 178: structural content width may continue to consume File 20 shell geometry.');
}

if (is_string($central)) {
    $check(str_contains($central, "public const PRIMARY_GREEN = '#087A4E';"), 'Review 177: latest correction layer must freeze #087A4E.');
    $check(str_contains($central, "\$base['design_token_owner'] = 'file-25';"), 'Review 178: contract must declare File 25 as canonical design-token owner.');
    $check(str_contains($central, "\$base['structural_layout_owner'] = 'file-20';"), 'Review 178: File 20 structural shell ownership must remain explicit.');
    $check(str_contains($central, "'duplicate_visual_settings_allowed' => false"), 'Review 178: duplicate canonical visual settings must be prohibited.');
}

$deps = is_string($depsRaw) ? json_decode($depsRaw, true) : null;
$check(is_array($deps), 'Review 179: staging dependency matrix must be valid JSON.');
$modules = [];
if (is_array($deps)) {
    foreach (($deps['modules'] ?? []) as $module) {
        if (is_array($module) && isset($module['file'])) {
            $modules[(int) $module['file']] = $module;
        }
    }
}

$file22 = $modules[22] ?? [];
$file23 = $modules[23] ?? [];
$file25 = $modules[25] ?? [];
$check(($file22['reviewed_source_version'] ?? '') === '0.4.0', 'Review 179: File 22 reviewed source version must be 0.4.0.');
$check(($file22['reviewed_source_commit'] ?? '') === '9d749a8e74910aa192c69a3727f5bf2f920af763', 'Review 179: File 22 exact reviewed commit must be frozen.');
$check(($file22['canonical_public_create_route'] ?? '') === '/create/', 'Review 179: File 22 canonical public create route must be frozen.');
$check(($file22['staging_status'] ?? '') === 'pending', 'Review 179: File 22 staging acceptance must remain pending.');
$check(($file23['reviewed_source_version'] ?? '') === '1.2.0', 'Review 179: File 23 reviewed source version must be 1.2.0.');
$check(($file23['reviewed_source_commit'] ?? '') === 'a8a8c805f4730998ccb44bd95c87591836561759', 'Review 179: File 23 exact reviewed commit must be frozen.');
$check(($file23['canonical_private_management_route'] ?? '') === '/publishing-dashboard/', 'Review 179: File 23 canonical private management route must be frozen.');
$check(($file23['staging_status'] ?? '') === 'pending', 'Review 179: File 23 staging acceptance must remain pending.');
$check(($file25['canonical_primary_color'] ?? '') === '#087A4E', 'Review 177: staging contract must freeze File 25 primary color #087A4E.');
$check(($file25['design_token_owner'] ?? '') === 'file-25', 'Review 178: staging contract must freeze File 25 token ownership.');
$check(($file25['structural_shell_owner'] ?? '') === 'file-20', 'Review 178: staging contract must preserve File 20 structural shell ownership.');

if ($failures !== []) {
    fwrite(STDERR, "FAILED Reviews 177-179\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "PASS: Reviews 177-179 latest central-plan reconciliation\n";
