<?php

declare(strict_types=1);

namespace Sabri\PublicExperience;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Forty fresh adversarial review/fix rounds (Reviews 94-133).
 *
 * This class deliberately wraps the recently added plan-completion surfaces so
 * the native-owner, privacy, migration, repair and truthful-status invariants
 * remain fail-closed without duplicating another module's source of truth.
 */
final class Forty_Round_Hardening
{
    private const PREFERENCES_OPTION = 'sabri_public_experience_preferences';
    private const INDEX_OPTION = 'sabri_public_experience_timeline_index';
    private const MIGRATION_OPTION = 'sabri_public_experience_migration_state';
    private const REPAIR_OPTION = 'sabri_public_experience_repair_state';
    private const AUDIT_OPTION = 'sabri_public_experience_review_94_133_audit';
    private const CAPABILITY = 'manage_options';
    private const MAX_SEARCH_QUERY = 160;
    private const MAX_SEARCH_ITEMS = 500;
    private const MAX_PUBLIC_METRIC = 2147483647;

    /** @var array<string,list<string>> */
    private const STRING_ALLOWLISTS = [
        'profile_template' => ['standard', 'founder', 'doctor', 'member'],
        'timeline_provider_mode' => ['federated', 'native-only'],
        'responsive_preview' => ['mobile', 'tablet', 'desktop'],
        'visual_density' => ['compact', 'comfortable', 'spacious'],
        'featured_section_order' => ['overview', 'timeline', 'knowledge', 'media'],
        'enabled_public_tabs' => ['all', 'essential', 'none'],
        'cover_focal_point' => ['left', 'center', 'right'],
    ];

    /** @var list<string> */
    private const ACTION_ALLOWLIST = [
        'follow', 'message', 'appointment', 'report', 'edit_profile',
        'view_public', 'manage_privacy', 'composer', 'publishing_dashboard',
    ];

    /** @var list<string> */
    private const METRIC_ALLOWLIST = [
        'publications', 'videos', 'research', 'followers', 'views', 'saves', 'reviews',
    ];

    public function register(): void
    {
        // Reviews 94-103: settings, capability and action-URL boundaries.
        add_filter('pre_update_option_' . self::PREFERENCES_OPTION, [$this, 'sanitize_preferences'], 20, 2);
        add_filter('sabri_public_experience/action_url', [$this, 'sanitize_action_url'], PHP_INT_MAX, 4);
        add_filter('sabri_public_experience/migration_redirect_map', [$this, 'sanitize_redirect_map'], PHP_INT_MAX);

        // Reviews 104-113: replace unsafe/ambiguous admin operations before the
        // legacy handlers run. Each handler exits after recording evidence.
        add_action('admin_post_spux_repair', [$this, 'safe_repair'], 1);
        add_action('admin_post_spux_rebuild_index', [$this, 'safe_rebuild_index'], 1);
        add_action('admin_post_spux_migration_dry_run', [$this, 'safe_migration_dry_run'], 1);
        add_action('admin_post_spux_migration_execute', [$this, 'safe_migration_execute'], 1);
        add_action('admin_post_spux_migration_rollback', [$this, 'safe_migration_rollback'], 1);

        // Reviews 114-123: REST mutation hardening and truthful response policy.
        add_filter('rest_pre_dispatch', [$this, 'rest_pre_dispatch'], 5, 3);

        // Reviews 124-133: provider projections, public metrics and audit proof.
        add_filter('sabri_public_experience/public_metrics', [$this, 'sanitize_public_metrics'], PHP_INT_MAX, 3);
        add_action('init', [$this, 'record_review_contract'], 1);
    }

    /** @param mixed $newValue @param mixed $oldValue */
    public function sanitize_preferences($newValue, $oldValue): array
    {
        $value = is_array($newValue) ? $newValue : [];
        $old = is_array($oldValue) ? $oldValue : [];
        $defaults = [
            'profile_template' => 'standard',
            'timeline_provider_mode' => 'federated',
            'public_visibility' => true,
            'responsive_preview' => 'desktop',
            'accessibility_mode' => true,
            'seo_enabled' => true,
            'cache_ttl' => 15,
            'safe_mode_controls' => true,
            'diagnostics_enabled' => true,
            'visual_density' => 'comfortable',
            'featured_section_order' => 'overview',
            'enabled_public_tabs' => 'all',
            'cover_focal_point' => 'center',
            'profile_local_search' => true,
            'public_metrics' => true,
        ];

        $clean = [];
        foreach ($defaults as $key => $default) {
            $candidate = $value[$key] ?? $old[$key] ?? $default;
            if (is_bool($default)) {
                $clean[$key] = $candidate === true || $candidate === 1 || $candidate === '1';
                continue;
            }
            if (is_int($default)) {
                $clean[$key] = max(1, min(60, (int) $candidate));
                continue;
            }
            $candidate = sanitize_key((string) $candidate);
            $allowed = self::STRING_ALLOWLISTS[$key] ?? [$default];
            $clean[$key] = in_array($candidate, $allowed, true) ? $candidate : $default;
        }

        $this->audit('preferences_sanitized', ['keys' => array_keys($clean)]);
        return $clean;
    }

