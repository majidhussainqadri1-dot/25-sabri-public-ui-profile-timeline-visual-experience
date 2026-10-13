<?php
declare(strict_types=1);

// A generic presentation filter does not authorize indexing a File03 member
// without a versioned owner-controlled, public indexability projection.
if (! defined('ABSPATH')) { define('ABSPATH', __DIR__ . '/fixtures/'); }
$GLOBALS['r1041_query'] = [];
$GLOBALS['r1041_filter_calls'] = 0;

if (! function_exists('sanitize_key')) {
    function sanitize_key(string $value): string {
        return preg_replace('/[^a-z0-9_-]/', '', strtolower($value)) ?? '';
    }
}
if (! function_exists('get_query_var')) {
    function get_query_var(string $key): mixed {
        return $GLOBALS['r1041_query'][$key] ?? '';
    }
}
if (! function_exists('is_404')) {
    function is_404(): bool { return ! empty($GLOBALS['r1041_404']); }
}
if (! function_exists('apply_filters')) {
    function apply_filters(string $hook, mixed $value, mixed ...$args): mixed {
        if ($hook === 'sabri_public_experience/index_nonprofessional_profile') {
            ++$GLOBALS['r1041_filter_calls'];
            return true; // Simulate an untrusted presentation plugin's opt-in.
        }
        return $value;
    }
}
require_once dirname(__DIR__) . '/includes/contracts/interface-profile-section-provider.php';
require_once dirname(__DIR__) . '/includes/class-section-registry.php';
require_once dirname(__DIR__) . '/includes/class-profile-router.php';
require_once dirname(__DIR__) . '/includes/class-profile-renderer.php';

$router = new \Sabri\PublicExperience\Profile_Router();
$reflection = new ReflectionClass(\Sabri\PublicExperience\Profile_Renderer::class);
$renderer = $reflection->newInstanceWithoutConstructor();
$property = $reflection->getProperty('router');
$property->setAccessible(true);
$property->setValue($renderer, $router);

$assert = static function (bool $ok, string $why): void {
    if (! $ok) { throw new RuntimeException($why); }
};

$GLOBALS['r1041_query'] = [
    'spux_profile_type' => 'member',
    'spux_profile_slug' => 'member-7',
    'spux_profile_section' => 'overview',
];
$GLOBALS['sabri_public_experience_profile'] = ['class' => 'member', 'display_name' => 'Member 7'];
$GLOBALS['sabri_public_experience_context'] = [];
$robots = $renderer->robots([]);
$assert(($robots['noindex'] ?? false) === true
    && ($robots['noarchive'] ?? false) === true,
    'Unverified member search-indexing opt-in must remain disallowed.');
$assert($GLOBALS['r1041_filter_calls'] === 0,
    'The generic presentation indexing filter must not be consulted for disclosure authorization.');

// Native verified professional remains indexable on ordinary public overview.
$GLOBALS['r1041_query']['spux_profile_type'] = 'doctor';
$GLOBALS['r1041_query']['spux_profile_slug'] = 'verified-doctor';
$GLOBALS['sabri_public_experience_profile'] = ['class' => 'doctor'];
$doctor = $renderer->robots([]);
$assert(! isset($doctor['noindex']), 'An eligible unfiltered public doctor remains indexable.');

// Even publicly eligible doctors must not index filtered/search/preview URLs.
$GLOBALS['sabri_public_experience_context'] = ['timeline_sort' => 'oldest'];
$filtered = $renderer->robots([]);
$assert(($filtered['noindex'] ?? false) === true, 'Filtered professional pages must remain noindex.');
$GLOBALS['sabri_public_experience_context'] = [];
$GLOBALS['r1041_404'] = true;
$missing = $renderer->robots([]);
$assert(($missing['noindex'] ?? false) === true
    && ($missing['noarchive'] ?? false) === true,
    'Missing professional profiles must never become indexable.');

echo "PASS: File25 File03-controlled member SEO indexability and professional/filtered parity\n";
