<?php

declare(strict_types=1);

namespace {
    if (! defined('ABSPATH')) {
        define('ABSPATH', __DIR__ . '/fixtures/');
    }

    $GLOBALS['file25_filters'] = [];
    $GLOBALS['file25_query_vars'] = [];

    function sanitize_key(string $value): string
    {
        return trim((string) preg_replace('/[^a-z0-9_\-]/', '-', strtolower($value)), '-');
    }
    function sanitize_title(string $value): string
    {
        return sanitize_key($value);
    }
    function home_url(string $path = ''): string
    {
        return 'https://example.test' . $path;
    }
    function esc_url_raw(string $url, ?array $protocols = null): string
    {
        return $url;
    }
    function apply_filters(string $hook, mixed $value, mixed ...$args): mixed
    {
        return array_key_exists($hook, $GLOBALS['file25_filters'])
            ? $GLOBALS['file25_filters'][$hook]
            : $value;
    }
    function do_action(string $hook, mixed ...$args): void
    {
    }
    function get_query_var(string $name): mixed
    {
        return $GLOBALS['file25_query_vars'][$name] ?? '';
    }
    function add_action(string $hook, mixed $callback, int $priority = 10, int $accepted_args = 1): void
    {
    }
    function add_filter(string $hook, mixed $callback, int $priority = 10, int $accepted_args = 1): void
    {
    }
    function add_rewrite_rule(string $regex, string $query, string $after = 'bottom'): void
    {
    }
    function flush_rewrite_rules(bool $hard = true): void
    {
    }
    function __(string $value, string $domain = ''): string
    {
        return $value;
    }
    function wp_strip_all_tags(string $value, bool $remove_breaks = false): string
    {
        return strip_tags($value);
    }
    function wp_json_encode(mixed $value): string|false
    {
        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    class WP_User
    {
        public int $ID;
        public function __construct(int $id = 1)
        {
            $this->ID = $id;
        }
    }
    class WP_REST_Request implements ArrayAccess
    {
        /** @param array<string,mixed> $params @param array<string,string> $headers */
        public function __construct(private array $params = [], private array $headers = [])
        {
        }
        public function get_header(string $name): string
        {
            return $this->headers[strtolower($name)] ?? '';
        }
        public function offsetExists(mixed $offset): bool { return isset($this->params[$offset]); }
        public function offsetGet(mixed $offset): mixed { return $this->params[$offset] ?? null; }
        public function offsetSet(mixed $offset, mixed $value): void { $this->params[(string) $offset] = $value; }
        public function offsetUnset(mixed $offset): void { unset($this->params[$offset]); }
    }
    class WP_REST_Response
    {
        /** @param array<string,string> $headers */
        public function __construct(public mixed $data = null, public int $status = 200, public array $headers = [])
        {
        }
    }

    require_once dirname(__DIR__) . '/includes/contracts/interface-timeline-provider.php';
    require_once dirname(__DIR__) . '/includes/contracts/interface-profile-section-provider.php';
    require_once dirname(__DIR__) . '/includes/class-public-url.php';
    require_once dirname(__DIR__) . '/includes/class-normalized-timeline-item.php';
    require_once dirname(__DIR__) . '/includes/class-timeline-registry.php';
    require_once dirname(__DIR__) . '/includes/class-timeline-service.php';
    require_once dirname(__DIR__) . '/includes/class-content-cards.php';
    require_once dirname(__DIR__) . '/includes/class-section-registry.php';
    require_once dirname(__DIR__) . '/includes/class-section-service.php';
    require_once dirname(__DIR__) . '/includes/class-profile-router.php';
    require_once dirname(__DIR__) . '/includes/class-rest-controller.php';
}

namespace Sabri\PublicExperience\Tests54_93 {
    use Sabri\PublicExperience\Contracts\Profile_Section_Provider;
    use Sabri\PublicExperience\Contracts\Timeline_Provider;
    use Sabri\PublicExperience\Normalized_Timeline_Item;

