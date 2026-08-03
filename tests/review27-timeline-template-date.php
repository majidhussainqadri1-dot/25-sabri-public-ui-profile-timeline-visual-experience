<?php

declare(strict_types=1);

$source = file_get_contents(dirname(__DIR__) . '/templates/partials/timeline.php');
if (! is_string($source)) {
    fwrite(STDERR, "Unable to read timeline template.\n");
    exit(1);
}

$required = [
    "DateTimeImmutable::createFromFormat(",
    "'!Y-m-d\\TH:i:s\\Z'",
    "new \\DateTimeZone('UTC')",
    "->format('Y-m-d\\TH:i:s\\Z') === \$published_at",
    "wp_date(get_option('date_format'), \$timestamp, new \\DateTimeZone('UTC'))",
];
foreach ($required as $marker) {
    if (! str_contains($source, $marker)) {
        fwrite(STDERR, "Review 27 deterministic timeline marker missing: {$marker}\n");
        exit(1);
    }
}
if (str_contains($source, 'strtotime(')) {
    fwrite(STDERR, "Review 27 failed: permissive strtotime remains in the public timeline template.\n");
    exit(1);
}

$utc = new DateTimeZone('UTC');
$valid = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:s\Z', '2026-08-03T10:20:30Z', $utc);
$invalid = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:s\Z', '2026-02-30T10:20:30Z', $utc);
if (! $valid instanceof DateTimeImmutable || $valid->format('Y-m-d\TH:i:s\Z') !== '2026-08-03T10:20:30Z') {
    fwrite(STDERR, "Review 27 failed: canonical UTC timestamp was not parsed deterministically.\n");
    exit(1);
}
$errors = DateTimeImmutable::getLastErrors();
if ($invalid instanceof DateTimeImmutable && (! is_array($errors) || (int) ($errors['warning_count'] ?? 0) === 0)) {
    fwrite(STDERR, "Review 27 failed: invalid calendar date did not produce a warning.\n");
    exit(1);
}

echo "Review 27 deterministic public timeline date checks passed.\n";
