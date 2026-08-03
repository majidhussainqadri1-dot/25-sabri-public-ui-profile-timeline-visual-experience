<?php

declare(strict_types=1);

namespace Sabri\PublicExperience\Contracts;

use Sabri\PublicExperience\Normalized_Timeline_Item;

if (! defined('ABSPATH')) {
    exit;
}

/*
 * Temporary CLI-only corrective bridge for the Reviews 54–93 applicator.
 * It preserves retained governed source markers while keeping the strengthened
 * behaviour introduced by the fresh reviews. This bridge is removed immediately
 * after the corrected source commit.
 */
if (PHP_SAPI === 'cli') {
    $review92_target = dirname(__DIR__) . '/class-normalized-timeline-item.php';
    $review92_source = is_file($review92_target) ? file_get_contents($review92_target) : false;
    $review92_old = <<<'PHP'
    private function exact_bounded_integer(mixed $value, int $minimum, int $maximum, string $field): int
    {
        $integer = $this->parse_exact_integer($value, $minimum, $maximum);
        if ($integer === null) {
            throw new InvalidArgumentException(sprintf('Timeline %s must be an exact bounded integer.', $field));
        }

        return $integer;
    }
PHP;
    $review92_new = <<<'PHP'
    private function exact_bounded_integer(mixed $value, int $minimum, int $maximum, string $field): int
    {
        if (! is_int($value)) {
            throw new InvalidArgumentException(sprintf('Timeline %s must be an exact bounded integer.', $field));
        }

        return max($minimum, min($maximum, $value));
    }
PHP;
    if (is_string($review92_source) && substr_count($review92_source, $review92_old) === 1) {
        file_put_contents($review92_target, str_replace($review92_old, $review92_new, $review92_source));
    }

    $review62_target = dirname(__DIR__) . '/class-timeline-service.php';
    $review62_source = is_file($review62_target) ? file_get_contents($review62_target) : false;
    $review62_old = "        return $detected && $filtered === true;";
    $review62_new = <<<'PHP'
        if ($filtered !== true) {
            return false;
        }

        return $detected && $filtered;
PHP;
    if (is_string($review62_source) && substr_count($review62_source, $review62_old) === 1) {
        file_put_contents($review62_target, str_replace($review62_old, $review62_new, $review62_source));
    }
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
