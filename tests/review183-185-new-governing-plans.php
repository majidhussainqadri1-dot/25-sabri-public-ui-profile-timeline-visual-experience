<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$failures = [];
$check = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) { $failures[] = $message; }
};
$read = static fn (string $path): string => (string) file_get_contents($root . '/' . $path);

$design = $read('includes/class-design-system.php');
$central = $read('includes/class-central-plan-2026-corrections.php');
$css = $read('assets/css/design-system.css');
$publicCss = $read('assets/css/public.css');
$shell = $read('includes/class-shell-integration.php');
$hero = $read('templates/partials/profile-hero.php');
$acceptance = $read('includes/class-visual-acceptance.php');
$ledger = json_decode($read('config/central-2026-file25-requirements.json'), true);

foreach ([$design, $central, $css, $publicCss] as $source) {
    $check(str_contains($source, '#087A4E'), 'Review 183: canonical primary #087A4E must be present.');
}
$check(str_contains($design, '#065C3B') && str_contains($design, '#E7F5EE'), 'Review 183: canonical PHP design tokens must use exact latest dark/light greens.');
$check(str_contains($central, "PRIMARY_GREEN_STRONG = '#065C3B'") && str_contains($central, "PRIMARY_GREEN_SOFT = '#E7F5EE'"), 'Review 183: correction contract must freeze exact latest dark/light greens.');
$check(str_contains($css, '--sabri-visual-primary: #087A4E;') && str_contains($css, '--sabri-visual-primary-strong: #065C3B;') && str_contains($css, '--sabri-visual-primary-soft: #E7F5EE;'), 'Review 183: active CSS must freeze F25-CEN-01 green triplet.');
foreach (['#ff8a1f', '#ffbd80', '#b94700', '#9a3d00', '#fff3e8', '#ffc48c'] as $historical) {
    $check(! str_contains(strtolower($css . $publicCss), strtolower($historical)), 'Review 183: historical orange token remains active: ' . $historical);
}
foreach (['--sabri-shell-primary', '--sabri-shell-text', '--sabri-shell-muted', '--sabri-shell-surface', '--sabri-shell-bg', '--sabri-shell-border', '--sabri-shell-focus', '--sabri-shell-radius', '--sabri-shell-gap', '--sabri-shell-font-scale'] as $visualShellToken) {
    $check(! str_contains($css . $publicCss, $visualShellToken), 'Review 184: active File 25 CSS must not inherit competing File 20 visual token ' . $visualShellToken . '.');
    $check(! str_contains($shell, "'" . $visualShellToken . "'"), 'Review 184: shell integration must not declare competing visual inheritance ' . $visualShellToken . '.');
}
$check(str_contains($css, '--sabri-shell-max-width'), 'Review 184: structural max-width inheritance from File 20 must remain.');
$check(str_contains($shell, "'--sabri-shell-max-width'"), 'Review 184: shell contract must preserve structural geometry inheritance.');
$check(str_contains($hero, 'sabri_public_experience/profile_qr_image_url'), 'Review 185: provider-owned QR presentation filter must exist.');
$check(str_contains($hero, 'Public_URL::sanitize_same_site'), 'Review 185: QR presentation must enforce same-site sanitization.');
$check(str_contains($acceptance, "'file20-shell-integration'"), 'Review 185: F25-CEN-02 screenshot contract must include File 20 shell integration.');
$check(str_contains($acceptance, "'qr-share-state'"), 'Review 185: AJ-04 QR/share state must be in visual acceptance surfaces.');
$check(is_array($ledger), 'Review 185: latest central/File25 requirement ledger must be valid JSON.');
if (is_array($ledger)) {
    $fileIds = array_column((array) ($ledger['file_specific_requirements'] ?? []), 'id');
    foreach (['F25-CEN-01', 'F25-CEN-02'] as $id) {
        $check(in_array($id, $fileIds, true), 'Review 185: latest ledger missing ' . $id . '.');
    }
    $journeys = array_column((array) ($ledger['acceptance_journeys'] ?? []), 'id');
    foreach (['AJ-04', 'AJ-31', 'AJ-32', 'AJ-33', 'AJ-39', 'AJ-40'] as $id) {
        $check(in_array($id, $journeys, true), 'Review 185: latest ledger missing ' . $id . '.');
    }
}

if ($failures !== []) {
    fwrite(STDERR, "FAILED Reviews 183-185\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}
echo "PASS: Reviews 183-185 newest central/File25 governing-plan reconciliation\n";
