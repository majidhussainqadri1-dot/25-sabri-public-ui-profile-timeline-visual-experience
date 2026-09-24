<?php

declare(strict_types=1);

define('ABSPATH', __DIR__ . '/');
function home_url(string $path = '/'): string { return 'https://example.test' . ($path === '' ? '/' : $path); }
function esc_url_raw(string $url, array $protocols = []): string { return $url; }
require_once dirname(__DIR__) . '/includes/class-public-url.php';

use Sabri\PublicExperience\Public_URL;

$accepted = [
    '/knowledge/item/',
    '/file%2Epdf/',
    '/search/?next=%2Fknowledge%2F',
    'https://example.test/Knowledge/Item/?a=1',
    'https://example.test/file%2Epdf',
];
foreach ($accepted as $url) {
    if (Public_URL::sanitize_same_site($url, false) === '') {
        fwrite(STDERR, "Review 30 rejected safe URL: {$url}\n");
        exit(1);
    }
}

$rejected = [
    '/../private',
    '/./knowledge',
    '/knowledge/%2e%2e/private',
    '/knowledge/%2E/private',
    '/knowledge/%2fprivate',
    '/knowledge/%5cprivate',
    '/knowledge/%252e%252e/private',
    '/knowledge/%252fprivate',
    "https://example.test/knowledge/%2e%2e/private",
    "https://example.test/knowledge/%0d%0aInjected",
];
foreach ($rejected as $url) {
    if (Public_URL::sanitize_same_site($url, false) !== '') {
        fwrite(STDERR, "Review 30 accepted ambiguous or unsafe URL: {$url}\n");
        exit(1);
    }
}

if (Public_URL::canonical_same_site_identity('https://example.test/a/../b', false) !== '') {
    fwrite(STDERR, "Review 30 canonical identity accepted a dot-segment route.\n");
    exit(1);
}

echo "Review 30 public URL path-hardening checks passed.\n";
