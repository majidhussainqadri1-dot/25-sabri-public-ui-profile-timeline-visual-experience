<?php
declare(strict_types=1);

// The HTML opening tag contains embedded PHP, including its own PHP closing delimiter.
// A generic /<div[^>]*>/ matcher stops at the PHP delimiter, not at the HTML tag.
$source = file_get_contents(dirname(__DIR__) . '/templates/partials/profile-hero.php');
if (! is_string($source)) {
    fwrite(STDERR, "Profile hero source unavailable\n");
    exit(1);
}

$opening = '<div class="spux-actions"';
$expected = '<div class="spux-actions" role="group" aria-label="<?php esc_attr_e(\'Profile actions\', \'sabri-public-experience\'); ?>">';
function has_named_action_group(string $markup, string $opening, string $expected): bool
{
    return substr_count($markup, $opening) === 1 && str_contains($markup, $expected);
}

if (! has_named_action_group($source, $opening, $expected)) {
    fwrite(STDERR, "Named profile action group requires role=group and translated aria-label\n");
    exit(1);
}

// Mutation fixtures ensure that both role and accessible label are essential.
$missing_role = str_replace('role="group"', '', $source);
$missing_label = str_replace('aria-label=', 'data-label=', $source);
if (has_named_action_group($missing_role, $opening, $expected)
    || has_named_action_group($missing_label, $opening, $expected)
) {
    fwrite(STDERR, "ARIA regression mutation fixtures did not reject broken markup\n");
    exit(1);
}

echo "PASS Reviews 1475–1514: named action group semantics and PHP delimiter handling\n";
