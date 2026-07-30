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
    private const MAX_ITEMS_PER_PROVIDER = 24;
    private const MAX_ITEMS_PER_SECTION = 48;

    /** @var array<string,array<string,mixed>> */
    private array $cache = [];

    public function __construct(private Section_Registry $registry)
    {
    }

    /** @param array<string,mixed> $profile @return array<string,string> */
    public function available_for_profile(int $user_id, array $profile): array
    {
        $available = [];
        foreach (Section_Registry::approved_sections() as $section) {
            $data = $this->get_section($user_id, $profile, $section);
            if (($data['items'] ?? []) === []) {
                continue;
            }
            $available[$section] = self::label($section);
        }

        return $available;
    }

    /**
     * @param array<string,mixed> $profile
     * @return array{section:string,label:string,items:list<string>,provider_error_count:int,truncated:bool,is_provider_section:bool}
     */
    public function get_section(int $user_id, array $profile, string $section): array
    {
        $section = self::key($section);
        $empty = [
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

        $cache_key = $user_id . ':' . $section . ':' . self::key((string) ($profile['class'] ?? 'member'));
        if (isset($this->cache[$cache_key])) {
            /** @var array{section:string,label:string,items:list<string>,provider_error_count:int,truncated:bool,is_provider_section:bool} */
            return $this->cache[$cache_key];
        }

        $items = [];
        $seen = [];
        $errors = 0;
        $truncated = false;

        foreach ($this->registry->for_section($section) as $provider) {
            if (! $this->provider_is_usable($provider, $profile, $section)) {
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

                $key = self::candidate_key($candidate);
                if ($key === '' || isset($seen[$key])) {
                    continue;
                }

                $rendered = Content_Cards::render($candidate);
                if ($rendered === '') {
                    continue;
                }

                $seen[$key] = true;
                $items[] = $rendered;
                if (count($items) >= self::MAX_ITEMS_PER_SECTION) {
                    $truncated = true;
                    break 2;
                }
            }
        }

        return $this->cache[$cache_key] = [
            'section' => $section,
            'label' => self::label($section),
            'items' => $items,
            'provider_error_count' => $errors,
            'truncated' => $truncated,
            'is_provider_section' => true,
        ];
    }

    /** @param array<string,mixed> $profile */
    private function provider_is_usable(Profile_Section_Provider $provider, array $profile, string $section): bool
    {
        try {
            if (! $this->registry->provider_is_consistent($provider, $section)) {
                return false;
            }

            $maturity = self::key($provider->get_maturity_level());
            if (! Section_Registry::maturity_is_approved($maturity) || $maturity === 'disabled') {
                return false;
            }
            if ($provider->owns_native_content()) {
                return false;
            }

            return $provider->is_available() && $provider->supports_profile($profile);
        } catch (\Throwable $exception) {
            self::emit_error($exception, $section);
            return false;
        }
    }

    /** @param array<string,mixed> $candidate */
    private static function candidate_key(array $candidate): string
    {
        $title = self::text($candidate['title'] ?? '', 240);
        if ($title === '') {
            return '';
        }

        $url = Public_URL::sanitize_same_site($candidate['url'] ?? '', false);
        $type = self::key((string) ($candidate['type'] ?? 'article'));
        $date = self::text($candidate['published_at'] ?? '', 80);

        // URL paths may be case-sensitive. Preserve the exact sanitized URL so
        // distinct canonical resources are not collapsed accidentally.
        return hash('sha256', $url . '|' . $type . '|' . $title . '|' . $date);
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
