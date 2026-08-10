<?php

declare(strict_types=1);

namespace {
    define('ABSPATH', __DIR__ . '/');

    class WP_REST_Request
    {
        /** @param array<string,string> $headers */
        public function __construct(private array $headers = []) {}
        public function get_header(string $name): string
        {
            return (string) ($this->headers[strtolower($name)] ?? '');
        }
    }

    class WP_REST_Response
    {
        /** @param array<string,string> $headers */
        public function __construct(
            public mixed $data,
            public int $status,
            public array $headers = []
        ) {}
    }

    require_once dirname(__DIR__) . '/includes/class-rest-controller.php';

    $reflection = new \ReflectionClass(\Sabri\PublicExperience\Rest_Controller::class);
    $controller = $reflection->newInstanceWithoutConstructor();
    $method = $reflection->getMethod('response');
    $method->setAccessible(true);

    $first = $method->invoke($controller, [
        'profile' => ['class' => 'doctor', 'slug' => 'doctor-one'],
        'items' => [['title' => 'One', 'url' => '/one']],
    ], 200, new WP_REST_Request());
    $second = $method->invoke($controller, [
        'items' => [['url' => '/one', 'title' => 'One']],
        'profile' => ['slug' => 'doctor-one', 'class' => 'doctor'],
    ], 200, new WP_REST_Request());

    $etag = (string) ($first->headers['ETag'] ?? '');
    if ($etag === '' || $etag !== ($second->headers['ETag'] ?? '')) {
        fwrite(STDERR, "Review 37 failed: semantically equivalent REST data produced divergent ETags.\n");
        exit(1);
    }

    foreach ([$etag, 'W/' . $etag, '"other", ' . $etag, '*'] as $header) {
        $response = $method->invoke(
            $controller,
            ['items' => [['title' => 'One', 'url' => '/one']], 'profile' => ['class' => 'doctor', 'slug' => 'doctor-one']],
            200,
            new WP_REST_Request(['if-none-match' => $header])
        );
        if ($response->status !== 304 || $response->data !== null || ($response->headers['ETag'] ?? '') !== $etag) {
            fwrite(STDERR, "Review 37 failed: matching If-None-Match did not produce an empty 304 response.\n");
            exit(1);
        }
    }

    $dated = $method->invoke($controller, [
        'profile' => ['updated_at' => '2026-08-10T12:30:00Z'],
        'items' => [['date_gmt' => '2026-08-10T12:00:00Z']],
    ], 200, new WP_REST_Request());
    $lastModified = (string) ($dated->headers['Last-Modified'] ?? '');
    if ($lastModified !== 'Mon, 10 Aug 2026 12:30:00 GMT') {
        fwrite(STDERR, "Review 517 failed: deterministic Last-Modified header missing or incorrect.\n");
        exit(1);
    }
    $date304 = $method->invoke($controller, [
        'profile' => ['updated_at' => '2026-08-10T12:30:00Z'],
    ], 200, new WP_REST_Request(['if-modified-since' => $lastModified]));
    if ($date304->status !== 304 || $date304->data !== null) {
        fwrite(STDERR, "Review 517 failed: If-Modified-Since did not produce 304.\n");
        exit(1);
    }
    $precedence = $method->invoke($controller, [
        'profile' => ['updated_at' => '2026-08-10T12:30:00Z'],
    ], 200, new WP_REST_Request(['if-none-match' => '"miss"', 'if-modified-since' => $lastModified]));
    if ($precedence->status !== 200) {
        fwrite(STDERR, "Review 517 failed: If-None-Match precedence was not preserved.\n");
        exit(1);
    }

    $miss = $method->invoke($controller, ['value' => 1], 200, new WP_REST_Request(['if-none-match' => '"not-this"']));
    if ($miss->status !== 200 || $miss->data !== ['value' => 1]) {
        fwrite(STDERR, "Review 37 failed: nonmatching ETag incorrectly suppressed a response.\n");
        exit(1);
    }

    $error = $method->invoke($controller, ['code' => 'profile_not_found'], 404, new WP_REST_Request(['if-none-match' => '*']));
    if ($error->status !== 404 || $error->data !== ['code' => 'profile_not_found']) {
        fwrite(STDERR, "Review 37 failed: conditional request changed an error response.\n");
        exit(1);
    }

    $source = (string) file_get_contents(dirname(__DIR__) . '/includes/class-rest-controller.php');
    foreach (['allow_public_request', 'sabri_public_experience/rest_rate_limit', 'wp_cache_incr', "'status' => 429", "'retry_after' => \$window"] as $marker) {
        if (! str_contains($source, $marker)) {
            fwrite(STDERR, "Review 518 failed: REST rate-limit contract marker missing: {$marker}.\n");
            exit(1);
        }
    }

    echo "Review 37/517/518 deterministic REST ETag, Last-Modified and rate-limit contract checks passed.\n";
}