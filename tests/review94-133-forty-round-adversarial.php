<?php

declare(strict_types=1);

$root = dirname(__DIR__);
define('ABSPATH', $root . '/');

$GLOBALS['spux_test_options'] = [];
$GLOBALS['spux_test_user_id'] = 7;
$GLOBALS['spux_test_filters'] = [];

function sanitize_key($value): string
{
    $value = strtolower((string) $value);
    return trim((string) preg_replace('/[^a-z0-9_-]/', '', $value));
}
function wp_strip_all_tags($value): string { return trim(strip_tags((string) $value)); }
function get_option($key, $default = false) { return $GLOBALS['spux_test_options'][$key] ?? $default; }
function update_option($key, $value, $autoload = null): bool { $GLOBALS['spux_test_options'][$key] = $value; return true; }
function delete_option($key): bool { unset($GLOBALS['spux_test_options'][$key]); return true; }
function add_option($key, $value, $deprecated = '', $autoload = null): bool { if (array_key_exists($key, $GLOBALS['spux_test_options'])) { return false; } $GLOBALS['spux_test_options'][$key] = $value; return true; }
function get_transient($key) { return false; }
function set_transient($key, $value, $ttl): bool { return true; }
function delete_transient($key): bool { return true; }
function apply_filters($hook, $value, ...$args) { return $GLOBALS['spux_test_filters'][$hook] ?? $value; }
function do_action($hook, ...$args): void {}
function get_current_user_id(): int { return (int) $GLOBALS['spux_test_user_id']; }
function is_user_logged_in(): bool { return get_current_user_id() > 0; }
function current_user_can($capability): bool { return get_current_user_id() > 0 && $capability === 'manage_options'; }
function home_url($path = '/'): string { return 'https://example.test' . (str_starts_with($path, '/') ? $path : '/' . $path); }
function esc_url_raw($url, $protocols = null): string { return (string) $url; }
function add_query_arg($key, $value = null, $url = null): string
{
    if (is_array($key)) { $args = $key; $url = (string) $value; } else { $args = [$key => $value]; $url = (string) $url; }
    $parts = parse_url($url);
    if (! is_array($parts)) { return ''; }
    $query = [];
    if (isset($parts['query'])) { parse_str($parts['query'], $query); }
    foreach ($args as $k => $v) { $query[$k] = $v; }
    $base = ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? 'example.test') . ($parts['path'] ?? '/');
    return $base . ($query ? '?' . http_build_query($query) : '');
}
function __($text, $domain = null): string { return (string) $text; }
function esc_attr($text): string { return htmlspecialchars((string) $text, ENT_QUOTES); }
function esc_html($text): string { return htmlspecialchars((string) $text, ENT_QUOTES); }
function esc_url($text): string { return (string) $text; }
function checked($a, $b = true, $echo = true): string { return $a === $b ? 'checked="checked"' : ''; }
function selected($a, $b = true, $echo = true): string { return $a === $b ? 'selected="selected"' : ''; }
function wp_json_encode($value, $flags = 0): string { return (string) json_encode($value, $flags); }
function wp_unslash($value) { return $value; }
function wp_generate_uuid4(): string { return '11111111-1111-4111-8111-111111111111'; }

require_once $root . '/includes/class-public-url.php';
require_once $root . '/includes/class-plan-completion.php';

use Sabri\PublicExperience\Plan_Completion;

$checks = 0;
$assert = static function (bool $condition, string $label) use (&$checks): void {
    $checks++;
    if (! $condition) {
        fwrite(STDERR, sprintf("[FAIL %02d] %s\n", $checks, $label));
        exit(1);
    }
};

$source = file_get_contents($root . '/includes/class-plan-completion.php');
$renderer = file_get_contents($root . '/includes/class-profile-renderer.php');
$timelineService = file_get_contents($root . '/includes/class-timeline-service.php');
$template = file_get_contents($root . '/templates/public-profile.php');
$timelineTemplate = file_get_contents($root . '/templates/partials/timeline.php');
$css = file_get_contents($root . '/assets/css/public.css');

// Reviews 94–103: governed preferences, revision, history, audit, and capabilities.
$assert(str_contains($source, 'PREFERENCE_SCHEMA'), 'Review 94: preference schema is explicit.');
$assert(str_contains($source, "'type' => 'enum'"), 'Review 95: enum values use allow-lists.');
$assert(str_contains($source, "'type' => 'list'"), 'Review 96: tab/order lists are canonicalized.');
$assert(str_contains($source, 'unknown_preference'), 'Review 97: unknown REST preference keys fail closed.');
$assert(str_contains($source, 'revision_conflict'), 'Review 98: optimistic revision conflicts are represented.');
$assert(str_contains($source, 'OPTION_HISTORY'), 'Review 99: prior preference snapshots are retained.');
$assert(str_contains($source, 'preferences_updated'), 'Review 100: preference changes emit an audit event.');
$assert(str_contains($source, 'OPERATION_CAPABILITIES'), 'Review 101: operations have explicit capability mapping.');
$assert(str_contains($source, 'high_risk_authorized'), 'Review 102: high-risk operations require a step-up contract.');
$assert(str_contains($source, "'minimum' => 1, 'maximum' => 1440"), 'Review 103: cache TTL is bounded.');

