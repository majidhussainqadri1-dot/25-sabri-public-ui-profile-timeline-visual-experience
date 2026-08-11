<?php

declare(strict_types=1);

namespace {
    define('ABSPATH', __DIR__ . '/fixtures/');
    $r41_query = [];
    function get_query_var(string $key): mixed { global $r41_query; return $r41_query[$key] ?? ''; }
    function sanitize_key(string $value): string { return trim((string) preg_replace('/[^a-z0-9_\-]/', '-', strtolower(trim($value))), '-'); }
    function apply_filters(string $hook, mixed $value, mixed ...$args): mixed { return $value; }
    function add_action(...$args): void {}
    function add_filter(...$args): void {}
    function add_rewrite_rule(...$args): void {}
    function flush_rewrite_rules(bool $hard = true): void {}
    require_once dirname(__DIR__) . '/includes/contracts/interface-profile-section-provider.php';
    require_once dirname(__DIR__) . '/includes/class-section-registry.php';
    require_once dirname(__DIR__) . '/includes/class-profile-router.php';

    $router = new \Sabri\PublicExperience\Profile_Router();
    $r41_query = ['spux_profile_type' => 'doctor', 'spux_profile_slug' => 'Doctor-Alias', 'spux_profile_section' => 'overview'];
    $context = $router->context();
    if (($context['slug'] ?? '') !== 'Doctor-Alias') { fwrite(STDERR, "FAILED: Router altered raw slug.\n"); exit(1); }
    $r41_query['spux_profile_slug'] = ' doctor-alias';
    if ($router->is_profile_request()) { fwrite(STDERR, "FAILED: Whitespace alias reached profile resolution.\n"); exit(1); }
    echo "PASS: Review 41 raw routed profile slug preservation\n";
}
