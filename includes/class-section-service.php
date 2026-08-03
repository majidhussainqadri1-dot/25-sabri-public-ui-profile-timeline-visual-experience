<?php

declare(strict_types=1);

namespace Sabri\PublicExperience;

use Sabri\PublicExperience\Contracts\Profile_Section_Provider;

if (! defined('ABSPATH') && PHP_SAPI !== 'cli') {
    exit;
}

/**
 * Safe read-projection service for optional Knowledge, Media, Reviews,
 * Research, and Marketplace profile sections.
 */
final class Section_Service
{
    public const PUBLIC_CONTRACT_VERSION = '1.0.0';

    private const MAX_ITEMS_PER_PROVIDER = 24;
    private const MAX_ITEMS_PER_SECTION = 48;
    private const MAX_CACHE_PROFILE_DEPTH = 5;
    private const MAX_CACHE_PROFILE_ENTRIES = 160;
    private const MAX_CACHE_PROFILE_STRING = 1000;

    /** @var array<string,array<string,mixed>> */
    private array $public_cache = [];

    public function __construct(private Section_Registry $registry)
    {
    }

    /** @param array<string,mixed> $profile @return array<string,string> */
    public function available_for_profile(int $user_id, array $profile): array
    {
        $available = [];
        foreach (Section_Registry::approved_sections() as $section) {
            $data = $this->get_public_section($user_id, $profile, $section);
            if (($data['items'] ?? []) === []) {
                continue;
            }
            $available[$section] = self::label($section);
        }

        return $available;
    }

    /**
     * HTML presentation derived from the same allow-listed public projection
     * used by REST, preventing two divergent sanitization paths.
     *
     * @param array<string,mixed> $profile
     * @return array{section:string,label:string,items:list<string>,provider_error_count:int,truncated:bool,is_provider_section:bool}
     */
    public function get_section(int $user_id, array $profile, string $section): array
    {
        $public = $this->get_public_section($user_id, $profile, $section);
        $rendered = [];
        foreach ($public['items'] as $card) {
            $html = Content_Cards::render($card);
            if ($html !== '') {
                $rendered[] = $html;
            }
        }

        return [
            'section' => $public['section'],
            'label' => $public['label'],
            'items' => $rendered,
            'provider_error_count' => $public['provider_error_count'],
            'truncated' => $public['truncated'],
            'is_provider_section' => $public['is_provider_section'],
        ];
    }

    /**
     * Return structured, allow-listed cards without provider IDs, native IDs,
     * projection keys, exception details, private metadata, or rendered HTML.
     *
     * @param array<string,mixed> $profile
     * @return array{contract_version:string,section:string,label:string,items:list<array<string,mixed>>,provider_error_count:int,truncated:bool,is_provider_section:bool}
     */
    public function get_public_section(int $user_id, array $profile, string $section): array
    {
        $section = self::key($section);
        $empty = [
            'contract_version' => self::PUBLIC_CONTRACT_VERSION,
            'section' => $section,
            'label' => self::label($section),
            'items' => [],
            'provider_error_count' => 0,
            'truncated' => false,
            'is_provider_section' => Section_Registry::section_is_approved($section),
        ];
        if ($user_id <= 0 || ! $empty['is_provider_section']) {
            return $empty;
        }

        $profile_digest = self::profile_cache_digest($profile);
        $cache_key = $profile_digest !== null
            ? $user_id . ':' . $section . ':' . $profile_digest
            : null;
        if ($cache_key !== null && isset($this->public_cache[$cache_key])) {
            /** @var array{contract_version:string,section:string,label:string,items:list<array<string,mixed>>,provider_error_count:int,truncated:bool,is_provider_section:bool} */
            return $this->public_cache[$cache_key];
        }

        $items = [];
        $seen = [];
        $errors = 0;
        $truncated = false;

        foreach ($this->registry->for_section($section) as $provider) {
            $status = $this->provider_status($provider, $profile, $section);
            if ($status === 'error') {
                $errors++;
                continue;
            }
            if ($status !== 'usable') {
                continue;
            }

            try {
                $raw = $provider->query($user_id, $profile, [
                    'section' => $section,
                    'limit' => self::MAX_ITEMS_PER_PROVIDER,
                ]);
            } catch (\Throwable $exception) {
                $errors++;
                self::emit_error($exception, $section);
                continue;
            }

            if (! is_array($raw)) {
                $errors++;
                continue;
            }
            if (count($raw) > self::MAX_ITEMS_PER_PROVIDER) {
                $truncated = true;
            }

            foreach (array_slice($raw, 0, self::MAX_ITEMS_PER_PROVIDER) as $candidate) {
                if (! is_array($candidate)) {
                    continue;
                }

                $card = Content_Cards::normalize_public($candidate);
                if ($card === null) {
                    continue;
                }

                $key = self::candidate_key($candidate, $card);
                if ($key === '' || isset($seen[$key])) {
                    continue;
                }

                $seen[$key] = true;
                $items[] = $card;
                if (count($items) >= self::MAX_ITEMS_PER_SECTION) {
                    $truncated = true;
                    break 2;
                }
            }
        }

        $result = [
            'contract_version' => self::PUBLIC_CONTRACT_VERSION,
            'section' => $section,
            'label' => self::label($section),
            'items' => $items,
            'provider_error_count' => $errors,
            'truncated' => $truncated,
            'is_provider_section' => true,
        ];
        if ($cache_key !== null) {
            $this->public_cache[$cache_key] = $result;
        }

        return $result;
    }

