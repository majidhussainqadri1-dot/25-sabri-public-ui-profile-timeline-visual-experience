<?php
declare(strict_types=1);
/** Reviews 1035–1074: real File25 admin capability and public metric gates. */
if (! defined('ABSPATH')) { define('ABSPATH', __DIR__ . '/fixtures/'); }
$GLOBALS['f25_r1035_admin'] = false;
$GLOBALS['f25_r1035_threshold'] = 'invalid';
$GLOBALS['f25_r1035_metric_allowed'] = true;
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
$metrics=\Sabri\PublicExperience\Plan_Completion::public_metrics($metric);
$check($metrics === [], 'Malformed public metric threshold must not expose any counts.');
$GLOBALS['f25_r1035_threshold']=10;
$check(\Sabri\PublicExperience\Plan_Completion::public_metrics($metric)===[],
    'Below-threshold counts must stay hidden.');
$GLOBALS['f25_r1035_threshold']=5;
$metrics=\Sabri\PublicExperience\Plan_Completion::public_metrics($metric);
$check(($metrics['publications']??null)===7 && !isset($metrics['followers'])
    && !isset($metrics['secret_internal']),
    'Valid aggregate thresholds must admit only approved numeric metrics above minimum.');
$GLOBALS['f25_r1035_metric_allowed']=false;
$check(\Sabri\PublicExperience\Plan_Completion::public_metrics($metric)===[],
    'Explicit metrics approval revocation must suppress all public counters.');
$GLOBALS['f25_r1035_metric_allowed']=true;
foreach ([null, [], '0', -1, 1000000001] as $invalid) {
    $GLOBALS['f25_r1035_threshold']=$invalid;
    $check(\Sabri\PublicExperience\Plan_Completion::public_metrics($metric)===[],
        'Malformed/overflow metric threshold must fail closed: '.get_debug_type($invalid));
}
if($errors!==[]){
    fwrite(STDERR,"FAILED R1035–1074 File25 admin and public metric privacy\n- ".implode("\n- ",$errors)."\n");
    exit(1);
}
echo "PASS R1035–1074: immutable admin owner capability and fail-closed public-metric thresholds\n";
