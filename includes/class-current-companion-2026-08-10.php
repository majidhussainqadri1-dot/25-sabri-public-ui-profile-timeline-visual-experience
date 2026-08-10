<?php

declare(strict_types=1);

namespace Sabri\PublicExperience;

if (! defined('ABSPATH') && PHP_SAPI !== 'cli') {
    exit;
}

/**
 * Current 10-Aug-2026 companion reconciliation layer.
 *
 * It consumes only File 03's declared public personal-site projection and
 * component metadata. It never reads File 03 tables or creates profile,
 * verification, clinic, appointment, ranking, contact or publication truth.
 */
final class Current_Companion_2026_08_10
{
    private const FILE_03_CONTRACT = '1.4.0';
    private const MAX_COMPONENT_PROVIDERS = 25;

    /** @var array<string,array<string,mixed>> */
    private static array $personal_site_cache = [];

    /** @var array<string,array<string,mixed>> */
    private static array $component_providers = [];

    public static function register(): void
    {
        add_action('sabri_file25_register_component_provider', [self::class, 'register_component_provider'], 10, 2);
        add_filter('sabri_public_experience/public_contacts', [self::class, 'filter_contacts'], 200, 3);
        add_filter('sabri_public_experience/action_url', [self::class, 'filter_action_url'], 200, 3);
        add_filter('sabri_public_experience/profile_translations', [self::class, 'profile_translations'], 200, 2);
        add_filter('sabri_public_experience/content_freshness', [self::class, 'content_freshness'], 200, 2);
        add_filter('sabri_visual_experience/contract', [self::class, 'filter_contract'], 210);
        add_filter('sabri_public_experience/design_system_contract', [self::class, 'filter_contract'], 210);

        // File 03 and File 25 can have the same plugins_loaded priority in some
        // installations. Recover current provider metadata if File 03 registered
        // before this listener existed; otherwise the action above handles it.
        if (class_exists('SPD_Contracts') && method_exists('SPD_Contracts', 'component_manifest')) {
            try {
                $manifest = \SPD_Contracts::component_manifest();
                self::register_component_provider('file03', is_array($manifest) ? $manifest : []);
            } catch (\Throwable) {
                // Optional presentation metadata fails closed.
            }
        }
    }

    /** @param array<string,mixed> $manifest */
    public static function register_component_provider(string $owner, array $manifest): void
    {
        $owner = sanitize_key($owner);
        if ($owner === '' || count(self::$component_providers) >= self::MAX_COMPONENT_PROVIDERS) {
            return;
        }
        $manifest_owner = sanitize_key((string) ($manifest['owner'] ?? $owner));
        if ($manifest_owner !== '' && ! hash_equals($owner, $manifest_owner)) {
            return;
        }
        $version = trim((string) ($manifest['version'] ?? $manifest['contract_version'] ?? ''));
        $components = [];
        foreach (array_slice((array) ($manifest['components'] ?? []), 0, 100) as $component) {
            if (! is_scalar($component)) {
                continue;
            }
            $key = sanitize_key((string) $component);
            if ($key !== '' && ! in_array($key, $components, true)) {
                $components[] = $key;
            }
        }
        if ($version === '' || $components === []) {
            return;
        }
        self::$component_providers[$owner] = [
            'owner' => $owner,
            'version' => sanitize_text_field($version),
            'components' => $components,
            'metadata_only' => true,
            'native_data_copy_allowed' => false,
        ];
    }

    /** @param array<string,string> $contacts @return array<string,string> */
    public static function filter_contacts(array $contacts, int $user_id, mixed $canonical = null): array
    {
        unset($canonical);
        $lifecycle = self::professional_lifecycle($user_id);
        if ($lifecycle !== '' && $lifecycle !== 'active') {
            return [];
        }
        return $contacts;
    }

    public static function filter_action_url(string $url, string $action, int $user_id): string
    {
        $action = sanitize_key($action);
        if (in_array($action, ['appointment', 'message'], true)) {
            $lifecycle = self::professional_lifecycle($user_id);
            if ($lifecycle !== '' && $lifecycle !== 'active') {
                return '';
            }
        }
        return $url;
    }

