<?php

declare(strict_types=1);

define('ABSPATH', __DIR__ . '/');

function home_url(string $path = '/'): string
{
    return 'https://example.com' . ($path === '' ? '/' : $path);
}

function esc_url_raw(string $url, array $protocols = []): string
{
    return $url;
}

require_once __DIR__ . '/../includes/class-public-url.php';

use Sabri\PublicExperience\Public_URL;

$identities = [
    Public_URL::canonical_same_site_identity('https://EXAMPLE.com.:443/path/', false),
    Public_URL::canonical_same_site_identity('https://example.com/path', false),
];
if ($identities[0] !== 'https://example.com/path' || ! hash_equals($identities[0], $identities[1])) {
    fwrite(STDERR, "Default-port or host normalization is inconsistent.\n");
    exit(1);
}
if (Public_URL::canonical_same_site_identity('https://example.com:8443/path', false) !== '') {
    fwrite(STDERR, "Mismatched non-default port was not rejected by same-site policy.\n");
    exit(1);
}
if (Public_URL::canonical_same_site_identity('https://evil.example/path', false) !== '') {
    fwrite(STDERR, "Cross-origin URL was not rejected.\n");
    exit(1);
}
if (Public_URL::canonical_same_site_identity('/Knowledge/Item/?a=1', false) !== '/Knowledge/Item?a=1') {
    fwrite(STDERR, "Relative path identity did not preserve case/query or normalize trailing slash.\n");
    exit(1);
}

$timeline = file_get_contents(__DIR__ . '/../includes/class-timeline-service.php');
$sections = file_get_contents(__DIR__ . '/../includes/class-section-service.php');
if (! is_string($timeline) || ! str_contains($timeline, 'Public_URL::canonical_same_site_identity($url, false)')) {
    fwrite(STDERR, "Timeline service does not use the shared canonical URL identity.\n");
    exit(1);
}
if (! is_string($sections) || ! str_contains($sections, "Public_URL::canonical_same_site_identity($candidate['url'] ?? '', false)")) {
    fwrite(STDERR, "Section service does not use the shared canonical URL identity.\n");
    exit(1);
}

echo "Review 26 shared canonical URL identity checks passed.\n";
