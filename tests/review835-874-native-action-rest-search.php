<?php
declare(strict_types=1);

// Reviews 835–874: executable PHP 8.0+ contract regressions for native action
// destinations and exact REST query validation, with no WordPress installation.
if (! defined('ABSPATH')) { define('ABSPATH', __DIR__ . '/fixtures/'); }
$GLOBALS['f25_test_rest_routes'] = [];
$GLOBALS['f25_test_action_url'] = '/appointments/book/invented-target/';

if (! function_exists('register_rest_route')) {
    function register_rest_route(string $namespace, string $route, array $args): void
    {
        $GLOBALS['f25_test_rest_routes'][$namespace . $route] = $args;
    }
}
if (! function_exists('apply_filters')) {
    function apply_filters(string $name, mixed $value, mixed ...$args): mixed
    {
        return $name === 'sabri_public_experience/action_url'
            ? $GLOBALS['f25_test_action_url'] : $value;
    }
}
if (! function_exists('wp_strip_all_tags')) {
    function wp_strip_all_tags(string $text): string { return strip_tags($text); }
}

require_once dirname(__DIR__) . '/includes/class-public-url.php';
require_once dirname(__DIR__) . '/includes/class-plan-completion.php';
require_once dirname(__DIR__) . '/includes/class-rest-controller.php';

use Sabri\PublicExperience\Plan_Completion;
use Sabri\PublicExperience\Rest_Controller;

$errors = [];
$check = static function (bool $condition, string $message) use (&$errors): void {
    if (! $condition) { $errors[] = $message; }
};

// The native adapter denies File08 Appointment/File17 Follow but a malicious,
// misplaced, or late extension filter must not re-enable either action.
$action = new ReflectionMethod(Plan_Completion::class, 'action_url');
$action->setAccessible(true);
foreach (['follow', 'appointment'] as $name) {
    $check($action->invoke(null, $name, 42) === '',
        'Unaccepted native action reactivated by later same-site filter: ' . $name);
}
$GLOBALS['f25_test_action_url'] = '/member/message/';
$check($action->invoke(null, 'message', 42) === '/member/message/',
    'Safe same-site message link must retain existing approved extension behavior.');
$GLOBALS['f25_test_action_url'] = 'https://attacker.invalid/redirect/';
$check($action->invoke(null, 'message', 42) === '',
    'External same-site action boundary must reject offsite destinations.');

// Capture the actual REST registration callbacks instead of merely matching
// text, then check sanitizer->validator parity for malformed input.
$rest = (new ReflectionClass(Rest_Controller::class))->newInstanceWithoutConstructor();
$rest->register_routes();
$route = $GLOBALS['f25_test_rest_routes']['sabri-public/v1/profiles/(?P<slug>[a-zA-Z0-9_-]+)/timeline'] ?? [];
$arg = $route['args']['search'] ?? [];
$sanitize = $arg['sanitize_callback'] ?? null;
$validate = $arg['validate_callback'] ?? null;
$check(is_callable($sanitize) && is_callable($validate),
    'Public Timeline REST search callbacks are missing.');

if (is_callable($sanitize) && is_callable($validate)) {
    foreach ([
        str_repeat('a', 121),
        "private\nquery",
        "\u{200B}hidden",
        ['array-instead-of-search'],
    ] as $invalid) {
        $sanitized = $sanitize($invalid);
        $check($validate($sanitized) === false,
            'Invalid REST search became valid/unfiltered after sanitization.');
    }
    $valid = $sanitize('  public   article  ');
    $check($valid === 'public article' && $validate($valid) === true,
        'Valid bounded REST search must normalize and remain valid.');
    $empty = $sanitize('');
    $check($empty === '' && $validate($empty) === true,
        'Intentionally unfiltered Timeline remains available.');
}

if ($errors !== []) {
    fwrite(STDERR, "FAILED Reviews 835-874 action / REST search parity\n- " . implode("\n- ", $errors) . "\n");
    exit(1);
}
echo "PASS: Reviews 835-874 File08/File17 native-action lock and strict REST search input\n";
