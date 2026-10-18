<?php
declare(strict_types=1);
/** File25 R1435-1437: explicit-null owner privacy preferences must fail closed. */
require_once dirname(__DIR__) . '/includes/class-plan-completion.php';

$normalizer = new ReflectionMethod(\Sabri\PublicExperience\Plan_Completion::class, 'normalize_preferences');
$normalizer->setAccessible(true);
$failures = [];
$check = static function (bool $condition, string $reason) use (&$failures): void {
    if (! $condition) {
        $failures[] = $reason;
    }
};
$keys = ['public_visibility', 'public_metrics', 'profile_local_search'];
$defaults = $normalizer->invoke(null, [], [], true);
foreach ($keys as $key) {
    $check(($defaults[$key] ?? null) === true, $key . ': absent option should retain documented fresh-install default.');
    foreach ([null, 'false', [], new stdClass()] as $invalid) {
        $result = $normalizer->invoke(null, [$key => $invalid], [], true);
        $check(($result[$key] ?? null) === false,
            $key . ': malformed explicit ' . get_debug_type($invalid) . ' must not enable a public surface.');
    }
    $false = $normalizer->invoke(null, [$key => false], [], true);
    $check(($false[$key] ?? null) === false, $key . ': false opt-out not preserved.');
    $false_string = $normalizer->invoke(null, [$key => '0'], [], true);
    $check(($false_string[$key] ?? null) === false, $key . ': 0 opt-out not preserved.');
    $true = $normalizer->invoke(null, [$key => true], [], true);
    $check(($true[$key] ?? null) === true, $key . ': explicit valid true not preserved.');
}
// Cover the partial update path used by REST/admin mutation; omitted fields
// retain the current privacy opt-outs, an explicitly null patch is rejected.
$current = $normalizer->invoke(null, ['public_visibility' => false, 'public_metrics' => false], [], true);
$after = $normalizer->invoke(null, ['profile_local_search' => null], $current, true);
$check(($after['public_visibility'] ?? null) === false
    && ($after['public_metrics'] ?? null) === false
    && ($after['profile_local_search'] ?? null) === false,
    'Sparse admin update must retain existing opt-outs and fail closed for explicit null.');
if ($failures !== []) {
    fwrite(STDERR, "FAIL File25 R1435-1437\n" . implode("\n", $failures) . "\n");
    exit(1);
}
echo "PASS File25 R1435-1437: stored, sparse and REST-style null privacy settings fail closed.\n";