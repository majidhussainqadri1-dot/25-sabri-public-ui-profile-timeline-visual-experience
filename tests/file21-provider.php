<?php

declare(strict_types=1);

namespace {
    if (! defined('ABSPATH')) {
        define('ABSPATH', __DIR__ . '/fixtures/');
    }
    if (! defined('SABRI_HNF_VERSION')) {
        define('SABRI_HNF_VERSION', '1.0.3');
    }
    if (! function_exists('sanitize_key')) {
        function sanitize_key(string $value): string
        {
            return trim((string) preg_replace('/[^a-z0-9_\-]/', '', strtolower($value)), '-');
        }
    }
}

namespace Sabri\HomeNewsFeed {
    final class ProfileTimeline
    {
        /** @var list<array<string,int>> */
        public static array $calls = [];
        public static bool $disabled = false;

        /** @param array<string,mixed> $args @return array<string,mixed> */
        public static function query(int $author_id, array $args = []): array
        {
            self::$calls[] = [
                'author_id' => $author_id,
                'page' => (int) ($args['page'] ?? 1),
                'per_page' => (int) ($args['per_page'] ?? 10),
            ];
            if (self::$disabled) {
                return ['status' => 'disabled', 'items' => [], 'has_more' => false];
            }

            $page = max(1, (int) ($args['page'] ?? 1));
            $per_page = max(1, min(20, (int) ($args['per_page'] ?? 10)));
            $total = 45;
            $offset = ($page - 1) * $per_page;
            $items = [];
            for ($index = $offset + 1; $index <= min($total, $offset + $per_page); $index++) {
                $items[] = [
                    'id' => $index,
                    'title' => '<b>Publication ' . $index . '</b>',
                    'excerpt' => '<script>bad()</script>Excerpt ' . $index,
                    'url' => 'https://example.test/publication/' . $index . '/',
                    'date_gmt' => sprintf('2026-07-%02dT12:00:00+00:00', (($index - 1) % 28) + 1),
                    'date_display' => 'July ' . $index,
                ];
            }

            return [
                'status' => 'ok',
                'items' => $items,
                'has_more' => $offset + count($items) < $total,
            ];
        }
    }
}

namespace {
    require_once dirname(__DIR__) . '/includes/contracts/interface-timeline-provider.php';
    require_once dirname(__DIR__) . '/includes/class-normalized-timeline-item.php';
    require_once dirname(__DIR__) . '/includes/providers/class-file-21-provider.php';

    use Sabri\HomeNewsFeed\ProfileTimeline;
    use Sabri\PublicExperience\Normalized_Timeline_Item;
    use Sabri\PublicExperience\Providers\File_21_Provider;

    $failures = [];
    $check = static function (bool $condition, string $message) use (&$failures): void {
        if (! $condition) {
            $failures[] = $message;
        }
    };

    $provider = new File_21_Provider();
    $check($provider->get_provider_id() === 'file-21', 'File 21 provider ID must remain canonical.');
    $check($provider->get_provider_version() === '1.0.3', 'File 21 provider must report the native runtime version.');
    $check($provider->is_available(), 'File 21 provider must detect the native ProfileTimeline contract.');
    $check($provider->get_maturity_level() === 'read-only', 'Source integration must not self-promote beyond read-only.');

    ProfileTimeline::$calls = [];
    $items = $provider->get_public_author_items(77, [
        'candidate_limit' => 45,
        'content_type' => 'post',
    ]);
    $check(count($items) === 45, 'Provider must gather a bounded multi-page candidate pool from File 21.');
    $check(count(ProfileTimeline::$calls) === 3, 'Provider must page through File 21 in bounded native chunks.');
    $check(ProfileTimeline::$calls[0]['page'] === 1 && ProfileTimeline::$calls[2]['page'] === 3, 'Native File 21 pages must be requested in order.');
    $check($items[0] instanceof Normalized_Timeline_Item, 'Provider must return normalized File 25 timeline items.');
    $check($items[0]->get('author_id') === 77, 'Normalized item must retain the requested author internally.');
    $check($items[0]->get('provider_id') === 'file-21', 'Normalized item must retain the registered provider identity.');
    $check($items[0]->get('title') === 'Publication 1', 'File 21 title must normalize to plain text.');
    $check($items[0]->get('safe_excerpt') === 'Excerpt 1', 'File 21 excerpt must remove executable content.');
    $check(! array_key_exists('native_object_id', $items[0]->to_public_array()), 'Public output must redact the native post ID.');

    ProfileTimeline::$calls = [];
    $unsupported = $provider->get_public_author_items(77, [
        'candidate_limit' => 20,
        'content_type' => 'video',
    ]);
    $check($unsupported === [], 'File 21 post provider must reject unrelated content types.');
    $check(ProfileTimeline::$calls === [], 'Unsupported content filters must not query File 21.');

    ProfileTimeline::$disabled = true;
    $disabled = $provider->get_public_author_items(77, ['candidate_limit' => 20]);
    $check($disabled === [], 'A disabled File 21 public timeline must fail closed.');
    ProfileTimeline::$disabled = false;

    $health = $provider->get_health_status();
    $check(($health['read_only'] ?? false) === true, 'Health output must declare read-only behavior.');
    $check(($health['owns_native_content'] ?? true) === false, 'Health output must deny native content ownership.');

    if ($failures !== []) {
        fwrite(STDERR, "FAILED\n- " . implode("\n- ", $failures) . "\n");
        exit(1);
    }

    echo "PASS: File 21 read-only timeline provider contract\n";
}
