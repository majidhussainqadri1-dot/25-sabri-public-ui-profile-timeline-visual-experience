<?php

declare(strict_types=1);

/**
 * File 25 Reviews 795-834: actual PHP source regression for WordPress-wide
 * robots scoping, owner-only profile visibility, privacy monotonicity and
 * strict opt-in indexing. Does not need an installed WordPress instance.
 */
if (! defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/fixtures/');
}
$GLOBALS['f25_seo_preferences'] = [];
$GLOBALS['f25_seo_query'] = [];
$GLOBALS['f25_seo_is404'] = false;
$GLOBALS['f25_seo_member_optin'] = false;

if (! function_exists('sanitize_key')) {
    function sanitize_key(string $value): string {
        return preg_replace('/[^a-z0-9_\\-]/', '', strtolower($value)) ?? '';
    }
}
if (! function_exists('get_option')) {
    function get_option(string $key, mixed $default = false): mixed {
        return $key === 'sabri_public_experience_preferences'
            ? $GLOBALS['f25_seo_preferences'] : $default;
    }
}
if (! function_exists('get_query_var')) {
    function get_query_var(string $key, mixed $default = ''): mixed {
        return $GLOBALS['f25_seo_query'][$key] ?? $default;
    }
}
if (! function_exists('is_404')) {
    function is_404(): bool { return $GLOBALS['f25_seo_is404']; }
}
if (! function_exists('apply_filters')) {
    function apply_filters(string $name, mixed $value, mixed ...$args): mixed {
        return $name === 'sabri_public_experience/index_nonprofessional_profile'
            ? $GLOBALS['f25_seo_member_optin'] : $value;
    }
}

require_once dirname(__DIR__) . '/includes/contracts/interface-profile-section-provider.php';
require_once dirname(__DIR__) . '/includes/class-section-registry.php';
require_once dirname(__DIR__) . '/includes/class-profile-router.php';
require_once dirname(__DIR__) . '/includes/class-plan-completion.php';
require_once dirname(__DIR__) . '/includes/class-profile-renderer.php';

use Sabri\PublicExperience\Plan_Completion;
use Sabri\PublicExperience\Profile_Renderer;
use Sabri\PublicExperience\Profile_Router;

$errors = [];
$expect = static function (bool $condition, string $reason) use (&$errors): void {
    if (! $condition) { $errors[] = $reason; }
};
$plan = new Plan_Completion();
$rendererClass = new ReflectionClass(Profile_Renderer::class);
$renderer = $rendererClass->newInstanceWithoutConstructor();
$router = $rendererClass->getProperty('router');
$router->setAccessible(true);
$router->setValue($renderer, new Profile_Router());

$profile = [
    'display_name' => 'Approved Founder',
    'class' => 'founder',
    'headline' => 'Public headline',
    'bio' => 'Public biography',
    'canonical_url' => 'https://example.test/founder/',
];
$GLOBALS['sabri_public_experience_profile'] = $profile;
$GLOBALS['sabri_public_experience_context'] = ['section' => 'overview', 'preview_mode' => ''];

// SEO disabled in File 25 MUST NOT disable File 21 News, File 20 Shell
// or any other unrelated public page in the shared WordPress installation.
$GLOBALS['f25_seo_query'] = [];
$GLOBALS['f25_seo_preferences'] = ['seo_enabled' => false];
$other = ['index' => true, 'follow' => true];
$expect($plan->profile_robots($other) === $other,
    'File 25 SEO-off must never noindex an unrelated page.');

$GLOBALS['f25_seo_query'] = [
    'spux_profile_type' => 'founder', 'spux_profile_section' => 'overview',
];
$result = $plan->profile_robots(['index' => true]);
$expect(($result['noindex'] ?? false) === true && ($result['noarchive'] ?? false) === true,
    'File 25 own profile must honor SEO-off noindex/noarchive.');

$GLOBALS['f25_seo_preferences'] = ['seo_enabled' => true, 'public_visibility' => false];
$private = $plan->profile_robots([]);
$expect(($private['noindex'] ?? false) === true,
    'Nonpublic owner-visible profile must never be indexable.');
$rendered = $renderer->robots(['nofollow' => true]);
$expect(($rendered['noindex'] ?? false) === true,
    'Renderer must not index owner-only globally hidden profiles.');
$expect(($rendered['nofollow'] ?? false) === true,
    'Renderer must preserve an existing native nofollow privacy directive.');

ob_start();
$renderer->render_meta();
$html = ob_get_clean();
$expect($html === '', 'Private owner-visible profile must emit no OpenGraph/JSON-LD profile payload.');

$GLOBALS['f25_seo_preferences'] = ['seo_enabled' => true, 'public_visibility' => true];
$GLOBALS['sabri_public_experience_context']['preview_mode'] = 'public';
$preview = $plan->profile_robots([]);
$expect(($preview['noindex'] ?? false) === true,
    'View-as-public owner preview must always carry noindex.');
$GLOBALS['sabri_public_experience_context']['preview_mode'] = '';

// A truthy STRING is not an affirmative authorization to index member pages.
$GLOBALS['sabri_public_experience_profile']['class'] = 'member';
$GLOBALS['f25_seo_member_optin'] = 'yes';
$noPermission = $renderer->robots([]);
$expect(($noPermission['noindex'] ?? false) === true,
    'Nonprofessional indexing opt-in requires strict boolean true.');
$GLOBALS['f25_seo_member_optin'] = true;
$permission = $renderer->robots([]);
$expect(! isset($permission['noindex']),
    'An explicitly granted public member indexing opt-in remains supported.');

// Do not downgrade a previous nofollow signal when a Timeline filter triggers
// noindex; a 404, however, must establish nofollow.
$GLOBALS['sabri_public_experience_context']['timeline_filtered_request'] = true;
$filtered = $renderer->robots(['nofollow' => true]);
$expect(($filtered['noindex'] ?? false) === true && ($filtered['nofollow'] ?? false) === true,
    'Filtered Timeline must retain pre-existing nofollow.');
$GLOBALS['f25_seo_is404'] = true;
$notFound = $renderer->robots([]);
$expect(($notFound['noindex'] ?? false) === true && ($notFound['nofollow'] ?? false) === true,
    'Unavailable profile must be noindex/nofollow.');
$GLOBALS['f25_seo_is404'] = false;

$GLOBALS['f25_seo_query'] = [];
$GLOBALS['f25_seo_preferences'] = ['seo_enabled' => false, 'public_visibility' => false];
$expect($plan->profile_robots(['index' => true]) === ['index' => true],
    'Other domain pages must never inherit File 25 privacy SEO settings.');

if ($errors !== []) {
    fwrite(STDERR, "FAIL: Reviews 795-834 profile SEO scope\n- " . implode("\n- ", $errors) . "\n");
    exit(1);
}
echo "PASS: Reviews 795-834 WordPress-wide SEO isolation, owner privacy, OG gate and strict indexing\n";
