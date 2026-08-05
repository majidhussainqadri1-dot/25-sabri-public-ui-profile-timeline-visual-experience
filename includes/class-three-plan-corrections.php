<?php

declare(strict_types=1);

namespace Sabri\PublicExperience;

if (! defined('ABSPATH') && PHP_SAPI !== 'cli') {
    exit;
}

/**
 * Reviews 174–176: bounded corrections derived from the three governing plans.
 *
 * File 20 remains the welcome-frequency and shell owner. File 25 supplies only
 * the accessible visual primitive. Native modules retain authorization and data
 * ownership; these filters only restore fail-closed behavior and preserve an
 * already-authorized File 03 contact value.
 */
final class Three_Plan_Corrections
{
    public const DIRECTIVE_REGISTER_VERSION = '2.1';

    public static function register(): void
    {
        add_filter(
            'sabri_public_experience/public_contacts',
            [self::class, 'preserve_authorized_contacts'],
            -100,
            2
        );
        add_filter(
            'sabri_public_experience/high_risk_authorized',
            [self::class, 'deny_high_risk_by_default'],
            -100,
            3
        );
        add_filter(
            'sabri_visual_experience/contract',
            [self::class, 'extend_visual_contract'],
            30,
            1
        );
        add_filter(
            'sabri_public_experience/design_system_contract',
            [self::class, 'extend_visual_contract'],
            30,
            1
        );
        add_action('wp_enqueue_scripts', [self::class, 'enqueue_css'], 40);
    }

    /**
     * Profile_Repository passes only consented, canonical File 03 values into
     * this filter. Convert that default value to a retain decision before any
     * later extension may revoke it. A later string substitution still fails
     * closed because the repository accepts only strict true at render time.
     *
     * @param mixed $contacts
     * @return array<string,bool>
     */
    public static function preserve_authorized_contacts(mixed $contacts, int $user_id = 0): array
    {
        unset($user_id);
        if (! is_array($contacts)) {
            return [];
        }

        $decisions = [];
        foreach (['phone', 'whatsapp'] as $field) {
            if (! array_key_exists($field, $contacts)) {
                continue;
            }
            $value = $contacts[$field];
            if (is_string($value) && trim($value) !== '') {
                $decisions[$field] = true;
                continue;
            }
            if ($value === true) {
                $decisions[$field] = true;
            }
        }

        return $decisions;
    }

    public static function deny_high_risk_by_default(
        mixed $authorized,
        string $operation = '',
        int $actor_id = 0
    ): bool {
        unset($authorized, $operation, $actor_id);

        // This callback runs before any explicit native authorization adapter.
        // No adapter means denial; a later authoritative callback may grant.
        return false;
    }

    /** @param mixed $contract @return array<string,mixed> */
    public static function extend_visual_contract(mixed $contract): array
    {
        $base = is_array($contract) ? $contract : [];
        $base['governing_amendment'] = [
            'document' => 'Sabri Platform All-Chats Recovered Directive Register',
            'version' => self::DIRECTIVE_REGISTER_VERSION,
            'status' => 'founder-approved-active-amendment',
        ];
        $base['directives']['CHAT-UX-001'] = [
            'decision' => 'welcome-first-eligible-visit-then-30-days',
            'frequency_owner' => 'file-20',
            'visual_owner' => 'file-25',
            'non_blocking' => true,
        ];
        $base['directives']['CHAT-UX-002'] = [
            'decision' => 'single-complete-top-navigation',
            'structural_owner' => 'file-20',
            'visual_owner' => 'file-25',
        ];
        $base['directives']['CHAT-UX-003'] = [
            'decision' => 'rtl-right-priority-with-semantic-ltr-exceptions',
            'structural_owner' => 'file-20',
            'visual_owner' => 'file-25',
        ];
        $base['directives']['CHAT-UX-004'] = [
            'decision' => 'back-home-and-contextual-forward',
            'component_owner' => 'file-20-and-native-modules',
            'visual_owner' => 'file-25',
        ];
        $base['directives']['CHAT-DOC-001'] = [
            'decision' => 'top-10-100-1000-all-verified-doctors',
            'ranking_owner' => 'file-26',
            'visual_owner' => 'file-25',
            'paid_bias' => false,
        ];
        $base['directives']['CHAT-DL-001'] = [
            'decision' => 'eligible-content-only-download-presentation',
            'eligibility_owner' => 'native-owner',
            'visual_owner' => 'file-25',
        ];
        $base['directives']['CHAT-QA-001'] = [
            'decision' => 'post-github-global-harmonization',
            'known_critical_high_defects_release_blocker' => true,
        ];
        $base['directives']['CHAT-DOC-021'] = [
            'decision' => 'doctor-as-personal-website-experience',
            'visual_owner' => 'file-25',
            'canonical_data_owners_preserved' => true,
        ];
        $base['directives']['CHAT-REC-024'] = [
            'decision' => 'recovered-directive-register-governance',
            'active_superseded_separation' => true,
        ];
        $base['ecosystem_principle'] = [
            'id' => 'ALL-CHATS-25-ONE-ROOF-HOMEOPATHY-ECOSYSTEM',
            'experience' => 'unified-discoverable-seamless',
            'canonical_ownership_preserved' => true,
        ];

        return $base;
    }

