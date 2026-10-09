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

    /**
     * Normalize one owner-native public candidate into File 25's immutable
     * presentation pointer. Providers remain authoritative for publication and
     * visibility truth.
     *
     * @param array<string,mixed> $native_item
     */
    public function normalize_public_item(array $native_item, int $author_id): ?Normalized_Timeline_Item;

    public function get_canonical_url(Normalized_Timeline_Item $item): string;

    public function get_visibility_state(Normalized_Timeline_Item $item): string;

    /** @return list<string> */
    public function get_public_actions(Normalized_Timeline_Item $item): array;

    /**
     * Return only public, aggregate, owner-defined metrics for presentation
     * decisions. Raw analytics, reader identities and private counters are
     * forbidden.
     *
     * @return array{views?:int,saves?:int,privacy_safe?:bool}
     */
    public function get_public_metrics(Normalized_Timeline_Item $item): array;

    public function get_correction_state(Normalized_Timeline_Item $item): string;

    /**
     * Register owner-native invalidation/synchronization signals when the
     * provider exposes them. This must not transfer canonical ownership.
     */
    public function register_sync_events(): void;

    /** @return array<string,mixed> */
    public function get_health_status(): array;
}
