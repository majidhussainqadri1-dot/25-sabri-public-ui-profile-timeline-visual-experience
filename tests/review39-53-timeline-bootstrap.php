<?php

declare(strict_types=1);

namespace {
    if (! defined('ABSPATH')) { define('ABSPATH', __DIR__ . '/fixtures/'); }
    function sanitize_key(string $value): string { return trim((string) preg_replace('/[^a-z0-9_\-]/', '-', strtolower(trim($value))), '-'); }
    function apply_filters(string $hook, mixed $value, mixed ...$args): mixed { return $value; }
    function do_action(string $hook, mixed ...$args): void {}
    function home_url(string $path = '/'): string { return 'https://example.test' . (str_starts_with($path, '/') ? $path : '/' . $path); }
    function esc_url_raw(string $url, array $protocols = []): string { return $url; }

    require_once dirname(__DIR__) . '/includes/contracts/interface-timeline-provider.php';
    require_once dirname(__DIR__) . '/includes/class-public-url.php';
    require_once dirname(__DIR__) . '/includes/class-normalized-timeline-item.php';
    require_once dirname(__DIR__) . '/includes/class-timeline-registry.php';
    require_once dirname(__DIR__) . '/includes/class-timeline-service.php';

    final class R3953_Timeline_Provider implements \Sabri\PublicExperience\Contracts\Timeline_Provider
    {
        /** @param list<mixed> $items */
        public function __construct(
            private string $id,
            private string $maturity,
            private array $items,
            private bool $available = true
        ) {}
        public function get_provider_id(): string { return $this->id; }
        public function get_provider_version(): string { return '1.0.0'; }
        public function is_available(): bool { return $this->available; }
        public function get_maturity_level(): string { return $this->maturity; }
        public function get_public_author_items(int $author_id, array $query): array { return $this->items; }
        public function get_health_status(): array { return []; }
    }

    /** @return \Sabri\PublicExperience\Normalized_Timeline_Item */
    function r3953_item(string $provider, string $id = '1', array $overrides = []): \Sabri\PublicExperience\Normalized_Timeline_Item
    {
        return new \Sabri\PublicExperience\Normalized_Timeline_Item(array_merge([
            'provider_id' => $provider,
            'provider_version' => '1.0.0',
            'native_object_type' => 'post',
            'native_object_id' => $id,
            'author_id' => 7,
            'public_profile_id' => 7,
            'title' => 'Public item ' . $id,
            'safe_excerpt' => 'Excerpt',
            'canonical_url' => 'https://example.test/post/' . $id . '/',
            'published_at' => '2026-08-03T12:00:00Z',
            'updated_at' => '2026-08-03T12:00:00Z',
            'visibility_state' => 'public',
            'native_status' => 'publish',
            'content_type' => 'post',
            'language' => 'en-US',
            'review_state' => 'published',
            'available_actions' => ['read'],
        ], $overrides));
    }

    function r3953_assert(bool $condition, string $message): void
    {
        if (! $condition) {
            fwrite(STDERR, "FAILED: {$message}\n");
            exit(1);
        }
    }
}
