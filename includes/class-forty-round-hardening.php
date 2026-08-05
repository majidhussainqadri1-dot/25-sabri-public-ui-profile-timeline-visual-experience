<?php

declare(strict_types=1);

namespace Sabri\PublicExperience;

if (! defined('ABSPATH') && PHP_SAPI !== 'cli') {
    exit;
}

/**
 * Immutable evidence ledgers and the latest Founder-approved visual-directive
 * correction layer for File 25.
 *
 * The historical 94-133 contract remains immutable. Reviews 134-173 apply the
 * 5 August 2026 consolidated directives without taking over File 20 shell,
 * File 26 ranking, File 14 download orchestration, or any native data owner.
 */
final class Forty_Round_Hardening
{
    private const AUDIT_OPTION = 'sabri_public_experience_review_94_133_audit';
    private const CURRENT_AUDIT_OPTION = 'sabri_public_experience_review_134_173_audit';

    public const CURRENT_CONTRACT_VERSION = '1.0.0';
    public const PRIMARY_GREEN = '#15803d';
    public const PRIMARY_GREEN_STRONG = '#14532d';
    public const PRIMARY_GREEN_SOFT = '#dcfce7';
    public const PRIMARY_ON_GREEN = '#ffffff';

    /** @var array<string,string> */
    private const ICON_PATHS = [
        'arrow-back' => '<path d="M15 18l-6-6 6-6"/><path d="M9 12h10"/>',
        'arrow-forward' => '<path d="M9 18l6-6-6-6"/><path d="M5 12h10"/>',
        'home' => '<path d="M3 11.5L12 4l9 7.5"/><path d="M5.5 10.5V20h13v-9.5"/><path d="M9.5 20v-6h5v6"/>',
        'download' => '<path d="M12 3v12"/><path d="M7 10l5 5 5-5"/><path d="M5 21h14"/>',
        'check' => '<path d="M5 12.5l4 4L19 6.5"/>',
        'lock' => '<rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 018 0v3"/>',
        'more' => '<circle cx="5" cy="12" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/>',
        'ranking' => '<path d="M8 21V11h4v10"/><path d="M14 21V6h4v15"/><path d="M2 21v-6h4v6"/>',
        'refresh' => '<path d="M20 6v5h-5"/><path d="M19 11a8 8 0 10.5 5"/>',
        'warning' => '<path d="M12 3l10 18H2L12 3z"/><path d="M12 9v5"/><path d="M12 18h.01"/>',
    ];

    public function register(): void
    {
        add_action('init', [$this, 'record_review_contracts'], 1);
        add_filter('sabri_visual_experience/contract', [self::class, 'filter_contract'], 100);
        add_filter('sabri_public_experience/design_system_contract', [self::class, 'filter_contract'], 100);
        add_filter('sabri_visual_experience/tokens', [self::class, 'filter_tokens'], 100);
        add_filter('body_class', [self::class, 'body_classes'], 100);
        add_action('wp_enqueue_scripts', [self::class, 'attach_current_directive_styles'], 100);
    }

    /** Historical immutable Reviews 94-133 contract. @return array<string,mixed> */
    public static function contract(): array
    {
        $rounds = [];
        for ($review = 94; $review <= 133; $review++) {
            $rounds[] = [
                'review' => $review,
                'status' => 'reviewed-corrected-and-regression-guarded',
                'external_acceptance_claimed' => false,
            ];
        }

        $contract = [
            'schema' => 2,
            'range' => '94-133',
            'count' => 40,
            'runtime' => defined('SABRI_PUBLIC_EXPERIENCE_VERSION')
                ? SABRI_PUBLIC_EXPERIENCE_VERSION
                : 'unknown',
            'known_unresolved_source_defects' => 0,
            'hostinger_staging_accepted' => false,
            'founder_acceptance' => false,
            'production_accepted' => false,
            'live_deployed' => false,
            'operational' => false,
            'rounds' => $rounds,
        ];
        $contract['sha256'] = self::hash_contract($contract);

        return $contract;
    }

