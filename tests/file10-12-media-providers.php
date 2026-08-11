<?php

declare(strict_types=1);

namespace {
    if (! defined('ABSPATH')) { define('ABSPATH', __DIR__ . '/fixtures/'); }
    if (! defined('SVW_VERSION')) { define('SVW_VERSION', '0.1.0'); }
    if (! defined('SRL_VERSION')) { define('SRL_VERSION', '0.1.0'); }
    if (! defined('SPL_VERSION')) { define('SPL_VERSION', '0.1.0'); }

    $GLOBALS['media_posts'] = [
        (object) ['ID' => 10, 'post_author' => 7, 'post_status' => 'publish', 'post_password' => '', 'post_title' => 'Long educational video', 'post_excerpt' => 'Video excerpt', 'post_date_gmt' => '2026-07-30 10:00:00'],
        (object) ['ID' => 11, 'post_author' => 7, 'post_status' => 'publish', 'post_password' => '', 'post_title' => 'Valid Reel', 'post_excerpt' => 'Reel excerpt', 'post_date_gmt' => '2026-07-30 11:00:00'],
        (object) ['ID' => 12, 'post_author' => 7, 'post_status' => 'publish', 'post_password' => '', 'post_title' => 'Invalid short Reel', 'post_excerpt' => 'Too short', 'post_date_gmt' => '2026-07-30 12:00:00'],
        (object) ['ID' => 20, 'post_author' => 7, 'post_status' => 'publish', 'post_password' => '', 'post_title' => 'Public PDF', 'post_excerpt' => 'PDF excerpt', 'post_date_gmt' => '2026-07-30 13:00:00'],
        (object) ['ID' => 21, 'post_author' => 8, 'post_status' => 'publish', 'post_password' => '', 'post_title' => 'Wrong author PDF', 'post_excerpt' => '', 'post_date_gmt' => '2026-07-30 14:00:00'],
        (object) ['ID' => 22, 'post_author' => 7, 'post_status' => 'private', 'post_password' => '', 'post_title' => 'Private PDF', 'post_excerpt' => '', 'post_date_gmt' => '2026-07-30 15:00:00'],
    ];
    $GLOBALS['media_types'] = [10 => 'svw_video', 11 => 'svw_video', 12 => 'svw_video', 20 => 'spl_document', 21 => 'spl_document', 22 => 'spl_document'];
    $GLOBALS['svw_meta'] = [
        10 => ['is_reel' => '0', 'duration' => '3600', 'language' => 'English'],
        11 => ['is_reel' => '1', 'duration' => '90', 'language' => 'English'],
        12 => ['is_reel' => '1', 'duration' => '30', 'language' => 'English'],
    ];
    $GLOBALS['spl_meta'] = [
        20 => ['pages' => '120', 'language' => 'English'],
        21 => ['pages' => '80', 'language' => 'English'],
        22 => ['pages' => '40', 'language' => 'English'],
    ];

