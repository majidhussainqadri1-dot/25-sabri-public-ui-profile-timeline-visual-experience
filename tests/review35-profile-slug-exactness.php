<?php

declare(strict_types=1);

namespace {
    define('ABSPATH', __DIR__ . '/');

    final class WP_User
    {
        public function __construct(public int $ID, public string $user_nicename, public string $display_name = '') {}
    }

    $external_profile = null;
    $fallback_profile = null;

    function sanitize_title(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9_-]+/', '-', $value) ?? '';
        return trim($value, '-');
    }
    function apply_filters(string $hook, mixed $value, mixed ...$args): mixed
    {
        global $external_profile;
        return $hook === 'sabri_public_experience/profile_by_slug' ? $external_profile : $value;
    }
    function get_user_by(string $field, string|int $value): WP_User|false
    {
        global $fallback_profile;
        return $field === 'slug' && $fallback_profile instanceof WP_User ? $fallback_profile : false;
    }

    require_once dirname(__DIR__) . '/includes/class-profile-repository.php';

    $reflection = new \ReflectionClass(\Sabri\PublicExperience\Profile_Repository::class);
    $repository = $reflection->newInstanceWithoutConstructor();

    $external_profile = new WP_User(7, 'different-doctor');
    if ($repository->find_by_slug('requested-doctor') !== null) {
        fwrite(STDERR, "Review 35 failed: filter aliased a different canonical profile slug.\n");
        exit(1);
    }

    $external_profile = new WP_User(7, 'requested-doctor');
    if ($repository->find_by_slug('requested-doctor') !== $external_profile) {
        fwrite(STDERR, "Review 35 failed: exact external canonical slug was not accepted.\n");
        exit(1);
    }

    $external_profile = new WP_User(7, 'Requested-Doctor');
    if ($repository->find_by_slug('requested-doctor') !== null) {
        fwrite(STDERR, "Review 35 failed: transformed external nicename was accepted as an exact slug.\n");
        exit(1);
    }

    $external_profile = null;
    $fallback_profile = new WP_User(8, 'requested-doctor');
    if ($repository->find_by_slug('requested-doctor') !== $fallback_profile) {
        fwrite(STDERR, "Review 35 failed: exact WordPress canonical slug was not accepted.\n");
        exit(1);
    }

    foreach (['Requested-Doctor', 'requested doctor', '12345', '', ' requested-doctor '] as $ambiguous) {
        if ($repository->find_by_slug($ambiguous) !== null) {
            fwrite(STDERR, "Review 35 failed: non-canonical slug was accepted: {$ambiguous}\n");
            exit(1);
        }
    }

    $fallback_profile = new WP_User(9, 'Requested-Doctor');
    if ($repository->find_by_slug('requested-doctor') !== null) {
        fwrite(STDERR, "Review 35 failed: transformed WordPress nicename was accepted as an exact slug.\n");
        exit(1);
    }

    $fallback_profile = new WP_User(9, 'another-doctor');
    if ($repository->find_by_slug('requested-doctor') !== null) {
        fwrite(STDERR, "Review 35 failed: fallback user slug mismatch was accepted.\n");
        exit(1);
    }

    echo "Review 35 exact canonical profile slug checks passed.\n";
}