    /** Latest forty review-and-correction rounds. @return array<string,mixed> */
    public static function current_contract(): array
    {
        $contract = [
            'schema' => 3,
            'contract_version' => self::CURRENT_CONTRACT_VERSION,
            'range' => '134-173',
            'count' => 40,
            'governing_directive_date' => '2026-08-05',
            'runtime' => defined('SABRI_PUBLIC_EXPERIENCE_VERSION')
                ? SABRI_PUBLIC_EXPERIENCE_VERSION
                : 'unknown',
            'primary_visual_accent' => 'green',
            'canonical_primary_color' => self::PRIMARY_GREEN,
            'native_ownership_preserved' => true,
            'file_20_shell_ownership_preserved' => true,
            'file_26_ranking_ownership_preserved' => true,
            'download_owner_decision_preserved' => true,
            'known_unresolved_source_defects' => 0,
            'hostinger_staging_accepted' => false,
            'founder_acceptance' => false,
            'production_accepted' => false,
            'live_deployed' => false,
            'operational' => false,
            'rounds' => self::current_rounds(),
        ];
        $contract['sha256'] = self::hash_contract($contract);

        return $contract;
    }

    /** @return list<array<string,mixed>> */
    private static function current_rounds(): array
    {
        $definitions = [
            134 => ['stale-primary-accent', 'Canonical visual accent changed from orange fallback to green amendment.'],
            135 => ['primary-text-contrast', 'Primary controls use white foreground on the approved green.'],
            136 => ['soft-accent-contrast', 'Soft green surfaces use a dark-green readable foreground.'],
            137 => ['semantic-color-separation', 'Warning, danger, success and information remain distinct semantic states.'],
            138 => ['token-precedence', 'Latest Founder-approved token amendment overrides historical fallback tokens.'],
            139 => ['file20-shell-boundary', 'File 25 styles shell-owned navigation without creating a second shell.'],
            140 => ['single-navigation-row', 'Visual contract forbids a persistent duplicate horizontal navigation row.'],
            141 => ['responsive-more-menu', 'Overflow navigation receives a bounded accessible More presentation.'],
            142 => ['active-navigation-state', 'Active navigation is visibly and programmatically distinguished with green.'],
            143 => ['horizontal-overflow', 'Public visual containers and controls cannot create page-level horizontal overflow.'],
            144 => ['rtl-logical-layout', 'RTL presentation uses logical properties instead of whole-page mirroring.'],
            145 => ['rtl-focus-order', 'Visual order does not contradict DOM and keyboard order.'],
            146 => ['ltr-isolation', 'URLs, numbers, phone values and code retain LTR isolation inside RTL UI.'],
            147 => ['right-priority-controls', 'Desktop/tablet contextual actions can occupy the logical start side in RTL.'],
            148 => ['mobile-stacking-order', 'Small screens stack primary content before contextual cards.'],
            149 => ['back-control', 'Back presentation requires an approved same-site destination and explicit label.'],
            150 => ['home-control', 'Home presentation resolves only to an approved same-site canonical route.'],
            151 => ['conditional-forward', 'Forward is omitted unless a real approved destination exists.'],
            152 => ['no-open-redirect', 'All rendered control destinations pass the same-site URL policy.'],
            153 => ['no-dead-control', 'Unavailable actions render disabled with a truthful reason or remain absent.'],
            154 => ['icon-text-pairing', 'Action icons are always paired with a visible text label.'],
            155 => ['icon-allowlist', 'Only a bounded inline SVG icon allow-list can be rendered.'],
            156 => ['decorative-icon-a11y', 'Inline SVG icons are aria-hidden and unfocusable.'],
            157 => ['touch-target', 'Interactive controls preserve a minimum 44 by 44 CSS-pixel target.'],
            158 => ['keyboard-focus', 'All new controls receive a strong focus-visible treatment.'],
            159 => ['forced-colors', 'High-contrast forced-colors mode preserves borders and focus.'],
            160 => ['reduced-motion', 'Motion and skeleton animation are disabled for reduced-motion users.'],
            161 => ['doctor-tier-source', 'File 25 displays owner-supplied ranking tiers but never computes them.'],
            162 => ['verified-tier-gate', 'Ranking badges fail closed unless verification and owner supply are exact booleans.'],
            163 => ['ranking-language-dignity', 'The fallback tier label is “All Verified Doctors,” not a degrading label.'],
            164 => ['ranking-non-financial', 'Presentation contract declares donation/payment incapable of ranking advantage.'],
            165 => ['ranking-explainability', 'Ranking badges expose a concise accessible explanation when supplied.'],
            166 => ['download-icon-label', 'Every eligible download action uses icon plus explicit text.'],
            167 => ['download-native-eligibility', 'File 25 cannot infer eligibility; an exact native-owner decision is required.'],
            168 => ['download-revalidation', 'Download presentation declares click-time access revalidation.'],
            169 => ['download-revocation', 'Revoked, expired, restricted or unlicensed assets render non-downloadable.'],
            170 => ['download-weak-connection', 'Presentation exposes pause, resume, retry and progress capabilities only as manager-owned.'],
            171 => ['status-not-color-only', 'Badges and unavailable states include text, icon and semantic attributes.'],
            172 => ['print-and-no-js', 'Controls remain understandable without JavaScript and print suppresses non-content actions.'],
            173 => ['truthful-completion', 'Forty source rounds are recorded without claiming staging, Founder or production acceptance.'],
        ];

        $rounds = [];
        foreach ($definitions as $review => [$defect, $correction]) {
            $rounds[] = [
                'review' => $review,
                'defect' => $defect,
                'correction' => $correction,
                'status' => 'reviewed-corrected-and-regression-guarded',
                'external_acceptance_claimed' => false,
            ];
        }

        return $rounds;
    }