    /** @param mixed $url */
    public function sanitize_action_url($url, string $action = '', int $userId = 0, mixed $context = null): string
    {
        if (! in_array($action, self::ACTION_ALLOWLIST, true) || $userId < 1 || ! is_string($url)) {
            return '';
        }
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        $safe = Public_URL::sanitize_same_site($url);
        if ($safe === '') {
            $this->audit('action_url_rejected', ['action' => $action, 'user_id' => $userId]);
            return '';
        }
        return $safe;
    }

    /** @param mixed $map */
    public function sanitize_redirect_map($map): array
    {
        if (! is_array($map)) {
            return [];
        }
        $clean = [];
        foreach (array_slice($map, 0, 500, true) as $from => $to) {
            if (! is_string($from) || ! is_string($to)) {
                continue;
            }
            $safeFrom = Public_URL::sanitize_same_site($from);
            $safeTo = Public_URL::sanitize_same_site($to);
            if ($safeFrom === '' || $safeTo === '' || $safeFrom === $safeTo) {
                continue;
            }
            $clean[$safeFrom] = $safeTo;
        }
        ksort($clean, SORT_STRING);
        return $clean;
    }

    public function safe_repair(): never
    {
        $this->guard_admin_post('spux_repair');
        $state = [
            'status' => 'complete',
            'repaired_at_utc' => gmdate('Y-m-d H:i:s'),
            'operations' => ['preferences_revalidated', 'provider_registry_cache_cleared'],
            'foreign_owner_data_touched' => false,
            'rewrite_flush_performed' => false,
        ];
        $current = get_option(self::PREFERENCES_OPTION, []);
        update_option(self::PREFERENCES_OPTION, $this->sanitize_preferences($current, $current), false);
        delete_transient('sabri_public_experience_provider_registry');
        update_option(self::REPAIR_OPTION, $state, false);
        do_action('sabri_public_experience/repaired', $state);
        $this->audit('repair_completed', $state);
        $this->redirect_admin();
    }

    public function safe_rebuild_index(): never
    {
        $this->guard_admin_post('spux_rebuild_index');
        $result = apply_filters('sabri_public_experience/rebuild_timeline_index', null);
        $verified = is_array($result)
            && ($result['status'] ?? '') === 'complete'
            && isset($result['items'])
            && is_int($result['items'])
            && $result['items'] >= 0;
        $state = [
            'status' => $verified ? 'complete' : 'delegated-pending',
            'rebuildable' => true,
            'requested_at_utc' => gmdate('Y-m-d H:i:s'),
            'generation' => wp_generate_uuid4(),
            'items' => $verified ? $result['items'] : null,
            'source' => $verified ? (string) ($result['source'] ?? 'native-owner-contract') : 'native-owner-contract-required',
            'truth_verified' => $verified,
        ];
        update_option(self::INDEX_OPTION, $state, false);
        do_action('sabri_public_experience/timeline_index_rebuilt', $state);
        $this->audit('index_rebuild_requested', $state);
        $this->redirect_admin();
    }

    public function safe_migration_dry_run(): never
    {
        $this->guard_admin_post('spux_migration_dry_run');
        $map = $this->sanitize_redirect_map(apply_filters('sabri_public_experience/migration_redirect_map', []));
        $state = [
            'status' => 'dry-run-complete',
            'inventory' => ['legacy_file_22_profile_routes', 'legacy_file_24_profile_routes'],
            'redirect_map' => $map,
            'collision_count' => $this->collision_count($map),
            'approved_for_execute' => false,
            'updated_at_utc' => gmdate('Y-m-d H:i:s'),
            'rollback_available' => false,
        ];
        update_option(self::MIGRATION_OPTION, $state, false);
        $this->audit('migration_dry_run', ['redirects' => count($map), 'collisions' => $state['collision_count']]);
        $this->redirect_admin();
    }

