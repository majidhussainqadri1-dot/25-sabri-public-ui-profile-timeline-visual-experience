<?php

declare(strict_types=1);

namespace {
    if (! defined('ABSPATH')) {
        define('ABSPATH', __DIR__ . '/fixtures/');
    }
    if (! function_exists('home_url')) {
        function home_url(string $path = ''): string
        {
            return 'https://example.test' . $path;
        }
    }
    if (! function_exists('esc_url_raw')) {
        function esc_url_raw(string $url, ?array $protocols = null): string
        {
            return $url;
        }
    }
    if (! function_exists('esc_url')) {
        function esc_url(string $url): string
        {
            return htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }
    }
    if (! function_exists('esc_html')) {
        function esc_html(string $value): string
        {
            return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }
    }
    if (! function_exists('esc_attr')) {
        function esc_attr(string $value): string
        {
            return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
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
    if (! function_exists('get_option')) {
        function get_option(string $name): string
        {
            return $name === 'date_format' ? 'M j, Y' : '';
        }
    }
    if (! function_exists('wp_date')) {
        function wp_date(string $format, int $timestamp, ?\DateTimeZone $timezone = null): string
        {
            return gmdate($format, $timestamp);
        }
    }
    if (! function_exists('do_action')) {
        function do_action(string $hook, mixed ...$args): void
        {
        }
    }

    require_once dirname(__DIR__) . '/includes/contracts/interface-profile-section-provider.php';
}

namespace Sabri\PublicExperience\Tests {
    use Sabri\PublicExperience\Contracts\Profile_Section_Provider;

    final class Mutable_Metadata_Provider implements Profile_Section_Provider
    {
        public string $id = 'mutable-metadata';
        public string $version = '1.0.0';
        public string $section = 'knowledge';
        public bool $throw_section = false;

        public function get_id(): string
        {
            return $this->id;
        }

        public function get_version(): string
        {
            return $this->version;
        }

        public function get_section(): string
        {
            if ($this->throw_section) {
                throw new \RuntimeException('Mutable section failure');
            }

            return $this->section;
        }

        public function get_maturity_level(): string
        {
            return 'read-only';
        }

        public function is_available(): bool
        {
            return true;
        }

        public function supports_profile(array $profile): bool
        {
            return ($profile['class'] ?? '') === 'doctor';
        }

        public function query(int $user_id, array $profile, array $context = []): array
        {
            return [
                ['type' => 'article', 'title' => 'Upper path', 'url' => '/Knowledge/Case/'],
                ['type' => 'article', 'title' => 'Upper path', 'url' => '/knowledge/case/'],
            ];
        }

        public function owns_native_content(): bool
        {
            return false;
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

    $failures = [];
    $check = static function (bool $condition, string $message) use (&$failures): void {
        if (! $condition) {
            $failures[] = $message;
        }
    };

    $profile = ['class' => 'doctor'];
    $provider = new Mutable_Metadata_Provider();
    $registry = new Section_Registry();
    $registry->register($provider);

    $data = (new Section_Service($registry))->get_section(11, $profile, 'knowledge');
    $check(count((array) ($data['items'] ?? [])) === 2, 'Case-sensitive canonical paths must not be collapsed during deduplication.');

    $provider->section = 'media';
    $mutated = (new Section_Service($registry))->get_section(11, $profile, 'knowledge');
    $check(($mutated['items'] ?? []) === [], 'A provider that mutates its registered section must fail closed.');

    $provider->section = 'knowledge';
    $provider->version = '2.0.0';
    $version_mutated = (new Section_Service($registry))->get_section(11, $profile, 'knowledge');
    $check(($version_mutated['items'] ?? []) === [], 'A provider that mutates its registered version must fail closed.');

    $provider->version = '1.0.0';
    $provider->throw_section = true;
    $metadata_failure = (new Section_Service($registry))->get_section(11, $profile, 'knowledge');
    $check(($metadata_failure['items'] ?? []) === [], 'Provider metadata exceptions must be isolated before query execution.');

    if ($failures !== []) {
        fwrite(STDERR, "FAILED\n- " . implode("\n- ", $failures) . "\n");
        exit(1);
    }

    echo "PASS: File 25 provider metadata immutability and canonical path contracts\n";
}
