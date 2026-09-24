<?php

declare(strict_types=1);

namespace Sabri\PublicExperience;

use DateTimeImmutable;
use InvalidArgumentException;
use JsonSerializable;

if (! defined('ABSPATH') && PHP_SAPI !== 'cli') {
    exit;
}

if (! class_exists(Public_URL::class)) {
    require_once __DIR__ . '/class-public-url.php';
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


    private const ALLOWED_ACTIONS = [
        'view',
        'read',
        'watch',
        'download',
        'share',
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

        $author_id = $this->exact_positive_integer($data['author_id']);
        $profile_id = $this->exact_positive_integer($data['public_profile_id']);
        if ($author_id === null || $profile_id === null) {
            throw new InvalidArgumentException('Timeline author and profile identifiers must be positive integers.');
        }
        if ((string) $data['visibility_state'] !== 'public') {
            throw new InvalidArgumentException('Only public items may enter the public timeline projection.');
        }

        $provider_id = $this->exact_key((string) $data['provider_id'], 64, 'provider ID');
        $provider_version = $this->exact_scalar((string) $data['provider_version'], 64, 'provider version');
        $object_type = $this->exact_key((string) $data['native_object_type'], 64, 'native object type');
        $object_id = $this->exact_scalar((string) $data['native_object_id'], 191, 'native object ID');
        $content_type = $this->exact_key((string) $data['content_type'], 64, 'content type');
        $native_status = $this->exact_key((string) $data['native_status'], 64, 'native status');
        $review_state = $this->exact_key((string) ($data['review_state'] ?? 'published'), 64, 'review state');
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

        $canonical_url = $this->validated_same_site_url($data['canonical_url']);
        $published_at = $this->required_date((string) $data['published_at']);
        $updated_at = $this->normalize_optional_date($data['updated_at'] ?? null);
        if (array_key_exists('updated_at', $data)
            && $data['updated_at'] !== null
            && $data['updated_at'] !== ''
            && $updated_at === null
        ) {
            throw new InvalidArgumentException('Timeline update date must be an absolute, valid UTC or offset timestamp.');
        }
        if ($updated_at !== null && strcmp($updated_at, $published_at) < 0) {
            throw new InvalidArgumentException('Timeline update date cannot precede its publication date.');
        }

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
            'updated_at' => $updated_at,
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
            'safety_flags' => $this->exact_key_list($data['safety_flags'] ?? [], 'safety flags'),
            'available_actions' => $this->exact_action_list($data['available_actions'] ?? []),
            'metrics_reference' => $this->scalar_reference($data['metrics_reference'] ?? null),
            'pin_weight' => $this->exact_bounded_integer($data['pin_weight'] ?? 0, 0, 1000, 'pin weight'),
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


    private function exact_key(string $value, int $limit, string $field): string
    {
        $canonical = $this->clean_key($value, $limit);
        if ($value === '' || strlen($value) > $limit || ! hash_equals($canonical, $value)) {
            throw new InvalidArgumentException(sprintf('Timeline %s must be an exact canonical lowercase safe key.', $field));
        }

        return $value;
    }

    private function exact_scalar(string $value, int $limit, string $field): string
    {
        if ($value === ''
            || strlen($value) > $limit
            || trim($value) !== $value
            || preg_match('/[\x00-\x1F\x7F]/', $value) === 1
        ) {
            throw new InvalidArgumentException(sprintf('Timeline %s must be an exact bounded scalar.', $field));
        }

        return $value;
    }

    private function clean_key(string $value, int $limit): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9_\-]/', '-', $value) ?? '';

        return $this->limit(trim($value, '-'), $limit);
    }

    /** @return list<string> */
    private function exact_key_list(mixed $values, string $field): array
    {
        if (! is_array($values) || ($values !== [] && array_keys($values) !== range(0, count($values) - 1)) || count($values) > 20) {
            throw new InvalidArgumentException(sprintf('Timeline %s must be a bounded list.', $field));
        }
        $clean = [];
        foreach ($values as $value) {
            if (! is_string($value)) {
                throw new InvalidArgumentException(sprintf('Timeline %s must contain exact string keys.', $field));
            }
            $key = $this->exact_key($value, 64, $field);
            if (isset($clean[$key])) {
                continue;
            }
            $clean[$key] = $key;
        }

        return array_values($clean);
    }

    /** @return list<string> */
    private function exact_action_list(mixed $values): array
    {
        if (! is_array($values)
            || ($values !== [] && array_keys($values) !== range(0, count($values) - 1))
            || count($values) > 20
        ) {
            throw new InvalidArgumentException('Timeline available actions must be a bounded list.');
        }

        $actions = [];
        foreach ($values as $value) {
            if (! is_string($value)) {
                continue;
            }
            $canonical = $this->clean_key($value, 64);
            if ($value === ''
                || strlen($value) > 64
                || ! hash_equals($canonical, $value)
                || ! in_array($value, self::ALLOWED_ACTIONS, true)
            ) {
                continue;
            }
            $actions[$value] = $value;
        }

        return array_values($actions);
    }

    private function exact_positive_integer(mixed $value): ?int
    {
        return $this->parse_exact_integer($value, 1, PHP_INT_MAX);
    }

    private function exact_bounded_integer(mixed $value, int $minimum, int $maximum, string $field): int
    {
        if (! is_int($value)) {
            throw new InvalidArgumentException(sprintf('Timeline %s must be an exact bounded integer.', $field));
        }

        return max($minimum, min($maximum, $value));
    }

    private function parse_exact_integer(mixed $value, int $minimum, int $maximum): ?int
    {
        if (is_int($value)) {
            return $value >= $minimum && $value <= $maximum ? $value : null;
        }
        if (! is_string($value) || preg_match('/^(?:0|[1-9][0-9]*)$/', $value) !== 1 || strlen($value) > 19) {
            return null;
        }
        $integer = (int) $value;

        return (string) $integer === $value && $integer >= $minimum && $integer <= $maximum ? $integer : null;
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
        if (strlen($value) > 35
            || trim($value) !== $value
            || preg_match('/^[A-Za-z]{2,8}(?:-[A-Za-z0-9]{1,8})*$/', $value) !== 1
        ) {
            throw new InvalidArgumentException('Timeline language must be an exact hyphenated BCP 47 language tag.');
        }

        return $value;
    }

    private function validated_same_site_url(mixed $value): string
    {
        if (! is_string($value) || trim($value) !== $value) {
            throw new InvalidArgumentException('Timeline canonical URL must be an exact same-site HTTP(S) URL.');
        }
        $safe = Public_URL::sanitize_same_site($value, false);
        $parts = $safe !== '' ? parse_url($safe) : false;
        if (! is_array($parts)
            || ! isset($parts['scheme'], $parts['host'])
            || ! hash_equals($safe, $value)
        ) {
            throw new InvalidArgumentException('Timeline canonical URL must be an exact same-site HTTP(S) URL.');
        }

        return $safe;
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
