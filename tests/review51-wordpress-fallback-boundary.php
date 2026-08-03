<?php

declare(strict_types=1);

namespace {
    define('ABSPATH', __DIR__ . '/fixtures/');
    define('SABRI_PUBLIC_EXPERIENCE_VERSION', '0.14.0');
    final class WP_Post
    {
        public function __construct(
            public int $ID,
            public int $post_author,
            public string $post_type,
            public string $post_status,
            public string $post_password = ''
        ) {}
    }
    $r51_posts = [
        new WP_Post(1, 7, 'post', 'publish'),
        new WP_Post(2, 7, 'book', 'publish'),
        new WP_Post(3, 7, 'post', 'draft'),
        new WP_Post(4, 8, 'post', 'publish'),
    ];
    final class WP_Query
    {
        public array $posts;
        public function __construct(array $args) { global $r51_posts; $this->posts = $r51_posts; }
    }
    function apply_filters(string $hook, mixed $value, mixed ...$args): mixed {
        if ($hook === 'sabri_public_experience/wordpress_post_types') { return ['post', 'book']; }
        return $value;
    }
    function do_action(string $hook, mixed ...$args): void {}
    function post_type_exists(string $type): bool { return in_array($type, ['post', 'book'], true); }
    function get_post_type_object(string $type): object { return (object) ['public' => true]; }
    function get_permalink(WP_Post $post): string { return 'https://example.test/' . $post->post_type . '/' . $post->ID . '/'; }
    function get_the_title(WP_Post $post): string { return 'Post ' . $post->ID; }
    function get_the_excerpt(WP_Post $post): string { return 'Excerpt'; }
    function wp_strip_all_tags(string $value): string { return strip_tags($value); }
    function wp_trim_words(string $value, int $words): string { return $value; }
    function get_post_time(string $format, bool $gmt, WP_Post $post): string { return '2026-08-03T12:00:00+00:00'; }
    function get_post_modified_time(string $format, bool $gmt, WP_Post $post): string { return '2026-08-03T12:00:00+00:00'; }
    function get_locale(): string { return 'en_US'; }
    function has_post_thumbnail(WP_Post $post): bool { return false; }
    function get_the_post_thumbnail_url(WP_Post $post, string $size): string|false { return false; }
    require_once dirname(__DIR__) . '/includes/contracts/interface-timeline-provider.php';
    require_once dirname(__DIR__) . '/includes/class-public-url.php';
    require_once dirname(__DIR__) . '/includes/class-normalized-timeline-item.php';
    require_once dirname(__DIR__) . '/includes/providers/class-wordpress-posts-provider.php';
    $provider = new \Sabri\PublicExperience\Providers\WordPress_Posts_Provider();
    $items = $provider->get_public_author_items(7, ['candidate_limit' => 20]);
    if (count($items) !== 1 || $items[0]->get('native_object_id') !== '1' || $items[0]->get('language') !== 'en-US') {
        fwrite(STDERR, "FAILED: WordPress fallback accepted foreign, draft, wrong-author, or noncanonical locale data.\n"); exit(1);
    }
    echo "PASS: Review 51 WordPress fallback ownership and status boundary\n";
}
