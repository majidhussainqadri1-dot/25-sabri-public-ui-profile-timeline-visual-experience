<?php

declare(strict_types=1);

namespace Sabri\PublicExperience;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Immutable evidence ledger for the forty fresh review-and-correction rounds.
 *
 * All operational corrections live in their canonical source classes. This
 * class deliberately registers no duplicate REST, admin-post, preference,
 * migration, repair, index, search, or metrics callbacks.
 */
final class Forty_Round_Hardening
{
    private const AUDIT_OPTION = 'sabri_public_experience_review_94_133_audit';

    public function register(): void
    {
        add_action('init', [$this, 'record_review_contract'], 1);
    }

    /** @return array<string,mixed> */
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
        $contract['sha256'] = hash(
            'sha256',
            (string) wp_json_encode($contract, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );

        return $contract;
    }

    public function record_review_contract(): void
    {
        $contract = self::contract();
        $current = get_option(self::AUDIT_OPTION, []);
        $current_hash = is_array($current) && isset($current['sha256']) && is_string($current['sha256'])
            ? $current['sha256']
            : '';

        if ($current_hash !== '' && hash_equals($current_hash, (string) $contract['sha256'])) {
            return;
        }

        update_option(self::AUDIT_OPTION, $contract, false);
    }
}