    final class TimelineProvider implements Timeline_Provider
    {
        public int $calls = 0;
        /** @param list<mixed> $items */
        public function __construct(
            public string $id = 'provider-one',
            public string $version = '1.0.0',
            public string $maturity = 'read-only',
            public array $items = [],
            public bool $available = true
        ) {
        }
        public function get_provider_id(): string { return $this->id; }
        public function get_provider_version(): string { return $this->version; }
        public function get_maturity_level(): string { return $this->maturity; }
        public function is_available(): bool { return $this->available; }
        public function get_health_status(): array { return ['healthy' => true]; }
        public function get_public_author_items(int $author_id, array $query = []): array
        {
            $this->calls++;
            if ($this->items !== []) {
                return $this->items;
            }
            $limit = (int) ($query['candidate_limit'] ?? 1);
            $items = [];
            for ($i = 1; $i <= $limit; $i++) {
                $items[] = item($this->id, $this->version, $author_id, $i);
            }
            return $items;
        }
    }

    final class SectionProvider implements Profile_Section_Provider
    {
        /** @param array<mixed> $items */
        public function __construct(
            public string $id = 'section-one',
            public string $version = '1.0.0',
            public string $section = 'knowledge',
            public string $maturity = 'read-only',
            public array $items = [],
            public bool $available = true,
            public bool $supported = true,
            public bool $owns = false
        ) {
        }
        public function get_id(): string { return $this->id; }
        public function get_version(): string { return $this->version; }
        public function get_section(): string { return $this->section; }
        public function get_maturity_level(): string { return $this->maturity; }
        public function is_available(): bool { return $this->available; }
        public function supports_profile(array $profile): bool { return $this->supported; }
        public function query(int $user_id, array $profile, array $context = []): array { return $this->items; }
        public function owns_native_content(): bool { return $this->owns; }
    }

    function item(string $provider, string $version, int $author, int $id = 1): Normalized_Timeline_Item
    {
        return new Normalized_Timeline_Item([
            'provider_id' => $provider,
            'provider_version' => $version,
            'native_object_type' => 'post',
            'native_object_id' => (string) $id,
            'author_id' => $author,
            'public_profile_id' => $author,
            'title' => 'Public item ' . $id,
            'safe_excerpt' => 'Public excerpt',
            'canonical_url' => 'https://example.test/item-' . $id . '/',
            'published_at' => '2026-08-03T00:00:00Z',
            'visibility_state' => 'public',
            'native_status' => 'publish',
            'content_type' => 'article',
            'available_actions' => ['view'],
        ]);
    }
}

namespace {
    use Sabri\PublicExperience\Normalized_Timeline_Item;
    use Sabri\PublicExperience\Profile_Router;
    use Sabri\PublicExperience\Public_URL;
    use Sabri\PublicExperience\Rest_Controller;
    use Sabri\PublicExperience\Section_Registry;
    use Sabri\PublicExperience\Section_Service;
    use Sabri\PublicExperience\Timeline_Registry;
    use Sabri\PublicExperience\Timeline_Service;
    use Sabri\PublicExperience\Tests54_93\SectionProvider;
    use Sabri\PublicExperience\Tests54_93\TimelineProvider;

    $review = defined('FILE25_REVIEW') ? (int) FILE25_REVIEW : 0;
    $failures = [];
    $check = static function (bool $condition, string $message) use (&$failures): void {
        if (! $condition) {
            $failures[] = $message;
        }
    };
    $throws = static function (callable $callback): bool {
        try { $callback(); } catch (Throwable) { return true; }
        return false;
    };
    $source_has = static function (string $file, string $needle): bool {
        $source = file_get_contents(dirname(__DIR__) . '/' . $file);
        return is_string($source) && str_contains($source, $needle);
    };
    $card = static fn (string $title, string $url, ?string $projection = null): array => array_filter([
        'type' => 'article', 'title' => $title, 'url' => $url, 'projection_key' => $projection,
    ], static fn ($value): bool => $value !== null);

