<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$failures = [];
$check = static function (bool $condition, string $message) use (&$failures): void { if (! $condition) { $failures[] = $message; } };
$design = (string) file_get_contents($root . '/includes/class-design-system.php');
$central = (string) file_get_contents($root . '/includes/class-central-plan-2026-corrections.php');
$deps = json_decode((string) file_get_contents($root . '/config/staging-dependencies.json'), true);

$check(str_contains($design, "'color-primary' => self::token('--sabri-visual-primary', '', '#087A4E')"), 'Review 177: canonical primary must be #087A4E.');
$check(! str_contains(strtolower($design), '#ff8a1f'), 'Review 177: historical orange primary must not remain canonical.');
foreach (['--sabri-shell-primary','--sabri-shell-text','--sabri-shell-muted','--sabri-shell-surface','--sabri-shell-bg','--sabri-shell-border','--sabri-shell-focus','--sabri-shell-radius','--sabri-shell-gap','--sabri-shell-font-scale'] as $stale) {
    $check(! str_contains($design, $stale), 'Review 178: File 25 visual token may not inherit ' . $stale);
}
$check(str_contains($design, "'content-wide' => self::token('--sabri-visual-content-wide', '--sabri-shell-max-width', '100rem')"), 'Review 178: structural width may consume File 20 geometry.');
$check(str_contains($central, "public const PRIMARY_GREEN = '#087A4E';"), 'Review 177: correction layer must freeze #087A4E.');
$check(str_contains($central, "\$base['design_token_owner'] = 'file-25';"), 'Review 178: File 25 token ownership missing.');
$check(str_contains($central, "\$base['structural_layout_owner'] = 'file-20';"), 'Review 178: File 20 structural ownership missing.');

$check(is_array($deps) && ($deps['schema_version'] ?? null) === 3, 'Review 179: current staging matrix schema 3 required.');
$modules = [];
foreach ((array) ($deps['modules'] ?? []) as $module) { if (is_array($module) && isset($module['file'])) { $modules[(int) $module['file']] = $module; } }
$file22 = $modules[22] ?? [];
$file23 = $modules[23] ?? [];
$file25 = $modules[25] ?? [];
$check(($file22['reviewed_source_version'] ?? '') === '1.0.0-rc.3', 'Review 179: File 22 reviewed source must be 1.0.0-rc.3.');
$check(($file22['reviewed_source_commit'] ?? '') === 'c3b775b66fbbda4a9dd9891d63c08c74e2178741', 'Review 179: File 22 exact reviewed head must be current.');
$check(($file22['required_contract_versions']['rest_api'] ?? '') === '1.2.0', 'Review 179: File 22 REST contract 1.2.0 required.');
$check(($file22['canonical_public_create_route'] ?? '') === '/create/', 'Review 179: File 22 create route must stay canonical.');
$check(($file22['staging_status'] ?? '') === 'pending', 'Review 179: File 22 staging remains pending.');
$check(($file23['reviewed_source_version'] ?? '') === '1.2.0', 'Review 179: File 23 stable source must be 1.2.0.');
$check(($file23['reviewed_source_commit'] ?? '') === 'a8a8c805f4730998ccb44bd95c87591836561759', 'Review 179: File 23 stable main head mismatch.');
$check(($file23['canonical_private_management_route'] ?? '') === '/publishing-dashboard/', 'Review 179: File 23 private route must stay canonical.');
$check(($file23['future_intelligence_branch']['head'] ?? '') === '50b9489a4a058d4628ef5dda220837393dd32010', 'Review 179: File 23 FPI24 branch truth missing.');
$check(($file23['staging_status'] ?? '') === 'pending', 'Review 179: File 23 staging remains pending.');
$check(($file25['canonical_primary_color'] ?? '') === '#087A4E' && ($file25['design_token_owner'] ?? '') === 'file-25' && ($file25['structural_shell_owner'] ?? '') === 'file-20', 'Reviews 177-178: File 25/File 20 visual ownership mismatch.');

if ($failures !== []) { fwrite(STDERR, "FAILED Reviews 177-179\n- " . implode("\n- ", $failures) . "\n"); exit(1); }
echo "PASS: Reviews 177-179 current central-plan reconciliation\n";
