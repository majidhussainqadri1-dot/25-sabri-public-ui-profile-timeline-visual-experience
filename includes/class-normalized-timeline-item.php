<?php

declare(strict_types=1);

namespace Sabri\PublicExperience;

use DateTimeImmutable;
use InvalidArgumentException;
use JsonSerializable;

if (! defined('ABSPATH') && PHP_SAPI !== 'cli') {
    exit;
}

/**
 * Immutable, presentation-safe pointer to a native public object.
 *
 * It intentionally excludes full bodies, comments, private metadata, patient
 * data, credentials, raw analytics, and media binaries.
 */
final class Normalized_Timeline_Item implements JsonSerializable
{
    private const ALLOWED_VISIBILITY = ['public'];

    /** @var array<string, mixed> */
    private array $data;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        $required = [
            'provider_id',
            'provider_version',
            'native_object_type',
            'native_object_id',
            'author_id',
            'public_profile_id',
            'title',
            'safe_excerpt',
            'canonical_url',
            'published_at',
            'visibility_state',
            'native_status',
            'content_type',
        ];

        foreach ($required as $field) {
            if (! array_key_exists($field, $data)) {
                throw new InvalidArgumentException(sprintf('Missing timeline field: %s', $field));
            }
        }

        if ((int) $data['author_id'] <= 0 || (int) $data['public_profile_id'] <= 0) {
            throw new InvalidArgumentException('Timeline author and profile identifiers must be positive integers.');
        }

        if (! in_array((string) $data['visibility_state'], self::ALLOWED_VISIBILITY, true)) {
            throw new InvalidArgumentException('Only public items may enter the public timeline projection.');
        }

        $canonical_url = trim((string) $data['canonical_url']);
        $scheme = strtolower((string) parse_url($canonical_url, PHP_URL_SCHEME));
        if (strlen($canonical_url) > 2048 || filter_var($canonical_url, FILTER_VALIDATE_URL) === false || ! in_array($scheme, ['http', 'https'], true)) {
            throw new InvalidArgumentException('Timeline canonical URL must be an HTTP or HTTPS URL.');
        }

        try {
            new DateTimeImmutable((string) $data['published_at']);
        } catch (\Throwable $exception) {
            throw new InvalidArgumentException('Timeline publication date is invalid.', 0, $exception);
        }

        $provider_id = $this->clean_key((string) $data['provider_id']);
        $object_type = $this->clean_key((string) $data['native_object_type']);
        $content_type = $this->clean_key((string) $data['content_type']);
        if ($provider_id === '' || $object_type === '' || $content_type === '') {
            throw new InvalidArgumentException('Provider, native object type, and content type must be non-empty safe keys.');
        }

        $this->data = [
            'provider_id'        => $provider_id,
            'provider_version'   => $this->limit(trim((string) $data['provider_version']), 64),
            'native_object_type' => $object_type,
            'native_object_id'   => $this->limit(trim((string) $data['native_object_id']), 191),
            'author_id'          => (int) $data['author_id'],
            'public_profile_id'  => (int) $data['public_profile_id'],
            'title'              => $this->limit(trim(strip_tags((string) $data['title'])), 300),
            'safe_excerpt'       => $this->limit(trim(strip_tags((string) $data['safe_excerpt'])), 1200),
            'canonical_url'      => $canonical_url,
            'published_at'       => (new DateTimeImmutable((string) $data['published_at']))->format(DATE_ATOM),
            'updated_at'         => $this->normalize_optional_date($data['updated_at'] ?? null),
            'visibility_state'   => 'public',
            'native_status'      => $this->clean_key((string) $data['native_status']),
            'content_type'       => $content_type,
            'topic'              => $this->limit(trim(strip_tags((string) ($data['topic'] ?? ''))), 300),
            'language'           => $this->limit(trim((string) ($data['language'] ?? 'en-US')), 35),
            'thumbnail_reference'=> $this->scalar_reference($data['thumbnail_reference'] ?? null),
            'media_type'         => $this->clean_key((string) ($data['media_type'] ?? 'none')),
            'verification_state' => $this->clean_key((string) ($data['verification_state'] ?? 'unverified')),
            'review_state'       => $this->clean_key((string) ($data['review_state'] ?? 'published')),
            'correction_state'   => $this->clean_key((string) ($data['correction_state'] ?? 'none')),
            'safety_flags'       => $this->safe_key_list((array) ($data['safety_flags'] ?? [])),
            'available_actions'  => $this->safe_key_list((array) ($data['available_actions'] ?? [])),
            'metrics_reference'  => $this->scalar_reference($data['metrics_reference'] ?? null),
            'pin_weight'         => (int) ($data['pin_weight'] ?? 0),
        ];
    }

    public function get(string $field): mixed
    {
        return $this->data[$field] ?? null;
    }

    /** @return array<string, mixed> */
    public function to_array(): array
    {
        return $this->data;
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return $this->to_array();
    }

    private function clean_key(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9_\-]/', '-', $value) ?? '';

        return trim($value, '-');
    }

    /** @param array<mixed> $values @return list<string> */
    private function safe_key_list(array $values): array
    {
        $clean = [];
        foreach ($values as $value) {
            if (! is_string($value)) {
                continue;
            }
            $key = $this->clean_key($value);
            if ($key !== '') {
                $clean[] = $key;
            }
        }

        return array_values(array_unique($clean));
    }

    private function scalar_reference(mixed $value): int|string|null
    {
        if (is_int($value) || is_string($value)) {
            return $value;
        }

        return null;
    }

    private function limit(string $value, int $length): string
    {
        return function_exists('mb_substr') ? mb_substr($value, 0, $length) : substr($value, 0, $length);
    }

    private function normalize_optional_date(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return (new DateTimeImmutable((string) $value))->format(DATE_ATOM);
        } catch (\Throwable) {
            return null;
        }
    }
}
