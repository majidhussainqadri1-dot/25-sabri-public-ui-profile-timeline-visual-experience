<?php
declare(strict_types=1);

// Reviews 1475–1514: the named profile action container must have a
// nameable semantic role, not the implicit generic role of a bare div.
$source = file_get_contents(dirname(__DIR__) . '/templates/partials/profile-hero.php');
if (! is_string($source)) {
    fwrite(STDERR, "Profile hero source unavailable\n");
    exit(1);
}
$count = preg_match_all('/<div\\b[^>]*class="spux-actions"[^>]*>/', $source, $matches);
if ($count !== 1) {
    fwrite(STDERR, "Expected exactly one profile actions container\n");
    exit(1);
}
$tag = $matches[0][0];
if (! str_contains($tag, 'role="group"')
    || ! str_contains($tag, 'aria-label="<?php esc_attr_e(\'Profile actions\', \'sabri-public-experience\'); ?>"')
) {
    fwrite(STDERR, "Named profile action group requires role=group and translated aria-label\n");
    exit(1);
}
echo "PASS Reviews 1475–1514: named action group semantics\n";
