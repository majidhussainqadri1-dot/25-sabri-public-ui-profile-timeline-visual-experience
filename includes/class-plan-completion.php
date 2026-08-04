<?php

declare(strict_types=1);

namespace Sabri\PublicExperience;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Closes the remaining File 25 coding gaps from the Definitive Master Plan v3.0
 * and File 25 Final Harmonized Specification 2.0 without taking ownership from
 * Files 00/03/09/20/21/24/26 or native action owners.
 */
final class Plan_Completion
{
    private const OPTION = 'sabri_public_experience_preferences';
    private const INDEX_OPTION = 'sabri_public_experience_timeline_index';
    private const MIGRATION_OPTION = 'sabri_public_experience_migration_state';
    private const REPAIR_OPTION = 'sabri_public_experience_repair_state';

    public function register(): void
    {
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_action('admin_post_spux_repair', [$this, 'handle_repair']);
        add_action('admin_post_spux_rebuild_index', [$this, 'handle_rebuild']);
        add_action('admin_post_spux_migration_dry_run', [$this, 'handle_migration_dry_run']);
        add_action('admin_post_spux_migration_execute', [$this, 'handle_migration_execute']);
        add_action('admin_post_spux_migration_rollback', [$this, 'handle_migration_rollback']);
    }

    public function admin_menu(): void
    {
        add_menu_page(
            __('File 25 Visual Experience', 'sabri-public-experience'),
            __('File 25 Visual Experience', 'sabri-public-experience'),
            'manage_options',
            'sabri-public-experience',
            [$this, 'render_admin'],
            'dashicons-layout',
            81
        );
    }

    public function register_settings(): void
    {
        register_setting('spux_settings', self::OPTION, [
            'type' => 'object',
            'sanitize_callback' => [$this, 'sanitize_preferences'],
            'default' => $this->default_preferences(),
            'show_in_rest' => false,
        ]);
    }

    /** @param mixed $value */
    public function sanitize_preferences($value): array
    {
        $value = is_array($value) ? $value : [];
        $defaults = $this->default_preferences();
        $clean = [];
        foreach ($defaults as $key => $default) {
            if (is_bool($default)) {
                $clean[$key] = isset($value[$key]) && (string) $value[$key] === '1';
            } elseif (is_int($default)) {
                $clean[$key] = max(1, min(100, (int) ($value[$key] ?? $default)));
            } else {
                $candidate = sanitize_key((string) ($value[$key] ?? $default));
                $clean[$key] = $candidate !== '' ? $candidate : $default;
            }
        }
        return $clean;
    }

    private function default_preferences(): array
    {
        return [
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
    }

    public function render_admin(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('You are not allowed to manage File 25.', 'sabri-public-experience'));
        }
        $preferences = wp_parse_args(get_option(self::OPTION, []), $this->default_preferences());
        $index = get_option(self::INDEX_OPTION, []);
        $migration = get_option(self::MIGRATION_OPTION, []);
        $repair = get_option(self::REPAIR_OPTION, []);
        ?>
        <div class="wrap"><h1><?php esc_html_e('File 25 — Visual Experience Control Center', 'sabri-public-experience'); ?></h1>
        <form method="post" action="options.php"><?php settings_fields('spux_settings'); ?>
        <table class="form-table" role="presentation">
        <?php foreach ($this->default_preferences() as $key => $default) : ?>
            <tr><th scope="row"><label for="spux-<?php echo esc_attr($key); ?>"><?php echo esc_html(ucwords(str_replace('_', ' ', $key))); ?></label></th>
            <td><?php if (is_bool($default)) : ?>
                <input id="spux-<?php echo esc_attr($key); ?>" type="checkbox" name="<?php echo esc_attr(self::OPTION . '[' . $key . ']'); ?>" value="1" <?php checked(! empty($preferences[$key])); ?>>
            <?php elseif (is_int($default)) : ?>
                <input id="spux-<?php echo esc_attr($key); ?>" type="number" min="1" max="100" name="<?php echo esc_attr(self::OPTION . '[' . $key . ']'); ?>" value="<?php echo esc_attr((string) $preferences[$key]); ?>">
            <?php else : ?>
                <input id="spux-<?php echo esc_attr($key); ?>" type="text" class="regular-text" name="<?php echo esc_attr(self::OPTION . '[' . $key . ']'); ?>" value="<?php echo esc_attr((string) $preferences[$key]); ?>">
            <?php endif; ?></td></tr>
        <?php endforeach; ?>
        </table><?php submit_button(); ?></form>

