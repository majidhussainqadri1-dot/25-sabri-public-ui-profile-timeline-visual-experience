<?php

declare(strict_types=1);

namespace {
    if (! defined('ABSPATH')) { define('ABSPATH', __DIR__ . '/fixtures/'); }
    function sanitize_key(string $value): string { return trim((string) preg_replace('/[^a-z0-9_\-]/', '-', strtolower(trim($value))), '-'); }
    function apply_filters(string $hook, mixed $value, mixed ...$args): mixed { return $value; }
    function do_action(string $hook, mixed ...$args): void {}
    function home_url(string $path = '/'): string { return 'https://example.test' . (str_starts_with($path, '/') ? $path : '/' . $path); }
    function esc_url_raw(string $url, array $protocols = []): string { return $url; }
    function __(string $text, string $domain = ''): string { return $text; }

    require_once dirname(__DIR__) . '/includes/contracts/interface-profile-section-provider.php';
    require_once dirname(__DIR__) . '/includes/class-public-url.php';
    require_once dirname(__DIR__) . '/includes/class-content-cards.php';
    require_once dirname(__DIR__) . '/includes/class-section-registry.php';
    require_once dirname(__DIR__) . '/includes/class-section-service.php';

    final class R3953_Section_Provider implements \Sabri\PublicExperience\Contracts\Profile_Section_Provider
    {
        public function __construct(private string $id, private string $maturity) {}
        public function get_id(): string { return $this->id; }
        public function get_version(): string { return '1.0.0'; }
        public function get_section(): string { return 'knowledge'; }
        public function get_maturity_level(): string { return $this->maturity; }
        public function is_available(): bool { return true; }
        public function supports_profile(array $profile): bool { return true; }
        public function query(int $user_id, array $profile, array $context = []): array
        {
            return [[
                'projection_key' => hash('sha256', $this->id),
                'type' => 'article',
                'title' => $this->id,
                'url' => 'https://example.test/knowledge/' . $this->id . '/',
            ]];
        }
        public function owns_native_content(): bool { return false; }
    }

    function r3953_section_assert(bool $condition, string $message): void
    {
        if (! $condition) { fwrite(STDERR, "FAILED: {$message}\n"); exit(1); }
    }
}
