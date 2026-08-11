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
        function wp_date(string $format, int $timestamp, ?\DateTimeZone $timezone = null): string
        {
            $date = new \DateTimeImmutable('@' . $timestamp);
            if ($timezone instanceof \DateTimeZone) {
                $date = $date->setTimezone($timezone);
            }

            return $date->format($format);
        }
    }
}

namespace {
    require_once dirname(__DIR__) . '/includes/class-public-url.php';
    require_once dirname(__DIR__) . '/includes/class-components.php';
    require_once dirname(__DIR__) . '/includes/class-content-cards.php';

    use Sabri\PublicExperience\Components;
    use Sabri\PublicExperience\Content_Cards;
    use Sabri\PublicExperience\Public_URL;

    $failures = [];
    $check = static function (bool $condition, string $message) use (&$failures): void {
        if (! $condition) {
            $failures[] = $message;
        }
    };

    $check(Public_URL::sanitize_same_site('/news/item/') === '/news/item/', 'Root-relative same-site URL must be accepted.');
    $check(Public_URL::sanitize_same_site('https://example.test/news/item/') === 'https://example.test/news/item/', 'Exact same-origin URL must be accepted.');
    $check(Public_URL::sanitize_same_site('//evil.example/path') === '', 'Protocol-relative external URL must fail closed.');
    $check(Public_URL::sanitize_same_site('/\\evil.example/path') === '', 'Backslash authority escape must fail closed.');
    $check(Public_URL::sanitize_same_site('/%5C%5Cevil.example/path') === '', 'Encoded backslash authority escape must fail closed.');
    $check(Public_URL::sanitize_same_site('https://evil.example/path') === '', 'Cross-origin URL must fail closed.');
    $check(Public_URL::sanitize_same_site('http://example.test/path') === '', 'HTTPS-to-HTTP downgrade URL must fail closed.');
    $check(Public_URL::sanitize_same_site('https://example.test:444/path') === '', 'Port-mismatched URL must fail closed.');
    $check(Public_URL::sanitize_same_site('#section') === '#section', 'Safe fragment must be accepted when fragments are allowed.');
    $check(Public_URL::sanitize_same_site('/item/#section', false) === '', 'Fragment must be rejected when a canonical card URL forbids it.');

    $state = Components::render_state([
        'type' => 'error',
        'title' => '<script>bad()</script>Unavailable',
        'message' => '<b>Safe public message</b>',
        'action_url' => '//evil.example/phish',
        'action_label' => 'Retry',
    ]);
    $check(! str_contains($state, '<script'), 'State renderer must remove executable markup.');
    $check(! str_contains($state, 'evil.example'), 'State renderer must reject protocol-relative external actions.');
    $check(str_contains($state, 'aria-atomic="true"'), 'State renderer must announce one atomic status.');

    $notice = Components::render_notice([
        'type' => 'info',
        'title' => 'Public notice',
        'message' => 'Approved information.',
        'action_url' => 'https://evil.example/path',
        'action_label' => 'Open',
    ]);
    $check(str_contains($notice, 'sabri-ui-notice--info'), 'Notice renderer must expose the requested approved type.');
    $check(! str_contains($notice, 'evil.example'), 'Notice renderer must reject cross-origin actions.');

    $source = [
        'type' => 'news',
        'title' => '<script>bad()</script>Public Update',
        'url' => 'https://example.test/news/public-update/',
        'excerpt' => '<b>Reviewed</b> public summary.',
        'eyebrow' => 'News',
        'image_url' => '//evil.example/tracker.jpg',
        'image_alt' => 'Public Update image',
        'badge' => 'Verified',
        'badge_tone' => 'verified',
        'meta' => ['Global', 'English', 'Global', 'Public', 'Reviewed', '2026', 'Extra', 'Ignored'],
        'published_at' => '2026-07-30T12:00:00+00:00',
        'action_label' => 'Read update',
        'native_object_id' => 99,
        'patient_id' => 100,
        'projection_key' => str_repeat('a', 64),
    ];
    $public = Content_Cards::normalize_public($source);
    $check(is_array($public), 'Public normalizer must accept a valid public card.');
    $check(! array_key_exists('native_object_id', (array) $public), 'Public normalizer must remove native object identifiers.');
    $check(! array_key_exists('patient_id', (array) $public), 'Public normalizer must remove patient identifiers.');
    $check(! array_key_exists('projection_key', (array) $public), 'Public normalizer must remove server-only projection keys.');
    $check(($public['image_url'] ?? 'x') === '', 'Public normalizer must reject external media.');