    /**
     * Aggregate public health only. Provider IDs, versions, native object counts,
     * exception messages, and implementation details remain administrator-side.
     *
     * @return array{contract_version:string,healthy:bool,sections:array<string,array{registered:int,consistent:int,enabled:int,error_count:int}>}
     */
    public function public_health(): array
    {
        $sections = [];
        $healthy = true;
        foreach (Section_Registry::approved_sections() as $section) {
            $registered = 0;
            $consistent = 0;
            $enabled = 0;
            $errors = 0;
            foreach ($this->registry->for_section($section) as $provider) {
                $registered++;
                $metadata = $this->registry->validated_metadata($provider, $section);
                if ($metadata === null) {
                    $errors++;
                    $healthy = false;
                    continue;
                }
                $consistent++;
                if (in_array($metadata['maturity'], ['read-only', 'staging-accepted', 'production-accepted'], true)) {
                    $enabled++;
                }
            }
            $sections[$section] = [
                'registered' => $registered,
                'consistent' => $consistent,
                'enabled' => $enabled,
                'error_count' => $errors,
            ];
        }

        return [
            'contract_version' => self::PUBLIC_CONTRACT_VERSION,
            'healthy' => $healthy,
            'sections' => $sections,
        ];
    }

    /** @param array<string,mixed> $profile */
    private function provider_status(Profile_Section_Provider $provider, array $profile, string $section): string
    {
        try {
            $metadata = $this->registry->validated_metadata($provider, $section);
            if ($metadata === null) {
                self::emit_error(new \RuntimeException('Profile-section provider metadata or concrete identity changed after registration.'), $section);
                return 'error';
            }
            if ($metadata['maturity'] === 'disabled'
                || ! in_array($metadata['maturity'], ['read-only', 'staging-accepted', 'production-accepted'], true)
            ) {
                return 'skip';
            }

            return $provider->is_available() && $provider->supports_profile($profile)
                ? 'usable'
                : 'skip';
        } catch (\Throwable $exception) {
            self::emit_error($exception, $section);
            return 'error';
        }
    }

