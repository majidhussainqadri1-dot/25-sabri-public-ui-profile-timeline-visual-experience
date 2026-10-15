<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/fixtures/');
}
if (! function_exists('wp_strip_all_tags')) {
    function wp_strip_all_tags(string $text, bool $remove_breaks = false): string
    {
        $text = strip_tags($text);
        return $remove_breaks ? preg_replace('/[\r\n\t ]+/', ' ', $text) ?? $text : $text;
    }
}

require_once dirname(__DIR__) . '/includes/class-profile-data.php';

use Sabri\PublicExperience\Profile_Data;

$failures = [];
$check = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) {
        $failures[] = $message;
    }
};

$founder = Profile_Data::founder_details([
    'mission' => '<script>bad()</script> Build a trusted platform. ',
    'vision' => '<b>Global learning</b>',
    'publications' => "Book One\nBook Two\nBook One\n<script>bad</script>Book Three",
    'identity_document' => 'must never appear',
]);
$check(($founder['mission'] ?? '') === 'Build a trusted platform.', 'Founder mission must strip executable content.');
$check(($founder['vision'] ?? '') === 'Global learning', 'Founder vision must be plain text.');
$check(($founder['publications'] ?? []) === ['Book One', 'Book Two', 'Book Three'], 'Founder publications must be unique, ordered, and safe.');
$check(! array_key_exists('identity_document', $founder), 'Founder allow-list must exclude identity evidence.');

$professional = Profile_Data::professional_details([
    'qualification' => '<b>DHMS</b>',
    'experience_years' => 14,
    'languages' => 'Urdu, English; Arabic',
    'consultation_mode' => ['Online', 'Clinic', 'Online'],
    'books_studied' => "Organon\nMateria Medica",
    'license_number' => 'private-number',
]);
$check(($professional['qualification'] ?? '') === 'DHMS', 'Professional qualification must be plain text.');
$check(($professional['experience_years'] ?? 0) === 14, 'Professional experience must remain bounded and numeric.');
$check(($professional['languages'] ?? []) === ['Urdu', 'English', 'Arabic'], 'Languages must normalize into a unique list.');
$check(($professional['consultation_mode'] ?? []) === ['Online', 'Clinic'], 'Consultation modes must be deduplicated.');
$check(! array_key_exists('license_number', $professional), 'Public professional data must exclude registration numbers.');

$invalid_experience = Profile_Data::professional_details(['experience_years' => 200]);
$check(! array_key_exists('experience_years', $invalid_experience), 'Unreasonable experience values must be rejected.');

$clinic = Profile_Data::clinic([
    'name' => 'Sabri Clinic',
    'address' => '<em>Gujrat</em>',
    'timezone' => 'Asia/Karachi',
    'owner_user_id' => 99,
    'status' => 'approved',
    'private_notes' => 'hidden',
]);
$check(($clinic['name'] ?? '') === 'Sabri Clinic', 'Clinic name must be preserved safely.');
$check(($clinic['address'] ?? '') === 'Gujrat', 'Clinic address must be plain text.');
$check(! array_key_exists('owner_user_id', $clinic), 'Clinic owner IDs must not enter public projection.');
$check(! array_key_exists('private_notes', $clinic), 'Clinic private notes must not enter public projection.');

// Owner projection privacy regression: rendering may only show the exact
// File 03-approved fields, or redact them. A presentation filter is not
// publication authority and a WordPress user description is not a public DTO.
$check(Profile_Data::monotonic_owner_text('', 'injected private biography', 300) === '',
    'Missing File 03 bio must not be populated by a presentation filter.');
$check(Profile_Data::monotonic_owner_text('Owner-approved bio', 'injected private biography', 300) === 'Owner-approved bio',
    'A presentation filter cannot replace owner-approved biography.');
$check(Profile_Data::monotonic_owner_text('Owner-approved headline', 'unauthorized headline', 300) === 'Owner-approved headline',
    'A presentation filter cannot replace owner-approved headline.');
$check(Profile_Data::monotonic_owner_text('Owner-approved bio', '', 300) === '',
    'A privacy-revoking presentation filter may redact approved biography.');
$check(Profile_Data::monotonic_owner_text('<b>Safe</b> name', 'Safe name', 300) === 'Safe name',
    'Native approved biography formatting must normalize without changing content.');

$repository_source = file_get_contents(dirname(__DIR__) . '/includes/class-profile-repository.php');
$check(is_string($repository_source) && ! str_contains($repository_source, '$user->description'),
    'File 25 must not fall back to WordPress raw user description.');
$check(is_string($repository_source) && ! str_contains($repository_source, '(string) $user->display_name'),
    'File 25 must not fall back to WordPress raw display name.');
$check(is_string($repository_source) && str_contains($repository_source, 'Profile_Data::monotonic_owner_text'),
    'Both public text fields must use the owner monotonic gate.');

if ($failures !== []) {
    fwrite(STDERR, "FAILED\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "PASS: File 25 structured public profile data contracts\n";
