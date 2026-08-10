<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$failures = [];
$check = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) {
        $failures[] = $message;
    }
};
$read = static function (string $path) use ($root, &$failures): string {
    $data = @file_get_contents($root . '/' . $path);
    if (! is_string($data)) {
        $failures[] = 'Unreadable review evidence: ' . $path;
        return '';
    }
    return $data;
};

$ledgerRaw = $read('config/review195-274-eighty-round-ledger.json');
try {
    $ledger = json_decode($ledgerRaw, true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $exception) {
    $ledger = [];
    $failures[] = 'Invalid eighty-round ledger JSON: ' . $exception->getMessage();
}
$check(($ledger['review_range']['first'] ?? null) === 195, 'Review ledger must start at 195.');
$check(($ledger['review_range']['last'] ?? null) === 274, 'Review ledger must end at 274.');
$check(($ledger['review_range']['count'] ?? null) === 80, 'Review ledger must contain exactly 80 rounds.');
$reviews = is_array($ledger['reviews'] ?? null) ? $ledger['reviews'] : [];
$check(count($reviews) === 80, 'Review ledger review array must contain exactly 80 entries.');
$expectedDefects = array_merge(range(195, 214), [274]);
$check(($ledger['defect_rounds'] ?? null) === $expectedDefects, 'Defect-bearing review list must be Reviews 195-214 and 274.');
$check(($ledger['clean_rounds'] ?? null) === range(215, 273), 'Clean review list must be exactly Reviews 215-273.');
$seen = [];
foreach ($reviews as $row) {
    if (! is_array($row)) {
        continue;
    }
    $number = $row['review'] ?? null;
    $check(is_int($number) && $number >= 195 && $number <= 274, 'Invalid review number in eighty-round ledger.');
    if (is_int($number)) {
        $check(! isset($seen[$number]), 'Duplicate review number: ' . $number);
        $seen[$number] = true;
        $expected = ($number <= 214 || $number === 274) ? 'defect-found-corrected' : 'reviewed-clean';
        $check(($row['status'] ?? null) === $expected, 'Unexpected status for Review ' . $number . '.');
    }
    $check(is_string($row['focus'] ?? null) && trim((string) $row['focus']) !== '', 'Every review requires a focus statement.');
}
$check(count($seen) === 80, 'All eighty review numbers must be unique and present.');
foreach (['hostinger_staging_accepted','founder_staging_acceptance','production_accepted','live_deployed','operational'] as $gate) {
    $check(($ledger['external_truth'][$gate] ?? null) === false, 'External truth gate must remain false: ' . $gate);
}

$main = $read('sabri-public-experience.php');
$plugin = $read('includes/class-plugin.php');
$future = $read('includes/class-future-public-experience.php');
$renderer = $read('includes/class-profile-renderer.php');
$repository = $read('includes/class-profile-repository.php');
$shell = $read('includes/class-shell-integration.php');
$css = $read('assets/css/future-public-experience.css');
$js = $read('assets/js/future-public-experience.js');
$sourceVerifier = $read('tools/verify-source-completion.php');
$runner = $read('tests/run-governed-suite.sh');
$composer = $read('composer.json');

$check(str_contains($main, "'includes/class-future-public-experience.php'"), 'Review 195 runtime loader correction missing.');
$check(str_contains($plugin, '(new Future_Public_Experience())->register();'), 'Review 195 runtime registration correction missing.');
$check(str_contains($future, '$verified = $default[\'verified\'] === true;'), 'Review 196 trust monotonicity correction missing.');
$check(str_contains($future, '! array_key_exists($key, $checks)'), 'Review 197 quality core-check immutability correction missing.');
$check(str_contains($future, "['!Y-m-d\\TH:i:s\\Z'"), 'Review 198 strict timestamp parsing correction missing.');
$check(str_contains($future, '(string) $integer === $value'), 'Review 199 canonical positive integer correction missing.');
$check(str_contains($future, "'toolbarLabel' => __('Reading and accessibility preferences'"), 'Review 200 localization vocabulary correction missing.');
$check(str_contains($js, 'const t = (key, fallback)'), 'Review 201 client i18n consumption correction missing.');
$check(str_contains($js, 'code !== primaryCode && code !== secondary'), 'Review 202 exactly-two bilingual correction missing.');
$check(str_contains($js, 'url.username || url.password'), 'Review 203 client credential URL rejection missing.');
$check(str_contains($shell, "$base['overlay_stacking_owner'] = 'file-20';"), 'Review 204 shell stacking ownership correction missing.');
$check(! str_contains($css, 'z-index: 9999'), 'Review 204 hard-coded overlay z-index remains.');
$check(str_contains($renderer, '$profile[\'user_id\'] = (int) $user->ID;'), 'Review 205 internal profile identity binding correction missing.');
$check(str_contains($repository, '$visibility = array_fill_keys(array_keys($canonical), true);'), 'Review 206 contact visibility-map correction missing.');
$check(str_contains($repository, '$public[$field] = $value;'), 'Review 206 canonical contact value preservation missing.');
$check(str_contains($renderer, "['_privacy_state' => 'not-public']"), 'Review 207 private-contact completion semantic correction missing.');
$check(str_contains($sourceVerifier, 'Future Public Experience class must be loaded by the production bootstrap.'), 'Review 208 source verifier bootstrap gate missing.');
$check(str_contains($runner, 'verify-future-structure.php'), 'Review 209 governed future structural verifier missing.');
$check(str_contains($composer, 'tests/future-public-experience.php'), 'Review 210 Composer future test manifest correction missing.');
$check(str_contains($css, '::view-transition-old(root)'), 'Review 211 reduced-motion View Transition correction missing.');
$check(str_contains($js, "profileRoot.querySelectorAll('video,audio')"), 'Review 212 low-data scope correction missing.');
$check(str_contains($js, "profileRoot.querySelectorAll('img')"), 'Review 213 visual-integrity scope correction missing.');
$check(str_contains($css, '--sabri-visual-muted'), 'Review 214 canonical muted token correction missing.');
$check(str_contains($css, '--sabri-visual-shadow-2'), 'Review 214 canonical shadow token correction missing.');

$check(! preg_match('/\b(?:eval|document\.write)\s*\(/i', $js), 'Client runtime contains unsafe eval/document.write behavior.');
$check(! preg_match('/https?:\/\//i', $js), 'Client runtime contains a remote destination literal.');
$check(str_contains($future, "'public_ranking_effect' => false"), 'Quality score ranking isolation marker missing.');
$check(str_contains($future, "'raw_sensitive_data_allowed' => false"), 'Raw sensitive-data invariant missing.');
$check(str_contains($future, "'global_search_discovery_ranking_owner' => 'file-26'"), 'File 26 global search ownership marker missing.');
$check(str_contains($future, "'security_governance_owner' => 'file-24'"), 'File 24 security ownership marker missing.');

if ($failures !== []) {
    fwrite(STDERR, "FAILED\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "PASS: File 25 Reviews 195-274 — eighty-round adversarial review ledger and corrective regression gate\n";
