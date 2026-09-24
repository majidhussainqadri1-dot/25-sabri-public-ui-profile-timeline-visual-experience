<?php

declare(strict_types=1);

namespace {
    define('ABSPATH', __DIR__ . '/fixtures/');
    $r42_routes = [];
    function add_action(...$args): void {}
    function __return_true(): bool { return true; }
    function sanitize_title(string $value): string {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9_-]+/', '-', $value) ?? '';
        return trim($value, '-');
    }
    function register_rest_route(string $namespace, string $route, array $args): void { global $r42_routes; $r42_routes[$route] = $args; }
    class WP_REST_Request implements \ArrayAccess {
        public function offsetExists(mixed $offset): bool { return false; }
        public function offsetGet(mixed $offset): mixed { return null; }
        public function offsetSet(mixed $offset, mixed $value): void {}
        public function offsetUnset(mixed $offset): void {}
    }
    class WP_REST_Response { public function __construct(mixed $data = null, int $status = 200, array $headers = []) {} }
    class WP_User {}
    class R42_Profile_Repository {}
    class R42_Timeline_Service {}
    class R42_Section_Service {}
}
namespace Sabri\PublicExperience {
    class Profile_Repository extends \R42_Profile_Repository {}
    class Timeline_Service extends \R42_Timeline_Service {}
    class Section_Service extends \R42_Section_Service {}
}
namespace {
    require_once dirname(__DIR__) . '/includes/class-rest-controller.php';
    $controller = new \Sabri\PublicExperience\Rest_Controller(
        new \Sabri\PublicExperience\Profile_Repository(),
        new \Sabri\PublicExperience\Timeline_Service(),
        new \Sabri\PublicExperience\Section_Service()
    );
    $controller->register_routes();
    global $r42_routes;
    $slug = $r42_routes['/profiles/(?P<slug>[a-zA-Z0-9_-]+)']['args']['slug'] ?? [];
    $validate = $slug['validate_callback'] ?? null;
    $sanitize = $slug['sanitize_callback'] ?? null;
    $ok = is_callable($validate) && is_callable($sanitize)
        && $validate('doctor-name')
        && ! $validate('Doctor-Name')
        && ! $validate(' doctor-name')
        && ! $validate('123')
        && $sanitize('Doctor-Name') === 'Doctor-Name';
    if (! $ok) { fwrite(STDERR, "FAILED: REST slug contract transformed or accepted an alias.\n"); exit(1); }
    echo "PASS: Review 42 exact REST profile slug contract\n";
}
