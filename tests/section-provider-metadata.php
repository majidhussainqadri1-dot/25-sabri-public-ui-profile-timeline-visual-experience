<?php

declare(strict_types=1);

namespace {
    if (! defined('ABSPATH')) { define('ABSPATH', __DIR__ . '/fixtures/'); }
    if (! function_exists('home_url')) { function home_url(string $path = ''): string { return 'https://example.test' . $path; } }
    if (! function_exists('esc_url_raw')) { function esc_url_raw(string $url, ?array $protocols = null): string { return $url; } }
    if (! function_exists('esc_url')) { function esc_url(string $url): string { return htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); } }
    if (! function_exists('esc_html')) { function esc_html(string $value): string { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); } }
    if (! function_exists('esc_attr')) { function esc_attr(string $value): string { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); } }
    if (! function_exists('sanitize_key')) { function sanitize_key(string $value): string { return trim((string) preg_replace('/[^a-z0-9_\-]/', '-', strtolower($value)), '-'); } }
    if (! function_exists('__')) { function __(string $value, string $domain = ''): string { return $value; } }
    if (! function_exists('get_option')) { function get_option(string $name): string { return $name === 'date_format' ? 'M j, Y' : ''; } }
    if (! function_exists('wp_date')) { function wp_date(string $format, int $timestamp, ?\DateTimeZone $timezone = null): string { return gmdate($format, $timestamp); } }
    if (! function_exists('do_action')) { function do_action(string $hook, mixed ...$args): void {} }

    require_once dirname(__DIR__) . '/includes/contracts/interface-profile-section-provider.php';
}

namespace Sabri\PublicExperience\Tests {
    use Sabri\PublicExperience\Contracts\Profile_Section_Provider;

    class Mutable_Metadata_Provider implements Profile_Section_Provider
    {
        public string $id = 'mutable-metadata';
        public string $version = '1.0.0';
        public string $section = 'knowledge';
        public string $maturity = 'read-only';
        public bool $owns = false;
        public bool $throw_section = false;

        public function get_id(): string { return $this->id; }
        public function get_version(): string { return $this->version; }
        public function get_section(): string
        {
            if ($this->throw_section) { throw new \RuntimeException('Mutable section failure'); }
            return $this->section;
        }
        public function get_maturity_level(): string { return $this->maturity; }
        public function is_available(): bool { return true; }
        public function supports_profile(array $profile): bool { return ($profile['class'] ?? '') === 'doctor'; }
        public function query(int $user_id, array $profile, array $context = []): array
        {
            return [
                ['type' => 'article', 'title' => 'Upper path', 'url' => '/Knowledge/Case/'],
                ['type' => 'article', 'title' => 'Lower path', 'url' => '/knowledge/case/'],
                ['type' => 'marketplace-item', 'title' => 'Listing One', 'url' => '/marketplace/', 'projection_key' => str_repeat('a', 64)],
                ['type' => 'marketplace-item', 'title' => 'Listing Two', 'url' => '/marketplace/', 'projection_key' => str_repeat('b', 64)],
            ];
        }
        public function owns_native_content(): bool { return $this->owns; }
    }

    final class Unstable_Maturity_Provider extends Mutable_Metadata_Provider
    {
        private int $reads = 0;

        public function __construct()
        {
            $this->id = 'unstable-maturity';
        }

        public function get_maturity_level(): string
        {
            $this->reads++;
            return $this->reads <= 2 ? 'read-only' : 'disabled';
        }

        public function query(int $user_id, array $profile, array $context = []): array
        {
            return [['type' => 'article', 'title' => 'Atomic metadata item', 'url' => '/atomic/']];
        }
    }
}

namespace {
    require_once dirname(__DIR__) . '/includes/class-public-url.php';
    require_once dirname(__DIR__) . '/includes/class-content-cards.php';
    require_once dirname(__DIR__) . '/includes/class-section-registry.php';
    require_once dirname(__DIR__) . '/includes/class-section-service.php';

    use Sabri\PublicExperience\Section_Registry;
    use Sabri\PublicExperience\Section_Service;
    use Sabri\PublicExperience\Tests\Mutable_Metadata_Provider;
    use Sabri\PublicExperience\Tests\Unstable_Maturity_Provider;

    $failures = [];
    $check = static function (bool $condition, string $message) use (&$failures): void {
        if (! $condition) { $failures[] = $message; }
    };

    $profile = ['class' => 'doctor'];
    $provider = new Mutable_Metadata_Provider();
    $registry = new Section_Registry();
    $registry->register($provider);

    $data = (new Section_Service($registry))->get_section(11, $profile, 'knowledge');
    $check(count((array) ($data['items'] ?? [])) === 4, 'Case-sensitive paths and distinct opaque projection keys must remain separate.');

    $provider->section = 'media';
    $mutated = (new Section_Service($registry))->get_section(11, $profile, 'knowledge');
    $check(($mutated['items'] ?? []) === [] && ($mutated['provider_error_count'] ?? 0) === 1, 'Section mutation must fail closed and be reported.');

    $provider->section = 'knowledge';
    $provider->version = '2.0.0';
    $version_mutated = (new Section_Service($registry))->get_section(11, $profile, 'knowledge');
    $check(($version_mutated['items'] ?? []) === [], 'Version mutation must fail closed.');

    $provider->version = '1.0.0';
    $provider->maturity = 'production-accepted';
    $maturity_mutated = (new Section_Service($registry))->get_section(11, $profile, 'knowledge');
    $check(($maturity_mutated['items'] ?? []) === [], 'Maturity self-promotion must fail closed.');

    $provider->maturity = 'read-only';
    $provider->owns = true;
    $ownership_mutated = (new Section_Service($registry))->get_section(11, $profile, 'knowledge');
    $check(($ownership_mutated['items'] ?? []) === [], 'Native ownership mutation must fail closed.');

    $provider->owns = false;
    $provider->throw_section = true;
    $metadata_failure = (new Section_Service($registry))->get_section(11, $profile, 'knowledge');
    $check(($metadata_failure['items'] ?? []) === [], 'Metadata exceptions must be isolated before query execution.');

    $provider->throw_section = false;
    $second = new Mutable_Metadata_Provider();
    $second->id = 'second-provider';
    $second_registry = new Section_Registry();
    $second_registry->register($provider);
    $second_registry->register($second);
    $provider->id = 'second-provider';
    $check($second_registry->validated_metadata($provider, 'knowledge') === null, 'A provider object may not impersonate another registered provider ID.');

    $unstable = new Unstable_Maturity_Provider();
    $unstable_registry = new Section_Registry();
    $unstable_registry->register($unstable);
    $atomic = (new Section_Service($unstable_registry))->get_section(11, $profile, 'knowledge');
    $check(count((array) ($atomic['items'] ?? [])) === 1, 'Provider maturity must be read once during atomic query-time metadata validation.');

    if ($failures !== []) {
        fwrite(STDERR, "FAILED\n- " . implode("\n- ", $failures) . "\n");
        exit(1);
    }

    echo "PASS: File 25 provider object identity, atomic metadata, and projection-key contracts\n";
}
