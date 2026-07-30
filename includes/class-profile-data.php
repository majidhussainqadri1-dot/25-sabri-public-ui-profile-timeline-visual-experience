<?php

declare(strict_types=1);

namespace Sabri\PublicExperience;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Pure, bounded normalization for public profile presentation data.
 *
 * Native modules remain authoritative. This class deliberately accepts only
 * the small allow-list needed by File 25 and never exposes identity evidence,
 * registration numbers, patient data, internal notes, or raw database rows.
 */
final class Profile_Data
{
    /** @return array<string,mixed> */
    public static function founder_details(array $source): array
    {
        $details = [];
        foreach ([
            'mission' => 2000,
            'vision' => 2000,
            'objectives' => 4000,
            'methodology' => 4000,
            'experience' => 3000,
            'research' => 3000,
            'location' => 240,
        ] as $field => $limit) {
            $value = self::text($source[$field] ?? '', $limit);
            if ($value !== '') {
                $details[$field] = $value;
            }
        }

        $publications = self::lines($source['publications'] ?? '', 50, 260);
        if ($publications !== []) {
            $details['publications'] = $publications;
        }

        return $details;
    }

    /** @return array<string,mixed> */
    public static function professional_details(array $source): array
    {
        $details = [];
        foreach ([
            'qualification' => 300,
            'institution' => 300,
            'council' => 300,
            'specialization' => 300,
            'fee' => 100,
            'currency' => 20,
        ] as $field => $limit) {
            $value = self::text($source[$field] ?? '', $limit);
            if ($value !== '') {
                $details[$field] = $value;
            }
        }

        $experience = isset($source['experience_years']) ? (int) $source['experience_years'] : 0;
        if ($experience > 0 && $experience <= 80) {
            $details['experience_years'] = $experience;
        }

        foreach ([
            'languages' => 20,
            'consultation_mode' => 12,
            'books_studied' => 50,
        ] as $field => $maximum) {
            $values = self::lines($source[$field] ?? '', $maximum, 180);
            if ($values !== []) {
                $details[$field] = $values;
            }
        }

        return $details;
    }

    /** @return array<string,string> */
    public static function clinic(array $source): array
    {
        $clinic = [];
        foreach ([
            'name' => 240,
            'address' => 500,
            'country' => 120,
            'city' => 120,
            'hours' => 1200,
            'timezone' => 100,
        ] as $field => $limit) {
            $value = self::text($source[$field] ?? '', $limit);
            if ($value !== '') {
                $clinic[$field] = $value;
            }
        }

        return $clinic;
    }

    /** @return list<string> */
    public static function lines(mixed $value, int $maximum = 50, int $limit = 260): array
    {
        if (is_array($value)) {
            $raw = $value;
        } elseif (is_scalar($value)) {
            $raw = preg_split('/[\r\n,;]+/u', (string) $value) ?: [];
        } else {
            $raw = [];
        }

        $result = [];
        foreach ($raw as $line) {
            if (! is_scalar($line)) {
                continue;
            }
            $clean = self::text((string) $line, $limit);
            if ($clean === '' || in_array($clean, $result, true)) {
                continue;
            }
            $result[] = $clean;
            if (count($result) >= max(1, min(100, $maximum))) {
                break;
            }
        }

        return $result;
    }

    private static function text(mixed $value, int $limit): string
    {
        if (! is_scalar($value)) {
            return '';
        }

        $text = (string) $value;
        $text = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', ' ', $text) ?? $text;
        $text = function_exists('wp_strip_all_tags')
            ? wp_strip_all_tags($text, true)
            : strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        $text = trim($text);
        $limit = max(1, min(12000, $limit));

        return function_exists('mb_substr')
            ? mb_substr($text, 0, $limit)
            : substr($text, 0, $limit);
    }
}
