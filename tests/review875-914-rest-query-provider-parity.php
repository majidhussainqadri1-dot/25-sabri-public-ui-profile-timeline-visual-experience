<?php
declare(strict_types=1);

/**
 * File25 Reviews 875–914. Run actual Rest_Controller and its registered
 * callbacks without WordPress. Native dependencies are contract fixtures;
 * the code under test is the real production REST controller.
 */
namespace {
    if (! defined('ABSPATH')) { define('ABSPATH', __DIR__ . '/fixtures/'); }
    $GLOBALS['f25_review915_routes'] = [];
    $GLOBALS['f25_review915_options'] = [];

    final class WP_User {
        public int $ID = 42;
    }
    final class WP_REST_Response {
        public function __construct(public mixed $data, public int $status = 200, public array $headers = []) {}
        public function get_data(): mixed { return $this->data; }
        public function get_status(): int { return $this->status; }
    }
    if (! function_exists('register_rest_route')) {
        function register_rest_route(string $namespace, string $route, array $args): void {
            $GLOBALS['f25_review915_routes'][$namespace . $route] = $args;
        }
    }
    if (! function_exists('get_option')) {
        function get_option(string $key, mixed $fallback = false): mixed {
            return $key === 'sabri_public_experience_preferences'
                ? $GLOBALS['f25_review915_options'] : $fallback;
        }
    }
    if (! function_exists('sanitize_key')) {
        function sanitize_key(string $value): string {
            return (string) preg_replace('/[^a-z0-9_\\-]/', '', strtolower($value));
        }
    }
}
namespace Sabri\PublicExperience {
    final class Profile_Repository {
        public function get_public_profile(\WP_User $user): ?array {
            if ($user->ID !== 42) { return null; }
            return [
                'display_name' => 'Canonical Owner-Approved Doctor',
                'class' => 'doctor',
                'available_sections' => ['overview', 'timeline'],
                'section_labels' => ['overview' => 'Overview', 'timeline' => 'Timeline'],
            ];
        }
    }
    final class Section_Service {
        public function available_for_profile(int $userId, array $profile): array {
            return $userId === 42 && ($profile['class'] ?? '') === 'doctor'
                ? ['knowledge' => 'Knowledge', 'media' => 'Media'] : [];
        }
    }
    final class Timeline_Service {}
}
namespace {
    require_once dirname(__DIR__) . '/includes/class-rest-controller.php';

    $errors = [];
    $assert = static function (bool $ok, string $message) use (&$errors): void {
        if (! $ok) { $errors[] = $message; }
    };

    $controller = new \Sabri\PublicExperience\Rest_Controller(
        new \Sabri\PublicExperience\Profile_Repository(),
        new \Sabri\PublicExperience\Timeline_Service(),
        new \Sabri\PublicExperience\Section_Service()
    );
    $controller->register_routes();
    $url = 'sabri-public/v1/profiles/(?P<slug>[a-zA-Z0-9_-]+)/timeline';
    $route = $GLOBALS['f25_review915_routes'][$url] ?? [];
    $arguments = $route['args'] ?? [];

    $bad = [
        'content_type' => [false, ['post'], 13],
        'provider' => [true, ['file-21'], new \stdClass()],
        'year' => [true, ['2026'], 2026],
        'language' => [false, ['ur-PK'], 15],
        'topic' => [false, ['subject'], " \n\t ", "<script></script>"],
        'sort' => [null, true, ['latest']],
    ];
    foreach ($bad as $field => $candidates) {
        $schema = $arguments[$field] ?? [];
        $sanitize = $schema['sanitize_callback'] ?? null;
        $validate = $schema['validate_callback'] ?? null;
        $assert(is_callable($sanitize) && is_callable($validate),
            'Optional filter route callbacks missing: ' . $field);
        if (! is_callable($sanitize) || ! is_callable($validate)) { continue; }
        foreach ($candidates as $value) {
            $clean = $sanitize($value);
            $assert($validate($clean) === false,
                'Malformed ' . $field . ' accidentally passed after sanitization.');
        }
    }
    $valid = [
        'content_type' => 'post',
        'provider' => 'file-21',
        'year' => '2026',
        'language' => 'ur-PK',
        'topic' => '  Homeopathy  ',
        'sort' => 'oldest',
    ];
    foreach ($valid as $field => $value) {
        $schema = $arguments[$field];
        $clean = ($schema['sanitize_callback'])($value);
        $assert(($schema['validate_callback'])($clean) === true,
            'Valid ' . $field . ' filtered query improperly rejected.');
        if ($field === 'topic') {
            $assert($clean === 'Homeopathy', 'Valid topic normalization must be retained.');
        }
    }

    // Verify REST manifest has the *same real available provider sections*
    // as the server renderer, and respects File25 administrator opt-outs.
    $method = new \ReflectionMethod($controller, 'profile_response');
    $method->setAccessible(true);
    $GLOBALS['f25_review915_options'] = [];
    $output = $method->invoke($controller, new WP_User(), null);
    $data = $output->get_data();
    $assert($output->get_status() === 200
        && in_array('knowledge', $data['available_sections'] ?? [], true)
        && in_array('media', $data['available_sections'] ?? [], true)
        && ($data['section_labels']['knowledge'] ?? '') === 'Knowledge',
        'Public REST profile omits a visible HTML provider tab.');
    $GLOBALS['f25_review915_options'] = ['enabled_public_tabs' => ['overview','knowledge']];
    $data = $method->invoke($controller, new WP_User(), null)->get_data();
    $assert(in_array('knowledge', $data['available_sections'] ?? [], true)
        && ! in_array('media', $data['available_sections'] ?? [], true),
        'REST provider tabs must remain bounded by administrator allowlist.');
    $GLOBALS['f25_review915_options'] = ['public_visibility' => false];
    $output = $method->invoke($controller, new WP_User(), null);
    $assert($output->get_status() === 404,
        'Global public profile opt-out must also close REST provider discovery.');

    if ($errors !== []) {
        fwrite(STDERR, "FAILED: File25 Reviews 875-914 REST schema and tab parity\n- "
            . implode("\n- ", $errors) . "\n");
        exit(1);
    }
    echo "PASS: File25 Reviews 875-914 six strict REST filters and visible provider tabs\n";
}
