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
    if (! function_exists('wp_strip_all_tags')) {
        function wp_strip_all_tags(string $value, bool $remove_breaks = false): string
        {
            return strip_tags($value);
        }
    }
    if (! function_exists('get_option')) {
        function get_option(string $name): string
        {
            return $name === 'date_format' ? 'M j, Y' : '';
        }
    }
    if (! function_exists('wp_date')) {
        function wp_date(string $format, int $timestamp): string
        {
            return gmdate($format, $timestamp);
        }
    }
    if (! function_exists('do_action')) {
        function do_action(string $hook, mixed ...$args): void
        {
        }
    }
}

namespace Sabri\PublicExperience\Tests {
    use Sabri\PublicExperience\Contracts\Profile_Section_Provider;

    final class Fake_Section_Provider implements Profile_Section_Provider
    {
        /** @param list<array<string,mixed>> $items */
        public function __construct(
            private string $id,
            private string $section,
            private array $items = [],
            public string $maturity = 'read-only',
            private bool $available = true,
            private bool $supported = true,
            private bool $owns = false,
            private bool $throws = false
        ) {
        }

        public function get_id(): string
        {
            return $this->id;
        }

        public function get_version(): string
        {
            return '1.0.0';
        }

        public function get_section(): string
        {
            return $this->section;
        }

        public function get_maturity_level(): string
        {
            return $this->maturity;
        }

        public function is_available(): bool
        {
            return $this->available;
        }

        public function supports_profile(array $profile): bool
        {
            return $this->supported && ($profile['class'] ?? '') === 'doctor';
        }

        public function query(int $user_id, array $profile, array $context = []): array
        {
            if ($this->throws) {
                throw new \RuntimeException('Provider failure');
            }

            return $this->items;
        }

        public function owns_native_content(): bool
        {
            return $this->owns;
        }
    }
}

namespace {
    require_once dirname(__DIR__) . '/includes/class-public-url.php';
    require_once dirname(__DIR__) . '/includes/class-content-cards.php';
    require_once dirname(__DIR__) . '/includes/contracts/interface-profile-section-provider.php';
    require_once dirname(__DIR__) . '/includes/class-section-registry.php';
    require_once dirname(__DIR__) . '/includes/class-section-service.php';

    use Sabri\PublicExperience\Section_Registry;
    use Sabri\PublicExperience\Section_Service;
    use Sabri\PublicExperience\Tests\Fake_Section_Provider;

    $failures = [];
    $check = static function (bool $condition, string $message) use (&$failures): void {
        if (! $condition) {
            $failures[] = $message;
        }
    };

    $profile = ['class' => 'doctor', 'display_name' => 'Verified Doctor'];
    $registry = new Section_Registry();
    $knowledge = new Fake_Section_Provider('file-06-knowledge', 'knowledge', [
        [
            'type' => 'article',
            'title' => 'Materia Medica Study',
            'url' => '/encyclopedia/materia-medica-study/',
            'excerpt' => 'Approved public study material.',
            'native_object_id' => 501,
        ],
        [
            'type' => 'article',
            'title' => 'Materia Medica Study',
            'url' => '/encyclopedia/materia-medica-study/',
        ],
    ]);
    $registry->register($knowledge);

    $service = new Section_Service($registry);
    $available = $service->available_for_profile(7, $profile);
    $check(isset($available['knowledge']), 'A content-backed accepted provider must expose its canonical section.');
    $check(! isset($available['media']), 'A section without approved content must remain hidden.');

    $section = $service->get_section(7, $profile, 'knowledge');
    $check(count((array) ($section['items'] ?? [])) === 1, 'Duplicate public cards must be suppressed across one section.');
    $rendered = (string) (($section['items'][0] ?? ''));
    $check(str_contains($rendered, 'Materia Medica Study'), 'Accepted public card must render through File 25.');
    $check(! str_contains($rendered, 'native_object_id'), 'Provider internal identifiers must not enter rendered output.');

    $duplicate_rejected = false;
    try {
        $registry->register(new Fake_Section_Provider('file-06-knowledge', 'knowledge'));
    } catch (\InvalidArgumentException) {
        $duplicate_rejected = true;
    }
    $check($duplicate_rejected, 'Duplicate provider IDs must fail closed.');

    $invalid_section_rejected = false;
    try {
        (new Section_Registry())->register(new Fake_Section_Provider('bad-section', 'messages'));
    } catch (\InvalidArgumentException) {
        $invalid_section_rejected = true;
    }
    $check($invalid_section_rejected, 'Unapproved profile sections must fail closed.');

    $ownership_rejected = false;
    try {
        (new Section_Registry())->register(new Fake_Section_Provider('native-owner', 'media', [], 'read-only', true, true, true));
    } catch (\InvalidArgumentException) {
        $ownership_rejected = true;
    }
    $check($ownership_rejected, 'A provider claiming native ownership must be rejected.');

    $mutable = new Fake_Section_Provider('mutable-provider', 'media', [[
        'type' => 'video',
        'title' => 'Public Video',
        'url' => '/video/public-video/',
    ]]);
    $mutable_registry = new Section_Registry();
    $mutable_registry->register($mutable);
    $mutable->maturity = 'fabricated-accepted';
    $mutable_available = (new Section_Service($mutable_registry))->available_for_profile(7, $profile);
    $check(! isset($mutable_available['media']), 'Mutated invalid maturity must be revalidated and fail closed at query time.');

    $failure_registry = new Section_Registry();
    $failure_registry->register(new Fake_Section_Provider('failing-reviews', 'reviews', [], 'read-only', true, true, false, true));
    $failure_data = (new Section_Service($failure_registry))->get_section(7, $profile, 'reviews');
    $check(($failure_data['provider_error_count'] ?? 0) === 1, 'Provider exceptions must be isolated and counted.');
    $check(($failure_data['items'] ?? []) === [], 'A failed provider must not fabricate section content.');

    $bounded_items = [];
    for ($index = 1; $index <= 30; $index++) {
        $bounded_items[] = [
            'type' => 'news',
            'title' => 'Public Item ' . $index,
            'url' => '/news/item-' . $index . '/',
        ];
    }
    $bounded_registry = new Section_Registry();
    $bounded_registry->register(new Fake_Section_Provider('bounded-research', 'research', $bounded_items));
    $bounded_data = (new Section_Service($bounded_registry))->get_section(7, $profile, 'research');
    $check(count((array) ($bounded_data['items'] ?? [])) === 24, 'Each provider result must be bounded to twenty-four cards.');
    $check(($bounded_data['truncated'] ?? false) === true, 'Bounded provider overflow must be reported truthfully.');

    if ($failures !== []) {
        fwrite(STDERR, "FAILED\n- " . implode("\n- ", $failures) . "\n");
        exit(1);
    }

    echo "PASS: File 25 optional profile section provider contracts\n";
}
