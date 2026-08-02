<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/fixtures/');
}
if (! function_exists('sanitize_key')) {
    function sanitize_key(string $value): string
    {
        return trim((string) preg_replace('/[^a-z0-9_\-]/', '-', strtolower($value)), '-');
    }
}

require_once dirname(__DIR__) . '/includes/class-staging-probe.php';

use Sabri\PublicExperience\Staging_Probe;

$failures = [];
$check = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) {
        $failures[] = $message;
    }
};

$contract = Staging_Probe::contract();
$check(($contract['contract_version'] ?? '') === '1.0.0', 'Staging-probe contract version must be current.');
$check(($contract['canonical_staging_host'] ?? '') === 'sabrisocialstaging.sabrihomeopathy.com', 'Canonical Hostinger staging host must be fixed.');
$check(($contract['live_host'] ?? '') === 'sabrihomeopathy.com', 'Live host exclusion must be explicit.');
$check(($contract['writes_runtime_data'] ?? true) === false, 'Staging probe must remain read-only.');
$check(($contract['staging_acceptance_implied'] ?? true) === false, 'Preflight must not imply staging acceptance.');

$root = sys_get_temp_dir() . '/file25-staging-probe-' . bin2hex(random_bytes(6));
mkdir($root . '/config', 0777, true);
$files = [
    'sabri-public-experience.php' => "<?php\n/* Version: 0.14.0 */\ndefine('SABRI_PUBLIC_EXPERIENCE_VERSION', '0.14.0');\n",
    'readme.txt' => "Stable tag: 0.14.0\n",
    'uninstall.php' => "<?php\n",
];
$scenario_ids = [
    'environment-host',
    'environment-privacy',
    'package-integrity',
    'activation-order',
    'file00-assertions',
    'file08-clinic-projection',
    'file09-doctor-decision',
    'file18-owner-dto',
    'minor-private',
    'wrong-author',
    'responsive-viewports',
    'urdu-rtl',
    'accessibility-input',
    'accessibility-display',
    'safe-mode',
    'upgrade-rollback',
    'performance-errors',
    'founder-acceptance',
];
$scenarios = array_map(
    static fn (string $id): array => [
        'id' => $id,
        'category' => 'acceptance',
        'requirement' => 'Governed acceptance requirement for ' . $id,
    ],
    $scenario_ids
);
$plan = [
    'schema_version' => 2,
    'owner' => 'file-25',
    'canonical_staging_host' => 'sabrisocialstaging.sabrihomeopathy.com',
    'live_host_must_remain_untouched' => true,
    'registration_must_remain_disabled' => true,
    'search_indexing_must_remain_disabled' => true,
    'source_contract_is_acceptance' => false,
    'green_ci_is_acceptance' => false,
    'staging_acceptance_implied' => false,
    'production_acceptance_implied' => false,
    'scenarios' => $scenarios,
];
$write_plan = static function (array $candidate) use ($root): void {
    file_put_contents(
        $root . '/config/staging-test-plan.json',
        json_encode($candidate, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"
    );
};
$write_plan($plan);
$files['config/staging-test-plan.json'] = file_get_contents($root . '/config/staging-test-plan.json') ?: '';
foreach ($files as $relative => $content) {
    $path = $root . '/' . $relative;
    if (! is_dir(dirname($path))) {
        mkdir(dirname($path), 0777, true);
    }
    file_put_contents($path, $content);
}

$manifest_files = [];
foreach (array_keys($files) as $relative) {
    $manifest_files[$relative] = [
        'sha256' => hash_file('sha256', $root . '/' . $relative),
        'bytes' => filesize($root . '/' . $relative),
    ];
}
$commit = str_repeat('a', 40);
$manifest = [
    'schema_version' => 1,
    'package' => 'sabri-public-experience',
    'file_number' => 25,
    'canonical_name' => 'Sabri Unified Global Visual Experience and Design System',
    'version' => '0.14.0',
    'commit_sha' => $commit,
    'source_date_epoch' => 1785456000,
    'generated_at_utc' => '2026-07-31T00:00:00Z',
    'files' => $manifest_files,
];
file_put_contents($root . '/STAGING-MANIFEST.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

$valid = Staging_Probe::verify_package_integrity($root, '0.14.0', $commit);
$check(($valid['valid'] ?? false) === true, 'Exact installed package must pass manifest verification.');
$check(($valid['verified_file_count'] ?? 0) === count($files), 'Every governed payload file must be verified.');
$check(Staging_Probe::verify_test_plan($root)['valid'] === true, 'Governed schema-2 staging test plan must validate.');

$schema_one = $plan;
$schema_one['schema_version'] = 1;
$write_plan($schema_one);
$check(Staging_Probe::verify_test_plan($root)['valid'] === false, 'Obsolete schema-1 staging plans must fail closed.');

$missing = $plan;
array_pop($missing['scenarios']);
$write_plan($missing);
$check(Staging_Probe::verify_test_plan($root)['valid'] === false, 'A missing mandatory staging scenario must fail closed.');

$duplicate = $plan;
$duplicate['scenarios'][] = $duplicate['scenarios'][0];
$write_plan($duplicate);
$check(Staging_Probe::verify_test_plan($root)['valid'] === false, 'Duplicate scenario identifiers must fail closed.');

$false_acceptance = $plan;
$false_acceptance['green_ci_is_acceptance'] = true;
$write_plan($false_acceptance);
$check(Staging_Probe::verify_test_plan($root)['valid'] === false, 'A plan that treats green CI as acceptance must fail closed.');

$write_plan($plan);
file_put_contents($root . '/extra.php', "<?php\n");
$extra = Staging_Probe::verify_package_integrity($root, '0.14.0', $commit);
$check(($extra['valid'] ?? true) === false, 'Ungoverned extra installed files must fail integrity.');
unlink($root . '/extra.php');

file_put_contents($root . '/readme.txt', "tampered\n");
$tampered = Staging_Probe::verify_package_integrity($root, '0.14.0', $commit);
$check(($tampered['valid'] ?? true) === false, 'Tampered payload must fail SHA-256 or byte-size verification.');
file_put_contents($root . '/readme.txt', $files['readme.txt']);

$wrong_commit = Staging_Probe::verify_package_integrity($root, '0.14.0', str_repeat('b', 40));
$check(($wrong_commit['valid'] ?? true) === false, 'Wrong expected commit must fail closed.');

$unsafe_manifest = $manifest;
$unsafe_manifest['files']['../escape.php'] = ['sha256' => str_repeat('c', 64), 'bytes' => 1];
file_put_contents($root . '/STAGING-MANIFEST.json', json_encode($unsafe_manifest, JSON_PRETTY_PRINT) . "\n");
$unsafe = Staging_Probe::verify_package_integrity($root, '0.14.0', $commit);
$check(($unsafe['valid'] ?? true) === false, 'Unsafe manifest paths must fail closed.');

$remove = static function (string $path) use (&$remove): void {
    if (is_dir($path) && ! is_link($path)) {
        foreach (scandir($path) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $remove($path . '/' . $entry);
        }
        rmdir($path);
        return;
    }
    if (file_exists($path) || is_link($path)) {
        unlink($path);
    }
};
$remove($root);

if ($failures !== []) {
    fwrite(STDERR, "FAILED\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "PASS: File 25 installed-package integrity and schema-2 staging-probe contract\n";
