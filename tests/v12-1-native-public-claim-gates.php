<?php
declare(strict_types=1);

/**
 * V12.1: generic UI hooks must not invent domain-owner public facts.
 * This is a CLI source regression, not a deployed File03/21 contract.
 */
if (! defined('ABSPATH')) { define('ABSPATH', __DIR__ . '/fixtures/'); }
if (! function_exists('__')) {
    function __(string $text, string $domain = ''): string { return $text; }
}
if (! function_exists('apply_filters')) {
    function apply_filters(string $hook, mixed $value, mixed ...$args): mixed {
        return match ($hook) {
            'sabri_public_experience/profile_trust_capsule' => [
                'verified' => true,
                'label' => 'Forged top specialist',
                'source_label' => 'Forged international university',
                'verified_at' => '2026-10-10T11:11:11Z',
                'freshness' => 'Updated live now',
                'correction_state' => 'retracted',
            ],
            'sabri_public_experience/profile_translations' => [
                'ur' => ['label' => 'اردو', 'headline' => 'Invented qualifications', 'bio' => 'Unauthorised translated bio'],
            ],
            'sabri_public_experience/public_relationships' => [
                ['label' => 'Fake medical institution', 'url' => 'https://example.test/institution/'],
            ],
            'sabri_public_experience/public_citations' => [
                ['title' => 'Imaginary scientific paper', 'url' => 'https://example.test/paper/'],
            ],
            'sabri_public_experience/content_freshness' => [
                'updated_at' => gmdate('Y-m-d\TH:i:s\Z', time() - 60),
                'label' => 'Live verified',
            ],
            default => $value,
        };
    }
}
require_once dirname(__DIR__) . '/includes/class-public-url.php';
require_once dirname(__DIR__) . '/includes/class-future-public-experience.php';

$failures = [];
$check = static function (bool $ok, string $reason) use (&$failures): void {
    if (! $ok) { $failures[] = $reason; }
};
$invoke = static function (string $method, array $args): mixed {
    $reflection = new ReflectionMethod(\Sabri\PublicExperience\Future_Public_Experience::class, $method);
    $reflection->setAccessible(true);
    return $reflection->invokeArgs(null, $args);
};

$profile = [
    'class' => 'doctor',
    'verified' => false,
    'role_label' => 'Doctor applicant',
    'display_name' => 'Example Doctor',
];
$trust = $invoke('trust_payload', [$profile]);
$check(($trust['verified'] ?? null) === false, 'A presentation filter cannot grant professional verification.');
$check(($trust['label'] ?? '') === 'Doctor applicant', 'Presentation filter cannot replace owner label.');
$check(($trust['source_label'] ?? '') === 'Institutional verification source', 'Forged issuer label must not appear.');
$check(($trust['verified_at'] ?? null) === '' && ($trust['freshness'] ?? null) === '',
    'Forged verification date and freshness must be suppressed.');
$check(($trust['correction_state'] ?? null) === 'none',
    'A presentation filter cannot reclassify owner profile as corrected/retracted.');

foreach (['translations','relationships','citations','freshness'] as $method) {
    $args = $method === 'citations' ? [$profile, ['section' => 'overview']] : [$profile];
    $check($invoke($method, $args) === [],
        'Generic filter must not invent public ' . $method . ' absent a typed owner-approved projection.');
}

$trusted = $profile;
$trusted['verified'] = true;
$trustedFacts = $invoke('trust_payload', [$trusted]);
$check(($trustedFacts['verified'] ?? null) === true,
    'Actual File03/09 native verified flag should remain visible, despite fabricated UI provenance.');
$check(($trustedFacts['verified_at'] ?? null) === '' && ($trustedFacts['source_label'] ?? '') !== 'Forged international university',
    'Even a true approved verified bit must not grant arbitrary filtered provenance or date.');

if ($failures !== []) {
    fwrite(STDERR, "FAILED File25 V12.1 native public claim gates\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}
echo "PASS File25 V12.1: profile trust, translations, citations, relationships and freshness cannot be forged via UI hooks\n";
