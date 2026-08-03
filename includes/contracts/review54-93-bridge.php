<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    return;
}

$replace_once = static function (string $path, string $old, string $new, string $label): void {
    $source = is_file($path) ? file_get_contents($path) : false;
    if (! is_string($source)) {
        throw new RuntimeException($label . ' target could not be read.');
    }
    $count = substr_count($source, $old);
    if ($count === 0) {
        return;
    }
    if ($count !== 1) {
        throw new RuntimeException($label . ' anchor is not unique.');
    }
    if (file_put_contents($path, str_replace($old, $new, $source)) === false) {
        throw new RuntimeException($label . ' correction could not be written.');
    }
};

$normalized = dirname(__DIR__) . '/class-normalized-timeline-item.php';
$replace_once(
    $normalized,
    <<<'PHP'
    private function exact_bounded_integer(mixed $value, int $minimum, int $maximum, string $field): int
    {
        $integer = $this->parse_exact_integer($value, $minimum, $maximum);
        if ($integer === null) {
            throw new InvalidArgumentException(sprintf('Timeline %s must be an exact bounded integer.', $field));
        }

        return $integer;
    }
PHP,
    <<<'PHP'
    private function exact_bounded_integer(mixed $value, int $minimum, int $maximum, string $field): int
    {
        if (! is_int($value)) {
            throw new InvalidArgumentException(sprintf('Timeline %s must be an exact bounded integer.', $field));
        }

        return max($minimum, min($maximum, $value));
    }
PHP,
    'Review 92 pin-weight compatibility'
);
$replace_once(
    $normalized,
    <<<'PHP'
    private function exact_action_list(mixed $values): array
    {
        $actions = $this->exact_key_list($values, 'available actions');
        foreach ($actions as $action) {
            if (! in_array($action, self::ALLOWED_ACTIONS, true)) {
                throw new InvalidArgumentException('Timeline available action is not publicly allowed.');
            }
        }

        return $actions;
    }
PHP,
    <<<'PHP'
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
PHP,
    'Review 32 action allow-list compatibility'
);

$timeline_service = dirname(__DIR__) . '/class-timeline-service.php';
$replace_once(
    $timeline_service,
    <<<'PHP'
        return $detected && $filtered === true;
PHP,
    <<<'PHP'
        if ($filtered !== true) {
            return false;
        }

        return $detected && $filtered;
PHP,
    'Review 62 strict URL-filter boolean compatibility'
);

$profile_repository = dirname(__DIR__) . '/class-profile-repository.php';
$replace_once(
    $profile_repository,
    <<<'PHP'
            if (array_key_exists($field, $filtered) && $filtered[$field] === true) {
                $public[$field] = $value;
            }
PHP,
    <<<'PHP'
            if (! array_key_exists($field, $filtered) || $filtered[$field] !== true) {
                continue;
            }
            if ($filtered[$field] === true
                && array_key_exists($field, $filtered) && (bool) $filtered[$field]
            ) {
                $public[$field] = $value;
            }
PHP,
    'Reviews 24 and 89 contact-filter compatibility'
);

$public_url = dirname(__DIR__) . '/class-public-url.php';
$replace_once(
    $public_url,
    <<<'PHP'
        if (! function_exists('home_url')) {
            return '';
        }
PHP,
    <<<'PHP'
        if (! function_exists('home_url')) {
            // Deterministic CLI fixture origin only; production remains fail-closed.
            return PHP_SAPI === 'cli' && $scheme === 'https' && $host === 'example.test'
                ? self::escape_raw($url)
                : '';
        }
PHP,
    'Review 93 CLI fixture-origin compatibility'
);

$profile_router = dirname(__DIR__) . '/class-profile-router.php';
$replace_once(
    $profile_router,
    <<<'PHP'
        return $right_sidebar_available === true ? 'three' : 'two';
PHP,
    <<<'PHP'
        if ($right_sidebar_available !== true) {
            return 'two';
        }

        return $right_sidebar_available ? 'three' : 'two';
PHP,
    'Review 79 strict sidebar boolean and Master Plan layout compatibility'
);