    final class SVW_Helpers
    {
        public const TYPE = 'svw_video';
        public const TAX = 'svw_category';
        public static function meta(int $id, string $key, mixed $default = ''): mixed { return $GLOBALS['svw_meta'][$id][$key] ?? $default; }
        public static function category(int $id): string { return $id === 10 ? 'Education' : 'Clinical Education'; }
        public static function duration(int $seconds): string { return sprintf('%02d:%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60), $seconds % 60); }
    }

    final class SPL_Helpers
    {
        public const TYPE = 'spl_document';
        public const DOCTYPE = 'spl_document_type';
        public static function meta(int $id, string $key, mixed $default = ''): mixed { return $GLOBALS['spl_meta'][$id][$key] ?? $default; }
        public static function term(int $id, string $taxonomy): string { return 'Book'; }
    }

    function absint(mixed $value): int { return abs((int) $value); }
    function sanitize_key(string $value): string { return trim((string) preg_replace('/[^a-z0-9_\-]/', '-', strtolower($value)), '-'); }
    function __(string $value, string $domain = ''): string { return $value; }
    function post_type_exists(string $type): bool { return in_array($type, ['svw_video', 'spl_document'], true); }
    function get_posts(array $args): array { return $GLOBALS['media_posts']; }
    function get_post_type(int $id): string { return $GLOBALS['media_types'][$id] ?? ''; }
    function get_the_title(int $id): string { foreach ($GLOBALS['media_posts'] as $post) { if ($post->ID === $id) { return $post->post_title; } } return ''; }
    function get_permalink(int $id): string { return 'https://example.test/content/' . $id . '/'; }
    function get_the_excerpt(int $id): string { foreach ($GLOBALS['media_posts'] as $post) { if ($post->ID === $id) { return $post->post_excerpt; } } return ''; }
    function get_post_time(string $format, bool $gmt, int $id): string { return '2026-07-30T12:00:00+00:00'; }
    function get_the_post_thumbnail_url(int $id, string $size): string { return 'https://example.test/uploads/' . $id . '.jpg'; }

    require_once dirname(__DIR__) . '/includes/contracts/interface-profile-section-provider.php';
    require_once dirname(__DIR__) . '/includes/providers/class-file-10-video-media-provider.php';
    require_once dirname(__DIR__) . '/includes/providers/class-file-11-reels-media-provider.php';
    require_once dirname(__DIR__) . '/includes/providers/class-file-12-pdf-media-provider.php';

    use Sabri\PublicExperience\Providers\File_10_Video_Media_Provider;
    use Sabri\PublicExperience\Providers\File_11_Reels_Media_Provider;
    use Sabri\PublicExperience\Providers\File_12_Pdf_Media_Provider;

    $failures = [];
    $check = static function (bool $condition, string $message) use (&$failures): void { if (! $condition) { $failures[] = $message; } };
    $profile = ['class' => 'doctor'];

    $video = new File_10_Video_Media_Provider();
    $check($video->is_available(), 'File 10 adapter must accept the reviewed 0.1.x contract.');
    $check($video->get_id() === 'file-10-video-media' && $video->get_section() === 'media', 'File 10 provider identity and section are canonical.');
    $videos = $video->query(7, $profile, ['limit' => 24]);
    $check(count($videos) === 1 && ($videos[0]['type'] ?? '') === 'video', 'File 10 adapter must expose only non-Reel public videos by the profile author.');

    $reels = new File_11_Reels_Media_Provider();
    $check($reels->is_available(), 'File 11 adapter must require reviewed File 10 and File 11 contracts.');
    $reel_items = $reels->query(7, $profile, ['limit' => 24]);
    $check(count($reel_items) === 1 && ($reel_items[0]['title'] ?? '') === 'Valid Reel', 'File 11 adapter must enforce the 60–600 second Reel duration contract.');

    $pdf = new File_12_Pdf_Media_Provider();
    $check($pdf->is_available(), 'File 12 adapter must accept the reviewed 0.1.x contract.');
    $documents = $pdf->query(7, $profile, ['limit' => 24]);
    $check(count($documents) === 1 && ($documents[0]['type'] ?? '') === 'pdf', 'File 12 adapter must expose only the author’s published public PDFs.');
    $check(($documents[0]['meta'][0] ?? '') === '120 pages', 'File 12 card must expose bounded public page metadata without native IDs.');

    foreach ([$video, $reels, $pdf] as $provider) {
        $check($provider->get_maturity_level() === 'read-only', 'Media adapters must remain read-only before staging acceptance.');
        $check($provider->owns_native_content() === false, 'Media adapters may not own native module content.');
    }

    if ($failures !== []) {
        fwrite(STDERR, "FAILED\n- " . implode("\n- ", $failures) . "\n");
        exit(1);
    }

    echo "PASS: File 10, File 11, and File 12 read-only media adapters\n";
}
