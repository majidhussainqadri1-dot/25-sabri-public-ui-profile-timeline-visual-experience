<?php
declare(strict_types=1);

/**
 * V12.1 V12-012: render the real File25 timeline partial with bounded
 * WordPress presentation doubles. No native owner records are accessed.
 */
if (! defined('ABSPATH')) { define('ABSPATH', __DIR__ . '/fixtures/'); }
if (! function_exists('__')) {
    function __(string $text, string $domain = ''): string { return $text; }
}
if (! function_exists('esc_html')) {
    function esc_html(string $text): string { return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
}
if (! function_exists('esc_attr')) {
    function esc_attr(string $text): string { return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
}
if (! function_exists('esc_url')) {
    function esc_url(string $url): string { return htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
}
if (! function_exists('esc_html_e')) {
    function esc_html_e(string $text, string $domain = ''): void { echo esc_html($text); }
}
if (! function_exists('esc_attr_e')) {
    function esc_attr_e(string $text, string $domain = ''): void { echo esc_attr($text); }
}
if (! function_exists('sanitize_key')) {
    function sanitize_key(string $value): string {
        return trim((string) preg_replace('/[^a-z0-9_-]/', '', strtolower($value)), '-');
    }
}
if (! function_exists('sanitize_text_field')) {
    function sanitize_text_field(string $value): string { return trim(strip_tags($value)); }
}
if (! function_exists('home_url')) {
    function home_url(string $path = '/'): string { return 'https://example.test/' . ltrim($path, '/'); }
}
if (! function_exists('selected')) {
    function selected(string $candidate, string $current): void {
        if ($candidate === $current) { echo ' selected="selected"'; }
    }
}
if (! function_exists('remove_query_arg')) {
    function remove_query_arg(array|string $keys, string $url = ''): string {
        $source = $url !== '' ? $url : (string) ($GLOBALS['spux_test_url'] ?? '');
        $parts = parse_url($source);
        if (! is_array($parts)) { return ''; }
        parse_str((string) ($parts['query'] ?? ''), $query);
        foreach ((array) $keys as $key) { unset($query[(string) $key]); }
        $result = (string) ($parts['scheme'] ?? 'https') . '://' . (string) ($parts['host'] ?? 'example.test')
            . (string) ($parts['path'] ?? '/');
        return $query === [] ? $result : $result . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }
}
if (! function_exists('add_query_arg')) {
    function add_query_arg(string $key, string|int $value, string $url = ''): string {
        $source = $url !== '' ? $url : (string) ($GLOBALS['spux_test_url'] ?? '');
        $parts = parse_url($source);
        if (! is_array($parts)) { return ''; }
        parse_str((string) ($parts['query'] ?? ''), $query);
        $query[$key] = $value;
        $result = (string) ($parts['scheme'] ?? 'https') . '://' . (string) ($parts['host'] ?? 'example.test')
            . (string) ($parts['path'] ?? '/');
        return $result . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }
}
require_once dirname(__DIR__) . '/includes/class-public-url.php';
require_once dirname(__DIR__) . '/includes/class-components.php';

$failures = [];
$assert = static function (bool $ok, string $message) use (&$failures): void {
    if (! $ok) { $failures[] = $message; }
};
$render = static function (array $scenario): string {
    $GLOBALS['spux_test_url'] = (string) ($scenario['url'] ?? 'https://example.test/doctors/approved/timeline/');
    $timeline = array_replace([
        'items' => [], 'page' => 1, 'per_page' => 20,
        'has_more' => false, 'truncated' => false, 'provider_errors' => [],
    ], (array) ($scenario['timeline'] ?? []));
    $context = array_replace([
        'timeline_content_type' => '', 'timeline_provider' => '', 'timeline_year' => '',
        'timeline_language' => '', 'timeline_topic' => '', 'timeline_sort' => 'latest',
        'timeline_search_query' => '', 'timeline_search_error' => '',
        'timeline_filters' => ['' => 'All'], 'timeline_provider_filters' => [],
        'preferences' => ['profile_local_search' => false], 'preview_mode' => '',
    ], (array) ($scenario['context'] ?? []));
    ob_start();
    include dirname(__DIR__) . '/templates/partials/timeline.php';
    return (string) ob_get_clean();
};

$empty = $render([]);
$assert(str_contains($empty, 'data-sabri-visual-state="empty"'), 'Unfiltered empty must use shared empty component.');
$assert(str_contains($empty, 'No approved public contributions available here'), 'Unfiltered empty must not claim that private content does not exist.');
$assert(str_contains($empty, 'Return Home'), 'Empty state needs safe next action.');

$filtered = $render([
    'url' => 'https://example.test/doctors/approved/timeline/?year=2025&spux_preview=public',
    'context' => ['timeline_year' => '2025', 'preview_mode' => 'public'],
]);
$assert(str_contains($filtered, 'data-sabri-visual-state="filtered-empty"'), 'Zero filtered records must be filtered-empty.');
$assert(str_contains($filtered, 'Clear timeline filters'), 'Filtered-empty needs clear-filter action.');
$assert(! str_contains($filtered, '?year=2025&amp;spux_preview=public"'), 'Clear action must drop the exhausted year filter.');
$assert(str_contains($filtered, 'spux_preview=public'), 'Clear action must preserve authorized preview context.');

$invalid = $render([
    'url' => 'https://example.test/doctors/approved/timeline/?year=9999',
    'context' => ['timeline_search_error' => 'invalid_timeline_filters'],
]);
$assert(str_contains($invalid, 'data-sabri-visual-state="error"'), 'Invalid requests must show an error, not generic empty.');
$assert(! str_contains($invalid, 'data-sabri-visual-state="empty"'), 'Invalid requests must not imply no publications.');
$assert(! str_contains($invalid, 'data-sabri-visual-state="filtered-empty"'), 'Invalid requests did not execute a filtered retrieval.');
$assert(str_contains($invalid, 'Clear the filters and try again.'), 'Invalid filters must give a plain-language next step.');

$partial = $render([
    'timeline' => ['provider_errors' => ['file-21']],
]);
$assert(str_contains($partial, 'data-sabri-visual-state="unavailable"'), 'Provider error cannot assert that nothing has been published.');
$assert(! str_contains($partial, 'No approved public contributions available here'), 'Provider outage must not masquerade as empty.');
$assert(str_contains($partial, 'Try the timeline again'), 'Unavailable state needs a retry.');
$assert(! str_contains($partial, 'Available verified items are shown below'), 'Do not falsely claim that zero items are displayed.');

$truncated = $render([
    'timeline' => ['truncated' => true],
]);
$assert(str_contains($truncated, 'data-sabri-visual-state="unavailable"'), 'Retrieval cap with zero items cannot prove none exist.');

$page = $render([
    'url' => 'https://example.test/doctors/approved/timeline/?paged=4',
    'timeline' => ['page' => 4],
]);
$assert(str_contains($page, 'data-sabri-visual-state="filtered-empty"'), 'Empty later page must not claim author has no public content.');
$assert(str_contains($page, 'Return to first page'), 'Empty later page must offer a usable previous destination.');
$assert(! str_contains($page, 'paged=4"'), 'Return-to-first-page URL must clear page number.');

$partialPage = $render([
    'url' => 'https://example.test/doctors/approved/timeline/?paged=4',
    'timeline' => ['page' => 4, 'provider_errors' => ['file-21']],
]);
$assert(str_contains($partialPage, 'data-sabri-visual-state="unavailable"'), 'A later-page outage stays unavailable.');
$assert(str_contains($partialPage, 'Return to first page'), 'Outage recovery label must match the actual reset-page destination.');
$assert(! str_contains($partialPage, 'Try the timeline again'), 'Do not describe a page reset as retrying the same page.');

if ($failures !== []) {
    fwrite(STDERR, "FAILED File25 V12.1 timeline-state truth\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}
echo "PASS File25 V12.1 timeline states: empty/filter/error/outage/truncation/pagination and actions\n";
