<?php

declare(strict_types=1);

namespace Sabri\PublicExperience;

if (! defined('ABSPATH') && PHP_SAPI !== 'cli') {
    exit;
}

/**
 * File 25 source-completion services.
 *
 * This class owns presentation preferences, public-profile projection helpers,
 * a derivative timeline-index lifecycle, File-25-only repair, and reversible
 * legacy-route migration. It never becomes the owner of identity, profile
 * master data, publications, appointments, communications, security policy,
 * or global search.
 */
final class Plan_Completion
{
    private const OPTION = 'sabri_public_experience_preferences';
    private const OPTION_META = 'sabri_public_experience_preferences_meta';
    private const OPTION_HISTORY = 'sabri_public_experience_preferences_history';
    private const INDEX_OPTION = 'sabri_public_experience_timeline_index';
    private const INDEX_LOCK_OPTION = 'sabri_public_experience_timeline_index_lock';
    private const MIGRATION_OPTION = 'sabri_public_experience_migration_state';
    private const MIGRATION_ACTIVE_OPTION = 'sabri_public_experience_legacy_redirects';
    private const REPAIR_OPTION = 'sabri_public_experience_repair_state';
    private const MAX_SEARCH_QUERY = 120;
    private const MAX_SEARCH_RESULTS = 100;
    private const MAX_METRIC_VALUE = 1000000000;
    private const RATE_LIMIT_SECONDS = 10;
    private const IDEMPOTENCY_TTL = 600;
    private const PREVIEW_MODES = ['public', 'member', 'mobile', 'desktop', 'contact', 'search', 'social'];
    private const SECTIONS = [
        'overview', 'timeline', 'books-research', 'clinic-contact', 'clinic', 'about',
        'knowledge', 'media', 'videos', 'reels', 'pdfs', 'marketplace',
    ];

    /** @var array<string,array<string,mixed>> */
    private const PREFERENCE_SCHEMA = [
        'profile_template' => ['type' => 'enum', 'default' => 'standard', 'allowed' => ['standard', 'compact', 'institutional']],
        'timeline_provider_mode' => ['type' => 'enum', 'default' => 'federated', 'allowed' => ['federated', 'indexed']],
        'public_visibility' => ['type' => 'bool', 'default' => true],
        'responsive_preview' => ['type' => 'enum', 'default' => 'desktop', 'allowed' => ['mobile', 'tablet', 'desktop']],
        'accessibility_mode' => ['type' => 'bool', 'default' => true],
        'seo_enabled' => ['type' => 'bool', 'default' => true],
        'cache_ttl' => ['type' => 'int', 'default' => 15, 'minimum' => 1, 'maximum' => 1440],
        'safe_mode_controls' => ['type' => 'bool', 'default' => true],
        'diagnostics_enabled' => ['type' => 'bool', 'default' => true],
        'visual_density' => ['type' => 'enum', 'default' => 'comfortable', 'allowed' => ['comfortable', 'compact']],
        'featured_section_order' => ['type' => 'list', 'default' => ['overview', 'timeline', 'about'], 'allowed' => self::SECTIONS, 'maximum' => 12],
        'enabled_public_tabs' => ['type' => 'list', 'default' => self::SECTIONS, 'allowed' => self::SECTIONS, 'maximum' => 12],
        'cover_focal_point' => ['type' => 'enum', 'default' => 'center', 'allowed' => ['center', 'top', 'bottom', 'left', 'right']],
        'profile_local_search' => ['type' => 'bool', 'default' => true],
        'public_metrics' => ['type' => 'bool', 'default' => true],
    ];

    /** @var array<string,string> */
    private const OPERATION_CAPABILITIES = [
        'preferences' => 'manage_options',
        'repair' => 'manage_options',
        'rebuild_index' => 'manage_options',
        'reconcile_profile' => 'manage_options',
        'migration_dry_run' => 'manage_options',
        'migration_execute' => 'manage_options',
        'migration_rollback' => 'manage_options',
    ];

    public function register(): void
    {
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_action('admin_post_spux_save_preferences', [$this, 'handle_save_preferences']);
        add_action('admin_post_spux_repair', [$this, 'handle_repair']);
        add_action('admin_post_spux_rebuild_index', [$this, 'handle_rebuild']);
        add_action('admin_post_spux_migration_dry_run', [$this, 'handle_migration_dry_run']);
        add_action('admin_post_spux_migration_execute', [$this, 'handle_migration_execute']);
        add_action('admin_post_spux_migration_rollback', [$this, 'handle_migration_rollback']);
        add_action('template_redirect', [$this, 'apply_legacy_redirects'], 0);
        add_filter('wp_robots', [$this, 'profile_robots'], 99);
    }

    public function admin_menu(): void
    {
        add_menu_page(
            __('File 25 Visual Experience', 'sabri-public-experience'),
            __('File 25 Visual Experience', 'sabri-public-experience'),
            self::capability_for('preferences'),
            'sabri-public-experience',
            [$this, 'render_admin'],
            'dashicons-layout',
            81
        );
    }

    public function register_settings(): void
    {
        // Registering the schema keeps WordPress ownership and REST discovery
        // explicit. Writes use the audited admin/REST handlers below.
        register_setting('spux_settings', self::OPTION, [
            'type' => 'object',
            'sanitize_callback' => [$this, 'sanitize_preferences'],
            'default' => self::default_preferences(),
            'show_in_rest' => false,
        ]);
    }

    /** @param mixed $value @return array<string,mixed> */
    public function sanitize_preferences($value): array
    {
        $input = is_array($value) ? $value : [];
        return self::normalize_preferences($input, self::default_preferences(), false);
    }

    /** @return array<string,mixed> */
    public static function preferences(): array
    {
        $stored = function_exists('get_option') ? get_option(self::OPTION, []) : [];
        $stored = is_array($stored) ? $stored : [];
        return self::normalize_preferences($stored, self::default_preferences(), true);
    }