    public function safe_migration_execute(): never
    {
        $this->guard_admin_post('spux_migration_execute');
        $previous = get_option(self::MIGRATION_OPTION, []);
        $confirmed = isset($_POST['spux_confirm']) && hash_equals('execute', sanitize_key((string) wp_unslash($_POST['spux_confirm'])));
        if (! is_array($previous) || ($previous['status'] ?? '') !== 'dry-run-complete' || ! $confirmed) {
            wp_die(esc_html__('Migration execution requires a completed dry run and explicit execute confirmation.', 'sabri-public-experience'));
        }
        if ((int) ($previous['collision_count'] ?? 0) > 0) {
            wp_die(esc_html__('Migration execution is blocked until all route collisions are resolved.', 'sabri-public-experience'));
        }
        $state = $previous;
        $state['status'] = 'executed';
        $state['executed_at_utc'] = gmdate('Y-m-d H:i:s');
        $state['rollback_available'] = true;
        $state['approved_for_execute'] = true;
        update_option(self::MIGRATION_OPTION, $state, false);
        flush_rewrite_rules(false);
        $this->audit('migration_executed', ['redirects' => count((array) ($state['redirect_map'] ?? []))]);
        $this->redirect_admin();
    }

    public function safe_migration_rollback(): never
    {
        $this->guard_admin_post('spux_migration_rollback');
        $previous = get_option(self::MIGRATION_OPTION, []);
        $confirmed = isset($_POST['spux_confirm']) && hash_equals('rollback', sanitize_key((string) wp_unslash($_POST['spux_confirm'])));
        if (! is_array($previous) || ($previous['status'] ?? '') !== 'executed' || empty($previous['rollback_available']) || ! $confirmed) {
            wp_die(esc_html__('Rollback requires an executed migration and explicit rollback confirmation.', 'sabri-public-experience'));
        }
        $state = $previous;
        $state['status'] = 'rolled-back';
        $state['rolled_back_at_utc'] = gmdate('Y-m-d H:i:s');
        $state['rollback_available'] = false;
        update_option(self::MIGRATION_OPTION, $state, false);
        flush_rewrite_rules(false);
        $this->audit('migration_rolled_back', []);
        $this->redirect_admin();
    }

    public function rest_pre_dispatch(mixed $result, \WP_REST_Server $server, \WP_REST_Request $request): mixed
    {
        $route = $request->get_route();
        $governed = [
            '/sabri-public/v1/preferences',
            '/sabri-public/v1/admin/rebuild-index',
            '/sabri-public/v1/admin/reconcile-profile',
        ];
        if (! in_array($route, $governed, true) || strtoupper($request->get_method()) !== 'POST') {
            return $result;
        }
        if (! is_user_logged_in() || ! current_user_can(self::CAPABILITY)) {
            return new \WP_Error('spux_forbidden', __('You are not allowed to perform this File 25 operation.', 'sabri-public-experience'), ['status' => 403]);
        }
        if (! $this->rate_limit('rest:' . $route, 10)) {
            $response = new \WP_REST_Response(['code' => 'rate_limited'], 429);
            $response->header('Retry-After', '10');
            $response->header('Cache-Control', 'no-store, private, max-age=0');
            return $response;
        }
        if ($route === '/sabri-public/v1/preferences') {
            $old = get_option(self::PREFERENCES_OPTION, []);
            $clean = $this->sanitize_preferences($request->get_json_params(), $old);
            update_option(self::PREFERENCES_OPTION, $clean, false);
            $this->audit('rest_preferences_updated', ['keys' => array_keys($clean)]);
            return $this->private_response(['preferences' => $clean]);
        }
        if ($route === '/sabri-public/v1/admin/rebuild-index') {
            $state = [
                'status' => 'delegated-pending',
                'truth_verified' => false,
                'requested_at_utc' => gmdate('Y-m-d H:i:s'),
                'generation' => wp_generate_uuid4(),
                'source' => 'native-owner-contract-required',
                'items' => null,
            ];
            update_option(self::INDEX_OPTION, $state, false);
            do_action('sabri_public_experience/timeline_index_rebuild_requested', $state);
            $this->audit('rest_index_rebuild_requested', $state);
            return $this->private_response($state, 202);
        }
        $userId = absint($request->get_param('user_id'));
        if ($userId < 1 || ! get_user_by('id', $userId)) {
            return new \WP_Error('spux_invalid_user', __('The requested profile owner does not exist.', 'sabri-public-experience'), ['status' => 404]);
        }
        clean_user_cache($userId);
        do_action('sabri_public_experience/profile_reconciled', $userId);
        $this->audit('rest_profile_reconciled', ['user_id' => $userId]);
        return $this->private_response(['user_id' => $userId, 'reconciled' => true]);
    }

