<?php

declare(strict_types=1);

namespace Sabri\PublicExperience\Contracts;

use Sabri\PublicExperience\Normalized_Timeline_Item;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Read-only contract for native content providers.
 *
 * Providers remain the source of truth. File 25 normalizes and presents only
 * public, approved, canonical contributions.
 */
interface Timeline_Provider
{
    public function get_provider_id(): string;

    public function get_provider_version(): string;

    public function is_available(): bool;

    /**
     * One of: detected, read-only, staging-accepted, production-accepted,
     * degraded, disabled.
     */
    public function get_maturity_level(): string;

    /**
     * Return the newest bounded candidate pool for one author.
     *
     * File 25 supplies `page=1`, a bounded `candidate_limit`, the original
     * `requested_page`, `per_page`, and optional `content_type`. Providers must
     * not apply the requested global page offset themselves; File 25 merges,
     * deduplicates, sorts, and paginates all providers together.
     *
     * Every returned item must retain the requested author/profile ID, be
     * publicly approved by its native owner, and point to a canonical same-site
     * destination.
     *
     * @param array<string,mixed> $query
     * @return list<Normalized_Timeline_Item>
     */
    public function get_public_author_items(int $author_id, array $query): array;

    /** @return array<string,mixed> */
    public function get_health_status(): array;
}
