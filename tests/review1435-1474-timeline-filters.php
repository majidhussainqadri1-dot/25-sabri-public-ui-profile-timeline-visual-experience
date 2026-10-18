<?php
declare(strict_types=1);
/** File25 R1438-1443: actual HTML Timeline filter request guard regression. */
if (! function_exists('wp_unslash')) {
    function wp_unslash($value) { return $value; }
}
require_once dirname(__DIR__) . '/includes/class-profile-renderer.php';

$rc = new ReflectionClass(\Sabri\PublicExperience\Profile_Renderer::class);
$renderer = $rc->newInstanceWithoutConstructor();
$guard = new ReflectionMethod(\Sabri\PublicExperience\Profile_Renderer::class, 'invalid_timeline_filter_request');
$guard->setAccessible(true);
$valid = [
    'type' => 'post',
    'provider' => 'file-21',
    'year' => '2025',
    'language' => 'en',
    'topic' => 'اردو تحقیق',
    'sort' => 'latest',
];
$errors = [];
$check = static function (bool $ok, string $msg) use (&$errors): void {
    if (! $ok) { $errors[] = $msg; }
};
$_GET = [];
$check($guard->invoke($renderer, $valid) === false, 'Unfiltered homepage request rejected.');
foreach ($valid as $key => $value) {
    $_GET = [$key => $value];
    $check($guard->invoke($renderer, $valid) === false, 'Valid filter rejected: ' . $key);
    $_GET = [$key => ''];
    $cleared = $valid; $cleared[$key] = $key === 'sort' ? 'latest' : '';
    $check($guard->invoke($renderer, $cleared) === false, 'Explicit All/clear rejected: ' . $key);
}
$invalidValues = [
    'type' => ['not-a-registered-type', ''],
    'provider' => ['not-a-provider', ''],
    'year' => ['9999', ''],
    'language' => ['invalid_language!', ''],
    'topic' => ["bad\u{202E}control", ''],
    'sort' => ['popular', 'latest'],
];
foreach ($invalidValues as $key => [$bad, $normalized]) {
    $_GET = [$key => $bad];
    $candidate = $valid; $candidate[$key] = $normalized;
    $check($guard->invoke($renderer, $candidate) === true,
        'Invalid nonempty ' . $key . ' must not silently broaden timeline retrieval.');
    $_GET = [$key => ['injection' => 'array']];
    $check($guard->invoke($renderer, $valid) === true,
        'Non-scalar ' . $key . ' must fail closed.');
    $_GET = [$key => null];
    $check($guard->invoke($renderer, $valid) === true,
        'Explicit null ' . $key . ' must fail closed.');
}
$_GET = ['type' => 'post', 'language' => 'invalid_language!'];
$check($guard->invoke($renderer, array_replace($valid, ['language' => ''])) === true,
    'A second invalid filter must not be ignored when another filter is valid.');
$source = file_get_contents(dirname(__DIR__) . '/includes/class-profile-renderer.php');
$check(is_string($source) && str_contains($source, "\$search_error = 'invalid_timeline_filters';")
    && str_contains($source, 'if ($search_error === \'\') {'),
    'Invalid filter must drive the same no-retrieval guard as invalid profile search.');
if ($errors !== []) {
    fwrite(STDERR, "FAILED File25 R1438-1443\n- " . implode("\n- ", $errors) . "\n");
    exit(1);
}
echo "PASS File25 R1438-1443: six invalid/non-scalar/combined HTML filter classes deny retrieval.\n";