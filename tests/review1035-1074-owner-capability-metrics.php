<?php
declare(strict_types=1);
/** Reviews 1035–1074: real File25 admin capability and public metric gates. */
if (! defined('ABSPATH')) { define('ABSPATH', __DIR__ . '/fixtures/'); }
$GLOBALS['f25_r1035_admin'] = false;
$GLOBALS['f25_r1035_threshold'] = 'invalid';
$GLOBALS['f25_r1035_metric_allowed'] = true;
$GLOBALS['f25_r1035_metrics_filter_calls'] = 0;
function is_user_logged_in(): bool { return true; }
function current_user_can(string $capability): bool {
    return $capability === 'read'
        || ($capability === 'manage_options' && $GLOBALS['f25_r1035_admin']);
}
function get_current_user_id(): int { return 42; }
function get_option(string $option, mixed $fallback = false): mixed {
    return $option === 'sabri_public_experience_preferences' ? [] : $fallback;
}
function apply_filters(string $hook, mixed $value, mixed ...$params): mixed {
    if ($hook === 'sabri_public_experience/operation_capability') {
        return 'read'; // Malicious/weaker extension must never grant an admin action.
    }
    if ($hook === 'sabri_public_experience/public_metrics') {
        ++$GLOBALS['f25_r1035_metrics_filter_calls'];
        return ['publications'=>7, 'followers'=>4, 'secret_internal'=>100];
    }
    if ($hook === 'sabri_public_experience/public_metric_minimum') {
        return $GLOBALS['f25_r1035_threshold'];
    }
    if ($hook === 'sabri_public_experience/public_metric_allowed') {
        return $GLOBALS['f25_r1035_metric_allowed'];
    }
    return $value;
}
require_once dirname(__DIR__) . '/includes/class-plan-completion.php';
$errors=[];
$check=static function(bool $ok,string $reason)use(&$errors):void{
    if(!$ok){$errors[]=$reason;}
};
$permission = new ReflectionMethod(\Sabri\PublicExperience\Plan_Completion::class,'can_operate');
$permission->setAccessible(true);
foreach (['preferences','repair','reconcile_profile','migration_dry_run'] as $operation) {
    $check($permission->invoke(null,$operation) === false,
        'An ordinary account gained elevated File25 '.$operation.' from a lowered capability filter.');
}
$GLOBALS['f25_r1035_admin'] = true;
$check($permission->invoke(null,'preferences') === true,
    'Owner administrator with required baseline may still update bounded preferences.');
$GLOBALS['f25_r1035_admin'] = false;

$metric = [ 'user_id' => 42 ];
// File 25 has no owner-authorized public-safe metrics projection. Even a
// valid-looking number or a benign threshold from a presentation hook must
// not create a count claimed by File03/21/other native owners.
foreach (['invalid', 10, 5, 0, null, [], '0', -1, 1000000001] as $candidate) {
    $GLOBALS['f25_r1035_threshold'] = $candidate;
    foreach ([true, false] as $allowed) {
        $GLOBALS['f25_r1035_metric_allowed'] = $allowed;
        $check(\Sabri\PublicExperience\Plan_Completion::public_metrics($metric) === [],
            'Unapproved metrics must remain absent regardless of filter thresholds and opt-in.');
    }
}
$check($GLOBALS['f25_r1035_metrics_filter_calls'] === 0,
    'Generic UI metrics filter must not be called as a data-publishing authority.');
$adminSource = (string) file_get_contents(dirname(__DIR__) . '/includes/class-plan-completion.php');
$check(str_contains($adminSource, 'Saving this preference does not authorize or publish a count.'),
    'Admin preference must not imply that unsigned native statistics become public.');
$check(\Sabri\PublicExperience\Plan_Completion::public_metrics(['user_id' => 0]) === [],
    'Invalid owner identity must remain excluded.');

if($errors!==[]){
    fwrite(STDERR,"FAILED R1035–1074 File25 admin and public metric privacy\n- ".implode("\n- ",$errors)."\n");
    exit(1);
}
echo "PASS R1035–1074: immutable admin owner capability and fail-closed public-metric thresholds\n";
