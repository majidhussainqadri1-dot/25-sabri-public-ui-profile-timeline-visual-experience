<?php

declare(strict_types=1);

namespace {
    if (! defined('ABSPATH')) { define('ABSPATH', __DIR__ . '/fixtures/'); }
    if (! defined('SMP_VERSION')) { define('SMP_VERSION', '1.2.0-RC1'); }

    final class WP_REST_Request
    {
        /** @var array<string,mixed> */
        public array $params = [];
        public function __construct(public string $method = 'GET', public string $route = '') {}
        public function set_param(string $key, mixed $value): void { $this->params[$key] = $value; }
        public function get_param(string $key): mixed { return $this->params[$key] ?? null; }
    }

    final class WP_REST_Response
    {
        /** @param array<string,mixed> $data */
        public function __construct(private array $data) {}
        /** @return array<string,mixed> */
        public function get_data(): array { return $this->data; }
    }

    final class SMP_Utils
    {
        public static function current_seller(?int $user_id = null): ?array
        {
            return $user_id === 7
                ? ['id' => 5, 'userId' => 7, 'status' => 'approved', 'storeName' => 'Sabri Books']
                : null;
        }
    }

    final class SMP_Activator
    {
        public static function marketplace_url(): string { return 'https://example.test/marketplace/'; }
    }

    final class SMP_REST
    {
        public static ?WP_REST_Request $last_request = null;
        public static function products(WP_REST_Request $request): WP_REST_Response
        {
            self::$last_request = $request;
            return new WP_REST_Response([
                'products' => [
                    [
                        'id' => 101, 'sellerId' => 5, 'title' => 'Homeopathy Book Set',
                        'slug' => 'homeopathy-book-set', 'category' => 'Books & Education',
                        'productType' => 'physical', 'condition' => 'new',
                        'shortDescription' => 'A public approved listing.',
                        'effectivePrice' => 4500, 'currency' => 'PKR',
                        'image' => 'https://example.test/uploads/product-101.jpg',
                        'dealStatus' => 'available', 'status' => 'published',
                        'updatedAt' => '2026-08-01 10:00:00', 'views' => 900,
                    ],
                    [
                        'id' => 102, 'sellerId' => 5, 'title' => 'Clinic Desk',
                        'slug' => 'clinic-desk', 'category' => 'Home & Living',
                        'productType' => 'physical', 'condition' => 'used',
                        'shortDescription' => 'Previously available public listing.',
                        'effectivePrice' => 22000, 'currency' => 'PKR', 'images' => [],
                        'dealStatus' => 'sold', 'status' => 'approved',
                        'createdAt' => '2026-07-29 01:00:00',
                    ],
                    ['id' => 103, 'sellerId' => 6, 'title' => 'Wrong seller', 'dealStatus' => 'available', 'status' => 'published'],
                    ['id' => 104, 'sellerId' => 5, 'title' => 'Draft listing', 'dealStatus' => 'available', 'status' => 'draft'],
                    ['id' => 105, 'sellerId' => 5, 'title' => 'Blocked listing', 'dealStatus' => 'blocked', 'status' => 'published'],
                ],
                'page' => 1,
                'limit' => 50,
            ]);
        }
    }

    function sanitize_key(string $value): string
    {
        return trim((string) preg_replace('/[^a-z0-9_\-]/', '-', strtolower($value)), '-');
    }
    function __(string $value, string $domain = ''): string { return $value; }
    function esc_url_raw(string $value, array $protocols = []): string { return $value; }

    require_once dirname(__DIR__) . '/includes/contracts/interface-profile-section-provider.php';
    require_once dirname(__DIR__) . '/includes/providers/class-file-18-marketplace-provider.php';

    use Sabri\PublicExperience\Providers\File_18_Marketplace_Provider;

    $failures = [];
    $check = static function (bool $condition, string $message) use (&$failures): void {
        if (! $condition) { $failures[] = $message; }
    };

    $provider = new File_18_Marketplace_Provider();
    $check($provider->is_available(), 'File 18 provider must accept the reviewed 1.2.0-RC1 owner API.');
    $check($provider->get_id() === 'file-18-marketplace', 'File 18 provider ID must be canonical.');
    $check($provider->get_maturity_level() === 'read-only', 'File 18 provider must remain read-only before staging acceptance.');
    $check($provider->owns_native_content() === false, 'File 18 must retain native listing ownership.');

    $items = $provider->query(7, ['class' => 'member'], ['limit' => 24]);
    $check(count($items) === 2, 'Only owner-returned public listings for the profile seller may be projected.');
    $check(($items[0]['type'] ?? '') === 'marketplace-item', 'Marketplace projection must use the canonical card type.');
    $check(($items[0]['url'] ?? '') === 'https://example.test/marketplace/', 'Cards must link honestly to the native Marketplace URL.');
    $check(($items[0]['meta'][2] ?? '') === 'PKR 4,500.00', 'Public effective price must be projected without transaction ownership.');
    $check(preg_match('/^[a-f0-9]{64}$/', (string) ($items[0]['projection_key'] ?? '')) === 1, 'Marketplace listings require an opaque server-only projection key.');
    $check(($items[0]['projection_key'] ?? '') !== ($items[1]['projection_key'] ?? ''), 'Distinct listings require distinct projection keys.');
    $check((SMP_REST::$last_request?->params['limit'] ?? null) === 50, 'Transitional owner API fetch must be explicitly bounded.');

    foreach ($items as $item) {
        foreach (['id', 'sellerId', 'seller_id', 'views', 'rating', 'reviewCount', 'moderation_note'] as $private_key) {
            $check(! array_key_exists($private_key, $item), 'Marketplace cards must not expose native/private field: ' . $private_key);
        }
    }

    $source = file_get_contents(dirname(__DIR__) . '/includes/providers/class-file-18-marketplace-provider.php') ?: '';
    $check(! str_contains($source, '$wpdb'), 'File 25 Marketplace adapter must not access WordPress database directly.');
    $check(! str_contains($source, 'SMP_DB::table'), 'File 25 Marketplace adapter must not resolve File 18 tables.');
    $check(str_contains($source, 'SMP_REST::products'), 'Transitional File 18 integration must call the owner public DTO API.');

    if ($failures !== []) {
        fwrite(STDERR, "FAILED\n- " . implode("\n- ", $failures) . "\n");
        exit(1);
    }

    echo "PASS: File 18 owner-executed public DTO profile adapter\n";
}