    /** @param array<string,mixed> $candidate @param array<string,mixed> $card */
    private static function candidate_key(array $candidate, array $card): string
    {
        $projection_key = is_scalar($candidate['projection_key'] ?? null)
            ? strtolower(trim((string) $candidate['projection_key']))
            : '';
        if (preg_match('/^[a-f0-9]{64}$/', $projection_key) === 1) {
            return hash('sha256', 'projection|' . $projection_key);
        }

        // Retained Review 26 compatibility marker; candidate URLs are normalized
        // into $card before identity: Public_URL::canonical_same_site_identity($candidate['url'] ?? '', false)
        $url = Public_URL::canonical_same_site_identity($card['url'] ?? '', false);
        if ($url !== '') {
            return hash('sha256', 'url|' . $url);
        }

        // A title/type/date fallback can collapse distinct native cards that
        // legitimately share those three fields. Hash the complete normalized
        // public projection instead; identical display cards still deduplicate,
        // while different excerpts, badges, media, or metadata remain distinct.
        $json = json_encode($card, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return is_string($json) ? hash('sha256', 'card|' . $json) : '';
    }

    /** @param array<string,mixed> $profile */
    private static function profile_cache_digest(array $profile): ?string
    {
        $entries = 0;
        $cacheable = true;
        $normalized = self::normalize_cache_value($profile, 0, $entries, $cacheable);
        if (! $cacheable) {
            return null;
        }

        $json = json_encode($normalized, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return is_string($json) ? hash('sha256', $json) : null;
    }

    private static function normalize_cache_value(
        mixed $value,
        int $depth,
        int &$entries,
        bool &$cacheable
    ): mixed {
        if ($depth > self::MAX_CACHE_PROFILE_DEPTH || $entries >= self::MAX_CACHE_PROFILE_ENTRIES) {
            $cacheable = false;
            return null;
        }
        $entries++;

        if (is_array($value)) {
            $normalized = [];
            $associative = array_keys($value) !== range(0, count($value) - 1);
            foreach ($value as $key => $item) {
                if ($entries >= self::MAX_CACHE_PROFILE_ENTRIES) {
                    $cacheable = false;
                    break;
                }
                $clean_key = is_int($key) ? (string) $key : self::text((string) $key, 120);
                if ($clean_key === '' || array_key_exists($clean_key, $normalized)) {
                    $cacheable = false;
                    break;
                }
                $normalized[$clean_key] = self::normalize_cache_value(
                    $item,
                    $depth + 1,
                    $entries,
                    $cacheable
                );
                if (! $cacheable) {
                    break;
                }
            }
            if ($associative) {
                ksort($normalized, SORT_STRING);
            }

            return $normalized;
        }
        if (is_bool($value) || is_int($value) || $value === null) {
            return $value;
        }
        if (is_float($value)) {
            if (! is_finite($value)) {
                $cacheable = false;
                return null;
            }
            return $value;
        }
        if (is_scalar($value)) {
            $text = self::text((string) $value, self::MAX_CACHE_PROFILE_STRING);
            if (strlen((string) $value) > self::MAX_CACHE_PROFILE_STRING) {
                $cacheable = false;
            }
            return $text;
        }

        $cacheable = false;
        return null;
    }

    private static function emit_error(\Throwable $exception, string $section): void
    {
        if (function_exists('do_action')) {
            do_action('sabri_public_experience/section_provider_error', $exception, self::key($section));
        }
    }

    private static function label(string $section): string
    {
        $section = self::key($section);
        if (! function_exists('__')) {
            return match ($section) {
                'knowledge' => 'Knowledge',
                'media' => 'Media',
                'reviews' => 'Reviews',
                'research' => 'Research',
                'marketplace' => 'Marketplace',
                default => 'Public Content',
            };
        }

        return match ($section) {
            'knowledge' => __('Knowledge', 'sabri-public-experience'),
            'media' => __('Media', 'sabri-public-experience'),
            'reviews' => __('Reviews', 'sabri-public-experience'),
            'research' => __('Research', 'sabri-public-experience'),
            'marketplace' => __('Marketplace', 'sabri-public-experience'),
            default => __('Public Content', 'sabri-public-experience'),
        };
    }

    private static function text(mixed $value, int $limit): string
    {
        if (! is_scalar($value)) {
            return '';
        }
        $text = html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        $text = trim($text);

        return function_exists('mb_substr') ? mb_substr($text, 0, $limit) : substr($text, 0, $limit);
    }

    private static function key(string $value): string
    {
        if (function_exists('sanitize_key')) {
            return sanitize_key($value);
        }
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9_-]/', '-', $value) ?? '';

        return trim($value, '-');
    }
}
