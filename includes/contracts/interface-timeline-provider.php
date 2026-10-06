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


/**
 * Compatibility defaults for the complete File 25 §46 provider contract.
 *
 * Real native providers should override normalize_public_item() where they
 * receive owner-specific raw DTOs. The remaining methods are safe,
 * presentation-only projections over an already-normalized item.
 */
trait Timeline_Provider_Plan_Defaults
{
    /** @param array<string,mixed> $item */
    public function normalize_public_item(array $item, int $author_id): ?Normalized_Timeline_Item
    {
        unset($item, $author_id);
        return null;
    }

    public function get_canonical_url(Normalized_Timeline_Item $item): string
    {
        return (string) $item->get('canonical_url');
    }

    public function get_visibility_state(Normalized_Timeline_Item $item): string
    {
        return (string) $item->get('visibility_state');
    }

    /** @return list<string> */
    public function get_public_actions(Normalized_Timeline_Item $item): array
    {
        $actions = $item->get('available_actions');
        return is_array($actions) ? array_values(array_filter($actions, 'is_string')) : [];
    }

    /** @return array<string,int|float|string> */
    public function get_public_metrics(Normalized_Timeline_Item $item): array
    {
        unset($item);
        return [];
    }

    public function get_correction_state(Normalized_Timeline_Item $item): string
    {
        return (string) $item->get('correction_state');
    }

    public function register_sync_events(): void
    {
        // Read-only providers have no synchronization event subscriptions by
        // default. Native owners may override this with bounded invalidation
        // hooks without transferring canonical ownership to File 25.
    }
}