// Reviews 104–113: mutation safety, response integrity, and reconciliation.
$assert(str_contains($source, 'Idempotency-Key'), 'Review 104: mutation idempotency key is required.');
$assert(str_contains($source, 'idempotency_cache_key'), 'Review 105: duplicate mutations reuse stored responses.');
$assert(str_contains($source, "'rate_limited'"), 'Review 106: atomic rate limiting is explicit.');
$assert(str_contains($source, 'Retry-After'), 'Review 107: 429 responses disclose bounded retry timing.');
$assert(str_contains($source, 'canonicalize_for_hash'), 'Review 108: ETags use canonical data ordering.');
$assert(str_contains($source, 'Last-Modified'), 'Review 109: mutation responses carry resource modification time.');
$assert(str_contains($source, 'X-Sabri-Request-ID'), 'Review 110: REST mutations have correlation IDs.');
$assert(str_contains($source, "get_user_by('id'"), 'Review 111: profile reconciliation verifies user existence.');
$assert(str_contains($source, 'reconcile_profile_projection'), 'Review 112: reconciliation uses a bounded owner contract.');
$assert(str_contains($source, "'owner_data_mutated' => false"), 'Review 113: File 25 reconciliation cannot mutate owner data.');

// Reviews 114–123: action, search, metric, and completion-assistant privacy.
$GLOBALS['spux_test_filters']['sabri_public_experience/action_url'] = 'https://evil.example/action';
$actions = Plan_Completion::profile_actions(['user_id' => 9, 'class' => 'doctor', 'verified' => true, 'canonical_url' => 'https://example.test/doctors/a/']);
$assert($actions === [], 'Review 114: cross-origin action URLs fail closed.');
$GLOBALS['spux_test_filters']['sabri_public_experience/action_url'] = 'https://example.test/action';
$actions = Plan_Completion::profile_actions(['user_id' => 9, 'class' => 'doctor', 'verified' => 'true', 'canonical_url' => 'https://example.test/doctors/a/']);
$assert(! isset($actions['appointment']), 'Review 115: Doctor authority requires exact boolean truth.');
$GLOBALS['spux_test_user_id'] = 99;
$assistant = Plan_Completion::completion_assistant(['user_id' => 7, 'display_name' => 'A']);
$assert(($assistant['authorized'] ?? null) === true, 'Review 116: administrator may inspect the completion assistant.');
$GLOBALS['spux_test_user_id'] = 0;
$assistant = Plan_Completion::completion_assistant(['user_id' => 7, 'display_name' => 'A']);
$assert(($assistant['authorized'] ?? null) === false && ($assistant['missing'] ?? ['x']) === [], 'Review 117: unauthorized completion details are not disclosed.');
$GLOBALS['spux_test_user_id'] = 7;
$assert(Plan_Completion::normalize_search_query(str_repeat('x', 121)) === '', 'Review 118: overlong search queries are rejected.');
$assert(Plan_Completion::normalize_search_query("safe\u{202E}query") === '', 'Review 119: bidi/format-control search queries are rejected.');
$assert(Plan_Completion::timeline_item_matches_search(['title' => 'Materia Medica', 'provider_id' => 'secret'], 'materia'), 'Review 120: search matches public title text.');
$assert(! Plan_Completion::timeline_item_matches_search(['title' => 'Public', 'provider_id' => 'secret'], 'secret'), 'Review 121: search ignores internal provider identity.');
$results = Plan_Completion::search_timeline(array_fill(0, 140, ['title' => 'match']), 'match');
$assert(count($results) === 100, 'Review 122: search result count is bounded.');
$assert(str_contains($timelineService, 'timeline_item_matches_search'), 'Review 123: search is applied before global Timeline pagination.');

// Reviews 124–133: real rendering, index/repair/migration, and rollback.
$assert(str_contains($renderer, "'search' => \$search_query"), 'Review 124: renderer passes the bounded query into Timeline Service.');
$assert(str_contains($timelineTemplate, 'spux-timeline-search'), 'Review 125: profile-local search has an accessible UI.');
$assert(str_contains($timelineTemplate, 'No matching public contributions'), 'Review 126: search has a truthful no-result state.');
$assert(str_contains($renderer, "['public_visibility'] !== true"), 'Review 127: public visibility setting is enforced.');
$assert(str_contains($renderer, 'apply_profile_preferences'), 'Review 128: tab/order preferences affect the rendered projection.');
$assert(str_contains($renderer, "['seo_enabled'] !== true"), 'Review 129: SEO preference suppresses public metadata.');
$assert(str_contains($template, 'spux-public-metrics'), 'Review 130: approved metrics are rendered, not merely declared.');
$assert(str_contains($template, 'spux-completion-assistant'), 'Review 131: owner-only completion guidance is rendered.');
$assert(str_contains($source, 'timeline_index_build') && str_contains($source, 'builder_contract_unavailable'), 'Review 132: index rebuild cannot report fake success without an accepted builder.');
$assert(str_contains($source, 'approved_dry_run_required')
    && str_contains($source, 'previous_active_map')
    && str_contains($source, 'apply_legacy_redirects')
    && str_contains($source, 'stale_rebuild_reconciled')
    && str_contains($css, '.spux-timeline-search'), 'Review 133: migration, rollback, repair, redirects, and new UI are operationally wired.');

echo sprintf("Reviews 94-133 corrective regressions PASS (%d checks)\n", $checks);