    $card = Content_Cards::render($source);
    $check(str_contains($card, 'sabri-ui-content-card--news'), 'News card variant must be rendered.');
    $check(! str_contains($card, '<script'), 'Card renderer must remove executable title markup.');
    $check(! str_contains($card, 'evil.example'), 'Card image must reject protocol-relative external media.');
    $check(! str_contains($card, 'native_object_id') && ! str_contains($card, 'patient_id'), 'Unknown internal fields must never enter card output.');
    $check(substr_count($card, 'href=') === 1, 'Card must avoid duplicate keyboard links to the same destination.');
    $check(substr_count($card, '<li>') <= 7, 'Card metadata must remain bounded to six entries plus an optional date.');
    $check(str_contains($card, 'datetime="2026-07-30T12:00:00+00:00"'), 'Card date must be normalized to an ISO datetime.');

    $linked_title = Content_Cards::render([
        'type' => 'book',
        'title' => 'Sabri Materia Medica',
        'url' => '/library/sabri-materia-medica/',
    ]);
    $check(substr_count($linked_title, 'href=') === 1, 'A card without an action button must link its title exactly once.');
    $check(str_contains($linked_title, '<h3 class="sabri-ui-content-card__title"><a'), 'Title must be the single link when no action label is supplied.');

    $check(Content_Cards::render(['type' => 'post', 'title' => '']) === '', 'Untitled content card must fail closed.');
    $check(Content_Cards::normalize_public(['type' => 'post', 'title' => '']) === null, 'Untitled structured card must fail closed.');

    $natural_date = Content_Cards::render([
        'type' => 'event',
        'title' => 'Future Event',
        'published_at' => 'next Thursday',
    ]);
    $check(! str_contains($natural_date, '<time'), 'Natural-language dates must not enter deterministic public card metadata.');

    $original_timezone = date_default_timezone_get();
    date_default_timezone_set('Asia/Karachi');
    $date_only = Content_Cards::render([
        'type' => 'event',
        'title' => 'Date-only Event',
        'published_at' => '2026-07-31',
    ]);
    date_default_timezone_set($original_timezone);
    $check(str_contains($date_only, 'datetime="2026-07-31T00:00:00+00:00"'), 'Date-only metadata must remain midnight UTC regardless of PHP default timezone.');
    $check(str_contains($date_only, '>Jul 31, 2026</time>'), 'Displayed date must use the deterministic UTC calendar date.');

    $invalid_calendar_date = Content_Cards::render([
        'type' => 'event',
        'title' => 'Invalid Event',
        'published_at' => '2026-02-30',
    ]);
    $check(! str_contains($invalid_calendar_date, '<time'), 'Invalid calendar dates must fail closed instead of normalizing silently.');

    $contract = Content_Cards::contract();
    $check(($contract['contract_version'] ?? '') === '1.2.0', 'Content-card contract must record the shared public normalizer.');
    $check(($contract['public_normalizer'] ?? null) === [Content_Cards::class, 'normalize_public'], 'Content-card contract must expose the shared public normalizer.');
    $check(($contract['owns_native_data'] ?? true) === false, 'Card contract must deny native data ownership.');
    $check(($contract['same_site_destinations'] ?? false) === true, 'Card contract must require same-site destinations.');
    $check(($contract['deterministic_dates'] ?? false) === true, 'Card contract must guarantee deterministic dates.');
    $check(($contract['date_timezone'] ?? '') === 'UTC', 'Card contract must publish the UTC date policy.');
    $check(in_array('doctor', (array) ($contract['types'] ?? []), true), 'Doctor card type must be available.');
    $check(in_array('marketplace-item', (array) ($contract['types'] ?? []), true), 'Marketplace visual card type must be available without taking Marketplace ownership.');

    if ($failures !== []) {
        fwrite(STDERR, "FAILED\n- " . implode("\n- ", $failures) . "\n");
        exit(1);
    }

    echo "PASS: File 25 reusable content-card, structured projection, and same-origin URL contracts\n";
}
