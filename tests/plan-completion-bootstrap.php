<?php

declare(strict_types=1);

namespace Sabri\PublicExperience;

if (! function_exists(__NAMESPACE__ . '\\wp_strip_all_tags')) {
    function wp_strip_all_tags(string $text, bool $remove_breaks = false): string
    {
        $text = preg_replace('@<(script|style)[^>]*?>.*?</\\1>@si', '', $text) ?? '';
        $text = strip_tags($text);
        return $remove_breaks ? (preg_replace('/[\r\n\t ]+/', ' ', $text) ?? '') : $text;
    }
}

require_once dirname(__DIR__) . '/includes/class-plan-completion.php';
