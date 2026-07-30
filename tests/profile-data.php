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

if ($failures !== []) {
    fwrite(STDERR, "FAILED\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "PASS: File 25 structured public profile data contracts\n";
