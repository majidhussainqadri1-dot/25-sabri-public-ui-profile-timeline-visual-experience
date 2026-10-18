<?php
declare(strict_types=1);
/** File25 R1444-1445: ensure preview links use loaded native same-site URL class. */
if (! defined('ABSPATH')) { define('ABSPATH', __DIR__ . '/fixtures/'); }
require_once dirname(__DIR__) . '/includes/class-public-url.php';
$errors = [];
$source = file_get_contents(dirname(__DIR__) . '/templates/public-profile.php');
if (! is_string($source)) {
    fwrite(STDERR, "FAIL File25 R1444-1445: public profile template cannot be read.\n");
    exit(1);
}
$bad = 'SabriPublicExperiencePublic_URL::sanitize_same_site';
if (str_contains($source, $bad)) {
    $errors[] = 'Unqualified nonexistent PHP class would fatal when preview links render.';
}
$correct = '\\Sabri\\PublicExperience\\Public_URL::sanitize_same_site';
if (substr_count($source, $correct) < 3) {
    $errors[] = 'Not all three preview/public profile URLs use the real canonical URL helper.';
}
if (! class_exists(\Sabri\PublicExperience\Public_URL::class)
    || ! method_exists(\Sabri\PublicExperience\Public_URL::class, 'sanitize_same_site')) {
    $errors[] = 'Actual Public_URL helper class/method is unavailable.';
}
if ($errors !== []) {
    fwrite(STDERR, "FAIL File25 R1444-1445\n- " . implode("\n- ", $errors) . "\n");
    exit(1);
}
echo "PASS File25 R1444-1445: three preview/public links reference canonical same-site URL helper.\n";