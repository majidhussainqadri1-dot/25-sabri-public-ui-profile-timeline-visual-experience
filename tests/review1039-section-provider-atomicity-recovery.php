<?php
declare(strict_types=1);

require __DIR__ . '/review39-53-section-bootstrap.php';

use Sabri\PublicExperience\Contracts\Profile_Section_Provider;
use Sabri\PublicExperience\Section_Registry;
use Sabri\PublicExperience\Section_Service;

final class R1039_Section_Provider implements Profile_Section_Provider
{
    public int $query_calls = 0;
    public bool $fail_query = false;
    /** @param array<mixed> $items */
    public function __construct(private string $id, public array $items) {}
    public function get_id(): string { return $this->id; }
    public function get_version(): string { return '1.0.0'; }
    public function get_section(): string { return 'knowledge'; }
    public function get_maturity_level(): string { return 'read-only'; }
    public function is_available(): bool { return true; }
    public function supports_profile(array $profile): bool { return true; }
    public function query(int $user_id, array $profile, array $context = []): array
    {
        ++$this->query_calls;
        if ($this->fail_query) {
            throw new RuntimeException('Simulated transient native provider error');
        }
        return $this->items;
    }
    public function owns_native_content(): bool { return false; }
}

function r1039_card(int $i): array
{
    return [
        'projection_key' => hash('sha256', 'section-row-' . $i),
        'type' => 'article',
        'title' => 'Public article ' . $i,
        'url' => 'https://example.test/knowledge/article-' . $i . '/',
    ];
}
$profile = ['class' => 'doctor'];
$valid = r1039_card(1);

// A bounded 24-row provider batch with a malformed LAST row must not cause
// a phantom truncated status when none of that provider's cards survived.
$batch = [];
for ($i = 1; $i <= 23; ++$i) {
    $batch[] = r1039_card($i);
}
$batch[] = 'INVALID_PRIVATE_PROVIDER_ROW';
$p = new R1039_Section_Provider('cap-then-invalid', $batch);
$registry = new Section_Registry();
$registry->register($p);
$service = new Section_Service($registry);
$bad = $service->get_public_section(7, $profile, 'knowledge');
r3953_section_assert($bad['items'] === []
    && $bad['provider_error_count'] === 1
    && $bad['truncated'] === false,
    'A fully rejected capped provider may not claim public truncation or publish partial cards.');

// Invalid batches must not be retained in the same service's public cache.
// Once the identical provider recovers, public data should become readable.
$p->items = [$valid];
$recovered = $service->get_public_section(7, $profile, 'knowledge');
r3953_section_assert(count($recovered['items']) === 1
    && $recovered['provider_error_count'] === 0
    && $recovered['truncated'] === false
    && $p->query_calls === 2,
    'Transient invalid provider batch must be retried and recover without a stale cache entry.');
$again = $service->get_public_section(7, $profile, 'knowledge');
r3953_section_assert($again['items'] === $recovered['items'] && $p->query_calls === 2,
    'Successfully recovered owner section may be safely cached afterward.');

// A real exception is also transient and cannot poison public-section cache.
$p2 = new R1039_Section_Provider('throw-then-recover', [$valid]);
$p2->fail_query = true;
$registry2 = new Section_Registry();
$registry2->register($p2);
$service2 = new Section_Service($registry2);
$failed = $service2->get_public_section(7, $profile, 'knowledge');
r3953_section_assert($failed['items'] === []
    && $failed['provider_error_count'] === 1
    && $failed['truncated'] === false,
    'Throwing owner provider must be isolated without publishing an incomplete section.');
$p2->fail_query = false;
$back = $service2->get_public_section(7, $profile, 'knowledge');
r3953_section_assert(count($back['items']) === 1
    && $back['provider_error_count'] === 0
    && $p2->query_calls === 2,
    'Owner provider exception recovery must be visible on next same-instance request.');

// Legitimate 24-row, fully valid provider still honestly reports the source cap.
$p3 = new R1039_Section_Provider('valid-cap', array_map('r1039_card', range(1, 24)));
$registry3 = new Section_Registry();
$registry3->register($p3);
$validCap = (new Section_Service($registry3))->get_public_section(7, $profile, 'knowledge');
r3953_section_assert(count($validCap['items']) === 24
    && $validCap['provider_error_count'] === 0
    && $validCap['truncated'] === true,
    'A completely valid bounded provider must preserve its honest truncation indication.');

echo "PASS: File25 public section atomic truncation and transient provider cache recovery\n";