    switch ($review) {
        case 54:
            $check($throws(fn () => (new Timeline_Registry())->register(new TimelineProvider(' Provider'))), 'Review 54: non-canonical timeline provider ID must be rejected.');
            break;
        case 55:
            $check($throws(fn () => (new Timeline_Registry())->register(new TimelineProvider('provider', ' 1.0.0'))), 'Review 55: padded timeline provider version must be rejected.');
            break;
        case 56:
            $check($throws(fn () => (new Timeline_Registry())->register(new TimelineProvider('provider', '1.0.0', 'Read-Only'))), 'Review 56: non-canonical timeline maturity must be rejected.');
            break;
        case 57:
            $r = new Timeline_Registry(); $p = new TimelineProvider('provider'); $r->register($p);
            $check($r->get('Provider') === null && $r->get('provider') === $p, 'Review 57: timeline lookup must not canonicalize aliases.');
            break;
        case 58:
            $r = new Timeline_Registry(); $p = new TimelineProvider('provider'); $r->register($p); $r->unregister('Provider');
            $check($r->get('provider') === $p, 'Review 58: timeline unregister must not target through an alias.');
            break;
        case 59:
            $r = new Timeline_Registry(); $p = new TimelineProvider('provider'); $r->register($p);
            $check($r->validated_metadata($p, 'Provider') === null, 'Review 59: expected timeline provider identity must be exact.');
            break;
        case 60:
            $r = new Timeline_Registry(); $p = new TimelineProvider(); $r->register($p); $s = new Timeline_Service($r);
            $check($s->get_for_author(7, ['page' => '01'])['items'] === [] && $p->calls === 0, 'Review 60: ambiguous timeline pagination must fail before providers run.');
            break;
        case 61:
            $r = new Timeline_Registry(); $p = new TimelineProvider(); $r->register($p); $s = new Timeline_Service($r);
            $check($s->get_for_author(7, ['content_type' => 'Article '])['items'] === [] && $p->calls === 0, 'Review 61: transformed timeline filters must fail closed.');
            break;
        case 62:
            $r = new Timeline_Registry(); $p = new TimelineProvider(); $p->items = [\Sabri\PublicExperience\Tests54_93\item($p->id, $p->version, 7)]; $r->register($p);
            $GLOBALS['file25_filters']['sabri_public_experience/canonical_url_allowed'] = 'false';
            $result = (new Timeline_Service($r))->get_for_author(7);
            $check($result['items'] === [] && $result['provider_errors'] === ['provider-one'], 'Review 62: non-boolean URL filter result must never grant.');
            break;
        case 63:
            $r = new Timeline_Registry(); $p = new TimelineProvider(); $r->register($p);
            $result = (new Timeline_Service($r))->get_for_author(7, ['page' => 1, 'per_page' => 20]);
            $check($result['truncated'] === true, 'Review 63: an exact hard-cap provider response must be reported as potentially truncated.');
            break;
        case 64:
            $check($throws(fn () => (new Section_Registry())->register(new SectionProvider(' Section'))), 'Review 64: section provider ID must be exact.');
            break;
        case 65:
            $check($throws(fn () => (new Section_Registry())->register(new SectionProvider('section', '1.0.0 '))), 'Review 65: section provider version must be exact.');
            break;
        case 66:
            $check($throws(fn () => (new Section_Registry())->register(new SectionProvider('section', '1.0.0', 'Knowledge'))), 'Review 66: section key must be exact.');
            break;
        case 67:
            $check($throws(fn () => (new Section_Registry())->register(new SectionProvider('section', '1.0.0', 'knowledge', 'Read-Only'))), 'Review 67: section maturity must be exact.');
            break;
        case 68:
            $r = new Section_Registry(); $p = new SectionProvider('section'); $r->register($p);
            $check($r->get('Section') === null && $r->for_section('Knowledge') === [], 'Review 68: section registry lookups must be exact.');
            break;
        case 69:
            $check(! Section_Registry::section_is_approved(' Knowledge') && ! Section_Registry::maturity_is_approved('READ-ONLY'), 'Review 69: section and maturity approval checks must not normalize aliases.');
            break;
        case 70:
            $r = new Section_Registry(); $p = new SectionProvider('section'); $r->register($p); $p->section = 'Knowledge';
            $check($r->validated_metadata($p, 'knowledge') === null, 'Review 70: changed non-canonical provider metadata must fail validation.');
            break;
        case 71:
            $r = new Section_Registry(); $r->register(new SectionProvider('section'));
            $result = (new Section_Service($r))->get_public_section(7, ['class' => 'doctor'], ' Knowledge');
            $check($result['is_provider_section'] === false && $result['items'] === [], 'Review 71: transformed section requests must fail closed.');
            break;
        case 72:
            $r = new Section_Registry(); $r->register(new SectionProvider('section', '1.0.0', 'knowledge', 'read-only', ['x' => $card('X', '/x/')]));
            $result = (new Section_Service($r))->get_public_section(7, ['class' => 'doctor'], 'knowledge');
            $check($result['provider_error_count'] === 1 && $result['items'] === [], 'Review 72: associative provider responses must be rejected.');
            break;
        case 73:
            $r = new Section_Registry(); $r->register(new SectionProvider('section', '1.0.0', 'knowledge', 'read-only', [$card('Good', '/good/'), 'bad']));
            $result = (new Section_Service($r))->get_public_section(7, ['class' => 'doctor'], 'knowledge');
            $check($result['provider_error_count'] === 1 && $result['items'] === [], 'Review 73: malformed late card must invalidate the complete provider batch.');
            break;
        case 74:
            $r = new Section_Registry(); $r->register(new SectionProvider('section', '1.0.0', 'knowledge', 'read-only', [$card('Bad key', '/bad/', strtoupper(str_repeat('a', 64)))]));
            $result = (new Section_Service($r))->get_public_section(7, ['class' => 'doctor'], 'knowledge');
            $check($result['provider_error_count'] === 1 && $result['items'] === [], 'Review 74: projection key must be exact lowercase SHA-256.');
            break;
        case 75:
            $r = new Section_Registry(); $r->register(new SectionProvider('one', '1.0.0', 'knowledge', 'read-only', [$card('One', '/one/')]));
            $s = new Section_Service($r); $first = $s->get_public_section(7, ['class' => 'doctor'], 'knowledge');
            $r->register(new SectionProvider('two', '1.0.0', 'knowledge', 'read-only', [$card('Two', '/two/')]));
            $second = $s->get_public_section(7, ['class' => 'doctor'], 'knowledge');
            $check(count($first['items']) === 1 && count($second['items']) === 2, 'Review 75: section cache identity must bind provider registry metadata.');
            break;
        case 76:
            $r = new Section_Registry(); $r->register(new SectionProvider('section', '1.0.0', 'knowledge', 'read-only', [], false));
            $health = (new Section_Service($r))->public_health();
            $check(($health['sections']['knowledge']['enabled'] ?? -1) === 0, 'Review 76: public health must not count an unavailable provider as enabled.');
            break;
        case 77:
            $GLOBALS['file25_query_vars'] = ['spux_profile_type' => 'Doctor', 'spux_profile_slug' => 'doctor-one'];
            $check((new Profile_Router())->context()['type'] === '', 'Review 77: route type must not be normalized.');
            break;
        case 78:
            $GLOBALS['file25_query_vars'] = ['spux_profile_type' => 'doctor', 'spux_profile_slug' => 'doctor-one', 'spux_profile_section' => 'Timeline '];
            $check((new Profile_Router())->context()['section'] === '', 'Review 78: route section must not be normalized.');
            break;
        case 79:
            $GLOBALS['file25_query_vars'] = ['spux_profile_type' => 'doctor', 'spux_profile_slug' => 'doctor-one'];
            $GLOBALS['file25_filters']['sabri_public_experience/profile_right_sidebar_available'] = 'true';
            $check((new Profile_Router())->shell_layout_mode('one') === 'two', 'Review 79: non-boolean sidebar filter must not grant a third column.');
            break;
        case 80:
            $check(Rest_Controller::sanitize_exact_positive_integer('01') === '' && Rest_Controller::sanitize_exact_positive_integer('2') === 2, 'Review 80: REST pagination must use exact decimal integers.');
            break;
        case 81:
            $method = new ReflectionMethod(Rest_Controller::class, 'exact_optional_key'); $method->setAccessible(true);
            $check($method->invoke(null, 'Article ') === null && $method->invoke(null, 'article') === 'article', 'Review 81: REST content type must already be canonical.');
            break;
        case 82:
            $method = new ReflectionMethod(Rest_Controller::class, 'request_matches_etag'); $method->setAccessible(true);
            $request = new WP_REST_Request([], ['if-none-match' => str_repeat('"x",', 40)]);
            $check($method->invoke(null, $request, '"x"') === false, 'Review 82: oversized/many-token If-None-Match headers must fail closed.');
            break;
        case 83:
            $method = new ReflectionMethod(Rest_Controller::class, 'canonicalize_for_etag'); $method->setAccessible(true);
            $value = 'leaf'; for ($i = 0; $i < 20; $i++) { $value = ['x' => $value]; }
            $entries = 0; $cacheable = true; $args = [$value, 0, &$entries, &$cacheable]; $method->invokeArgs(null, $args);
            $check($cacheable === false, 'Review 83: ETag canonicalization must be bounded by depth.');
            break;
        case 84:
            $check($source_has('includes/class-visibility-policy.php', 'return $authoritative && $filtered === true;'), 'Review 84: profile visibility extension must require exact boolean true.');
            break;
        case 85:
            $source = file_get_contents(dirname(__DIR__) . '/includes/class-visibility-policy.php') ?: '';
            $check(substr_count($source, 'return $authoritative && $filtered === true;') >= 2, 'Review 85: contact visibility extension must require exact boolean true.');
            break;
        case 86:
            $source = file_get_contents(dirname(__DIR__) . '/includes/class-native-integration.php') ?: '';
            $check(substr_count($source, 'strict_boolean_filter(apply_filters(') >= 9, 'Review 86: dependency availability filters must use strict booleans.');
            break;
        case 87:
            $check($source_has('includes/class-native-integration.php', "strict_boolean_filter(apply_filters('sabri_public_experience/is_founder'"), 'Review 87: Founder filter must use strict boolean truth.');
            break;
        case 88:
            $check($source_has('includes/class-native-integration.php', "strict_boolean_filter(apply_filters('sabri_public_experience/is_verified_doctor'"), 'Review 88: Doctor filter must use strict boolean truth.');
            break;
        case 89:
            $check($source_has('includes/class-profile-repository.php', '$filtered[$field] === true'), 'Review 89: contact revocation filter must require exact boolean true.');
            break;
        case 90:
            $source = file_get_contents(dirname(__DIR__) . '/includes/class-profile-repository.php') ?: '';
            $check(str_contains($source, '$raw_section = (string) $section;') && ! str_contains(substr($source, strpos($source, '$raw_section = (string) $section;'), 300), 'sanitize_key'), 'Review 90: available-section filters must not normalize aliases.');
            break;
        case 91:
            $check(Public_URL::sanitize_same_site('#sec%E2%80%8Btion', true) === '' && Public_URL::sanitize_same_site('?next=%255cadmin', true) === '', 'Review 91: query/fragment recursive decoding and Unicode format controls must fail closed.');
            break;
        case 92:
            $base = [
                'provider_id' => 'provider', 'provider_version' => '1.0.0', 'native_object_type' => 'post', 'native_object_id' => '1',
                'author_id' => '01', 'public_profile_id' => 7, 'title' => 'X', 'safe_excerpt' => '',
                'canonical_url' => 'https://example.test/x/', 'published_at' => '2026-08-03T00:00:00Z',
                'visibility_state' => 'public', 'native_status' => 'publish', 'content_type' => 'article',
            ];
            $check($throws(fn () => new Normalized_Timeline_Item($base)), 'Review 92: ambiguous identifiers must be rejected.');
            break;
        case 93:
            $check($throws(fn () => new Normalized_Timeline_Item([
                'provider_id' => 'provider', 'provider_version' => '1.0.0', 'native_object_type' => 'post', 'native_object_id' => '1',
                'author_id' => 7, 'public_profile_id' => 7, 'title' => 'X', 'safe_excerpt' => '',
                'canonical_url' => 'https://external.example/x/', 'published_at' => '2026-08-03T00:00:00Z',
                'visibility_state' => 'public', 'native_status' => 'publish', 'content_type' => 'article',
            ])), 'Review 93: timeline canonical URL must be same-site at construction.');
            break;
        default:
            $failures[] = 'Unknown File 25 review number.';
    }

    if ($failures !== []) {
        fwrite(STDERR, "FAILED\n- " . implode("\n- ", $failures) . "\n");
        exit(1);
    }

    echo 'PASS: File 25 Review ' . $review . " corrective regression\n";
}
