<?php

declare(strict_types=1);

namespace {
    define('ABSPATH', __DIR__ . '/fixtures/');
    function home_url(string $path = '/'): string { return 'https://example.test' . (str_starts_with($path, '/') ? $path : '/' . $path); }
    function esc_url_raw(string $url, array $protocols = []): string { return $url; }
    require_once dirname(__DIR__) . '/includes/class-public-url.php';
    use Sabri\PublicExperience\Public_URL;
    $valid = Public_URL::sanitize_same_site('https://example.test/knowledge/remedy/', false);
    $unsafe = [
        'https://example.test/a/%25252fadmin',
        'https://example.test/a/%25255cadmin',
        'https://example.test/a/%25252e%25252e/admin',
        'https://example.test/a/%ZZ/admin',
        'https://example.test/a\\admin',
    ];
    if ($valid === '') { fwrite(STDERR, "FAILED: Valid same-site path was rejected.\n"); exit(1); }
    foreach ($unsafe as $url) {
        if (Public_URL::sanitize_same_site($url, false) !== '') {
            fwrite(STDERR, "FAILED: Recursively ambiguous path was accepted: {$url}\n"); exit(1);
        }
    }
    echo "PASS: Review 48 recursive public URL decoding hardening\n";
}
