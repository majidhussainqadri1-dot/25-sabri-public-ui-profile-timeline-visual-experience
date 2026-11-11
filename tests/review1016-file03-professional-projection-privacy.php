<?php
declare(strict_types=1);

// File03 decides which verified-doctor professional fields are genuinely public.
// A File09 approved snapshot is evidence for eligibility, not a second
// publication source when File03's public DTO withholds the fields.
require __DIR__ . '/review39-53-native-bootstrap.php';

use Sabri\PublicExperience\Native_Integration;

SMC_Contracts::$assertions[7] = r3953_assertions(7, 'doctor');
$r3953_decisions[7] = r3953_valid_decision('a');
$r3953_snapshots[7] = [
    'profile' => [
        'qualification' => 'PRIVATE_SNAPSHOT_ONLY_QUALIFICATION',
        'specialty' => 'PRIVATE_SNAPSHOT_ONLY_SPECIALTY',
        'licensing_authority' => 'PRIVATE_SNAPSHOT_ONLY_COUNCIL',
        'license_number' => 'PRIVATE_EVIDENCE_IDENTIFIER',
    ],
];
$r3953_profiles[7] = [
    'contract_version' => '1.4.0',
    'public_id' => '00000000-0000-4000-8000-000000000007',
    'canonical_url' => 'https://example.test/doctors/doctor-7/',
    'profile_type' => 'doctor',
    'state' => 'active',
    'version' => 1,
    'display_name' => 'Doctor 7',
    'badge' => ['verified' => true],
    'fields' => [],
    'professional' => [], // File03 deliberately approved no public fields.
    'media' => [],
    'contacts' => [],
];

$native = new Native_Integration();
$sourceSnapshot = $native->doctor_approved_snapshot(7);
r3953_native_assert(($sourceSnapshot['profile']['qualification'] ?? '') ===
    'PRIVATE_SNAPSHOT_ONLY_QUALIFICATION',
    'The fixture must prove an approved raw File09 snapshot is available.');
r3953_native_assert($native->professional_credentials(7) === [],
    'Withheld File03 professional fields must not fall back to File09 snapshot data.');

// File03-approved fields must remain visible without exposing File09-only data.
$r3953_profiles[7]['professional'] = [
    'qualification' => 'FILE03_PUBLIC_DHMS',
    'licensing_authority' => 'FILE03_PUBLIC_COUNCIL',
];
$approvedNative = new Native_Integration();
$publicCredentials = $approvedNative->professional_credentials(7);
r3953_native_assert(($publicCredentials['qualification'] ?? '') === 'FILE03_PUBLIC_DHMS'
    && ($publicCredentials['council'] ?? '') === 'FILE03_PUBLIC_COUNCIL',
    'Canonical File03-approved professional fields must still render.');
r3953_native_assert(! str_contains((string) json_encode($publicCredentials), 'PRIVATE_'),
    'File09-only evidence must never enter public professional DTO.');

echo "PASS: File25 File03-exclusive professional disclosure and File09 snapshot isolation\n";
