<?php

declare(strict_types=1);

$root = dirname(__DIR__);
define('ABSPATH', $root . '/');

$GLOBALS['spux_test_options'] = [];
$GLOBALS['spux_test_filters'] = [];
$GLOBALS['spux_inline_css'] = '';

function add_action($hook, $callback, $priority = 10, $accepted_args = 1): void {}
function add_filter($hook, $callback, $priority = 10, $accepted_args = 1): void {}
function sanitize_key($value): string { return trim((string) preg_replace('/[^a-z0-9_-]/', '', strtolower((string) $value))); }
function home_url($path = '/'): string { return 'https://example.test' . (str_starts_with((string) $path, '/') ? $path : '/' . $path); }
function esc_url_raw($url, $protocols = null): string { return (string) $url; }
function esc_html($text): string { return htmlspecialchars((string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function esc_attr($text): string { return htmlspecialchars((string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function esc_url($text): string { return htmlspecialchars((string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function wp_json_encode($value, $flags = 0): string { return (string) json_encode($value, $flags); }
function get_option($key, $default = false) { return $GLOBALS['spux_test_options'][$key] ?? $default; }
function update_option($key, $value, $autoload = null): bool { $GLOBALS['spux_test_options'][$key] = $value; return true; }
function wp_add_inline_style($handle, $css): bool { $GLOBALS['spux_inline_css'] .= (string) $css; return true; }

require_once $root . '/includes/class-public-url.php';
require_once $root . '/includes/class-forty-round-hardening.php';

use Sabri\PublicExperience\Forty_Round_Hardening;

$checks = 0;
$assert = static function (bool $condition, string $label) use (&$checks): void {
    $checks++;
    if (! $condition) {
        fwrite(STDERR, sprintf("[FAIL %03d] %s\n", $checks, $label));
        exit(1);
    }
};

$contract = Forty_Round_Hardening::current_contract();
$tokens = Forty_Round_Hardening::filter_tokens([]);
$css = Forty_Round_Hardening::current_directive_css();
$filtered = Forty_Round_Hardening::filter_contract(['tokens' => []]);

$assert(($contract['canonical_primary_color'] ?? '') === '#15803d', 'Review 134: canonical primary accent is green.');
$assert(($tokens['color-on-primary']['fallback'] ?? '') === '#ffffff', 'Review 135: primary text contrast is white.');
$assert(($tokens['color-primary-soft']['fallback'] ?? '') === '#dcfce7', 'Review 136: soft green token is explicit.');
$semantic = Forty_Round_Hardening::filter_tokens(['color-warning' => ['fallback' => '#aa5500'], 'color-danger' => ['fallback' => '#bb0000']]);
$assert(($semantic['color-warning']['fallback'] ?? '') === '#aa5500' && ($semantic['color-danger']['fallback'] ?? '') === '#bb0000', 'Review 137: primary override does not replace semantic status tokens.');
$assert(($filtered['tokens']['color-primary']['fallback'] ?? '') === '#15803d' && ($filtered['tokens']['color-primary']['inherits'] ?? 'x') === '' && str_contains($css, '--sabri-visual-primary: #15803d;'), 'Review 138: current token amendment has contract precedence.');
$assert(($filtered['navigation_presentation']['owner'] ?? '') === 'file-20', 'Review 139: File 20 remains shell owner.');
$assert(($filtered['navigation_presentation']['persistent_secondary_row'] ?? true) === false, 'Review 140: duplicate persistent navigation row is forbidden.');
$assert(($filtered['navigation_presentation']['overflow_pattern'] ?? '') === 'more-menu-or-drawer', 'Review 141: bounded overflow pattern is declared.');
$assert(str_contains($css, '[aria-current="page"].sabri-ui-nav-item'), 'Review 142: active navigation presentation exists.');
$assert(str_contains($css, 'overflow: clip') && str_contains($css, 'max-inline-size: 100%'), 'Review 143: horizontal overflow is bounded.');
$assert(str_contains($css, '[dir="rtl"]') && str_contains($css, 'margin-inline-start'), 'Review 144: RTL uses logical properties.');
$assert(! str_contains($css, 'scaleX(-1)'), 'Review 145: focus/visual order is not created by page mirroring.');
$assert(str_contains($css, '.sabri-ui-ltr-isolate') && str_contains($css, 'unicode-bidi: isolate'), 'Review 146: LTR values can be safely isolated.');
$assert(str_contains($css, '.sabri-ui-contextual-actions') && str_contains($css, 'justify-content: flex-start'), 'Review 147: RTL right-priority contextual presentation exists.');
$assert(str_contains($css, '@media (max-width: 47.99rem)') && str_contains($css, 'grid-template-columns: 1fr'), 'Review 148: mobile actions stack safely.');
$back = Forty_Round_Hardening::render_icon_button(['label' => 'Back', 'icon' => 'arrow-back', 'url' => 'https://example.test/previous/']);
$assert(str_contains($back, 'data-sabri-icon="arrow-back"') && str_contains($back, '>Back<') && str_contains($back, 'href="https://example.test/previous/"'), 'Review 149: Back control is same-site and explicitly labelled.');
$home = Forty_Round_Hardening::render_icon_button(['label' => 'Home', 'icon' => 'home', 'url' => '/']);
$assert(str_contains($home, '>Home<') && str_contains($home, 'href="/"'), 'Review 150: Home control accepts canonical same-site route.');
$forward = Forty_Round_Hardening::render_icon_button(['label' => 'Next', 'icon' => 'arrow-forward', 'url' => '']);
$assert(str_contains($forward, '<button') && str_contains($forward, 'disabled'), 'Review 151: absent Forward destination renders non-actionable.');
$external = Forty_Round_Hardening::render_icon_button(['label' => 'Back', 'icon' => 'arrow-back', 'url' => 'https://evil.example/back']);
$assert(str_contains($external, '<button') && ! str_contains($external, 'evil.example'), 'Review 152: cross-origin control fails closed.');
$assert(str_contains($forward, 'aria-disabled="true"'), 'Review 153: dead controls become truthful disabled controls.');
$sanitized = Forty_Round_Hardening::render_icon_button(['label' => '<script>bad()</script>Safe', 'icon' => 'check', 'url' => '/safe/']);
$assert(str_contains($back, 'sabri-ui-icon-button__label') && str_contains($back, '<svg') && str_contains($sanitized, '>Safe<') && ! str_contains($sanitized, 'bad()'), 'Review 154: icon and visible text are paired and unsafe label content is removed.');
$assert(Forty_Round_Hardening::render_icon('unknown-icon') === '', 'Review 155: icon rendering is allow-listed.');
$icon = Forty_Round_Hardening::render_icon('download');
$assert(str_contains($icon, 'aria-hidden="true"') && str_contains($icon, 'focusable="false"'), 'Review 156: decorative icons are hidden from assistive technology.');
$assert(str_contains($css, '--sabri-visual-touch-target: 2.75rem') && str_contains($css, 'min-block-size: var(--sabri-visual-touch-target)'), 'Review 157: 44px touch target is enforced.');
$assert(str_contains($css, ':focus-visible') && str_contains($css, 'outline: 3px solid'), 'Review 158: keyboard focus is explicit.');
$assert(str_contains($css, '@media (forced-colors: active)') && str_contains($css, 'Highlight'), 'Review 159: forced-colors mode is supported.');
$assert(str_contains($css, '@media (prefers-reduced-motion: reduce)') && str_contains($css, 'animation-duration: 0.01ms'), 'Review 160: reduced motion is enforced.');
$assert(($filtered['doctor_ranking_presentation']['ranking_owner'] ?? '') === 'file-26' && ($filtered['doctor_ranking_presentation']['computes_rank'] ?? true) === false, 'Review 161: File 25 does not compute ranking.');
$assert(Forty_Round_Hardening::render_ranking_badge(['verified' => 'true', 'owner_supplied' => true, 'tier' => 'top-10']) === '', 'Review 162: ranking verification requires exact boolean truth.');
$badge = Forty_Round_Hardening::render_ranking_badge(['verified' => true, 'owner_supplied' => true, 'tier' => 'invalid']);
$assert(str_contains($badge, 'All Verified Doctors'), 'Review 163: dignified verified-doctor fallback label is used.');
$assert(($filtered['doctor_ranking_presentation']['financial_advantage_allowed'] ?? true) === false, 'Review 164: financial ranking advantage is forbidden.');
$badge = Forty_Round_Hardening::render_ranking_badge(['verified' => true, 'owner_supplied' => true, 'tier' => 'top-100', 'explanation' => 'Owner-supplied governed tier.']);
$assert(str_contains($badge, 'title="Owner-supplied governed tier."') && str_contains($badge, 'aria-label="Top 100 Verified Doctors. Owner-supplied governed tier."'), 'Review 165: ranking explanation is accessible.');
$download = Forty_Round_Hardening::render_download_action(['native_owner_eligible' => true, 'url' => '/file.pdf', 'label' => 'Download PDF']);
$assert(str_contains($download, 'Download PDF') && str_contains($download, '<svg') && str_contains($download, ' download'), 'Review 166: eligible download has icon, text and download semantics.');
$downloadUnknown = Forty_Round_Hardening::render_download_action(['native_owner_eligible' => null, 'url' => '/secret.pdf']);
$fragmentDownload = Forty_Round_Hardening::render_download_action(['native_owner_eligible' => true, 'url' => '#secret']);
$assert(! str_contains($downloadUnknown, 'href=') && str_contains($downloadUnknown, 'disabled') && ! str_contains($fragmentDownload, 'href='), 'Review 167: unknown owner eligibility and fragment-only downloads fail closed.');
$assert(str_contains($download, 'data-access-revalidation="click-time"'), 'Review 168: download declares click-time access revalidation.');
$revoked = Forty_Round_Hardening::render_download_action(['native_owner_eligible' => false, 'reason' => 'Access revoked.']);
$assert(str_contains($revoked, 'Access revoked.') && ! str_contains($revoked, ' download'), 'Review 169: revoked download is non-downloadable with reason.');
$assert(($filtered['download_presentation']['manager_owner'] ?? '') === 'shared-download-manager' && str_contains($download, 'data-manager-owner="shared-download-manager"'), 'Review 170: pause/resume/retry remain manager-owned.');
$revokedAgain = Forty_Round_Hardening::render_download_action(['native_owner_eligible' => false, 'reason' => 'Access revoked.']);
preg_match('/id="([^"]+)"/', $revoked, $firstReason);
preg_match('/id="([^"]+)"/', $revokedAgain, $secondReason);
$assert(str_contains($revoked, 'sabri-ui-download-reason') && str_contains($revoked, '<svg') && str_contains($revoked, 'aria-describedby') && ($firstReason[1] ?? '') !== ($secondReason[1] ?? ''), 'Review 171: unavailable state is not color-only and repeated controls have unique description IDs.');
$assert(str_contains($css, '@media print') && str_contains($css, 'display: none !important'), 'Review 172: print/no-JS presentation remains understandable and uncluttered.');
$scalarSafe = Forty_Round_Hardening::filter_contract(['renderers' => 'invalid']);
$assert(($contract['range'] ?? '') === '134-173' && ($contract['count'] ?? 0) === 40 && ($contract['hostinger_staging_accepted'] ?? true) === false && count($contract['rounds'] ?? []) === 40 && is_array($scalarSafe['renderers'] ?? null), 'Review 173: forty rounds recorded without external acceptance overclaim and malformed renderer input is repaired.');

echo sprintf("Reviews 134-173 current-directive corrective regressions PASS (%d checks)\n", $checks);
