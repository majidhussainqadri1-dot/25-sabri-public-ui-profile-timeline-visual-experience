<?php
declare(strict_types=1);
/** File25 R1555–1563: genuine Plan_Completion REST replay and reconciliation behavior. */
if (! defined('ABSPATH')) { define('ABSPATH', __DIR__ . '/fixtures/'); }
$GLOBALS['r1555_options'] = [];
$GLOBALS['r1555_receipts'] = [];
$GLOBALS['r1555_accept'] = false;
$GLOBALS['r1555_scope'] = 'file-25-public-projection';
$GLOBALS['r1555_mutated'] = false;
$GLOBALS['r1555_actions'] = [];
function get_option(string $key, mixed $default = false): mixed {
    return array_key_exists($key, $GLOBALS['r1555_options']) ? $GLOBALS['r1555_options'][$key] : $default;
}
function update_option(string $key, mixed $value, mixed $autoload = null): bool {
    $GLOBALS['r1555_options'][$key] = $value; return true;
}
function add_option(string $key, mixed $value, string $deprecated = '', mixed $autoload = null): bool {
    if (array_key_exists($key, $GLOBALS['r1555_options'])) { return false; }
    $GLOBALS['r1555_options'][$key] = $value; return true;
}
function delete_option(string $key): bool { unset($GLOBALS['r1555_options'][$key]); return true; }
function get_transient(string $key): mixed { return $GLOBALS['r1555_receipts'][$key] ?? false; }
function set_transient(string $key, mixed $value, int $ttl = 0): bool {
    $GLOBALS['r1555_receipts'][$key] = $value; return true;
}
function sanitize_key(string $value): string { return (string) preg_replace('/[^a-z0-9_-]/', '', strtolower($value)); }
function get_current_user_id(): int { return 42; }
function get_user_by(string $field, int $id): mixed { return $field === 'id' && $id > 0 ? (object) ['ID' => $id] : false; }
function clean_user_cache(int $id): void {}
function wp_json_encode(mixed $data, int $flags = 0): string|false { return json_encode($data, $flags); }
function wp_generate_uuid4(): string { return '12345678-1234-4234-8234-123456789012'; }
function do_action(string $hook, mixed ...$args): void { $GLOBALS['r1555_actions'][] = [$hook, $args]; }
function apply_filters(string $hook, mixed $value, mixed ...$args): mixed {
    if ($hook === 'sabri_public_experience/reconcile_profile_projection' && $GLOBALS['r1555_accept']) {
        return [
            'accepted' => $GLOBALS['r1555_accept'] === 'string' ? 'true' : true,
            'scope' => $GLOBALS['r1555_scope'],
            'owner_data_mutated' => $GLOBALS['r1555_mutated'],
        ];
    }
    return $value;
}
class WP_REST_Request {
    public function __construct(private ?array $body = null, private array $params = [], private array $headers = []) {}
    public function get_json_params(): ?array { return $this->body; }
    public function get_param(string $key): mixed { return $this->params[$key] ?? null; }
    public function get_header(string $key): string { return (string) ($this->headers[$key] ?? ''); }
}
class WP_REST_Response {
    private array $headers = [];
    public function __construct(private mixed $data, private int $status) {}
    public function header(string $name, string $value): void { $this->headers[$name] = $value; }
    public function get_headers(): array { return $this->headers; }
    public function get_data(): mixed { return $this->data; }
    public function get_status(): int { return $this->status; }
}
require_once dirname(__DIR__) . '/includes/class-plan-completion.php';
$service = new \Sabri\PublicExperience\Plan_Completion();
$errors = [];
$check = static function (bool $condition, string $reason) use (&$errors): void {
    if (! $condition) { $errors[] = $reason; }
};
$preference_request = static fn (array $body, string $key): WP_REST_Request =>
    new WP_REST_Request($body, [], ['Idempotency-Key' => $key]);
$reconcile_request = static fn (int $id, string $key): WP_REST_Request =>
    new WP_REST_Request(null, ['user_id' => $id], ['Idempotency-Key' => $key]);
$reset_rate = static function (): void {
    foreach (array_keys($GLOBALS['r1555_options']) as $key) {
        if (str_starts_with($key, 'spux_rate_')) { unset($GLOBALS['r1555_options'][$key]); }
    }
};

// One accepted preference update has exactly one canonical mutation intent.
$original = ['public_visibility' => false, 'revision' => 0];
$first = $service->rest_preferences($preference_request($original, 'r1555-prefs-key'));
$check($first->get_status() === 200 && ($first->get_data()['status'] ?? '') === 'updated',
    'Initial preference write should succeed.');
