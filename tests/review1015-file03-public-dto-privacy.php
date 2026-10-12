<?php
declare(strict_types=1);

// File 25 may consume only explicit File 03 public-projection fields. A valid
// public profile cannot promote unrelated WP_User name/description by fallback.
require __DIR__ . '/review39-53-native-bootstrap.php';
require_once dirname(__DIR__) . '/includes/class-profile-data.php';
require_once dirname(__DIR__) . '/includes/class-visibility-policy.php';
require_once dirname(__DIR__) . '/includes/class-profile-repository.php';

use Sabri\PublicExperience\Native_Integration;
use Sabri\PublicExperience\Profile_Repository;
use Sabri\PublicExperience\Visibility_Policy;

if (! function_exists('__')) {
    function __(string $text, string $domain = ''): string { return $text; }
}
if (! function_exists('sanitize_title')) {
    function sanitize_title(string $title): string
    {
        return trim((string) preg_replace('/[^a-z0-9-]/', '-', strtolower($title)), '-');
    }
}
if (! function_exists('wp_strip_all_tags')) {
    function wp_strip_all_tags(string $text, bool $remove_breaks = false): string
    {
        return trim(strip_tags($text));
    }
}
if (! function_exists('trailingslashit')) {
    function trailingslashit(string $value): string { return rtrim($value, '/') . '/'; }
}

SMC_Contracts::$assertions[10] = r3953_assertions(10, 'member');
$source = spd_get_public_profile(10);
$source['display_name'] = ''; // Canonical owner did not grant public name.
$source['fields'] = [];       // Canonical owner did not grant public biography.
$r3953_profiles[10] = $source;

$wpUser = new WP_User(10);
$wpUser->display_name = 'WP_ONLY_UNREVIEWED_NAME';
$wpUser->description = 'WP_ONLY_UNREVIEWED_BIO';

$native = new Native_Integration();
$repository = new Profile_Repository(new Visibility_Policy($native), $native);
$public = $repository->get_public_profile($wpUser);
r3953_native_assert(is_array($public), 'Fixture must represent an otherwise eligible public member profile.');
r3953_native_assert(($public['bio'] ?? 'not_empty') === '',
    'Missing File03-approved bio must not be replaced by raw WordPress description.');
r3953_native_assert(($public['display_name'] ?? '') === 'Public profile',
    'Missing File03-approved name must show a neutral, nonidentifying label.');
r3953_native_assert(! str_contains((string) json_encode($public), 'WP_ONLY_UNREVIEWED_'),
    'No unreviewed WordPress-only private identity fields may cross public projection.');

// The patch must not suppress values actually authorized by File 03.
$source['display_name'] = 'File03 Approved Public Name';
$source['fields'] = ['bio' => 'File03 approved public biography'];
$r3953_profiles[10] = $source;
$nativeApproved = new Native_Integration();
$repositoryApproved = new Profile_Repository(new Visibility_Policy($nativeApproved), $nativeApproved);
$approved = $repositoryApproved->get_public_profile($wpUser);
r3953_native_assert(is_array($approved)
    && ($approved['display_name'] ?? null) === 'File03 Approved Public Name'
    && ($approved['bio'] ?? null) === 'File03 approved public biography',
    'File03-authorized public name and biography must continue to render normally.');

echo "PASS: File25 File03-authorized-only public name/bio fallback regression\n";