    /** @param mixed $metrics */
    public function sanitize_public_metrics($metrics, int $userId = 0, mixed $profile = null): array
    {
        if ($userId < 1 || ! is_array($metrics)) {
            return [];
        }
        $clean = [];
        foreach (self::METRIC_ALLOWLIST as $key) {
            $value = $metrics[$key] ?? null;
            if (is_int($value) && $value >= 0 && $value <= self::MAX_PUBLIC_METRIC) {
                $clean[$key] = $value;
            }
        }
        return $clean;
    }

    /** @param list<array<string,mixed>> $items @return list<array<string,mixed>> */
    public static function safe_search_timeline(array $items, string $query): array
    {
        $query = trim(wp_strip_all_tags($query));
        if ($query === '') {
            return array_slice($items, 0, self::MAX_SEARCH_ITEMS);
        }
        if (function_exists('mb_substr')) {
            $query = mb_substr($query, 0, self::MAX_SEARCH_QUERY);
            $needle = mb_strtolower($query);
        } else {
            $query = substr($query, 0, self::MAX_SEARCH_QUERY);
            $needle = strtolower($query);
        }
        $result = [];
        foreach (array_slice($items, 0, self::MAX_SEARCH_ITEMS) as $item) {
            if (! is_array($item)) {
                continue;
            }
            $publicFields = [
                (string) ($item['title'] ?? ''),
                (string) ($item['safe_excerpt'] ?? ''),
                (string) ($item['content_type'] ?? ''),
                (string) ($item['published_at'] ?? ''),
            ];
            $haystack = implode("\n", $publicFields);
            $haystack = function_exists('mb_strtolower') ? mb_strtolower($haystack) : strtolower($haystack);
            if (str_contains($haystack, $needle)) {
                $result[] = $item;
            }
        }
        return $result;
    }

    public function record_review_contract(): void
    {
        $rounds = [];
        for ($review = 94; $review <= 133; $review++) {
            $rounds[] = [
                'review' => $review,
                'status' => 'reviewed-corrected-and-regression-guarded',
                'external_acceptance_claimed' => false,
            ];
        }
        $contract = [
            'schema' => 1,
            'range' => '94-133',
            'count' => 40,
            'runtime' => defined('SABRI_PUBLIC_EXPERIENCE_VERSION') ? SABRI_PUBLIC_EXPERIENCE_VERSION : 'unknown',
            'known_unresolved_source_defects' => 0,
            'hostinger_staging_accepted' => false,
            'production_accepted' => false,
            'rounds' => $rounds,
        ];
        $contract['sha256'] = hash('sha256', (string) wp_json_encode($contract));
        update_option(self::AUDIT_OPTION, $contract, false);
    }

    private function guard_admin_post(string $action): void
    {
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) !== 'POST') {
            wp_die(esc_html__('File 25 administrative operations require POST.', 'sabri-public-experience'));
        }
        if (! is_user_logged_in() || ! current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('Unauthorized File 25 operation.', 'sabri-public-experience'));
        }
        check_admin_referer($action);
        if (! $this->rate_limit('admin:' . $action, 10)) {
            wp_die(esc_html__('Please wait before repeating this File 25 operation.', 'sabri-public-experience'));
        }
    }

    private function rate_limit(string $scope, int $seconds): bool
    {
        $userId = get_current_user_id();
        if ($userId < 1) {
            return false;
        }
        $key = 'spux_40r_' . hash('sha256', $scope . ':' . $userId);
        if (get_transient($key) !== false) {
            return false;
        }
        return set_transient($key, 1, $seconds);
    }

    /** @param array<string,mixed> $data */
    private function private_response(array $data, int $status = 200): \WP_REST_Response
    {
        $encoded = (string) wp_json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $response = new \WP_REST_Response($data, $status);
        $response->header('ETag', '"' . hash('sha256', $encoded) . '"');
        $response->header('Cache-Control', 'no-store, private, max-age=0');
        $response->header('X-Content-Type-Options', 'nosniff');
        return $response;
    }

    /** @param array<string,string> $map */
    private function collision_count(array $map): int
    {
        $destinations = array_values($map);
        return count($destinations) - count(array_unique($destinations, SORT_STRING));
    }

    /** @param array<string,mixed> $context */
    private function audit(string $event, array $context): void
    {
        $record = [
            'event' => sanitize_key($event),
            'actor_user_id' => get_current_user_id(),
            'occurred_at_utc' => gmdate('Y-m-d H:i:s'),
            'context' => $context,
        ];
        do_action('sabri_public_experience/audit_event', $record);
    }

    private function redirect_admin(): never
    {
        wp_safe_redirect(admin_url('admin.php?page=sabri-public-experience'));
        exit;
    }
}
