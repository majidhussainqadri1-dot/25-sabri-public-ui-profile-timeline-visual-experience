<?php
declare(strict_types=1);
/** R1115–1154: guard against HTML timeline search silently broadening. */
$source = file_get_contents(dirname(__DIR__) . '/includes/class-profile-renderer.php');
if (! is_string($source)
    || ! str_contains($source, "if (\$search_error === '') {")
    || ! str_contains($source, "get_for_author((int) \$user->ID")
) {
    fwrite(STDERR, "R1115–1154: HTML timeline search guard missing.\n");
    exit(1);
}
echo "R1115–1154: HTML timeline search guard present.\n";
