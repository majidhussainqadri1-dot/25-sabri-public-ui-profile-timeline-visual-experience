<?php

declare(strict_types=1);

$source = file_get_contents(__DIR__ . '/../includes/class-native-integration.php');
if (! is_string($source)) {
    fwrite(STDERR, "Unable to read Native_Integration source.\n");
    exit(1);
}

$required = [
    "FILE_08_MINIMUM_VERSION = '0.2.1'",
    'SWC_PUBLIC_CLINIC_CONTRACT_VERSION',
    'swc_public_clinic_projection_contract',
    'swc_get_public_clinic_projection',
    'sanitize_key((string) ($source[\'owner\'] ?? \'\')) !== \'file-08\'',
    "['name', 'address', 'country', 'city', 'hours', 'timezone']",
    "['phone', 'whatsapp', 'email', 'user_id', 'native_id', 'appointments', 'patient_data']",
    '(bool) $source[\'writes_data\'] !== false',
    "FILE_03_MINIMUM_VERSION = '0.2.0'",
    "method_exists('SPD_Helpers', 'can_show_contact')",
    "function_exists('smp_get_public_profile_listings')",
];

foreach ($required as $needle) {
    if (! str_contains($source, $needle)) {
        fwrite(STDERR, "Missing review-20 contract marker: {$needle}\n");
        exit(1);
    }
}

$forbidden = [
    'SWC_Helpers::public_clinic_projection',
    "class_exists('SMP_REST')",
    "'display_name', 'country', 'city', 'qualification'",
    "'consultation_modes', 'bio'",
];

foreach ($forbidden as $needle) {
    if (str_contains($source, $needle)) {
        fwrite(STDERR, "Forbidden owner-boundary fallback remains: {$needle}\n");
        exit(1);
    }
}

echo "Review 20 File 08/File 03/File 18 owner-contract checks passed.\n";
