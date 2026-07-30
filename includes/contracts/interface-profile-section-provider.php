<?php

declare(strict_types=1);

namespace Sabri\PublicExperience\Contracts;

if (! defined('ABSPATH') && PHP_SAPI !== 'cli') {
    exit;
}

/**
 * Read-only provider contract for optional public profile sections.
 *
 * Native modules remain authoritative. Providers return bounded public card
 * descriptors only; File 25 validates and renders them through its canonical
 * content-card system.
 */
interface Profile_Section_Provider
{
    public function get_id(): string;

    public function get_version(): string;

    public function get_section(): string;

    public function get_maturity_level(): string;

    public function is_available(): bool;

    /** @param array<string,mixed> $profile */
    public function supports_profile(array $profile): bool;

    /**
     * @param array<string,mixed> $profile
     * @param array<string,mixed> $context
     * @return list<array<string,mixed>>
     */
    public function query(int $user_id, array $profile, array $context = []): array;

    /** File 25 providers are read projections and never own native content. */
    public function owns_native_content(): bool;
}
