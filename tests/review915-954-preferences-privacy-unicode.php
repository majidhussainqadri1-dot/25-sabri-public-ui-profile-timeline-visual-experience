<?php
declare(strict_types=1);

/**
 * Reviews 915–954: actual File25 preference schema and fallback regression.
 * Isolated WordPress fixture: no database, network or deployed-state claims.
 */
if (! defined('ABSPATH')) { define('ABSPATH', __DIR__ . '/fixtures/'); }
$GLOBALS['f25_review915_option'] = [];
if (! function_exists('get_option')) {
    function get_option(string $key, mixed $fallback = false): mixed {
        return $key === 'sabri_public_experience_preferences'
            ? $GLOBALS['f25_review915_option'] : $fallback;
    }
}
if (! function_exists('sanitize_key')) {
    function sanitize_key(string $key): string {
        return (string) preg_replace('/[^a-z0-9_\\-]/', '', strtolower($key));
    }
}
if (! function_exists('wp_strip_all_tags')) {
    function wp_strip_all_tags(string $text): string { return strip_tags($text); }
}
require_once dirname(__DIR__) . '/includes/class-plan-completion.php';
use Sabri\PublicExperience\Plan_Completion;

$errors=[];
$check=static function (bool $ok, string $message) use (&$errors): void {
    if (! $ok) { $errors[]=$message; }
};
$preferences = new Plan_Completion();
$defaults = Plan_Completion::preferences();
$all = $defaults['enabled_public_tabs'] ?? [];
$check(count($all) === 14 && in_array('pdfs', $all, true)
    && in_array('marketplace', $all, true),
    'File25 must expose all fourteen approved section keys by default.');

$saved = $preferences->sanitize_preferences([
    'enabled_public_tabs'=>$all,
    'featured_section_order'=>$all,
]);
$check(count($saved['enabled_public_tabs']) === 14
    && in_array('pdfs', $saved['enabled_public_tabs'], true)
    && in_array('marketplace', $saved['enabled_public_tabs'], true),
    'Admin save must not silently truncate late PDF and Marketplace tabs.');
$check(count($saved['featured_section_order']) === 14
    && end($saved['featured_section_order']) === 'marketplace',
    'A valid fourteen-item featured order must survive saving.');

$GLOBALS['f25_review915_option'] = $saved;
$roundtrip = Plan_Completion::preferences();
$check($roundtrip['enabled_public_tabs'] === $saved['enabled_public_tabs'],
    'Read-after-write must preserve the complete approved public tab set.');

$profile = [
    'section_labels' => array_fill_keys($all,'Approved'),
    'available_sections' => $all,
];
$projection=Plan_Completion::apply_profile_preferences($profile);
$check(in_array('pdfs',$projection['available_sections'],true)
    && in_array('marketplace',$projection['available_sections'],true),
    'Saving defaults must not unexpectedly hide last two profile sections.');

$GLOBALS['f25_review915_option'] = [
    'enabled_public_tabs'=>['unknown-only-section'],
    'enabled_optional_providers'=>['unknown-external-module'],
    'public_visibility'=>'false',
    'public_metrics'=>'yes',
    'profile_local_search'=>new stdClass(),
];
$invalid = Plan_Completion::preferences();
$check($invalid['enabled_public_tabs'] === ['overview'],
    'Malformed public tabs must fail closed to Overview, not re-enable fourteen sections.');
$check($invalid['enabled_optional_providers'] === [],
    'Malformed providers must not restore all five optional providers.');
foreach(['public_visibility','public_metrics','profile_local_search'] as $key){
    $check($invalid[$key] === false,'Malformed privacy-sensitive boolean must fail closed: '.$key);
}
$GLOBALS['f25_review915_option'] = [
    'public_visibility' => '0',
    'public_metrics' => false,
    'profile_local_search' => '1',
    'enabled_public_tabs' => ['overview','research'],
    'enabled_optional_providers' => [],
];
$valid = Plan_Completion::preferences();
$check($valid['public_visibility'] === false && $valid['public_metrics'] === false
    && $valid['profile_local_search'] === true,
    'Valid administrator booleans and explicit public opt-outs must be preserved.');
$check($valid['enabled_public_tabs'] === ['overview','research']
    && $valid['enabled_optional_providers'] === [],
    'Intentional tab choices and disable-all provider state must remain respected.');

$GLOBALS['f25_review915_option'] = [];
$check(Plan_Completion::preferences()['public_visibility'] === true,
    'Fresh-install missing setting must retain documented default; only malformed explicit values fail closed.');

$ur120 = str_repeat('ع',120);
$ur121 = $ur120.'ع';
$emoji120 = str_repeat('🙂',120);
$check(Plan_Completion::normalize_search_query($ur120) === $ur120,
    'Unicode query budget is 120 characters, not 120 bytes, for Urdu.');
$check(Plan_Completion::normalize_search_query($emoji120) === $emoji120,
    'Four-byte Unicode symbols must satisfy 120-character bounded search.');
$check(Plan_Completion::normalize_search_query($ur121) === '',
    '121 Unicode codepoints must be rejected.');
$check(Plan_Completion::normalize_search_query('مضمون'.chr(0xE2).chr(0x80).chr(0xAE)) === '',
    'Hidden bidi-override marks must be rejected.');
$check(Plan_Completion::normalize_search_query(str_repeat('a',481)) === '',
    'Oversized raw search input must be bounded before normalization.');

if($errors!==[]){
    fwrite(STDERR,"FAILED File25 Reviews 915–954 preference and Unicode regressions\n- ".implode("\n- ",$errors)."\n");
    exit(1);
}
echo "PASS: File25 Reviews 915–954 fourteen public tabs, fail-closed privacy and Unicode search\n";
