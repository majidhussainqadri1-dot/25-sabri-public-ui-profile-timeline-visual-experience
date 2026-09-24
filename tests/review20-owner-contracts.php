<?php

declare(strict_types=1);

$source = file_get_contents(__DIR__ . '/../includes/class-native-integration.php');
if (! is_string($source)) { fwrite(STDERR, "Unable to read Native_Integration source.\n"); exit(1); }

$required = [
    "FILE_08_MINIMUM_VERSION = '1.2.0'",
    "FILE_08_CANONICAL_CONTRACT = '1.1.0'",
    "FILE_08_PUBLIC_PROJECTION_CONTRACT = '1.0.0'",
    'WCA_Contracts::PUBLIC_CLINIC_CONTRACT_VERSION',
    'swc_public_clinic_projection_contract',
    'swc_get_public_clinic_projection',
    "sanitize_key((string) (\$source['owner'] ?? '')) !== 'file-08'",
    "['name', 'address', 'country', 'city', 'hours', 'timezone']",
    "['phone', 'whatsapp', 'email', 'user_id', 'native_id', 'appointments', 'patient_data']",
    "FILE_03_MINIMUM_VERSION = '1.2.0-rc2'",
    "FILE_03_CONTRACT_VERSION = '1.4.0'",
    "function_exists('spd_get_public_profile')",
    "function_exists('spd_get_profile_contract_manifest')",
    "function_exists('smp_get_public_profile_listings')",
];
foreach ($required as $needle) {
    if (! str_contains($source, $needle)) { fwrite(STDERR, "Missing review-20 contract marker: {$needle}\n"); exit(1); }
}

$forbidden = [
    'SWC_Helpers::public_clinic_projection',
    "class_exists('SMP_REST')",
    'SPD_Helpers::get',
    'SPD_Helpers::founder',
    'SPD_Helpers::can_show_contact',
    'SPD_Helpers::verification_status',
];
foreach ($forbidden as $needle) {
    if (str_contains($source, $needle)) { fwrite(STDERR, "Forbidden owner-boundary fallback remains: {$needle}\n"); exit(1); }
}

echo "Review 20 current File 03/File 08/File 18 owner-contract checks passed.\n";