$stored = $GLOBALS['r1555_receipts'];
$check(count($stored) === 1 && is_string(reset($stored)['fingerprint'] ?? null),
    'Completed receipt must contain a hashed mutation fingerprint.');
$replay = $service->rest_preferences($preference_request(['revision' => 0, 'public_visibility' => false], 'r1555-prefs-key'));
$check($replay->get_status() === 200 && $replay->get_data() === $first->get_data(),
    'Equivalent preference payload with reordered keys should replay safely.');
$different = $service->rest_preferences($preference_request(
    ['public_visibility' => true, 'revision' => 0], 'r1555-prefs-key'
));
$check($different->get_status() === 409
    && ($different->get_data()['code'] ?? '') === 'idempotency_payload_conflict',
    'Same idempotency key with different privacy action must be rejected with 409.');
$check(\Sabri\PublicExperience\Plan_Completion::preferences()['public_visibility'] === false,
    'Conflicting replay must not re-enable a public privacy setting.');

// A legacy receipt with no fingerprint is untrusted and may not be replayed.
foreach (array_keys($GLOBALS['r1555_receipts']) as $receipt_key) {
    unset($GLOBALS['r1555_receipts'][$receipt_key]['fingerprint']);
}
$legacy = $service->rest_preferences($preference_request($original, 'r1555-prefs-key'));
$check($legacy->get_status() === 409,
    'Old unbound idempotency receipts must fail closed instead of inventing proof of matching payload.');
$reset_rate();

// File25 cache invalidation alone is not source-owner reconciliation.
$before_actions = count($GLOBALS['r1555_actions']);
$none = $service->rest_reconcile_profile($reconcile_request(11, 'r1555-reconcile-1'));
$check($none->get_status() === 502
    && ($none->get_data()['code'] ?? '') === 'reconciliation_contract_failed',
    'No native accepted reconciliation contract must return 502.');
$check(count($GLOBALS['r1555_actions']) === $before_actions,
    'Missing native adapter must not emit profile_reconciled event.');
$reset_rate();
$GLOBALS['r1555_accept'] = 'string';
$not_bool = $service->rest_reconcile_profile($reconcile_request(11, 'r1555-reconcile-2'));
$check($not_bool->get_status() === 502, 'Truth-like string accepted flag must fail.');
$reset_rate();
$GLOBALS['r1555_accept'] = true;
$GLOBALS['r1555_mutated'] = true;
$unsafe_owner = $service->rest_reconcile_profile($reconcile_request(11, 'r1555-reconcile-3'));
$check($unsafe_owner->get_status() === 502, 'Adapter must not claim canonical owner writes.');
$reset_rate();
$GLOBALS['r1555_mutated'] = false;
$GLOBALS['r1555_scope'] = 'foreign-owner-write';
$wrong_scope = $service->rest_reconcile_profile($reconcile_request(11, 'r1555-reconcile-4'));
$check($wrong_scope->get_status() === 502, 'Reconcile scope must remain File25-owned projection only.');
$reset_rate();
$GLOBALS['r1555_scope'] = 'file-25-public-projection';
$accepted = $service->rest_reconcile_profile($reconcile_request(11, 'r1555-reconcile-5'));
$check($accepted->get_status() === 200 && ($accepted->get_data()['reconciled'] ?? null) === true,
    'An explicit verified in-scope adapter response should permit a success result.');
$replay_same = $service->rest_reconcile_profile($reconcile_request(11, 'r1555-reconcile-5'));
$check($replay_same->get_status() === 200 && $replay_same->get_data() === $accepted->get_data(),
    'Same profile/key must replay the accepted receipt.');
$replay_different = $service->rest_reconcile_profile($reconcile_request(12, 'r1555-reconcile-5'));
$check($replay_different->get_status() === 409,
    'Same key reused for a DIFFERENT user profile must fail with 409.');
$events = array_filter($GLOBALS['r1555_actions'],
    static fn (array $e): bool => $e[0] === 'sabri_public_experience/profile_reconciled');
$check(count($events) === 1, 'Only the real accepted fresh reconciliation may emit the success event.');

if ($errors !== []) {
    fwrite(STDERR, "FAILED File25 R1555–1563\n- " . implode("\n- ", $errors) . "\n");
    exit(1);
}
echo "PASS File25 R1555–1563: mutation-specific replay fingerprints and accepted-only profile reconciliation.\n";