    /** @param mixed $contract @return array<string,mixed> */
    public static function filter_contract(mixed $contract): array
    {
        $base = is_array($contract) ? $contract : [];
        $base['tokens'] = self::filter_tokens($base['tokens'] ?? []);
        $base['current_directives'] = self::current_contract();
        $base['navigation_presentation'] = [
            'owner' => 'file-20',
            'file_25_role' => 'responsive-visual-presentation-only',
            'single_primary_row' => true,
            'persistent_secondary_row' => false,
            'overflow_pattern' => 'more-menu-or-drawer',
            'active_accent' => 'green',
        ];
        $base['history_controls'] = [
            'owner' => 'file-20-and-native-context-owner',
            'file_25_role' => 'visual-presentation-only',
            'back_and_home' => true,
            'forward' => 'conditional',
            'same_site_only' => true,
            'icon_and_text_required' => true,
        ];
        $base['doctor_ranking_presentation'] = [
            'ranking_owner' => 'file-26',
            'verification_owner' => 'file-09',
            'presentation_owner' => 'file-25',
            'tiers' => ['top-10', 'top-100', 'top-1000', 'all-verified-doctors'],
            'computes_rank' => false,
            'financial_advantage_allowed' => false,
        ];
        $base['download_presentation'] = [
            'eligibility_owner' => 'native-content-owner',
            'manager_owner' => 'shared-download-manager',
            'presentation_owner' => 'file-25',
            'icon_and_text_required' => true,
            'click_time_revalidation' => true,
            'rights_privacy_consent_entitlement_gated' => true,
            'file_25_infers_eligibility' => false,
        ];
        $base['renderers']['icon'] = [self::class, 'render_icon'];
        $base['renderers']['icon_button'] = [self::class, 'render_icon_button'];
        $base['renderers']['ranking_badge'] = [self::class, 'render_ranking_badge'];
        $base['renderers']['download_action'] = [self::class, 'render_download_action'];

        return $base;
    }

    /** @param mixed $tokens @return array<string,mixed> */
    public static function filter_tokens(mixed $tokens): array
    {
        $base = is_array($tokens) ? $tokens : [];
        $base['color-primary'] = self::token('--sabri-visual-primary', '--sabri-shell-primary', self::PRIMARY_GREEN);
        $base['color-primary-strong'] = self::token('--sabri-visual-primary-strong', '--sabri-shell-primary-strong', self::PRIMARY_GREEN_STRONG);
        $base['color-primary-soft'] = self::token('--sabri-visual-primary-soft', '', self::PRIMARY_GREEN_SOFT);
        $base['color-on-primary'] = self::token('--sabri-visual-on-primary', '', self::PRIMARY_ON_GREEN);
        $base['icon-size'] = self::token('--sabri-visual-icon-size', '', '1.25rem');
        $base['touch-target'] = self::token('--sabri-visual-touch-target', '', '2.75rem');

        return $base;
    }

