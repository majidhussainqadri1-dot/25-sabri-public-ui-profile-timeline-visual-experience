<?php
declare(strict_types=1);

/**
 * V12.1 File03 owner index consent + native metrics public-publishing boundary.
 * Exercises actual PHP production methods, not just source-string assertions.
 */
if (! defined('ABSPATH')) { define('ABSPATH', __DIR__ . '/fixtures/'); }
$GLOBALS['v12_owner_filter_calls'] = ['index' => 0, 'metrics' => 0];
$GLOBALS['v12_owner_profile_type'] = 'member';
if (! function_exists('sanitize_key')) {
    function sanitize_key(string $value): string {
        return (string) preg_replace('/[^a-z0-9_-]/', '', strtolower($value));
    }
}
if (! function_exists('get_option')) {
    function get_option(string $name, mixed $fallback = false): mixed {
        if ($name === 'sabri_public_experience_preferences') {
            return ['public_visibility'=>true, 'seo_enabled'=>true, 'public_metrics'=>true];
        }
        return $fallback;
    }
}
if (! function_exists('get_query_var')) {
    function get_query_var(string $name, mixed $default = ''): mixed {
        return match($name) {
            'spux_profile_type' => $GLOBALS['v12_owner_profile_type'],
            'spux_profile_slug' => 'example',
            'spux_profile_section' => 'overview',
            default => $default,
        };
    }
}
if (! function_exists('is_404')) {
    function is_404(): bool { return false; }
}
if (! function_exists('apply_filters')) {
    function apply_filters(string $hook, mixed $value, mixed ...$params): mixed {
        if ($hook === 'sabri_public_experience/index_nonprofessional_profile') {
            ++$GLOBALS['v12_owner_filter_calls']['index'];
            return true;
        }
        if ($hook === 'sabri_public_experience/public_metrics') {
            ++$GLOBALS['v12_owner_filter_calls']['metrics'];
            return ['followers'=>100000, 'publications'=>99, 'reviews'=>7];
        }
        if ($hook === 'sabri_public_experience/public_metric_minimum') return 0;
        if ($hook === 'sabri_public_experience/public_metric_allowed') return true;
        return $value;
    }
}
require_once dirname(__DIR__) . '/includes/contracts/interface-profile-section-provider.php';
require_once dirname(__DIR__) . '/includes/class-section-registry.php';
require_once dirname(__DIR__) . '/includes/class-profile-router.php';
require_once dirname(__DIR__) . '/includes/class-plan-completion.php';
require_once dirname(__DIR__) . '/includes/class-profile-renderer.php';

$errors = [];
$check = static function(bool $valid, string $description) use (&$errors): void {
    if (! $valid) $errors[] = $description;
};
$cls = new ReflectionClass(\Sabri\PublicExperience\Profile_Renderer::class);
$renderer = $cls->newInstanceWithoutConstructor();
$prop = $cls->getProperty('router');
$prop->setAccessible(true);
$prop->setValue($renderer, new \Sabri\PublicExperience\Profile_Router());

$GLOBALS['sabri_public_experience_profile'] = [
    'class'=>'member', 'display_name'=>'Public member', 'user_id'=>42,
];
$GLOBALS['sabri_public_experience_context'] = [];
$member = $renderer->robots([]);
$check(($member['noindex'] ?? false) === true && ($member['noarchive'] ?? false) === true,
    'Generic WordPress filter must not authorize a member search index.');
$check($GLOBALS['v12_owner_filter_calls']['index'] === 0,
    'Untrusted member index approval filter must never be consulted.');
$metrics = \Sabri\PublicExperience\Plan_Completion::public_metrics([
    'class'=>'member','user_id'=>42,'verified'=>true,
]);
$check($metrics === [], 'Fabricated native owner public counts must be withheld.');
$check($GLOBALS['v12_owner_filter_calls']['metrics'] === 0,
    'UI metrics data filter must not become a second authority to publish statistics.');

$GLOBALS['v12_owner_profile_type'] = 'doctor';
$GLOBALS['sabri_public_experience_profile']['class'] = 'doctor';
$doctor = $renderer->robots([]);
$check(! isset($doctor['noindex']),
    'Unfiltered ordinary public professional profile remains SEO indexable.');
$GLOBALS['sabri_public_experience_context'] = ['timeline_filtered_request'=>true];
$filtered = $renderer->robots([]);
$check(($filtered['noindex'] ?? false) === true,
    'Filtered professional Timeline remains noindex independently of native authority.');
$GLOBALS['sabri_public_experience_context'] = [];
$GLOBALS['sabri_public_experience_profile'] = [];
$empty = $renderer->robots([]);
$check(($empty['noindex'] ?? false) === true,
    'Absent identity projection must remain non-indexable.');

if($errors !== []) {
    fwrite(STDERR, "FAIL V12.1 owner-index/metrics gate\n- ".implode("\n- ",$errors)."\n");
    exit(1);
}
echo "PASS File25 V12.1: native owner public SEO consent and unapproved metrics fail closed\n";
