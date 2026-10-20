<?php
declare(strict_types=1);
/** File25 R1675–1684: public legacy redirect root/auth safety and chain refusal. */
if (! defined('ABSPATH')) { define('ABSPATH', __DIR__ . '/fixtures/'); }
$GLOBALS['r1675_candidate'] = [];
function home_url(string $path = ''): string { return 'https://example.test' . ($path === '' ? '' : $path); }
function wp_json_encode(mixed $value, int $flags = 0): string|false { return json_encode($value, $flags); }
function get_option(string $key, mixed $default = false): mixed { return $default; }
function apply_filters(string $name, mixed $value, mixed ...$args): mixed {
    return $name === 'sabri_public_experience/migration_redirect_map'
        ? $GLOBALS['r1675_candidate'] : $value;
}
require_once dirname(__DIR__) . '/includes/class-public-url.php';
require_once dirname(__DIR__) . '/includes/class-plan-completion.php';
$subject = new \Sabri\PublicExperience\Plan_Completion();
$validate = new ReflectionMethod(\Sabri\PublicExperience\Plan_Completion::class, 'validated_redirect_map');
$validate->setAccessible(true);
$dry = new ReflectionMethod(\Sabri\PublicExperience\Plan_Completion::class, 'migration');
$dry->setAccessible(true);
$errors = [];
$assert = static function (bool $ok, string $reason) use (&$errors): void {
    if (! $ok) { $errors[] = $reason; }
};
$entry = static fn(string $from, string $to): array => ['from'=>$from,'to'=>$to,'status'=>301];
$validate_map = static fn(array $m): array => $validate->invoke(null, $m);

$blocked = [
    ['/', 'homepage'],
    ['/wp-login.php', 'login entrypoint'],
    ['/blog/wp-login.php', 'subdirectory login'],
    ['/wp-admin/', 'admin entrypoint'],
    ['/site/wp-admin/options.php', 'nested admin endpoint'],
    ['/wp-json/', 'REST entrypoint'],
    ['/sub/wp-json/wp/v2/', 'nested REST endpoint'],
    ['/wp-cron.php', 'scheduled task runner'],
    ['/sub/xmlrpc.php', 'XML-RPC endpoint'],
];
foreach ($blocked as [$path, $name]) {
    $result = $validate_map([$entry($path, 'https://example.test/safe-profile/')]);
    $assert($result['invalid'] > 0 && $result['map'] === [],
        "Core $name must never become an active File25 legacy redirect source.");
}
$maps = [
    '2-step chain' => [
        $entry('/old-a/', 'https://example.test/old-b/'),
        $entry('/old-b/', 'https://example.test/final/'),
    ],
    '2-step cycle' => [
        $entry('/old-a/', 'https://example.test/old-b/'),
        $entry('/old-b/', 'https://example.test/old-a/'),
    ],
    '3-step cycle' => [
        $entry('/old-a/', 'https://example.test/old-b/'),
        $entry('/old-b/', 'https://example.test/old-c/'),
        $entry('/old-c/', 'https://example.test/old-a/'),
    ],
    'slash-equivalent chain' => [
        $entry('/old-a/', 'https://example.test/old-b'),
        $entry('/old-b/', 'https://example.test/final/'),
    ],
    'query-target chain' => [
        $entry('/old-a/', 'https://example.test/old-b/?from=migration'),
        $entry('/old-b/', 'https://example.test/final/'),
    ],
];
foreach ($maps as $name => $map) {
    $result = $validate_map($map);
    $assert($result['collisions'] > 0, "$name must be detected as a redirect collision.");
    $GLOBALS['r1675_candidate'] = $map;
    $dry_run = $dry->invoke($subject, 'dry-run');
    $assert(($dry_run['status'] ?? '') === 'blocked'
        && ($dry_run['collision_count'] ?? 0) > 0,
        "$name dry-run must not approve an invalid migration.");
}
$accepted = $validate_map([
    $entry('/old-profile/a/', 'https://example.test/profiles/a/'),
    $entry('/old-profile/b/', 'https://example.test/profiles/b/'),
]);
$assert($accepted['invalid'] === 0 && $accepted['collisions'] === 0
    && count($accepted['map']) === 2,
    'Two independent valid profile migrations should remain accepted.');
$GLOBALS['r1675_candidate'] = [
    $entry('/old-profile/a/', 'https://example.test/profiles/a/'),
    $entry('/old-profile/b/', 'https://example.test/profiles/b/'),
];
$valid_dry = $dry->invoke($subject, 'dry-run');
$assert(($valid_dry['status'] ?? '') === 'ready'
    && ($valid_dry['collision_count'] ?? -1) === 0,
    'Valid migrated profile URLs still produce ready dry-run.');
$many = [];
for ($i = 0; $i < 500; $i++) {
    $many[] = $entry('/retired-profile-' . $i . '/', 'https://example.test/profiles/new-' . $i . '/');
}
$large = $validate_map($many);
$assert($large['invalid'] === 0 && $large['collisions'] === 0
    && count($large['map']) === 500, 'Bounded 500 independent routes still accepted.');
$many[] = $entry('/retired-profile-501/', 'https://example.test/profiles/new-501/');
$oversize = $validate_map($many);
$assert($oversize['invalid'] > 0, '501 entries must still be refused, not sliced.');
if ($errors !== []) {
    fwrite(STDERR, "FAIL File25 R1675–1684\n- " . implode("\n- ", $errors) . "\n");
    exit(1);
}
echo "PASS File25 R1675–1684: core route takeover and redirect chain/cycle blocked; valid migration preserved.\n";