    /** @return array<string,mixed> */
    private static function default_preferences(): array
    {
        $defaults = [];
        foreach (self::PREFERENCE_SCHEMA as $key => $definition) {
            $defaults[$key] = $definition['default'];
        }
        return $defaults;
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $base
     * @return array<string,mixed>
     */
    private static function normalize_preferences(array $input, array $base, bool $partial): array
    {
        $clean = $partial ? array_merge(self::default_preferences(), $base) : self::default_preferences();
        foreach (self::PREFERENCE_SCHEMA as $key => $definition) {
            if ($partial && ! array_key_exists($key, $input)) {
                continue;
            }
            $value = $input[$key] ?? $definition['default'];
            $type = (string) $definition['type'];
            if ($type === 'bool') {
                $parsed = self::exact_boolean($value);
                $clean[$key] = $parsed ?? (bool) $definition['default'];
                continue;
            }
            if ($type === 'int') {
                $integer = self::exact_integer($value);
                if ($integer === null) {
                    $integer = (int) $definition['default'];
                }
                $clean[$key] = max((int) $definition['minimum'], min((int) $definition['maximum'], $integer));
                continue;
            }
            if ($type === 'enum') {
                $candidate = is_string($value) ? $value : '';
                $clean[$key] = in_array($candidate, (array) $definition['allowed'], true)
                    ? $candidate
                    : (string) $definition['default'];
                continue;
            }
            if ($type === 'list') {
                $clean[$key] = self::normalize_key_list(
                    $value,
                    (array) $definition['allowed'],
                    (int) $definition['maximum'],
                    (array) $definition['default']
                );
            }
        }
        return $clean;
    }

    /** @param mixed $value */
    private static function exact_boolean($value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if ($value === 1 || $value === '1') {
            return true;
        }
        if ($value === 0 || $value === '0') {
            return false;
        }
        return null;
    }

    /** @param mixed $value */
    private static function exact_integer($value): ?int
    {
        if (is_int($value)) {
            return $value;
        }
        if (! is_string($value) || preg_match('/^(?:0|[1-9][0-9]*)$/', $value) !== 1 || strlen($value) > 10) {
            return null;
        }
        $integer = (int) $value;
        return (string) $integer === $value ? $integer : null;
    }

    /** @param mixed $value @param list<string> $allowed @param list<string> $fallback @return list<string> */
    private static function normalize_key_list($value, array $allowed, int $maximum, array $fallback): array
    {
        $values = is_array($value) ? $value : explode(',', is_string($value) ? $value : '');
        $clean = [];
        foreach ($values as $candidate) {
            if (! is_string($candidate)) {
                continue;
            }
            $candidate = trim($candidate);
            if ($candidate === '' || strlen($candidate) > 64 || sanitize_key($candidate) !== $candidate) {
                continue;
            }
            if (! in_array($candidate, $allowed, true) || in_array($candidate, $clean, true)) {
                continue;
            }
            $clean[] = $candidate;
            if (count($clean) >= $maximum) {
                break;
            }
        }
        return $clean !== [] ? $clean : array_values($fallback);
    }

    public function render_admin(): void
    {
        if (! self::can_operate('preferences')) {
            wp_die(esc_html__('You are not allowed to manage File 25.', 'sabri-public-experience'));
        }
        $preferences = self::preferences();
        $meta = self::preference_meta();
        $index = get_option(self::INDEX_OPTION, []);
        $migration = get_option(self::MIGRATION_OPTION, []);
        $repair = get_option(self::REPAIR_OPTION, []);
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('File 25 — Visual Experience Control Center', 'sabri-public-experience'); ?></h1>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="spux_save_preferences">
                <input type="hidden" name="revision" value="<?php echo esc_attr((string) $meta['revision']); ?>">
                <?php wp_nonce_field('spux_save_preferences'); ?>
                <table class="form-table" role="presentation">
                    <?php foreach (self::PREFERENCE_SCHEMA as $key => $definition) : ?>
                        <tr>
                            <th scope="row"><label for="spux-<?php echo esc_attr($key); ?>"><?php echo esc_html(ucwords(str_replace('_', ' ', $key))); ?></label></th>
                            <td><?php $this->render_preference_control($key, $definition, $preferences[$key]); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
                <?php submit_button(__('Save audited preferences', 'sabri-public-experience')); ?>
            </form>

            <h2><?php esc_html_e('Operations', 'sabri-public-experience'); ?></h2>
            <?php $this->operation_form('spux_rebuild_index', __('Rebuild Timeline Index', 'sabri-public-experience')); ?>
            <?php $this->operation_form('spux_repair', __('Run Safe Repair', 'sabri-public-experience')); ?>
            <?php $this->operation_form('spux_migration_dry_run', __('Migration Dry Run', 'sabri-public-experience')); ?>
            <?php $this->operation_form('spux_migration_execute', __('Execute Approved Migration', 'sabri-public-experience'), true); ?>
            <?php $this->operation_form('spux_migration_rollback', __('Rollback Migration', 'sabri-public-experience'), true); ?>

            <h2><?php esc_html_e('Current Evidence', 'sabri-public-experience'); ?></h2>
            <pre><?php echo esc_html((string) wp_json_encode(['preferences_meta' => $meta, 'index' => $index, 'migration' => $migration, 'repair' => $repair], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)); ?></pre>
        </div>
        <?php
    }

    /** @param array<string,mixed> $definition @param mixed $value */
    private function render_preference_control(string $key, array $definition, $value): void
    {
        $name = self::OPTION . '[' . $key . ']';
        $type = (string) $definition['type'];
        if ($type === 'bool') {
            echo '<input type="hidden" name="' . esc_attr($name) . '" value="0">';
            echo '<input id="spux-' . esc_attr($key) . '" type="checkbox" name="' . esc_attr($name) . '" value="1" ' . checked($value === true, true, false) . '>';
            return;
        }
        if ($type === 'int') {
            echo '<input id="spux-' . esc_attr($key) . '" type="number" min="' . esc_attr((string) $definition['minimum']) . '" max="' . esc_attr((string) $definition['maximum']) . '" name="' . esc_attr($name) . '" value="' . esc_attr((string) $value) . '">';
            return;
        }
        if ($type === 'enum') {
            echo '<select id="spux-' . esc_attr($key) . '" name="' . esc_attr($name) . '">';
            foreach ((array) $definition['allowed'] as $option) {
                echo '<option value="' . esc_attr((string) $option) . '" ' . selected($value, $option, false) . '>' . esc_html(ucwords(str_replace('-', ' ', (string) $option))) . '</option>';
            }
            echo '</select>';
            return;
        }
        echo '<input id="spux-' . esc_attr($key) . '" type="text" class="large-text" name="' . esc_attr($name) . '" value="' . esc_attr(implode(',', (array) $value)) . '" aria-describedby="spux-' . esc_attr($key) . '-help">';
        echo '<p id="spux-' . esc_attr($key) . '-help" class="description">' . esc_html__('Comma-separated approved section keys.', 'sabri-public-experience') . '</p>';
    }

    private function operation_form(string $action, string $label, bool $high_risk = false): void
    {
        ?><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block;margin:0 8px 8px 0"<?php echo $high_risk ? ' data-spux-confirm="high-risk"' : ''; ?>>
            <input type="hidden" name="action" value="<?php echo esc_attr($action); ?>">
            <?php wp_nonce_field($action); ?>
            <?php submit_button($label, $high_risk ? 'secondary' : 'secondary', 'submit', false); ?>
        </form><?php
    }

    public function register_rest_routes(): void
    {
        register_rest_route('sabri-public/v1', '/preferences', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_preferences'],
            'permission_callback' => static fn (): bool => self::can_operate('preferences'),
        ]);
        register_rest_route('sabri-public/v1', '/admin/rebuild-index', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_rebuild_index'],
            'permission_callback' => static fn (): bool => self::can_operate('rebuild_index'),
        ]);
        register_rest_route('sabri-public/v1', '/admin/reconcile-profile', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_reconcile_profile'],
            'permission_callback' => static fn (): bool => self::can_operate('reconcile_profile'),
            'args' => ['user_id' => ['required' => true, 'type' => 'integer', 'minimum' => 1]],
        ]);
    }

    public function can_manage(): bool
    {
        return self::can_operate('preferences');
    }

    private static function capability_for(string $operation): string
    {
        $default = self::OPERATION_CAPABILITIES[$operation] ?? 'manage_options';
        $filtered = apply_filters('sabri_public_experience/operation_capability', $default, $operation);
        return is_string($filtered)
            && $filtered !== ''
            && strlen($filtered) <= 64
            && preg_match('/^[a-z][a-z0-9_]{0,63}$/', $filtered) === 1
                ? $filtered
                : $default;
    }

    private static function can_operate(string $operation): bool
    {
        if (! function_exists('is_user_logged_in') || ! is_user_logged_in()) {
            return false;
        }
        if (! current_user_can(self::capability_for($operation))) {
            return false;
        }
        if (in_array($operation, ['rebuild_index', 'migration_execute', 'migration_rollback'], true)) {
            return apply_filters('sabri_public_experience/high_risk_authorized', true, $operation, get_current_user_id()) === true;
        }
        return true;
    }

    public function rest_preferences(\WP_REST_Request $request): \WP_REST_Response
    {
        $preflight = $this->mutation_preflight('preferences', $request);
        if ($preflight instanceof \WP_REST_Response) {
            return $preflight;
        }
        $params = $request->get_json_params();
        if (! is_array($params)) {
            return $this->error_response('invalid_json', 400);
        }
        $allowed = array_merge(array_keys(self::PREFERENCE_SCHEMA), ['revision']);
        foreach (array_keys($params) as $key) {
            if (! is_string($key) || ! in_array($key, $allowed, true)) {
                return $this->error_response('unknown_preference', 400, ['field' => is_scalar($key) ? (string) $key : 'invalid']);
            }
        }
        $revision = array_key_exists('revision', $params) ? self::exact_integer($params['revision']) : null;
        unset($params['revision']);
        $result = $this->write_preferences($params, true, $revision, 'rest');
        if ($result['status'] === 'conflict') {
            return $this->error_response('revision_conflict', 409, ['revision' => $result['revision']]);
        }
        $response = $this->response($result, 200, (string) $result['updated_at_utc']);
        $this->store_idempotent_response('preferences', $request, $response);
        return $response;
    }

    public function rest_rebuild_index(\WP_REST_Request $request): \WP_REST_Response
    {
        $preflight = $this->mutation_preflight('rebuild_index', $request);
        if ($preflight instanceof \WP_REST_Response) {
            return $preflight;
        }
        $state = $this->rebuild_index();
        $status = ($state['status'] ?? '') === 'complete' ? 200 : 503;
        $response = $this->response($state, $status, (string) ($state['updated_at_utc'] ?? gmdate('Y-m-d H:i:s')));
        $this->store_idempotent_response('rebuild_index', $request, $response);
        return $response;
    }

    public function rest_reconcile_profile(\WP_REST_Request $request): \WP_REST_Response
    {
        $preflight = $this->mutation_preflight('reconcile_profile', $request);
        if ($preflight instanceof \WP_REST_Response) {
            return $preflight;
        }
        $user_id = self::exact_integer($request->get_param('user_id'));
        if ($user_id === null || $user_id < 1 || ! get_user_by('id', $user_id)) {
            return $this->error_response('profile_not_found', 404);
        }
        clean_user_cache($user_id);
        $result = apply_filters('sabri_public_experience/reconcile_profile_projection', [
            'accepted' => true,
            'scope' => 'file-25-public-projection',
            'owner_data_mutated' => false,
        ], $user_id);
        if (! is_array($result)
            || ($result['accepted'] ?? null) !== true
            || ($result['owner_data_mutated'] ?? null) !== false
        ) {
            return $this->error_response('reconciliation_contract_failed', 502);
        }
        $updated = gmdate('Y-m-d H:i:s');
        do_action('sabri_public_experience/profile_reconciled', $user_id, $result, $updated);
        $data = ['user_id' => $user_id, 'reconciled' => true, 'scope' => (string) ($result['scope'] ?? 'file-25-public-projection'), 'updated_at_utc' => $updated];
        $response = $this->response($data, 200, $updated);
        $this->store_idempotent_response('reconcile_profile', $request, $response);
        return $response;
    }

    /** @return array<string,mixed>|\WP_REST_Response */
    private function mutation_preflight(string $operation, \WP_REST_Request $request)
    {
        $idempotency = $this->idempotency_key($request);
        if ($idempotency === '') {
            return $this->error_response('idempotency_key_required', 400);
        }
        $cached = get_transient($this->idempotency_cache_key($operation, $idempotency));
        if (is_array($cached) && isset($cached['data'], $cached['status'], $cached['modified'])) {
            return $this->response((array) $cached['data'], (int) $cached['status'], (string) $cached['modified']);
        }
        $retry_after = $this->rate_limit($operation);
        if ($retry_after > 0) {
            return $this->error_response('rate_limited', 429, [], $retry_after);
        }
        return ['idempotency_key' => $idempotency];
    }

    private function rate_limit(string $operation): int
    {
        $user_id = get_current_user_id();
        if ($user_id < 1) {
            return self::RATE_LIMIT_SECONDS;
        }
        $key = 'spux_rate_' . hash('sha256', $operation . ':' . $user_id);
        $now = time();
        $expires = (int) get_option($key, 0);
        if ($expires > $now) {
            return max(1, $expires - $now);
        }
        if ($expires > 0) {
            delete_option($key);
        }
        if (! add_option($key, $now + self::RATE_LIMIT_SECONDS, '', false)) {
            return self::RATE_LIMIT_SECONDS;
        }
        return 0;
    }

    private function idempotency_key(\WP_REST_Request $request): string
    {
        $key = trim((string) $request->get_header('Idempotency-Key'));
        return strlen($key) >= 8
            && strlen($key) <= 128
            && preg_match('/^[A-Za-z0-9._:-]+$/', $key) === 1
                ? $key
                : '';
    }

    private function idempotency_cache_key(string $operation, string $key): string
    {
        return 'spux_idem_' . hash('sha256', get_current_user_id() . ':' . $operation . ':' . $key);
    }

    private function store_idempotent_response(string $operation, \WP_REST_Request $request, \WP_REST_Response $response): void
    {
        $key = $this->idempotency_key($request);
        if ($key === '') {
            return;
        }
        $headers = $response->get_headers();
        set_transient($this->idempotency_cache_key($operation, $key), [
            'data' => $response->get_data(),
            'status' => $response->get_status(),
            'modified' => (string) ($headers['Last-Modified'] ?? gmdate('D, d M Y H:i:s') . ' GMT'),
        ], self::IDEMPOTENCY_TTL);
    }

    private function response(array $data, int $status = 200, string $modified = ''): \WP_REST_Response
    {
        $canonical = self::canonicalize_for_hash($data);
        $encoded = wp_json_encode($canonical, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $etag = '"' . hash('sha256', is_string($encoded) ? $encoded : serialize($canonical)) . '"';
        $modified_header = self::http_date($modified);
        $response = new \WP_REST_Response($data, $status);
        $response->header('ETag', $etag);
        $response->header('Last-Modified', $modified_header);
        $response->header('Cache-Control', 'no-store, private, max-age=0');
        $response->header('X-Sabri-Request-ID', function_exists('wp_generate_uuid4') ? wp_generate_uuid4() : hash('sha256', uniqid('', true)));
        return $response;
    }

    private function error_response(string $code, int $status, array $extra = [], int $retry_after = 0): \WP_REST_Response
    {
        $data = array_merge(['code' => $code], $extra);
        $response = $this->response($data, $status, gmdate('Y-m-d H:i:s'));
        if ($retry_after > 0) {
            $response->header('Retry-After', (string) $retry_after);
        }
        return $response;
    }

    /** @param mixed $value @return mixed */
    private static function canonicalize_for_hash($value)
    {
        if (! is_array($value)) {
            return $value;
        }
        $is_list = array_keys($value) === range(0, count($value) - 1);
        if (! $is_list) {
            ksort($value, SORT_STRING);
        }
        foreach ($value as $key => $item) {
            $value[$key] = self::canonicalize_for_hash($item);
        }
        return $value;
    }

    private static function http_date(string $value): string
    {
        $timestamp = strtotime($value . ' UTC');
        if ($timestamp === false) {
            $timestamp = time();
        }
        return gmdate('D, d M Y H:i:s', $timestamp) . ' GMT';
    }

    /** @param array<string,mixed> $profile @return array<string,string> */
    public static function profile_actions(array $profile): array
    {
        $actions = [];
        $user_id = self::exact_integer($profile['user_id'] ?? null) ?? 0;
        $canonical = Public_URL::sanitize_same_site($profile['canonical_url'] ?? '', false);
        $current = function_exists('get_current_user_id') ? get_current_user_id() : 0;
        $owner = $current > 0 && $current === $user_id;
        $is_doctor = ($profile['verified'] ?? null) === true && ($profile['class'] ?? '') === 'doctor';
        if (function_exists('is_user_logged_in') && is_user_logged_in() && ! $owner && $user_id > 0) {
            $actions['follow'] = self::action_url('follow', $user_id);
            $actions['message'] = self::action_url('message', $user_id);
            if ($is_doctor) {
                $actions['appointment'] = self::action_url('appointment', $user_id);
            }
            $actions['report'] = self::action_url('report', $user_id);
        }
        if ($owner) {
            $actions['edit_profile'] = self::action_url('edit_profile', $user_id);
            if ($canonical !== '') {
                $actions['view_public'] = Public_URL::sanitize_same_site(add_query_arg('spux_preview', 'public', $canonical), false);
            }
            $actions['manage_privacy'] = self::action_url('manage_privacy', $user_id);
            $actions['composer'] = self::action_url('composer', $user_id);
            $actions['publishing_dashboard'] = self::action_url('publishing_dashboard', $user_id);
        }
        return array_filter($actions, static fn ($url): bool => is_string($url) && $url !== '');
    }

    private static function action_url(string $action, int $user_id): string
    {
        $candidate = apply_filters('sabri_public_experience/action_url', '', $action, $user_id);
        return Public_URL::sanitize_same_site($candidate, false);
    }

    public static function render_profile_actions(array $profile): string
    {
        $labels = [
            'follow' => __('Follow', 'sabri-public-experience'),
            'message' => __('Message', 'sabri-public-experience'),
            'appointment' => __('Appointment', 'sabri-public-experience'),
            'report' => __('Report', 'sabri-public-experience'),
            'edit_profile' => __('Edit Profile', 'sabri-public-experience'),
            'view_public' => __('View as Public', 'sabri-public-experience'),
            'manage_privacy' => __('Manage Privacy', 'sabri-public-experience'),
            'composer' => __('Open Composer', 'sabri-public-experience'),
            'publishing_dashboard' => __('Publishing Dashboard', 'sabri-public-experience'),
        ];
        $html = '';
        foreach (self::profile_actions($profile) as $action => $url) {
            if (! isset($labels[$action])) {
                continue;
            }
            $rel = $action === 'report' ? ' rel="nofollow"' : '';
            $html .= '<a class="spux-button spux-button--secondary" data-spux-action="' . esc_attr($action) . '" href="' . esc_url($url) . '"' . $rel . '>' . esc_html($labels[$action]) . '</a>';
        }
        return $html;
    }

    public static function normalize_search_query(string $query): string
    {
        $query = trim(wp_strip_all_tags($query));
        $length = function_exists('mb_strlen') ? mb_strlen($query, 'UTF-8') : strlen($query);
        if ($query === '' || $length > self::MAX_SEARCH_QUERY) {
            return '';
        }
        if (preg_match('/[\x00-\x1F\x7F]/', $query) === 1
            || preg_match('/[\x{00AD}\x{061C}\x{200B}-\x{200F}\x{202A}-\x{202E}\x{2060}-\x{206F}\x{FEFF}]/u', $query) === 1
        ) {
            return '';
        }
        return preg_replace('/\s+/u', ' ', $query) ?? '';
    }

    public static function timeline_item_matches_search(array $item, string $query): bool
    {
        $query = self::normalize_search_query($query);
        if ($query === '') {
            return false;
        }
        $fields = ['title', 'safe_excerpt', 'content_type', 'language'];
        $terms = [];
        foreach ($fields as $field) {
            if (isset($item[$field]) && is_scalar($item[$field])) {
                $terms[] = wp_strip_all_tags((string) $item[$field]);
            }
        }
        if (isset($item['public_terms']) && is_array($item['public_terms'])) {
            foreach (array_slice($item['public_terms'], 0, 20) as $term) {
                if (is_scalar($term)) {
                    $terms[] = wp_strip_all_tags((string) $term);
                }
            }
        }
        $haystack = implode(' ', $terms);
        $needle = function_exists('mb_strtolower') ? mb_strtolower($query, 'UTF-8') : strtolower($query);
        $haystack = function_exists('mb_strtolower') ? mb_strtolower($haystack, 'UTF-8') : strtolower($haystack);
        return str_contains($haystack, $needle);
    }

    /** @param list<array<string,mixed>> $items @return list<array<string,mixed>> */
    public static function search_timeline(array $items, string $query): array
    {
        $query = self::normalize_search_query($query);
        if ($query === '') {
            return $items;
        }
        $results = [];
        foreach (array_slice($items, 0, 500) as $item) {
            if (! is_array($item) || ! self::timeline_item_matches_search($item, $query)) {
                continue;
            }
            $results[] = $item;
            if (count($results) >= self::MAX_SEARCH_RESULTS) {
                break;
            }
        }
        return $results;
    }

    /** @param array<string,mixed> $profile @return array<string,int> */
    public static function public_metrics(array $profile): array
    {
        if (self::preferences()['public_metrics'] !== true) {
            return [];
        }
        $user_id = self::exact_integer($profile['user_id'] ?? null) ?? 0;
        if ($user_id < 1) {
            return [];
        }
        $metrics = apply_filters('sabri_public_experience/public_metrics', [], $user_id, $profile);
        if (! is_array($metrics)) {
            return [];
        }
        $allowed = ['publications', 'videos', 'research', 'followers', 'views', 'saves', 'reviews'];
        $clean = [];
        foreach ($allowed as $key) {
            if (! array_key_exists($key, $metrics) || ! is_int($metrics[$key])) {
                continue;
            }
            $value = $metrics[$key];
            if ($value < 0 || $value > self::MAX_METRIC_VALUE) {
                continue;
            }
            $threshold = apply_filters('sabri_public_experience/public_metric_minimum', 0, $key, $user_id, $profile);
            if (! is_int($threshold) || $threshold < 0 || $threshold > self::MAX_METRIC_VALUE) {
                $threshold = 0;
            }
            if ($value < $threshold) {
                continue;
            }
            if (apply_filters('sabri_public_experience/public_metric_allowed', true, $key, $user_id, $profile) !== true) {
                continue;
            }
            $clean[$key] = $value;
        }
        return $clean;
    }

    /** @param array<string,mixed> $profile @return array<string,mixed> */
    public static function completion_assistant(array $profile): array
    {
        $user_id = self::exact_integer($profile['user_id'] ?? null) ?? 0;
        $current = function_exists('get_current_user_id') ? get_current_user_id() : 0;
        $authorized = $user_id > 0 && ($current === $user_id || (function_exists('current_user_can') && current_user_can('manage_options')));
        if (! $authorized) {
            return ['authorized' => false, 'complete' => false, 'missing' => [], 'preview_modes' => [], 'edit_url' => ''];
        }
        $missing = [];
        foreach (['display_name', 'avatar_url', 'cover_url', 'headline', 'bio'] as $field) {
            if (! isset($profile[$field]) || trim((string) $profile[$field]) === '') {
                $missing[] = $field;
            }
        }
        if (! empty($profile['avatar_url']) && empty($profile['avatar_alt'])) {
            $missing[] = 'avatar_alt_text';
        }
        if (empty($profile['contacts'])) {
            $missing[] = 'contact_privacy_or_contact';
        }
        if (($profile['class'] ?? '') === 'doctor' && ($profile['verified'] ?? null) !== true) {
            $missing[] = 'doctor_verification';
        }
        if (($profile['class'] ?? '') === 'doctor' && empty($profile['clinic'])) {
            $missing[] = 'clinic_information';
        }
        if (Public_URL::sanitize_same_site($profile['canonical_url'] ?? '', false) === '') {
            $missing[] = 'canonical_profile_url';
        }
        return [
            'authorized' => true,
            'complete' => $missing === [],
            'missing' => array_values(array_unique($missing)),
            'preview_modes' => self::PREVIEW_MODES,
            'edit_url' => self::action_url('edit_profile', $user_id),
        ];
    }

    /** @param array<string,mixed> $profile @return array<string,mixed> */
    public static function apply_profile_preferences(array $profile): array
    {
        $preferences = self::preferences();
        $enabled = (array) $preferences['enabled_public_tabs'];
        $labels = is_array($profile['section_labels'] ?? null) ? $profile['section_labels'] : [];
        $available = is_array($profile['available_sections'] ?? null) ? $profile['available_sections'] : [];
        $labels = array_filter($labels, static fn ($label, $key): bool => is_string($key) && in_array($key, $enabled, true) && is_scalar($label), ARRAY_FILTER_USE_BOTH);
        $available = array_values(array_filter($available, static fn ($section): bool => is_string($section) && in_array($section, $enabled, true)));
        if (! in_array('overview', $available, true)) {
            array_unshift($available, 'overview');
        }
        $order = array_values(array_unique(array_merge((array) $preferences['featured_section_order'], $enabled)));
        uksort($labels, static function (string $left, string $right) use ($order): int {
            $left_index = array_search($left, $order, true);
            $right_index = array_search($right, $order, true);
            return ($left_index === false ? PHP_INT_MAX : $left_index) <=> ($right_index === false ? PHP_INT_MAX : $right_index);
        });
        $profile['section_labels'] = $labels;
        $profile['available_sections'] = $available;
        $profile['visual_density'] = (string) $preferences['visual_density'];
        $profile['cover_focal_point'] = (string) $preferences['cover_focal_point'];
        return $profile;
    }

    /** @param array<string,mixed> $profile */
    public static function preview_mode(array $profile): string
    {
        if (! isset($_GET['spux_preview']) || ! is_scalar($_GET['spux_preview'])) {
            return '';
        }
        $raw = (string) wp_unslash($_GET['spux_preview']);
        if (sanitize_key($raw) !== $raw || ! in_array($raw, self::PREVIEW_MODES, true)) {
            return 'denied';
        }
        $user_id = self::exact_integer($profile['user_id'] ?? null) ?? 0;
        $current = get_current_user_id();
        return $user_id > 0 && ($current === $user_id || current_user_can('manage_options')) ? $raw : 'denied';
    }

    /** @param array<string,bool> $robots @return array<string,bool> */
    public function profile_robots(array $robots): array
    {
        if (self::preferences()['seo_enabled'] !== true || ! empty($GLOBALS['sabri_public_experience_context']['preview_mode'])) {
            $robots['noindex'] = true;
            $robots['noarchive'] = true;
        }
        return $robots;
    }

    public function handle_save_preferences(): void
    {
        $this->guard_admin_post('spux_save_preferences', 'preferences');
        $raw = isset($_POST[self::OPTION]) && is_array($_POST[self::OPTION])
            ? wp_unslash($_POST[self::OPTION])
            : [];
        $revision = isset($_POST['revision']) ? self::exact_integer(wp_unslash($_POST['revision'])) : null;
        $result = $this->write_preferences((array) $raw, false, $revision, 'admin');
        $this->redirect_admin($result['status'] === 'conflict' ? 'revision_conflict' : 'preferences_saved');
    }

    public function handle_repair(): void
    {
        $this->guard_admin_post('spux_repair', 'repair');
        update_option(self::REPAIR_OPTION, $this->repair(), false);
        $this->redirect_admin('repair_complete');
    }

    public function handle_rebuild(): void
    {
        $this->guard_admin_post('spux_rebuild_index', 'rebuild_index');
        $this->rebuild_index();
        $this->redirect_admin('index_rebuild_finished');
    }

    public function handle_migration_dry_run(): void
    {
        $this->guard_admin_post('spux_migration_dry_run', 'migration_dry_run');
        update_option(self::MIGRATION_OPTION, $this->migration('dry-run'), false);
        $this->redirect_admin('migration_dry_run');
    }

    public function handle_migration_execute(): void
    {
        $this->guard_admin_post('spux_migration_execute', 'migration_execute');
        update_option(self::MIGRATION_OPTION, $this->migration('executed'), false);
        $this->redirect_admin('migration_executed');
    }

    public function handle_migration_rollback(): void
    {
        $this->guard_admin_post('spux_migration_rollback', 'migration_rollback');
        update_option(self::MIGRATION_OPTION, $this->migration('rolled-back'), false);
        $this->redirect_admin('migration_rolled_back');
    }

    private function guard_admin_post(string $nonce_action, string $operation): void
    {
        if (! self::can_operate($operation)) {
            wp_die(esc_html__('Unauthorized File 25 operation.', 'sabri-public-experience'));
        }
        check_admin_referer($nonce_action);
    }

    private function redirect_admin(string $notice): void
    {
        wp_safe_redirect(add_query_arg('spux_notice', sanitize_key($notice), admin_url('admin.php?page=sabri-public-experience')));
        exit;
    }

    /** @param array<string,mixed> $patch @return array<string,mixed> */
    private function write_preferences(array $patch, bool $partial, ?int $expected_revision, string $source): array
    {
        $current = self::preferences();
        $meta = self::preference_meta();
        if ($expected_revision !== null && $expected_revision !== (int) $meta['revision']) {
            return ['status' => 'conflict', 'revision' => (int) $meta['revision'], 'updated_at_utc' => (string) $meta['updated_at_utc']];
        }
        $clean = self::normalize_preferences($patch, $current, $partial);
        $history = get_option(self::OPTION_HISTORY, []);
        $history = is_array($history) ? $history : [];
        array_unshift($history, ['revision' => (int) $meta['revision'], 'preferences' => $current, 'updated_at_utc' => (string) $meta['updated_at_utc']]);
        $history = array_slice($history, 0, 10);
        $updated = gmdate('Y-m-d H:i:s');
        $revision = (int) $meta['revision'] + 1;
        update_option(self::OPTION_HISTORY, $history, false);
        update_option(self::OPTION, $clean, false);
        update_option(self::OPTION_META, [
            'revision' => $revision,
            'updated_at_utc' => $updated,
            'source' => $source,
            'hash' => hash('sha256', (string) wp_json_encode(self::canonicalize_for_hash($clean))),
        ], false);
        do_action('sabri_public_experience/preferences_updated', $clean, $current, $revision, $source);
        return ['status' => 'updated', 'preferences' => $clean, 'revision' => $revision, 'updated_at_utc' => $updated];
    }

    /** @return array{revision:int,updated_at_utc:string,source:string,hash:string} */
    private static function preference_meta(): array
    {
        $meta = function_exists('get_option') ? get_option(self::OPTION_META, []) : [];
        $meta = is_array($meta) ? $meta : [];
        return [
            'revision' => is_int($meta['revision'] ?? null) && $meta['revision'] >= 0 ? $meta['revision'] : 0,
            'updated_at_utc' => is_string($meta['updated_at_utc'] ?? null) ? $meta['updated_at_utc'] : '1970-01-01 00:00:00',
            'source' => is_string($meta['source'] ?? null) ? $meta['source'] : 'default',
            'hash' => is_string($meta['hash'] ?? null) && preg_match('/^[a-f0-9]{64}$/', $meta['hash']) === 1 ? $meta['hash'] : str_repeat('0', 64),
        ];
    }

    /** @return array<string,mixed> */
    private function rebuild_index(): array
    {
        $now = time();
        $lock = get_option(self::INDEX_LOCK_OPTION, []);
        if (is_array($lock) && (int) ($lock['expires'] ?? 0) > $now) {
            return ['status' => 'busy', 'updated_at_utc' => gmdate('Y-m-d H:i:s'), 'retry_after' => max(1, (int) $lock['expires'] - $now)];
        }
        delete_option(self::INDEX_LOCK_OPTION);
        $generation = wp_generate_uuid4();
        if (! add_option(self::INDEX_LOCK_OPTION, ['generation' => $generation, 'expires' => $now + 300], '', false)) {
            return ['status' => 'busy', 'updated_at_utc' => gmdate('Y-m-d H:i:s'), 'retry_after' => 300];
        }
        $started = gmdate('Y-m-d H:i:s');
        $running = ['status' => 'running', 'rebuildable' => true, 'generation' => $generation, 'started_at_utc' => $started, 'updated_at_utc' => $started];
        update_option(self::INDEX_OPTION, $running, false);
        try {
            $result = apply_filters('sabri_public_experience/timeline_index_build', [
                'accepted' => false,
                'items' => 0,
                'checksum' => '',
                'source_versions' => [],
            ], $generation);
            if (! is_array($result)
                || ($result['accepted'] ?? null) !== true
                || ! is_int($result['items'] ?? null)
                || $result['items'] < 0
                || $result['items'] > 1000000
                || ! is_string($result['checksum'] ?? null)
                || preg_match('/^[a-f0-9]{64}$/', $result['checksum']) !== 1
                || ! is_array($result['source_versions'] ?? null)
            ) {
                throw new \UnexpectedValueException('No accepted timeline-index builder contract was available.');
            }
            $completed = gmdate('Y-m-d H:i:s');
            $state = [
                'status' => 'complete',
                'rebuildable' => true,
                'generation' => $generation,
                'started_at_utc' => $started,
                'completed_at_utc' => $completed,
                'updated_at_utc' => $completed,
                'items' => $result['items'],
                'checksum' => $result['checksum'],
                'source_versions' => self::canonical_source_versions($result['source_versions']),
                'source' => 'federated-provider-pointers',
            ];
        } catch (\Throwable $exception) {
            $failed = gmdate('Y-m-d H:i:s');
            $state = [
                'status' => 'failed',
                'rebuildable' => true,
                'generation' => $generation,
                'started_at_utc' => $started,
                'failed_at_utc' => $failed,
                'updated_at_utc' => $failed,
                'error_code' => 'builder_contract_unavailable',
            ];
            do_action('sabri_public_experience/timeline_index_error', $state, $exception);
        } finally {
            delete_option(self::INDEX_LOCK_OPTION);
        }
        update_option(self::INDEX_OPTION, $state, false);
        do_action('sabri_public_experience/timeline_index_rebuilt', $state);
        return $state;
    }

    /** @param array<mixed> $versions @return array<string,string> */
    private static function canonical_source_versions(array $versions): array
    {
        $clean = [];
        foreach ($versions as $id => $version) {
            if (! is_string($id) || ! is_string($version)
                || sanitize_key($id) !== $id
                || preg_match('/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/', $version) !== 1
            ) {
                continue;
            }
            $clean[$id] = $version;
        }
        ksort($clean, SORT_STRING);
        return $clean;
    }

    /** @return array<string,mixed> */
    private function repair(): array
    {
        $operations = [];
        $current = get_option(self::OPTION, []);
        $current = is_array($current) ? $current : [];
        $normalized = self::normalize_preferences($current, self::default_preferences(), true);
        $operations['preferences'] = update_option(self::OPTION, $normalized, false) || $current === $normalized;
        $operations['provider_registry_cache'] = delete_transient('sabri_public_experience_provider_registry') || get_transient('sabri_public_experience_provider_registry') === false;
        $index = get_option(self::INDEX_OPTION, []);
        if (is_array($index) && ($index['status'] ?? '') === 'running') {
            $started = strtotime((string) ($index['started_at_utc'] ?? '') . ' UTC');
            if ($started !== false && $started < time() - 600) {
                $index['status'] = 'failed';
                $index['error_code'] = 'stale_rebuild_reconciled';
                $index['updated_at_utc'] = gmdate('Y-m-d H:i:s');
                update_option(self::INDEX_OPTION, $index, false);
            }
        }
        $operations['stale_index'] = true;
        $operations['rewrite_rules'] = true;
        Profile_Router::flush();
        $status = ! in_array(false, $operations, true) ? 'complete' : 'partial';
        $state = ['status' => $status, 'repaired_at_utc' => gmdate('Y-m-d H:i:s'), 'operations' => $operations, 'foreign_owner_data_touched' => false];
        do_action('sabri_public_experience/repaired', $state);
        return $state;
    }

    /** @return array<string,mixed> */
    private function migration(string $status): array
    {
        $previous = get_option(self::MIGRATION_OPTION, []);
        $previous = is_array($previous) ? $previous : [];
        $active = get_option(self::MIGRATION_ACTIVE_OPTION, []);
        $active = is_array($active) ? $active : [];
        if ($status === 'dry-run') {
            $validation = self::validated_redirect_map(apply_filters('sabri_public_experience/migration_redirect_map', []));
            $state = [
                'status' => $validation['collisions'] === 0 && $validation['invalid'] === 0 ? 'ready' : 'blocked',
                'requested_action' => 'dry-run',
                'inventory' => ['legacy_file_22_profile_routes', 'legacy_file_24_profile_routes'],
                'redirect_map' => $validation['map'],
                'map_checksum' => hash('sha256', (string) wp_json_encode($validation['map'])),
                'collision_count' => $validation['collisions'],
                'invalid_count' => $validation['invalid'],
                'previous_active_map' => $active,
                'updated_at_utc' => gmdate('Y-m-d H:i:s'),
                'rollback_available' => false,
            ];
            return $state;
        }
        if ($status === 'executed') {
            if (($previous['status'] ?? '') !== 'ready'
                || ! is_array($previous['redirect_map'] ?? null)
                || ($previous['collision_count'] ?? 1) !== 0
                || ($previous['invalid_count'] ?? 1) !== 0
                || ! is_string($previous['map_checksum'] ?? null)
                || ! hash_equals($previous['map_checksum'], hash('sha256', (string) wp_json_encode($previous['redirect_map'])))
            ) {
                return ['status' => 'blocked', 'requested_action' => 'executed', 'error_code' => 'approved_dry_run_required', 'updated_at_utc' => gmdate('Y-m-d H:i:s'), 'rollback_available' => false];
            }
            update_option(self::MIGRATION_ACTIVE_OPTION, $previous['redirect_map'], false);
            Profile_Router::flush();
            $previous['status'] = 'executed';
            $previous['requested_action'] = 'executed';
            $previous['executed_at_utc'] = gmdate('Y-m-d H:i:s');
            $previous['updated_at_utc'] = $previous['executed_at_utc'];
            $previous['rollback_available'] = true;
            return $previous;
        }
        if ($status === 'rolled-back') {
            if (($previous['status'] ?? '') !== 'executed' || ($previous['rollback_available'] ?? null) !== true) {
                return ['status' => 'blocked', 'requested_action' => 'rolled-back', 'error_code' => 'executed_migration_required', 'updated_at_utc' => gmdate('Y-m-d H:i:s'), 'rollback_available' => false];
            }
            $restore = is_array($previous['previous_active_map'] ?? null) ? $previous['previous_active_map'] : [];
            update_option(self::MIGRATION_ACTIVE_OPTION, $restore, false);
            Profile_Router::flush();
            $previous['status'] = 'rolled-back';
            $previous['requested_action'] = 'rolled-back';
            $previous['rolled_back_at_utc'] = gmdate('Y-m-d H:i:s');
            $previous['updated_at_utc'] = $previous['rolled_back_at_utc'];
            $previous['rollback_available'] = false;
            return $previous;
        }
        return ['status' => 'blocked', 'error_code' => 'unknown_migration_action', 'updated_at_utc' => gmdate('Y-m-d H:i:s')];
    }

    /** @param mixed $candidate @return array{map:list<array{from:string,to:string,status:int}>,collisions:int,invalid:int} */
    private static function validated_redirect_map($candidate): array
    {
        if (! is_array($candidate)) {
            return ['map' => [], 'collisions' => 0, 'invalid' => 1];
        }
        $map = [];
        $seen = [];
        $collisions = 0;
        $invalid = 0;
        foreach (array_slice($candidate, 0, 500) as $entry) {
            if (! is_array($entry)) {
                $invalid++;
                continue;
            }
            $from = is_string($entry['from'] ?? null) ? $entry['from'] : '';
            $to = Public_URL::sanitize_same_site($entry['to'] ?? '', false);
            $redirect_status = self::exact_integer($entry['status'] ?? 301);
            if ($from === ''
                || strlen($from) > 512
                || ! str_starts_with($from, '/')
                || str_starts_with($from, '//')
                || preg_match('/[\x00-\x20\x7F\\?#]/', $from) === 1
                || $to === ''
                || ! in_array($redirect_status, [301, 308], true)
                || Public_URL::canonical_same_site_identity($from, false) === Public_URL::canonical_same_site_identity($to, false)
            ) {
                $invalid++;
                continue;
            }
            if (isset($seen[$from])) {
                $collisions++;
                continue;
            }
            $seen[$from] = true;
            $map[] = ['from' => $from, 'to' => $to, 'status' => $redirect_status];
        }
        usort($map, static fn (array $left, array $right): int => strcmp($left['from'], $right['from']));
        return ['map' => $map, 'collisions' => $collisions, 'invalid' => $invalid];
    }

    public function apply_legacy_redirects(): void
    {
        if (is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
            return;
        }
        $map = get_option(self::MIGRATION_ACTIVE_OPTION, []);
        if (! is_array($map) || $map === []) {
            return;
        }
        $request_uri = isset($_SERVER['REQUEST_URI']) && is_string($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '';
        $path = parse_url($request_uri, PHP_URL_PATH);
        if (! is_string($path) || $path === '') {
            return;
        }
        foreach ($map as $entry) {
            if (! is_array($entry) || ($entry['from'] ?? '') !== $path) {
                continue;
            }
            $to = Public_URL::sanitize_same_site($entry['to'] ?? '', false);
            $status = self::exact_integer($entry['status'] ?? null);
            if ($to !== '' && in_array($status, [301, 308], true)) {
                wp_safe_redirect($to, $status);
                exit;
            }
        }
    }

    /** @return array<string,mixed> */
    public static function uninstall_report(): array
    {
        return [
            'schema' => 1,
            'generated_at_utc' => gmdate('Y-m-d H:i:s'),
            'preserved_canonical_data' => true,
            'rebuildable_options' => [self::INDEX_OPTION, self::INDEX_LOCK_OPTION, self::REPAIR_OPTION],
            'reversible_migration_options' => [self::MIGRATION_OPTION, self::MIGRATION_ACTIVE_OPTION],
            'preferences_preserved' => true,
            'foreign_owner_data_touched' => false,
            'destructive_purge_performed' => false,
        ];
    }
}
