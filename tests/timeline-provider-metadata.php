<?php

declare(strict_types=1);

namespace {
    if (! defined('ABSPATH')) { define('ABSPATH', __DIR__ . '/fixtures/'); }
    if (! function_exists('sanitize_key')) {
        function sanitize_key(string $value): string
        {
            return trim((string) preg_replace('/[^a-z0-9_\-]/', '-', strtolower($value)), '-');
        }
    }
    if (! function_exists('do_action')) { function do_action(string $hook, mixed ...$args): void {} }
    if (! function_exists('apply_filters')) { function apply_filters(string $hook, mixed $value, mixed ...$args): mixed { return $value; } }
    if (! function_exists('home_url')) { function home_url(string $path = ''): string { return 'https://example.test/' . ltrim($path, '/'); } }
    if (! function_exists('wp_parse_url')) { function wp_parse_url(string $url): array|false { return parse_url($url); } }

    require_once dirname(__DIR__) . '/includes/contracts/interface-timeline-provider.php';
    require_once dirname(__DIR__) . '/includes/class-normalized-timeline-item.php';
    require_once dirname(__DIR__) . '/includes/class-timeline-registry.php';
    require_once dirname(__DIR__) . '/includes/class-timeline-service.php';
}

namespace Sabri\PublicExperience\Tests {
    use Sabri\PublicExperience\Contracts\Timeline_Provider;
    use Sabri\PublicExperience\Normalized_Timeline_Item;

    class Mutable_Timeline_Provider implements Timeline_Provider
    {
        public string $id = 'mutable-timeline';
        public string $version = '1.0.0';
        public string $maturity = 'read-only';
        public bool $available = true;

        public function get_provider_id(): string { return $this->id; }
        public function get_provider_version(): string { return $this->version; }
        public function is_available(): bool { return $this->available; }
        public function get_maturity_level(): string { return $this->maturity; }
        public function get_health_status(): array { return ['available' => $this->available]; }
        public function get_public_author_items(int $author_id, array $query): array
        {
            return [new Normalized_Timeline_Item([
                'provider_id' => $this->id,
                'provider_version' => $this->version,
                'native_object_type' => 'publication',
                'native_object_id' => '1',
                'author_id' => $author_id,
                'public_profile_id' => $author_id,
                'title' => 'Immutable provider item',
                'safe_excerpt' => 'Public excerpt',
                'canonical_url' => 'https://example.test/publication/1/',
                'published_at' => '2026-07-31T01:00:00+00:00',
                'visibility_state' => 'public',
                'native_status' => 'published',
                'content_type' => 'post',
                'review_state' => 'published',
            ])];
        }
    }

    final class Unstable_Timeline_Maturity extends Mutable_Timeline_Provider
    {
        private int $reads = 0;

        public function __construct()
        {
            $this->id = 'unstable-timeline';
        }

        public function get_maturity_level(): string
        {
            $this->reads++;
            return $this->reads <= 2 ? 'read-only' : 'disabled';
        }
    }
}

namespace {
    use Sabri\PublicExperience\Timeline_Registry;
    use Sabri\PublicExperience\Timeline_Service;
    use Sabri\PublicExperience\Tests\Mutable_Timeline_Provider;
    use Sabri\PublicExperience\Tests\Unstable_Timeline_Maturity;

    $failures = [];
    $check = static function (bool $condition, string $message) use (&$failures): void {
        if (! $condition) { $failures[] = $message; }
    };

    $provider = new Mutable_Timeline_Provider();
    $registry = new Timeline_Registry();
    $registry->register($provider);
    $initial = (new Timeline_Service($registry))->get_for_author(7);
    $check(count($initial['items']) === 1, 'A consistent registered timeline provider must render its public item.');

    $provider->version = '2.0.0';
    $changed_version = (new Timeline_Service($registry))->get_for_author(7);
    $check($changed_version['items'] === [] && $changed_version['provider_errors'] === ['mutable-timeline'], 'Timeline provider version mutation must fail closed.');

    $provider->version = '1.0.0';
    $provider->maturity = 'production-accepted';
    $changed_maturity = (new Timeline_Service($registry))->get_for_author(7);
    $check($changed_maturity['items'] === [], 'Timeline provider maturity self-promotion must fail closed.');

    $provider->maturity = 'read-only';
    $second = new Mutable_Timeline_Provider();
    $second->id = 'second-timeline';
    $identity_registry = new Timeline_Registry();
    $identity_registry->register($provider);
    $identity_registry->register($second);
    $provider->id = 'second-timeline';
    $check($identity_registry->validated_metadata($provider, 'second-timeline') === null, 'A timeline provider object may not impersonate another registered provider ID.');

    $unstable = new Unstable_Timeline_Maturity();
    $unstable_registry = new Timeline_Registry();
    $unstable_registry->register($unstable);
    $atomic = (new Timeline_Service($unstable_registry))->get_for_author(7);
    $check(count($atomic['items']) === 1, 'Timeline provider maturity must be read once during atomic query-time validation.');

    $invalid_version = false;
    try {
        $bad = new Mutable_Timeline_Provider();
        $bad->id = 'bad-version';
        $bad->version = 'not-semver';
        (new Timeline_Registry())->register($bad);
    } catch (InvalidArgumentException) {
        $invalid_version = true;
    }
    $check($invalid_version, 'Timeline provider registration must reject malformed versions.');

    if ($failures !== []) {
        fwrite(STDERR, "FAILED\n- " . implode("\n- ", $failures) . "\n");
        exit(1);
    }

    echo "PASS: File 25 immutable timeline provider object metadata contract\n";
}