    /** @param array<string,mixed> $existing @param array<string,mixed> $profile @return array<string,mixed> */
    public static function profile_translations(array $existing, array $profile): array
    {
        $source = self::personal_site_from_profile($profile);
        $rows = (array) ($source['future']['multilingual_editions'] ?? []);
        if ($rows === []) {
            return $existing;
        }
        $out = $existing;
        foreach (array_slice($rows, 0, 20) as $row) {
            if (! is_array($row)) {
                continue;
            }
            $locale = strtolower(trim((string) ($row['locale'] ?? '')));
            if (preg_match('/^[a-z]{2,3}(?:-[a-z]{2})?$/', $locale) !== 1) {
                continue;
            }
            $headline = sanitize_text_field((string) ($row['headline'] ?? ''));
            $bio = sanitize_textarea_field((string) ($row['bio'] ?? ''));
            if ($headline === '' && $bio === '') {
                continue;
            }
            $out[$locale] = [
                'label' => strtoupper($locale),
                'headline' => $headline,
                'bio' => $bio,
            ];
        }
        return $out;
    }

    /** @param array<string,mixed> $existing @param array<string,mixed> $profile @return array<string,string> */
    public static function content_freshness(array $existing, array $profile): array
    {
        $source = self::personal_site_from_profile($profile);
        $rows = (array) ($source['future']['freshness'] ?? []);
        $latest = 0;
        $latest_text = '';
        foreach (array_slice($rows, 0, 100) as $row) {
            if (! is_array($row)) {
                continue;
            }
            foreach (['updated_at', 'confirmed_at', 'expires_at'] as $key) {
                $candidate = trim((string) ($row[$key] ?? ''));
                if ($candidate === '') {
                    continue;
                }
                $timestamp = strtotime($candidate);
                if ($timestamp !== false && $timestamp > $latest) {
                    $latest = $timestamp;
                    $latest_text = gmdate('c', $timestamp);
                }
            }
        }
        if ($latest_text === '') {
            return $existing;
        }
        return [
            'updated_at' => $latest_text,
            'label' => __('Public profile facts were revalidated by their native owner.', 'sabri-public-experience'),
        ];
    }

    /** @param array<string,mixed> $contract @return array<string,mixed> */
    public static function filter_contract(array $contract): array
    {
        $contract['current_companion_reconciliation'] = [
            'file03_contract' => self::FILE_03_CONTRACT,
            'file03_personal_site_query' => 'spd_get_personal_site_profile',
            'professional_lifecycle_action_suppression' => true,
            'component_provider_registry' => self::$component_providers,
            'component_provider_limit' => self::MAX_COMPONENT_PROVIDERS,
            'registry_is_metadata_only' => true,
            'native_owner_actions_required' => true,
        ];
        return $contract;
    }

    private static function professional_lifecycle(int $user_id): string
    {
        $source = self::personal_site($user_id);
        $lifecycle = sanitize_key((string) ($source['future']['lifecycle']['status'] ?? ''));
        return in_array($lifecycle, ['active', 'retired', 'legacy'], true) ? $lifecycle : '';
    }

    /** @param array<string,mixed> $profile @return array<string,mixed> */
    private static function personal_site_from_profile(array $profile): array
    {
        $user_id = max(0, (int) ($profile['user_id'] ?? 0));
        return $user_id > 0 ? self::personal_site($user_id) : [];
    }

    /** @return array<string,mixed> */
    private static function personal_site(int $user_id): array
    {
        if ($user_id <= 0) {
            return [];
        }
        $key = (string) $user_id;
        if (array_key_exists($key, self::$personal_site_cache)) {
            return self::$personal_site_cache[$key];
        }
        if (! defined('SPD_CONTRACT_VERSION')
            || ! hash_equals(self::FILE_03_CONTRACT, trim((string) SPD_CONTRACT_VERSION))
            || ! function_exists('spd_get_profile_contract_manifest')
            || ! function_exists('spd_get_personal_site_profile')
        ) {
            return self::$personal_site_cache[$key] = [];
        }
        try {
            $manifest = spd_get_profile_contract_manifest();
            $queries = is_array($manifest['queries'] ?? null) ? $manifest['queries'] : [];
            if (! is_array($manifest)
                || sanitize_key((string) ($manifest['owner_key'] ?? '')) !== 'file03'
                || ! hash_equals(self::FILE_03_CONTRACT, trim((string) ($manifest['contract_version'] ?? '')))
                || ($queries['get_personal_site_profile'] ?? '') !== 'spd_get_personal_site_profile'
            ) {
                return self::$personal_site_cache[$key] = [];
            }
            $source = spd_get_personal_site_profile($user_id, 0);
        } catch (\Throwable) {
            return self::$personal_site_cache[$key] = [];
        }
        if ((function_exists('is_wp_error') && is_wp_error($source))
            || ! is_array($source)
            || ! hash_equals(self::FILE_03_CONTRACT, trim((string) ($source['contract_version'] ?? '')))
        ) {
            return self::$personal_site_cache[$key] = [];
        }
        return self::$personal_site_cache[$key] = $source;
    }
}
