<?php
declare(strict_types=1);

/** File25 review: actual REST limiter, persistence-failure and bound paths. */
if (! defined('ABSPATH')) { define('ABSPATH', __DIR__ . '/fixtures/'); }
class WP_Error {
    public function __construct(public string $code, public string $message, public array $data = []) {}
    public function get_error_code(): string { return $this->code; }
    public function get_error_data(): array { return $this->data; }
}
class WP_REST_Request {
    public function get_route(): string { return '/sabri-public/v1/founder/timeline'; }
}
function __(string $text, string $domain = ''): string { return $text; }
function wp_using_ext_object_cache(): bool { return false; }
function wp_salt(string $scheme): string { return 'fixed-test-secret-not-real-credentials'; }
function get_current_user_id(): int { return 42; }
function apply_filters(string $name, mixed $value, mixed ...$args): mixed {
    return $name === 'sabri_public_experience/rest_rate_limit' ? 2 : $value;
}
$GLOBALS['f25_rate_store'] = [];
$GLOBALS['f25_rate_write_allowed'] = false;
function get_transient(string $key): mixed { return $GLOBALS['f25_rate_store'][$key] ?? false; }
function set_transient(string $key, mixed $value, int $ttl): bool {
    if (! $GLOBALS['f25_rate_write_allowed']) { return false; }
    $GLOBALS['f25_rate_store'][$key] = $value;
    return true;
}
require_once dirname(__DIR__) . '/includes/class-rest-controller.php';
$controller = (new ReflectionClass(\Sabri\PublicExperience\Rest_Controller::class))
    ->newInstanceWithoutConstructor();
$req = new WP_REST_Request();
$errors=[];
$check=static function (bool $ok, string $message) use (&$errors): void {
    if (! $ok) { $errors[]=$message; }
};
$failure=$controller->allow_public_request($req);
$check($failure instanceof WP_Error && $failure->get_error_code() === 'sabri_public_rate_storage_unavailable'
    && ($failure->get_error_data()['status']??0) === 503,
    'Failed transient persistence must not allow public REST request.');
$GLOBALS['f25_rate_write_allowed']=true;
$GLOBALS['f25_rate_store']=[];
$check($controller->allow_public_request($req) === true,'First bounded request must pass.');
$check($controller->allow_public_request($req) === true,'Second bounded request must pass.');
$limit=$controller->allow_public_request($req);
$check($limit instanceof WP_Error && $limit->get_error_code() === 'sabri_public_rate_limited'
    && ($limit->get_error_data()['status']??0) === 429,
    'Third request must trigger rate-limit 429 (limit=2).');
if($errors!==[]){
    fwrite(STDERR,"FAILED Reviews 995–1034 public limiter storage gate\n- ".implode("\n- ",$errors)."\n");
    exit(1);
}
echo "PASS: Reviews 995–1034 actual REST rate limiter fails closed on storage error and enforces 429\n";
