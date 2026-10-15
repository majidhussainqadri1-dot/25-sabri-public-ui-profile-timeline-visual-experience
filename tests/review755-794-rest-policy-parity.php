<?php
declare(strict_types=1);

/**
 * Reviews 755–794: public REST parity with the real File 25 preference schema.
 * Runs without WordPress, verifies real private helper behavior by reflection,
 * and guards their use on every relevant public read endpoint.
 */
if (! defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/fixtures/');
}

$GLOBALS['file25_rest_policy_options'] = [];

if (! function_exists('get_option')) {
    function get_option(string $key, mixed $default = false): mixed
    {
        return $key === 'sabri_public_experience_preferences'
            ? $GLOBALS['file25_rest_policy_options']
            : $default;
    }
}
if (! function_exists('sanitize_key')) {
    function sanitize_key(string $value): string
    {
        return trim((string) preg_replace('/[^a-z0-9_\-]/', '', strtolower($value)), '-');
    }
}

require_once dirname(__DIR__) . '/includes/class-plan-completion.php';
require_once dirname(__DIR__) . '/includes/class-rest-controller.php';

use Sabri\PublicExperience\Plan_Completion;
use Sabri\PublicExperience\Rest_Controller;

$failures = [];
$check = static function (bool $ok, string $reason) use (&$failures): void {
    if (! $ok) {
        $failures[] = $reason;
    }
};
$surface = new ReflectionMethod(Rest_Controller::class, 'public_surface_enabled');
$search = new ReflectionMethod(Rest_Controller::class, 'public_search_enabled');
// PHP 8.0 still requires explicit access for private-method regression tests.
$surface->setAccessible(true);
$search->setAccessible(true);

// Default public availability; preserve both native-owner and admin decisions.
$GLOBALS['file25_rest_policy_options'] = [];
$check($surface->invoke(null, '') === true, 'Default public profile must be available.');
$check($surface->invoke(null, 'timeline') === true, 'Default public timeline must be available.');
$check($surface->invoke(null, 'reviews') === true, 'Doctor Reviews tab must be an enabled default.');
$check($surface->invoke(null, 'research') === true, 'Governed Research provider section must be an enabled default.');
$check($search->invoke(null, 'safe query') === true, 'Default profile-local search must be available.');

$GLOBALS['file25_rest_policy_options'] = ['public_visibility' => false];
foreach (['', 'timeline', 'knowledge', 'media'] as $section) {
    $check($surface->invoke(null, $section) === false,
        'Global public visibility kill switch must close REST surface: ' . $section);
}

// Hidden tab must not remain readable from a direct REST request.
$GLOBALS['file25_rest_policy_options'] = [
    'public_visibility' => true,
    'enabled_public_tabs' => ['overview', 'knowledge', 'reviews'],
];
$check($surface->invoke(null, '') === true, 'Public profile stays visible with selected tabs.');
$check($surface->invoke(null, 'knowledge') === true, 'Enabled Knowledge tab stays readable.');
$check($surface->invoke(null, 'reviews') === true, 'Enabled Reviews tab stays readable.');
$check($surface->invoke(null, 'timeline') === false, 'Hidden Timeline must be closed in REST.');
$check($surface->invoke(null, 'media') === false, 'Hidden Media must be closed in REST.');

$GLOBALS['file25_rest_policy_options'] = [
    'public_visibility' => true,
    'profile_local_search' => false,
];
$check($search->invoke(null, '') === true, 'Unfiltered timeline request must remain allowed.');
$check($search->invoke(null, 'private-query') === false,
    'Disabled profile-local search must not be usable through REST.');

$source = file_get_contents(dirname(__DIR__) . '/includes/class-rest-controller.php');
$check(is_string($source), 'REST source must remain inspectable.');
if (is_string($source)) {
    $sections = [
        'profile_response' => ['private function profile_response', 'private function timeline_response'],
        'timeline_response' => ['private function timeline_response', 'private function section_response'],
        'section_response' => ['private function section_response', 'private static function public_surface_enabled'],
    ];
    foreach ($sections as $name => [$from, $to]) {
        $start = strpos($source, $from);
        $end = strpos($source, $to);
        $body = $start !== false && $end !== false && $end > $start
            ? substr($source, $start, $end - $start)
            : '';
        $check($body !== '' && str_contains($body, 'public_surface_enabled('),
            'REST handler missing public visibility enforcement: ' . $name);
        if ($name === 'timeline_response') {
            $check(str_contains($body, 'public_search_enabled('),
                'Timeline REST handler missing profile-local search parity.');
        }
    }
    $check(str_contains($source, 'Plan_Completion::apply_profile_preferences($profile)'),
        'REST public profile must not list admin-hidden tabs as available.');
}

if ($failures !== []) {
    fwrite(STDERR, "FAILED Reviews 755-794 REST policy parity\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}
echo "PASS: Reviews 755-794 public REST profile/tab/search parity\n";
