<?php

declare(strict_types=1);

namespace {
    if (! defined('ABSPATH')) { define('ABSPATH', __DIR__ . '/fixtures/'); }
    if (! defined('SMP_VERSION')) { define('SMP_VERSION', '1.1.0'); }
    if (! defined('ARRAY_A')) { define('ARRAY_A', 'ARRAY_A'); }

    final class SMP_DB
    {
        public static function table(string $name): string
        {
            return 'wp_smp_' . $name;
        }
    }

    final class SMP_Activator
    {
        public static function marketplace_url(): string
        {
            return 'https://example.test/marketplace/';
        }
    }

    final class SMP_Utils
    {
        public static function decode_json(mixed $value, array $fallback = []): array
        {
            if (is_array($value)) { return $value; }
            $decoded = json_decode((string) $value, true);
            return is_array($decoded) ? $decoded : $fallback;
        }
    }

    final class Fake_Marketplace_WPDB
    {
        /** @var list<array<string,mixed>> */
        public array $rows = [];
        /** @var list<mixed> */
        public array $prepared_args = [];

        public function prepare(string $sql, mixed ...$args): string
        {
            $this->prepared_args = $args;
            return $sql;
        }

        public function get_results(string $sql, string $output): array
        {
            return $this->rows;
        }
    }

    $wpdb = new Fake_Marketplace_WPDB();
    $wpdb->rows = [
        [
            'id' => 101,
            'seller_user_id' => 7,
            'seller_status' => 'approved',
            'status' => 'published',
            'deal_status' => 'available',
            'title' => 'Homeopathy Book Set',
            'slug' => 'homeopathy-book-set',
            'category' => 'Books & Education',
            'product_type' => 'physical',
            'condition_name' => 'new',
            'short_description' => 'A public approved listing.',
            'price' => '5000.00',
            'sale_price' => '4500.00',
            'currency' => 'PKR',
            'images' => json_encode([['id' => 999, 'url' => 'https://example.test/uploads/product-101.jpg']]),
            'published_at' => '2026-07-31 01:00:00',
            'created_at' => '2026-07-30 01:00:00',
            'moderation_note' => 'private note',
            'views' => 900,
        ],
        [
            'id' => 102,
            'seller_user_id' => 7,
            'seller_status' => 'approved',
            'status' => 'approved',
            'deal_status' => 'sold',
            'title' => 'Clinic Desk',
            'slug' => 'clinic-desk',
            'category' => 'Home & Living',
            'product_type' => 'physical',
            'condition_name' => 'used',
            'short_description' => 'Previously available public listing.',
            'price' => '22000.00',
            'sale_price' => '0',
            'currency' => 'PKR',
            'images' => '[]',
            'published_at' => '',
            'created_at' => '2026-07-29 01:00:00',
        ],
        ['id' => 103, 'seller_user_id' => 8, 'seller_status' => 'approved', 'status' => 'published', 'deal_status' => 'available', 'title' => 'Wrong author'],
        ['id' => 104, 'seller_user_id' => 7, 'seller_status' => 'pending', 'status' => 'published', 'deal_status' => 'available', 'title' => 'Pending seller'],
        ['id' => 105, 'seller_user_id' => 7, 'seller_status' => 'approved', 'status' => 'draft', 'deal_status' => 'available', 'title' => 'Draft listing'],
        ['id' => 106, 'seller_user_id' => 7, 'seller_status' => 'approved', 'status' => 'published', 'deal_status' => 'blocked', 'title' => 'Blocked listing'],
    ];
    $GLOBALS['wpdb'] = $wpdb;

    function sanitize_key(string $value): string
    {
        return trim((string) preg_replace('/[^a-z0-9_\-]/', '-', strtolower($value)), '-');
    }
    function __(string $value, string $domain = ''): string { return $value; }

    require_once dirname(__DIR__) . '/includes/contracts/interface-profile-section-provider.php';
    require_once dirname(__DIR__) . '/includes/providers/class-file-18-marketplace-provider.php';

    use Sabri\PublicExperience\Providers\File_18_Marketplace_Provider;

    $failures = [];
    $check = static function (bool $condition, string $message) use (&$failures): void {
        if (! $condition) { $failures[] = $message; }
    };

    $provider = new File_18_Marketplace_Provider();
    $check($provider->is_available(), 'File 18 provider must accept the reviewed 1.1.x Marketplace contract.');
    $check($provider->get_id() === 'file-18-marketplace', 'File 18 provider ID must be canonical.');
    $check($provider->get_section() === 'marketplace', 'File 18 provider must register only for Marketplace.');
    $check($provider->get_maturity_level() === 'read-only', 'File 18 provider must remain read-only before staging acceptance.');
    $check($provider->owns_native_content() === false, 'File 18 must retain native listing ownership.');
    $check($provider->supports_profile(['class' => 'member']), 'Approved public members may expose their approved Marketplace listings.');

    $items = $provider->query(7, ['class' => 'member'], ['limit' => 24]);
    $check(count($items) === 2, 'Only approved-seller, public-status, allowed-deal listings by the profile owner may be projected.');
    $check(($items[0]['type'] ?? '') === 'marketplace-item', 'Marketplace projection must use the canonical card type.');
    $check(($items[0]['url'] ?? '') === 'https://example.test/marketplace/', 'Cards must link honestly to the native Marketplace application URL.');
    $check(($items[0]['meta'][2] ?? '') === 'PKR 4,500.00', 'Public sale price must be projected without transaction ownership.');
    $check(preg_match('/^[a-f0-9]{64}$/', (string) ($items[0]['projection_key'] ?? '')) === 1, 'Marketplace listings require an opaque server-only projection key.');
    $check(($items[0]['projection_key'] ?? '') !== ($items[1]['projection_key'] ?? ''), 'Distinct native listings require distinct projection keys.');

    foreach ($items as $item) {
        foreach (['id', 'seller_id', 'seller_user_id', 'moderation_note', 'views', 'rating', 'review_count'] as $private_key) {
            $check(! array_key_exists($private_key, $item), 'Marketplace cards must not expose native/private field: ' . $private_key);
        }
    }
    $check(($wpdb->prepared_args[0] ?? null) === 7 && ($wpdb->prepared_args[1] ?? null) === 24, 'Marketplace query must bind profile owner and bounded limit.');

    if ($failures !== []) {
        fwrite(STDERR, "FAILED\n- " . implode("\n- ", $failures) . "\n");
        exit(1);
    }

    echo "PASS: File 18 read-only Marketplace profile adapter\n";
}