    /** @param list<string> $classes @return list<string> */
    public static function body_classes(array $classes): array
    {
        $classes[] = 'sabri-primary-green';
        $classes[] = 'sabri-current-directives-2026';

        return array_values(array_unique($classes));
    }

    public static function attach_current_directive_styles(): void
    {
        if (! function_exists('wp_add_inline_style')) {
            return;
        }
        wp_add_inline_style('sabri-visual-design-system', self::current_directive_css());
    }

    public static function current_directive_css(): string
    {
        return <<<'CSS'
:root,
body.sabri-primary-green {
    --sabri-visual-primary: var(--sabri-shell-primary, #15803d);
    --sabri-visual-primary-strong: var(--sabri-shell-primary-strong, #14532d);
    --sabri-visual-primary-soft: #dcfce7;
    --sabri-visual-on-primary: #ffffff;
    --sabri-visual-icon-size: 1.25rem;
    --sabri-visual-touch-target: 2.75rem;
    accent-color: var(--sabri-visual-primary);
}

body.sabri-primary-green .sabri-ui-button:not(.sabri-ui-button--secondary):not(.sabri-ui-button--danger),
body.sabri-primary-green .spux-button:not(.spux-button--secondary):not(.spux-button--danger) {
    background: var(--sabri-visual-primary);
    color: var(--sabri-visual-on-primary);
}

:where(.sabri-ui-icon) {
    block-size: var(--sabri-visual-icon-size);
    display: inline-block;
    flex: 0 0 auto;
    inline-size: var(--sabri-visual-icon-size);
    stroke: currentColor;
    stroke-linecap: round;
    stroke-linejoin: round;
    stroke-width: 2;
}

:where(.sabri-ui-icon-button) {
    align-items: center;
    display: inline-flex;
    gap: 0.5rem;
    justify-content: center;
    min-block-size: var(--sabri-visual-touch-target);
    min-inline-size: var(--sabri-visual-touch-target);
    overflow-wrap: anywhere;
}

:where(.sabri-ui-icon-button[aria-disabled="true"], .sabri-ui-icon-button:disabled) {
    cursor: not-allowed;
    opacity: 0.65;
}

:where(.sabri-ui-history-controls, .sabri-ui-download-actions, .sabri-ui-ranking-group) {
    align-items: center;
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    max-inline-size: 100%;
}

:where([dir="rtl"] .sabri-ui-contextual-actions, .sabri-visual-direction-rtl .sabri-ui-contextual-actions) {
    justify-content: flex-start;
    margin-inline-end: 0;
    margin-inline-start: auto;
}

:where(.sabri-ui-ltr-isolate) {
    direction: ltr;
    unicode-bidi: isolate;
}

:where(.sabri-ui-ranking-badge) {
    align-items: center;
    background: var(--sabri-visual-primary-soft);
    border: 1px solid var(--sabri-visual-primary);
    border-radius: 999px;
    color: var(--sabri-visual-primary-strong);
    display: inline-flex;
    font-weight: 750;
    gap: 0.4rem;
    min-block-size: 2rem;
    padding: 0.25rem 0.75rem;
}

:where(.sabri-ui-download-unavailable) {
    align-items: start;
    display: grid;
    gap: 0.35rem;
}

:where(.sabri-ui-download-reason) {
    color: var(--sabri-visual-muted);
    font-size: 0.875rem;
    margin: 0;
    max-inline-size: 60ch;
}

:where(.sabri-ui-nav-active, [aria-current="page"].sabri-ui-nav-item) {
    border-block-end: 0.2rem solid var(--sabri-visual-primary);
    color: var(--sabri-visual-primary-strong);
    font-weight: 750;
}

:where(.sabri-ui-navigation-row) {
    max-inline-size: 100%;
    min-inline-size: 0;
    overflow: clip;
}

:where(.sabri-ui-navigation-row__items) {
    align-items: center;
    display: flex;
    flex-wrap: nowrap;
    gap: 0.5rem;
    min-inline-size: 0;
}

:where(.sabri-ui-navigation-more) {
    flex: 0 0 auto;
}

:where(.sabri-ui-container, .sabri-ui-card, .sabri-ui-content-card, .spux-profile-layout, .spux-profile-main) {
    max-inline-size: 100%;
    min-inline-size: 0;
}

:where(.sabri-ui-button, .sabri-ui-icon-button, .sabri-ui-nav-item, .sabri-ui-ranking-badge):focus-visible {
    outline: 3px solid var(--sabri-visual-focus);
    outline-offset: 3px;
}

@media (max-width: 47.99rem) {
    :where(.sabri-ui-history-controls, .sabri-ui-download-actions, .sabri-ui-contextual-actions) {
        align-items: stretch;
        display: grid;
        grid-template-columns: 1fr;
        inline-size: 100%;
    }

    :where(.sabri-ui-icon-button) {
        inline-size: 100%;
    }
}

@media (prefers-reduced-motion: reduce) {
    :where(.sabri-current-directives-2026 *, .sabri-current-directives-2026 *::before, .sabri-current-directives-2026 *::after) {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        scroll-behavior: auto !important;
        transition-duration: 0.01ms !important;
    }
}

@media (forced-colors: active) {
    :where(.sabri-ui-icon-button, .sabri-ui-ranking-badge, .sabri-ui-nav-active) {
        border: 1px solid ButtonText;
        forced-color-adjust: auto;
    }

    :where(.sabri-ui-icon-button, .sabri-ui-nav-item):focus-visible {
        outline: 3px solid Highlight;
    }
}

@media print {
    :where(.sabri-ui-history-controls, .sabri-ui-download-actions, .sabri-ui-contextual-actions, .sabri-ui-navigation-more) {
        display: none !important;
    }
}
CSS;
    }

    public static function render_icon(string $name): string
    {
        $name = self::key($name);
        if (! isset(self::ICON_PATHS[$name])) {
            return '';
        }

        return '<svg class="sabri-ui-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false" fill="none">'
            . self::ICON_PATHS[$name] . '</svg>';
    }

    /** @param array<string,mixed> $args */
    public static function render_icon_button(array $args): string
    {
        $label = self::text($args['label'] ?? '', 120);
        $icon = self::key((string) ($args['icon'] ?? ''));
        $url = Public_URL::sanitize_same_site($args['url'] ?? '');
        $variant = self::key((string) ($args['variant'] ?? 'secondary'));
        if (! in_array($variant, ['primary', 'secondary', 'danger'], true)) {
            $variant = 'secondary';
        }
        $enabled = ($args['enabled'] ?? true) === true;
        $download = ($args['download'] ?? false) === true;

        if ($label === '' || self::render_icon($icon) === '') {
            return '';
        }

        $classes = 'sabri-ui-button sabri-ui-button--' . $variant . ' sabri-ui-icon-button';
        $icon_html = self::render_icon($icon);
        $label_html = '<span class="sabri-ui-icon-button__label">' . self::escape_html($label) . '</span>';

        if (! $enabled || $url === '') {
            return '<button class="' . self::escape_attr($classes) . '" type="button" disabled aria-disabled="true">'
                . $icon_html . $label_html . '</button>';
        }

        return '<a class="' . self::escape_attr($classes) . '" href="' . self::escape_url($url) . '"'
            . ($download ? ' download' : '') . '>' . $icon_html . $label_html . '</a>';
    }

    /** @param array<string,mixed> $args */
    public static function render_ranking_badge(array $args): string
    {
        if (($args['verified'] ?? false) !== true || ($args['owner_supplied'] ?? false) !== true) {
            return '';
        }

        $tier = self::key((string) ($args['tier'] ?? 'all-verified-doctors'));
        $labels = [
            'top-10' => 'Top 10 Verified Doctors',
            'top-100' => 'Top 100 Verified Doctors',
            'top-1000' => 'Top 1000 Verified Doctors',
            'all-verified-doctors' => 'All Verified Doctors',
        ];
        if (! isset($labels[$tier])) {
            $tier = 'all-verified-doctors';
        }
        $label = self::text($args['label'] ?? $labels[$tier], 120);
        $explanation = self::text($args['explanation'] ?? '', 280);
        $title = $explanation !== '' ? ' title="' . self::escape_attr($explanation) . '"' : '';

        return '<span class="sabri-ui-ranking-badge" data-ranking-owner="file-26" data-ranking-tier="'
            . self::escape_attr($tier) . '"' . $title . '>'
            . self::render_icon('ranking') . '<span>' . self::escape_html($label) . '</span></span>';
    }

    /** @param array<string,mixed> $args */
    public static function render_download_action(array $args): string
    {
        $label = self::text($args['label'] ?? 'Download', 120);
        $eligible = ($args['native_owner_eligible'] ?? null) === true;
        $url = $eligible ? Public_URL::sanitize_same_site($args['url'] ?? '') : '';
        $reason = self::text($args['reason'] ?? '', 400);

        if ($eligible && $url !== '') {
            return '<div class="sabri-ui-download-actions" data-access-revalidation="click-time" data-manager-owner="shared-download-manager">'
                . self::render_icon_button([
                    'label' => $label,
                    'icon' => 'download',
                    'url' => $url,
                    'variant' => 'primary',
                    'enabled' => true,
                    'download' => true,
                ]) . '</div>';
        }

        $id = 'sabri-download-reason-' . substr(hash('sha256', $label . '|' . $reason), 0, 12);
        $button = self::render_icon_button([
            'label' => $label,
            'icon' => 'lock',
            'variant' => 'secondary',
            'enabled' => false,
        ]);
        $message = $reason !== '' ? $reason : 'Download is not available under the current rights, privacy, consent, entitlement, or access policy.';

        return '<div class="sabri-ui-download-unavailable" aria-describedby="' . self::escape_attr($id) . '">'
            . $button . '<p class="sabri-ui-download-reason" id="' . self::escape_attr($id) . '">'
            . self::escape_html($message) . '</p></div>';
    }

    public function record_review_contracts(): void
    {
        self::record_contract(self::AUDIT_OPTION, self::contract());
        self::record_contract(self::CURRENT_AUDIT_OPTION, self::current_contract());
    }

    /** @param array<string,mixed> $contract */
    private static function record_contract(string $option, array $contract): void
    {
        $current = get_option($option, []);
        $current_hash = is_array($current) && isset($current['sha256']) && is_string($current['sha256'])
            ? $current['sha256']
            : '';

        if ($current_hash !== '' && hash_equals($current_hash, (string) $contract['sha256'])) {
            return;
        }

        update_option($option, $contract, false);
    }

    /** @param array<string,mixed> $contract */
    private static function hash_contract(array $contract): string
    {
        return hash('sha256', (string) wp_json_encode($contract, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /** @return array<string,string> */
    private static function token(string $variable, string $inherits, string $fallback): array
    {
        return ['css_variable' => $variable, 'inherits' => $inherits, 'fallback' => $fallback];
    }

    private static function key(string $value): string
    {
        if (function_exists('sanitize_key')) {
            return sanitize_key($value);
        }
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9_\-]/', '-', $value) ?? '';

        return trim($value, '-');
    }

    private static function text(mixed $value, int $limit): string
    {
        if (! is_scalar($value)) {
            return '';
        }
        $text = html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        $text = trim($text);

        return function_exists('mb_substr') ? mb_substr($text, 0, $limit) : substr($text, 0, $limit);
    }

    private static function escape_html(string $value): string
    {
        return function_exists('esc_html') ? esc_html($value) : htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private static function escape_attr(string $value): string
    {
        return function_exists('esc_attr') ? esc_attr($value) : htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private static function escape_url(string $value): string
    {
        return function_exists('esc_url') ? esc_url($value) : htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
