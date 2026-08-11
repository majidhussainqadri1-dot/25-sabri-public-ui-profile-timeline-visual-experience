<?php

declare(strict_types=1);

namespace {
    if (! defined('ABSPATH')) {
        define('ABSPATH', __DIR__ . '/fixtures/');
    }
    if (! defined('HE_VERSION')) {
        define('HE_VERSION', '0.1.0');
    }

    final class HE_Content
    {
        public const TYPE = 'he_entry';

        public static function type(int $post_id, string $field = 'name'): string
        {
            return $post_id === 11 ? 'Remedy' : 'Pathology';
        }
    }

    if (! function_exists('sanitize_key')) {
        function sanitize_key(string $value): string
        {
            return trim((string) preg_replace('/[^a-z0-9_\-]/', '-', strtolower($value)), '-');
        }
    }
    if (! function_exists('__')) {
        function __(string $value, string $domain = ''): string
        {
            return $value;
        }
    }
    if (! function_exists('post_type_exists')) {
        function post_type_exists(string $post_type): bool
        {
            return $post_type === 'he_entry';
        }
    }
    if (! function_exists('get_posts')) {
        function get_posts(array $args): array
        {
            $GLOBALS['file06_query_args'] = $args;

            return [
                (object) [
                    'ID' => 11,
                    'post_author' => 7,
                    'post_status' => 'publish',
                    'post_password' => '',
                    'post_title' => 'Arnica Montana',
                    'post_excerpt' => 'Approved public encyclopedia entry.',
                    'post_date_gmt' => '2026-07-30 12:00:00',
                ],
                (object) [
                    'ID' => 12,
                    'post_author' => 8,
                    'post_status' => 'publish',
                    'post_password' => '',
                    'post_title' => 'Wrong author',
                ],
                (object) [
                    'ID' => 13,
                    'post_author' => 7,
                    'post_status' => 'draft',
                    'post_password' => '',
                    'post_title' => 'Draft entry',
                ],
                (object) [
                    'ID' => 14,
                    'post_author' => 7,
                    'post_status' => 'publish',
                    'post_password' => 'secret',
                    'post_title' => 'Protected entry',
                ],
                (object) [
                    'ID' => 15,
                    'post_author' => 7,
                    'post_status' => 'publish',
                    'post_password' => '',
                    'post_title' => 'Wrong post type',
                ],
            ];
        }
    }
    if (! function_exists('get_post_type')) {
        function get_post_type(int $post_id): string
        {
            return $post_id === 15 ? 'post' : 'he_entry';
        }
    }
    if (! function_exists('get_the_title')) {
        function get_the_title(int $post_id): string
        {
            return $post_id === 11 ? 'Arnica Montana' : 'Other';
        }
    }
    if (! function_exists('get_permalink')) {
        function get_permalink(int $post_id): string
        {
            return 'https://example.test/encyclopedia-entry/' . $post_id . '/';
        }
    }
    if (! function_exists('get_the_excerpt')) {
        function get_the_excerpt(int $post_id): string
        {
            return $post_id === 11 ? 'Approved public encyclopedia entry.' : '';
        }
    }
    if (! function_exists('get_post_meta')) {
        function get_post_meta(int $post_id, string $key, bool $single = false): string
        {
            return $post_id === 11 && $key === '_he_body_system' ? 'Musculoskeletal System' : '';
        }
    }
    if (! function_exists('get_post_time')) {
        function get_post_time(string $format, bool $gmt, int $post_id): string
        {
            return $post_id === 11 ? '2026-07-30T12:00:00+00:00' : '';
        }
    }
    if (! function_exists('get_the_post_thumbnail_url')) {
        function get_the_post_thumbnail_url(int $post_id, string $size = 'post-thumbnail'): string|false
        {
            return $post_id === 11 ? 'https://example.test/uploads/arnica.webp' : false;
        }
    }

    require_once dirname(__DIR__) . '/includes/contracts/interface-profile-section-provider.php';
    require_once dirname(__DIR__) . '/includes/providers/class-file-06-knowledge-provider.php';

    use Sabri\PublicExperience\Providers\File_06_Knowledge_Provider;

    $failures = [];
    $check = static function (bool $condition, string $message) use (&$failures): void {
        if (! $condition) {
            $failures[] = $message;
        }
    };

    $provider = new File_06_Knowledge_Provider();
    $check($provider->get_id() === 'file-06-knowledge', 'File 06 provider ID must remain canonical.');
    $check($provider->get_version() === '0.1.0', 'File 06 provider must report the native reviewed version.');
    $check($provider->get_section() === 'knowledge', 'File 06 provider must supply the Knowledge section.');
    $check($provider->get_maturity_level() === 'read-only', 'File 06 source adapter must remain read-only.');
    $check($provider->owns_native_content() === false, 'File 06 provider must deny native content ownership.');
    $check($provider->is_available(), 'Reviewed File 06 0.1.0 contract must be available.');
    $check($provider->supports_profile(['class' => 'doctor']), 'A public Doctor profile may receive encyclopedia knowledge.');
    $check(! $provider->supports_profile(['class' => 'patient']), 'Patient profiles must not expose the professional encyclopedia section through this adapter.');

    $items = $provider->query(7, ['class' => 'doctor'], ['limit' => 99]);
    $check(count($items) === 1, 'Provider must admit only public, unprotected, correct-author File 06 entries.');
    $item = $items[0] ?? [];
    $check(($item['title'] ?? '') === 'Arnica Montana', 'Provider must preserve the canonical native title.');
    $check(($item['url'] ?? '') === 'https://example.test/encyclopedia-entry/11/', 'Provider must preserve the canonical native permalink.');
    $check(($item['badge'] ?? '') === 'Remedy', 'Provider must expose the approved native knowledge type.');
    $check(in_array('Musculoskeletal System', (array) ($item['meta'] ?? []), true), 'Provider may expose the approved public body-system label.');
    $check(($item['published_at'] ?? '') === '2026-07-30T12:00:00+00:00', 'Provider must expose a deterministic native publication date.');
    foreach (['ID', 'post_id', 'author_id', 'views', 'comments', 'patient_id'] as $forbidden) {
        $check(! array_key_exists($forbidden, $item), 'Provider descriptor must exclude internal field: ' . $forbidden);
    }

    $query_args = (array) ($GLOBALS['file06_query_args'] ?? []);
    $check(($query_args['post_type'] ?? '') === 'he_entry', 'Provider query must remain bound to File 06 post type.');
    $check(($query_args['post_status'] ?? '') === 'publish', 'Provider query must request published entries only.');
    $check(($query_args['author'] ?? 0) === 7, 'Provider query must remain author-bound.');
    $check(($query_args['posts_per_page'] ?? 0) === 24, 'Provider query must enforce the twenty-four-item upper bound.');
    $check(($query_args['has_password'] ?? null) === false, 'Provider query must reject password-protected entries.');

    if ($failures !== []) {
        fwrite(STDERR, "FAILED\n- " . implode("\n- ", $failures) . "\n");
        exit(1);
    }

    echo "PASS: File 25 native File 06 knowledge provider contract\n";
}
