<?php
declare(strict_types=1);
if (! defined('ABSPATH')) { define('ABSPATH', __DIR__ . '/fixtures/'); }
if (! defined('SABRI_PUBLIC_EXPERIENCE_VERSION')) {
    define('SABRI_PUBLIC_EXPERIENCE_VERSION', '0.15.0');
}
class WP_Post {
    public int $ID = 731;
    public int $post_author = 42;
    public string $post_password = '';
    public string $post_status = 'publish';
    public string $post_type = 'post';
}
class WP_Query {
    /** @var list<WP_Post> */
    public array $posts;
    public function __construct(array $args) {
        $this->posts = [new WP_Post()];
    }
}
$GLOBALS['f25_approval_value'] = true;
function apply_filters(string $name, mixed $fallback, mixed ...$args): mixed {
    if ($name === 'sabri_public_experience/wordpress_post_is_public_approved') {
        return $GLOBALS['f25_approval_value'];
    }
    return $fallback;
}
function post_type_exists(string $post_type): bool { return $post_type === 'post'; }
function get_post_type_object(string $post_type): ?object {
    return $post_type === 'post' ? (object) ['public' => true] : null;
}
function get_permalink(WP_Post $post): string { return 'https://example.test/post-731/'; }
function get_the_title(WP_Post $post): string { return 'Approved public contribution'; }
function get_the_excerpt(WP_Post $post): string { return 'Public excerpt only.'; }
function wp_strip_all_tags(string $text): string { return strip_tags($text); }
function wp_trim_words(string $text, int $count): string { return $text; }
function get_post_time(string $format, bool $gmt, WP_Post $post): string { return '2026-10-15T12:00:00Z'; }
function get_post_modified_time(string $format, bool $gmt, WP_Post $post): string { return '2026-10-15T13:00:00Z'; }
function get_locale(): string { return 'ur_PK'; }
function has_post_thumbnail(WP_Post $post): bool { return false; }
function do_action(string $hook, mixed ...$args): void {}

final class F25_Test_Public_Item {
    public function __construct(public array $source) {}
}

require_once dirname(__DIR__) . '/includes/contracts/interface-timeline-provider.php';

// The constructor is called by the actual WordPress_Posts_Provider only on
// explicitly public-approved posts. No private data or actual DB is involved.
class_alias(F25_Test_Public_Item::class, 'Sabri\PublicExperience\Normalized_Timeline_Item');

require_once dirname(__DIR__) . '/includes/providers/class-wordpress-posts-provider.php';
$provider = new \Sabri\PublicExperience\Providers\WordPress_Posts_Provider();
$errors = [];
$check = static function (bool $result, string $reason) use (&$errors): void {
    if (! $result) { $errors[] = $reason; }
};
foreach (['false', 'no', 'yes', 1, 0, null, false, []] as $value) {
    $GLOBALS['f25_approval_value'] = $value;
    $items = $provider->get_public_author_items(42, ['candidate_limit'=>10]);
    $check($items === [],
        'Only strict true may authorize a native WordPress public post; got '.get_debug_type($value));
}
$GLOBALS['f25_approval_value'] = true;
$items = $provider->get_public_author_items(42, ['candidate_limit'=>10]);
$check(count($items) === 1 && $items[0] instanceof F25_Test_Public_Item
    && ($items[0]->source['author_id'] ?? 0) === 42,
    'Explicit boolean true must preserve published, same-author public post projection.');
if ($errors !== []) {
    fwrite(STDERR, "FAILED Reviews 995–1034 WordPress owner/public approval gate\n- "
        . implode("\n- ",$errors)."\n");
    exit(1);
}
echo "PASS: Reviews 995–1034 native WordPress public post filter strict boolean approval\n";