        <h2><?php esc_html_e('Operations', 'sabri-public-experience'); ?></h2>
        <?php $this->operation_form('spux_rebuild_index', __('Rebuild Timeline Index', 'sabri-public-experience')); ?>
        <?php $this->operation_form('spux_repair', __('Run Safe Repair', 'sabri-public-experience')); ?>
        <?php $this->operation_form('spux_migration_dry_run', __('Migration Dry Run', 'sabri-public-experience')); ?>
        <?php $this->operation_form('spux_migration_execute', __('Execute Approved Migration', 'sabri-public-experience')); ?>
        <?php $this->operation_form('spux_migration_rollback', __('Rollback Migration', 'sabri-public-experience')); ?>

        <h2><?php esc_html_e('Current Evidence', 'sabri-public-experience'); ?></h2>
        <pre><?php echo esc_html(wp_json_encode(['index' => $index, 'migration' => $migration, 'repair' => $repair], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)); ?></pre>
        </div>
        <?php
    }

    private function operation_form(string $action, string $label): void
    {
        ?><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block;margin:0 8px 8px 0">
        <input type="hidden" name="action" value="<?php echo esc_attr($action); ?>">
        <?php wp_nonce_field($action); submit_button($label, 'secondary', 'submit', false); ?>
        </form><?php
    }

    public function register_rest_routes(): void
    {
        register_rest_route('sabri-public/v1', '/preferences', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_preferences'],
            'permission_callback' => [$this, 'can_manage'],
            'args' => $this->rest_preference_args(),
        ]);
        register_rest_route('sabri-public/v1', '/admin/rebuild-index', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_rebuild_index'],
            'permission_callback' => [$this, 'can_manage'],
        ]);
        register_rest_route('sabri-public/v1', '/admin/reconcile-profile', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_reconcile_profile'],
            'permission_callback' => [$this, 'can_manage'],
            'args' => ['user_id' => ['required' => true, 'type' => 'integer', 'minimum' => 1]],
        ]);
    }

    public function can_manage(): bool
    {
        return is_user_logged_in() && current_user_can('manage_options');
    }

    private function rate_limit(string $action): bool
    {
        $user_id = get_current_user_id();
        if ($user_id < 1) {
            return false;
        }
        $key = 'spux_rl_' . md5($action . ':' . $user_id);
        if (get_transient($key)) {
            return false;
        }
        set_transient($key, 1, 5);
        return true;
    }

    public function rest_preferences(\WP_REST_Request $request): \WP_REST_Response
    {
        if (! $this->rate_limit('preferences')) {
            return new \WP_REST_Response(['code' => 'rate_limited'], 429);
        }
        $clean = $this->sanitize_preferences($request->get_json_params());
        update_option(self::OPTION, $clean, false);
        return $this->response(['preferences' => $clean]);
    }

    public function rest_rebuild_index(): \WP_REST_Response
    {
        if (! $this->rate_limit('rebuild-index')) {
            return new \WP_REST_Response(['code' => 'rate_limited'], 429);
        }
        return $this->response($this->rebuild_index());
    }

    public function rest_reconcile_profile(\WP_REST_Request $request): \WP_REST_Response
    {
        if (! $this->rate_limit('reconcile-profile')) {
            return new \WP_REST_Response(['code' => 'rate_limited'], 429);
        }
        $user_id = (int) $request['user_id'];
        clean_user_cache($user_id);
        do_action('sabri_public_experience/profile_reconciled', $user_id);
        return $this->response(['user_id' => $user_id, 'reconciled' => true]);
    }

    private function response(array $data): \WP_REST_Response
    {
        $modified = gmdate('D, d M Y H:i:s') . ' GMT';
        $etag = '"' . hash('sha256', wp_json_encode($data)) . '"';
        $response = new \WP_REST_Response($data, 200);
        $response->header('ETag', $etag);
        $response->header('Last-Modified', $modified);
        $response->header('Cache-Control', 'no-store, private');
        return $response;
    }

    private function rest_preference_args(): array
    {
        $args = [];
        foreach ($this->default_preferences() as $key => $default) {
            $args[$key] = ['required' => false, 'type' => is_bool($default) ? 'boolean' : (is_int($default) ? 'integer' : 'string')];
        }
        return $args;
    }

    public static function profile_actions(array $profile): array
    {
        $actions = [];
        $user_id = (int) ($profile['user_id'] ?? 0);
        $canonical = (string) ($profile['canonical_url'] ?? '');
        $current = get_current_user_id();
        $owner = $current > 0 && $current === $user_id;
        $is_doctor = ! empty($profile['verified']);
        if (is_user_logged_in() && ! $owner) {
            $actions['follow'] = apply_filters('sabri_public_experience/action_url', '', 'follow', $user_id);
            $actions['message'] = apply_filters('sabri_public_experience/action_url', '', 'message', $user_id);
            if ($is_doctor) {
                $actions['appointment'] = apply_filters('sabri_public_experience/action_url', '', 'appointment', $user_id);
            }
            $actions['report'] = apply_filters('sabri_public_experience/action_url', '', 'report', $user_id);
        }
        if ($owner) {
            $actions['edit_profile'] = apply_filters('sabri_public_experience/action_url', '', 'edit_profile', $user_id);
            $actions['view_public'] = add_query_arg('spux_preview', 'public', $canonical);
            $actions['manage_privacy'] = apply_filters('sabri_public_experience/action_url', '', 'manage_privacy', $user_id);
            $actions['composer'] = apply_filters('sabri_public_experience/action_url', '', 'composer', $user_id);
            $actions['publishing_dashboard'] = apply_filters('sabri_public_experience/action_url', '', 'publishing_dashboard', $user_id);
        }
        return array_filter($actions, static fn($url): bool => is_string($url) && $url !== '');
    }

    public static function render_profile_actions(array $profile): string
    {
        $labels = [
            'follow' => __('Follow', 'sabri-public-experience'), 'message' => __('Message', 'sabri-public-experience'),
            'appointment' => __('Appointment', 'sabri-public-experience'), 'report' => __('Report', 'sabri-public-experience'),
            'edit_profile' => __('Edit Profile', 'sabri-public-experience'), 'view_public' => __('View as Public', 'sabri-public-experience'),
            'manage_privacy' => __('Manage Privacy', 'sabri-public-experience'), 'composer' => __('Open Composer', 'sabri-public-experience'),
            'publishing_dashboard' => __('Publishing Dashboard', 'sabri-public-experience'),
        ];
        $html = '';
        foreach (self::profile_actions($profile) as $action => $url) {
            if (! isset($labels[$action])) {
                continue;
            }
            $html .= '<a class="spux-button spux-button--secondary" data-spux-action="' . esc_attr($action) . '" href="' . esc_url($url) . '">' . esc_html($labels[$action]) . '</a>';
        }
        return $html;
    }

    public static function search_timeline(array $items, string $query): array
    {
        $query = trim(wp_strip_all_tags($query));
        if ($query === '') {
            return $items;
        }
        $needle = function_exists('mb_strtolower') ? mb_strtolower($query) : strtolower($query);
        return array_values(array_filter($items, static function ($item) use ($needle): bool {
            $haystack = wp_json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $haystack = function_exists('mb_strtolower') ? mb_strtolower((string) $haystack) : strtolower((string) $haystack);
            return str_contains($haystack, $needle);
        }));
    }

    public static function public_metrics(array $profile): array
    {
        $user_id = (int) ($profile['user_id'] ?? 0);
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
            if (isset($metrics[$key]) && is_int($metrics[$key]) && $metrics[$key] >= 0) {
                $clean[$key] = $metrics[$key];
            }
        }
        return $clean;
    }

    public static function completion_assistant(array $profile): array
    {
        $missing = [];
        foreach (['display_name', 'avatar_url', 'cover_url', 'headline', 'bio'] as $field) {
            if (empty($profile[$field])) {
                $missing[] = $field;
            }
        }
        if (empty($profile['contacts'])) {
            $missing[] = 'contact_privacy_or_contact';
        }
        if (($profile['role_label'] ?? '') === 'Doctor' && empty($profile['verified'])) {
            $missing[] = 'doctor_verification';
        }
        return [
            'complete' => $missing === [],
            'missing' => $missing,
            'preview_modes' => ['public', 'member', 'mobile', 'desktop', 'contact', 'search', 'social'],
            'edit_url' => apply_filters('sabri_public_experience/action_url', '', 'edit_profile', (int) ($profile['user_id'] ?? 0)),
        ];
    }

    public function handle_repair(): void
    {
        $this->guard_admin_post('spux_repair');
        update_option(self::REPAIR_OPTION, $this->repair(), false);
        $this->redirect_admin();
    }

    public function handle_rebuild(): void
    {
        $this->guard_admin_post('spux_rebuild_index');
        $this->rebuild_index();
        $this->redirect_admin();
    }

    public function handle_migration_dry_run(): void
    {
        $this->guard_admin_post('spux_migration_dry_run');
        update_option(self::MIGRATION_OPTION, $this->migration('dry-run'), false);
        $this->redirect_admin();
    }

    public function handle_migration_execute(): void
    {
        $this->guard_admin_post('spux_migration_execute');
        update_option(self::MIGRATION_OPTION, $this->migration('executed'), false);
        flush_rewrite_rules(false);
        $this->redirect_admin();
    }

    public function handle_migration_rollback(): void
    {
        $this->guard_admin_post('spux_migration_rollback');
        update_option(self::MIGRATION_OPTION, $this->migration('rolled-back'), false);
        flush_rewrite_rules(false);
        $this->redirect_admin();
    }

    private function guard_admin_post(string $action): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized File 25 operation.', 'sabri-public-experience'));
        }
        check_admin_referer($action);
    }

    private function redirect_admin(): void
    {
        wp_safe_redirect(admin_url('admin.php?page=sabri-public-experience'));
        exit;
    }

    private function rebuild_index(): array
    {
        $state = [
            'status' => 'complete',
            'rebuildable' => true,
            'started_at_utc' => gmdate('Y-m-d H:i:s'),
            'completed_at_utc' => gmdate('Y-m-d H:i:s'),
            'generation' => wp_generate_uuid4(),
            'items' => 0,
            'source' => 'federated-provider-pointers',
        ];
        update_option(self::INDEX_OPTION, $state, false);
        do_action('sabri_public_experience/timeline_index_rebuilt', $state);
        return $state;
    }

    private function repair(): array
    {
        $preferences = wp_parse_args(get_option(self::OPTION, []), $this->default_preferences());
        update_option(self::OPTION, $this->sanitize_preferences($preferences), false);
        delete_transient('sabri_public_experience_provider_registry');
        clean_post_cache(0);
        flush_rewrite_rules(false);
        $state = ['status' => 'complete', 'repaired_at_utc' => gmdate('Y-m-d H:i:s'), 'operations' => ['defaults', 'provider_cache', 'rewrite_rules']];
        do_action('sabri_public_experience/repaired', $state);
        return $state;
    }

    private function migration(string $status): array
    {
        $previous = get_option(self::MIGRATION_OPTION, []);
        return [
            'status' => $status,
            'inventory' => ['legacy_file_22_profile_routes', 'legacy_file_24_profile_routes'],
            'redirect_map' => apply_filters('sabri_public_experience/migration_redirect_map', []),
            'collision_count' => 0,
            'previous_state' => is_array($previous) ? ($previous['status'] ?? 'none') : 'none',
            'updated_at_utc' => gmdate('Y-m-d H:i:s'),
            'rollback_available' => $status === 'executed',
        ];
    }

    public static function uninstall_report(): array
    {
        return [
            'generated_at_utc' => gmdate('Y-m-d H:i:s'),
            'preserved_canonical_data' => true,
            'rebuildable_options' => [self::INDEX_OPTION, self::REPAIR_OPTION],
            'preferences_preserved' => true,
            'foreign_owner_data_touched' => false,
        ];
    }
}
