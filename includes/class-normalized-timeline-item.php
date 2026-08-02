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
    private const ALLOWED_NATIVE_STATUSES = [
        'publish',
        'published',
        'approved',
        'public',
        'active',
        'verified',
        'corrected',
        'retracted',
    ];

    private const ALLOWED_REVIEW_STATES = [
        'published',
        'approved',
        'reviewed',
        'not-required',
        'corrected',
        'retracted',
    ];

    /** @var array<string,mixed> */
    private array $data;

    /** @param array<string,mixed> $data */
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

        $author_id = (int) $data['author_id'];
        $profile_id = (int) $data['public_profile_id'];
        if ($author_id <= 0 || $profile_id <= 0) {
            throw new InvalidArgumentException('Timeline author and profile identifiers must be positive integers.');
        }
        if ((string) $data['visibility_state'] !== 'public') {
            throw new InvalidArgumentException('Only public items may enter the public timeline projection.');
        }

        $provider_id = $this->clean_key((string) $data['provider_id'], 64);
        $provider_version = $this->limit(trim((string) $data['provider_version']), 64);
        $object_type = $this->clean_key((string) $data['native_object_type'], 64);
        $object_id = $this->limit(trim((string) $data['native_object_id']), 191);
        $content_type = $this->clean_key((string) $data['content_type'], 64);
        $native_status = $this->clean_key((string) $data['native_status'], 64);
        $review_state = $this->clean_key((string) ($data['review_state'] ?? 'published'), 64);
        $title = $this->plain_text((string) $data['title'], 300);

        if ($provider_id === '' || $provider_version === '' || $object_type === '' || $object_id === '' || $content_type === '' || $title === '') {
            throw new InvalidArgumentException('Timeline identity, type, version, and title fields must be non-empty.');
        }
        if (! in_array($native_status, self::ALLOWED_NATIVE_STATUSES, true)) {
            throw new InvalidArgumentException('Timeline native status is not publicly eligible.');
        }
        if (! in_array($review_state, self::ALLOWED_REVIEW_STATES, true)) {
            throw new InvalidArgumentException('Timeline review state is not publicly eligible.');
        }

        $canonical_url = $this->validated_url((string) $data['canonical_url']);
        $published_at = $this->required_date((string) $data['published_at']);

        $this->data = [
            'provider_id' => $provider_id,
            'provider_version' => $provider_version,
            'native_object_type' => $object_type,
            'native_object_id' => $object_id,
            'author_id' => $author_id,
            'public_profile_id' => $profile_id,
            'title' => $title,
            'safe_excerpt' => $this->plain_text((string) $data['safe_excerpt'], 1200),
            'canonical_url' => $canonical_url,
            'published_at' => $published_at,
            'updated_at' => $this->normalize_optional_date($data['updated_at'] ?? null),
            'visibility_state' => 'public',
            'native_status' => $native_status,
            'content_type' => $content_type,
            'topic' => $this->plain_text((string) ($data['topic'] ?? ''), 300),
            'language' => $this->language((string) ($data['language'] ?? 'en-US')),
            'thumbnail_reference' => $this->media_reference($data['thumbnail_reference'] ?? null),
            'media_type' => $this->clean_key((string) ($data['media_type'] ?? 'none'), 64) ?: 'none',
            'verification_state' => $this->clean_key((string) ($data['verification_state'] ?? 'unverified'), 64) ?: 'unverified',
            'review_state' => $review_state,
            'correction_state' => $this->clean_key((string) ($data['correction_state'] ?? 'none'), 64) ?: 'none',
            'safety_flags' => $this->safe_key_list((array) ($data['safety_flags'] ?? [])),
            'available_actions' => $this->safe_key_list((array) ($data['available_actions'] ?? [])),
            'metrics_reference' => $this->scalar_reference($data['metrics_reference'] ?? null),
            'pin_weight' => max(0, min(1000, (int) ($data['pin_weight'] ?? 0))),
        ];
    }

    public function get(string $field): mixed
    {
        return $this->data[$field] ?? null;
    }

    /** @return array<string,mixed> */
    public function to_array(): array
    {
        return $this->data;
    }

    /**
     * Public clients do not need internal WordPress/provider identifiers.
     *
     * @return array<string,mixed>
     */
    public function to_public_array(): array
    {
        $public = array_diff_key($this->data, array_flip([
            'provider_id',
            'provider_version',
            'native_object_type',
            'native_object_id',
            'author_id',
            'public_profile_id',
            'thumbnail_reference',
            'metrics_reference',
        ]));

        // Native attachment IDs and ungoverned remote image references never
        // cross the public REST boundary. A provider may expose only a strict
        // same-site thumbnail URL under the public `thumbnail_url` field.
        $thumbnail = $this->data['thumbnail_reference'] ?? null;
        if (is_string($thumbnail)) {
            $thumbnail_url = Public_URL::sanitize_same_site($thumbnail, false);
            if ($thumbnail_url !== '') {
                $public['thumbnail_url'] = $thumbnail_url;
            }
        }

        return $public;
    }

    /** @return array<string,mixed> */
    public function jsonSerialize(): array
    {
        return $this->to_public_array();
    }

    private function clean_key(string $value, int $limit): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9_\-]/', '-', $value) ?? '';

        return $this->limit(trim($value, '-'), $limit);
    }

    /** @param array<mixed> $values @return list<string> */
    private function safe_key_list(array $values): array
    {
        $clean = [];
        foreach (array_slice($values, 0, 20) as $value) {
            if (! is_string($value)) {
                continue;
            }
            $key = $this->clean_key($value, 64);
            if ($key !== '') {
                $clean[] = $key;
            }
        }

        return array_values(array_unique($clean));
    }

    private function scalar_reference(mixed $value): int|string|null
    {
        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }
        if (is_string($value)) {
            $value = trim($value);

            return $value === '' ? null : $this->limit($value, 191);
        }

        return null;
    }

    private function media_reference(mixed $value): int|string|null
    {
        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }
        if (ctype_digit($value)) {
            $id = (int) $value;

            return $id > 0 ? $id : null;
        }

        try {
            return $this->validated_url($value);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    private function plain_text(string $value, int $length): string
    {
        $value = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', ' ', $value) ?? $value;
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return $this->limit(trim($value), $length);
    }

    private function language(string $value): string
    {
        $value = preg_replace('/[^A-Za-z0-9\-]/', '', trim($value)) ?? '';

        return $this->limit($value !== '' ? $value : 'en-US', 35);
    }

    private function validated_url(string $url): string
    {
        $url = trim($url);
        $parts = parse_url($url);
        $scheme = is_array($parts) ? strtolower((string) ($parts['scheme'] ?? '')) : '';
        $host = is_array($parts) ? (string) ($parts['host'] ?? '') : '';
        $has_credentials = is_array($parts) && (isset($parts['user']) || isset($parts['pass']));
        $has_fragment = is_array($parts) && isset($parts['fragment']);

        if (
            strlen($url) > 2048
            || filter_var($url, FILTER_VALIDATE_URL) === false
            || ! in_array($scheme, ['http', 'https'], true)
            || $host === ''
            || $has_credentials
            || $has_fragment
        ) {
            throw new InvalidArgumentException('Timeline URL must be a credential-free HTTP(S) URL without a fragment.');
        }

        return $url;
    }

    private function required_date(string $value): string
    {
        $normalized = $this->strict_absolute_date($value);
        if ($normalized === null) {
            throw new InvalidArgumentException('Timeline publication date must be an absolute, valid UTC or offset timestamp.');
        }

        return $normalized;
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

        return is_scalar($value) ? $this->strict_absolute_date((string) $value) : null;
    }

    private function strict_absolute_date(string $value): ?string
    {
        $value = trim($value);
        if ($value === '' || strlen($value) > 64) {
            return null;
        }

        $utc = new \DateTimeZone('UTC');
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value) === 1) {
            $date = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value, $utc);
            $errors = DateTimeImmutable::getLastErrors();
            if (! $date instanceof DateTimeImmutable
                || (is_array($errors) && ((int) ($errors['warning_count'] ?? 0) > 0 || (int) ($errors['error_count'] ?? 0) > 0))
                || $date->format('Y-m-d H:i:s') !== $value
            ) {
                return null;
            }

            return $date->format('Y-m-d\TH:i:s\Z');
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?(?:Z|[+\-]\d{2}:\d{2})$/', $value) !== 1) {
            return null;
        }

        try {
            $date = new DateTimeImmutable($value);
            $errors = DateTimeImmutable::getLastErrors();
            if (is_array($errors) && ((int) ($errors['warning_count'] ?? 0) > 0 || (int) ($errors['error_count'] ?? 0) > 0)) {
                return null;
            }

            return $date->setTimezone($utc)->format('Y-m-d\TH:i:s\Z');
        } catch (\Throwable) {
            return null;
        }
    }
}