    /** @param array<string,mixed> $args */
    public static function render_welcome_panel(array $args = []): string
    {
        // File 25 must never decide whether the overlay is due. File 20 invokes
        // this renderer only after its account/cookie/localStorage 30-day gate.
        if (($args['invoked_by_file_20'] ?? false) !== true) {
            return '';
        }

        $title = self::text($args['title'] ?? 'Welcome to Sabri Social Homeopathy Platform', 180);
        $message = self::text($args['message'] ?? '', 600);
        $continue_label = self::text($args['continue_label'] ?? 'Continue', 80);
        $close_label = self::text($args['close_label'] ?? 'Close', 80);
        $continue_url = Public_URL::sanitize_same_site($args['continue_url'] ?? home_url('/'));
        if ($title === '' || $continue_label === '' || $close_label === '' || $continue_url === '') {
            return '';
        }

        $description = $message !== ''
            ? '<p class="sabri-ui-welcome-panel__message">' . self::escape_html($message) . '</p>'
            : '';

        return '<section class="sabri-ui-welcome-panel" role="region" aria-labelledby="sabri-welcome-title"'
            . ' data-directive="CHAT-UX-001" data-frequency-owner="file-20" data-visual-owner="file-25">'
            . '<h2 id="sabri-welcome-title">' . self::escape_html($title) . '</h2>'
            . $description
            . '<div class="sabri-ui-welcome-panel__actions">'
            . '<a class="sabri-ui-button sabri-ui-button--primary" href="' . self::escape_url($continue_url) . '">'
            . self::escape_html($continue_label) . '</a>'
            . '<button class="sabri-ui-button sabri-ui-button--secondary" type="button" data-sabri-welcome-dismiss>'
            . self::escape_html($close_label) . '</button>'
            . '</div></section>';
    }

    public static function enqueue_css(): void
    {
        if (! function_exists('wp_add_inline_style')) {
            return;
        }
        wp_add_inline_style('sabri-visual-design-system', self::css());
    }

    public static function css(): string
    {
        return <<<'CSS'
.sabri-ui-welcome-panel {
    display: grid;
    gap: 1rem;
    inline-size: min(100%, 48rem);
    margin-inline: auto;
    padding: clamp(1rem, 3vw, 2rem);
    border: 1px solid color-mix(in srgb, var(--sabri-visual-primary, #15803d) 28%, transparent);
    border-radius: 1rem;
    background: var(--sabri-surface, #fff);
    box-shadow: 0 1rem 2.5rem rgb(15 23 42 / 10%);
    color: var(--sabri-text, #111827);
}
.sabri-ui-welcome-panel h2,
.sabri-ui-welcome-panel p { margin: 0; }
.sabri-ui-welcome-panel__actions {
    display: flex;
    flex-wrap: wrap;
    gap: .75rem;
    justify-content: flex-start;
}
[dir="rtl"] .sabri-ui-welcome-panel__actions { justify-content: flex-start; }
.sabri-ui-welcome-panel__actions .sabri-ui-button { min-block-size: 44px; }
@media (max-width: 35rem) {
    .sabri-ui-welcome-panel__actions { display: grid; grid-template-columns: 1fr; }
}
@media (prefers-reduced-motion: reduce) {
    .sabri-ui-welcome-panel { scroll-behavior: auto; }
}
CSS;
    }

    private static function text(mixed $value, int $limit): string
    {
        if (! is_scalar($value)) {
            return '';
        }
        $text = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', ' ', (string) $value) ?? '';
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        $text = trim($text);

        return function_exists('mb_substr') ? mb_substr($text, 0, $limit) : substr($text, 0, $limit);
    }

    private static function escape_html(string $value): string
    {
        return function_exists('esc_html') ? esc_html($value) : htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private static function escape_url(string $value): string
    {
        return function_exists('esc_url') ? esc_url($value) : htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
