<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$failures = [];
$check = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) {
        $failures[] = $message;
    }
};

$visibility = file_get_contents($root . '/includes/class-visibility-policy.php') ?: '';
$repository = file_get_contents($root . '/includes/class-profile-repository.php') ?: '';
$router = file_get_contents($root . '/includes/class-profile-router.php') ?: '';
$renderer = file_get_contents($root . '/includes/class-profile-renderer.php') ?: '';
$template = file_get_contents($root . '/templates/public-profile.php') ?: '';
$cards = file_get_contents($root . '/includes/class-content-cards.php') ?: '';
$sections = file_get_contents($root . '/includes/class-section-service.php') ?: '';
$rest = file_get_contents($root . '/includes/class-rest-controller.php') ?: '';
$design = file_get_contents($root . '/includes/class-design-system.php') ?: '';

$check(str_contains($visibility, 'SPD_Helpers::can_show_contact'), 'File 25 must consume File 03 public-contact consent.');
$check(! str_contains($visibility, 'elseif ($this->is_verified_doctor'), 'Verified Doctor status must not bypass public-contact consent.');
$check(str_contains($visibility, 'return $authoritative && $filtered'), 'Contact filters may revoke but never grant a denied contact projection.');

$check(str_contains($repository, "FOUNDER_DISPLAY_NAME = 'Dr. Allamah Majid Hussain Sabri Muhaddith Mursheed'"), 'Canonical Founder spelling must be frozen in code.');
$check(! str_contains($repository, 'get_avatar_url'), 'File 25 must not silently use external avatar services.');
$check(str_contains($repository, 'Public_URL::sanitize_same_site'), 'Profile media must use same-origin URL enforcement.');

$check(str_contains($router, 'profile_right_sidebar_available'), 'Three-column profile layout must require real right-sidebar content.');
$check(str_contains($router, 'return $right_sidebar_available ? \'three\' : \'two\';'), 'Profile layout must default to two columns.');

$check(str_contains($renderer, "'@type' => 'BreadcrumbList'"), 'Profile SEO must include BreadcrumbList structured data.');
$check(str_contains($renderer, 'og:image:alt'), 'Profile OpenGraph media must include safe alternative text.');
$check(str_contains($renderer, 'missing_profile_status'), 'Deleted-profile policy must support explicit 410 tombstones while defaulting to 404.');
$check(str_contains($template, 'spux-breadcrumbs'), 'Public profiles must render visible accessible breadcrumbs.');

$check(str_contains($cards, 'public static function normalize_public'), 'HTML and REST must share one public card allow-list.');
$check(str_contains($sections, 'get_public_section'), 'Optional sections must expose structured public projections.');
$check(str_contains($sections, 'public_health'), 'Provider health must have a bounded aggregate public projection.');
$check(str_contains($sections, 'Provider IDs, versions'), 'Public health must explicitly keep provider identifiers private.');

foreach ([
    '/founder/knowledge',
    '/founder/media',
    '/profiles/(?P<slug>[a-zA-Z0-9_-]+)/knowledge',
    '/profiles/(?P<slug>[a-zA-Z0-9_-]+)/media',
    '/providers/health',
] as $route) {
    $check(str_contains($rest, $route), 'Planned public REST route is missing: ' . $route);
}
$check(str_contains($rest, "'ETag'"), 'Public REST responses must emit deterministic ETags.');
$check(! preg_match('/provider_id|native_id|projection_key/', $rest), 'Public REST controller must not expose provider/native/projection identifiers.');

foreach ([
    'native-contact-consent',
    'same-origin-profile-media',
    'conditional-profile-layout',
    'profile-breadcrumbs',
    'structured-profile-section-rest',
] as $scope) {
    $check(str_contains($design, $scope), 'Design contract is missing reconciled scope: ' . $scope);
}

if ($failures !== []) {
    fwrite(STDERR, "FAILED\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "PASS: File 25 master-plan and final-specification reconciliation contracts\n";
