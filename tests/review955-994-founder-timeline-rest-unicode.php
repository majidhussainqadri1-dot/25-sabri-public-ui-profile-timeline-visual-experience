<?php
declare(strict_types=1);

/** Reviews 955–994: executable route-schema and downstream Timeline Unicode parity. */
namespace {
    if (! defined('ABSPATH')) { define('ABSPATH', __DIR__ . '/fixtures/'); }
    $GLOBALS['f25_r955_rest_routes'] = [];
    if (! function_exists('register_rest_route')) {
        function register_rest_route(string $namespace, string $route, array $args): void {
            $GLOBALS['f25_r955_rest_routes'][$namespace . $route] = $args;
        }
    }
    if (! function_exists('wp_strip_all_tags')) {
        function wp_strip_all_tags(string $text): string { return strip_tags($text); }
    }
    if (! function_exists('sanitize_key')) {
        function sanitize_key(string $value): string {
            return (string) preg_replace('/[^a-z0-9_\-]/', '', strtolower($value));
        }
    }
}
namespace Sabri\PublicExperience {
    // Registry fixture exercises the real Timeline_Service::get_for_author().
    // No private provider/source database or live WordPress code is invented.
    final class Timeline_Registry {
        public int $queries = 0;
        public function all(): array {
            return ['test-public' => $this];
        }
        public function validated_metadata(object $provider, string $provider_id): ?array {
            return ['maturity' => 'read-only', 'version' => '1.0.0'];
        }
        public function is_available(): bool { return true; }
        public function get_public_author_items(int $author_id, array $query): array {
            $this->queries++;
            return [];
        }
    }
}
namespace {
    require_once dirname(__DIR__) . '/includes/class-plan-completion.php';
    require_once dirname(__DIR__) . '/includes/class-rest-controller.php';
    require_once dirname(__DIR__) . '/includes/class-timeline-service.php';

    $errors = [];
    $check = static function (bool $ok, string $reason) use (&$errors): void {
        if (! $ok) { $errors[] = $reason; }
    };
    $rest = (new \ReflectionClass(\Sabri\PublicExperience\Rest_Controller::class))
        ->newInstanceWithoutConstructor();
    $rest->register_routes();
    $root = 'sabri-public/v1';
    $founder = $GLOBALS['f25_r955_rest_routes'][$root . '/founder/timeline']['args'] ?? [];
    $member = $GLOBALS['f25_r955_rest_routes'][$root . '/profiles/(?P<slug>[a-zA-Z0-9_-]+)/timeline']['args'] ?? [];
    $expected = ['page','per_page','content_type','provider','year','language','topic','sort','search'];
    foreach ($expected as $field) {
        $check(isset($founder[$field]['validate_callback'])
            && is_callable($founder[$field]['validate_callback']),
            'Founder Timeline missing strict REST validator for ' . $field);
        $check(isset($member[$field]['validate_callback'])
            && is_callable($member[$field]['validate_callback']),
            'Member/Doctor Timeline missing REST validator for ' . $field);
        if (! isset($founder[$field], $member[$field])) { continue; }
        foreach (['sanitize_callback','validate_callback'] as $callback) {
            $check($founder[$field][$callback] === $member[$field][$callback],
                'Founder and member Timeline differ in ' . $field . ' ' . $callback);
        }
    }
    foreach ([
        'year' => ['2026', ['2026']],
        'language' => ['ur-PK', ['ur-PK']],
        'provider' => ['file-21', ['file-21']],
        'sort' => ['latest', ['latest']],
        'topic' => ['اردو', ['اردو']],
        'search' => ['اردو', ['اردو']],
    ] as $field => [$good, $bad]) {
        if (! isset($founder[$field])) { continue; }
        $san = $founder[$field]['sanitize_callback'];
        $valid = $founder[$field]['validate_callback'];
        $check($valid($san($good)) === true,
            'Valid Founder Timeline ' . $field . ' should pass.');
        $check($valid($san($bad)) === false,
            'Malformed Founder Timeline ' . $field . ' must fail.');
    }
    $reg = new \Sabri\PublicExperience\Timeline_Registry();
    $service = new \Sabri\PublicExperience\Timeline_Service($reg);
    $ur120 = str_repeat('ع',120);
    $ur121 = $ur120 . 'ع';
    $emoji120 = str_repeat('🙂',120);
    foreach ([$ur120, $emoji120] as $value) {
        $before = $reg->queries;
        $service->get_for_author(42, ['topic' => $value]);
        $check($reg->queries === $before + 1,
            'Native Timeline rejected valid 120-character Unicode topic.');
    }
    foreach ([$ur121, 'موضوع' . "\u{202E}", str_repeat('x',481)] as $value) {
        $before = $reg->queries;
        $service->get_for_author(42, ['topic' => $value]);
        $check($reg->queries === $before,
            'Native Timeline admitted over-limit or hidden bidi topic.');
    }
    $before = $reg->queries;
    $service->get_for_author(42, ['topic' => '']);
    $check($reg->queries === $before + 1,
        'Intentional empty Timeline topic should remain functional.');

    if ($errors !== []) {
        fwrite(STDERR,"FAILED File25 Reviews 955–994 strict Founder and native Unicode parity\n- "
            . implode("\n- ", $errors) . "\n");
        exit(1);
    }
    echo "PASS: File25 Reviews 955–994 Founder REST and native Unicode/bidi gate\n";
}
