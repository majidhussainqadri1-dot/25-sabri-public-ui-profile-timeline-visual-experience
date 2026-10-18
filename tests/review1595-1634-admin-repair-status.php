<?php
declare(strict_types=1);
/** File25 R1600-1603: actual repair status + admin failure-notice contract. */
namespace Sabri\PublicExperience {
    final class Profile_Router {
        public static function flush():void{++$GLOBALS['r1600_flushes'];}
    }
}
namespace {
    if(!defined('ABSPATH')){define('ABSPATH',__DIR__.'/fixtures/');}
    $GLOBALS['r1600_options']=[];
    $GLOBALS['r1600_fail_stale']=true;
    $GLOBALS['r1600_flushes']=0;
    function get_option(string $key,mixed $default=false):mixed{
        return array_key_exists($key,$GLOBALS['r1600_options'])?$GLOBALS['r1600_options'][$key]:$default;
    }
    function update_option(string $key,mixed $value,mixed $autoload=null):bool{
        if($GLOBALS['r1600_fail_stale'] && $key==='sabri_public_experience_timeline_index'
            && is_array($value) && ($value['error_code']??'')==='stale_rebuild_reconciled'){
            return false;
        }
        $GLOBALS['r1600_options'][$key]=$value;return true;
    }
    function do_action(string $hook,mixed ...$args):void {}
    function delete_transient(string $name):bool{return true;}
    function get_transient(string $name):mixed{return false;}
    function sanitize_key(string $name):string{return (string)preg_replace('/[^a-z0-9_-]/','',strtolower($name));}
    require_once dirname(__DIR__).'/includes/class-plan-completion.php';
    $s=new \Sabri\PublicExperience\Plan_Completion();
    $repair=new \ReflectionMethod($s,'repair');$repair->setAccessible(true);
    $failed=[];
    $check=static function(bool $ok,string $msg)use(&$failed):void{if(!$ok){$failed[]=$msg;}};
    $initial=[
        'status'=>'running','generation'=>'stale',
        'started_at_utc'=>gmdate('Y-m-d H:i:s',time()-1000),
        'updated_at_utc'=>gmdate('Y-m-d H:i:s',time()-1000),
    ];
    $GLOBALS['r1600_options']=[
        'sabri_public_experience_preferences'=>[],
        'sabri_public_experience_timeline_index'=>$initial,
    ];
    $bad=$repair->invoke($s);
    $check(($bad['status']??'')==='partial'&&($bad['operations']['stale_index']??null)===false,
        'Failed stale-index repair must not report complete when the WordPress write failed.');
    $check(($GLOBALS['r1600_options']['sabri_public_experience_timeline_index']['status']??'')==='running',
        'Failed repair must not invent persisted failed-state transition.');
    $GLOBALS['r1600_fail_stale']=false;
    $good=$repair->invoke($s);
    $check(($good['status']??'')==='complete'&&($good['operations']['stale_index']??null)===true,
        'Successful persisted stale index repair should complete.');
    $check(($GLOBALS['r1600_options']['sabri_public_experience_timeline_index']['error_code']??'')==='stale_rebuild_reconciled',
        'Successful stale index repair must persist reconciled failure reason.');

    // admin-post methods terminate with redirect_admin()->exit, so inspect
    // the actual dispatch contract without pretending to have an HTTP server.
    $source=file_get_contents(dirname(__DIR__).'/includes/class-plan-completion.php');
    $check(is_string($source) && str_contains($source,"'busy' => 'index_rebuild_busy'")
        && str_contains($source,"default => 'index_rebuild_failed'"),
        'Admin rebuild must distinguish busy/failed states from success.');
    $check(is_string($source) && str_contains($source,"? 'repair_complete'")
        && str_contains($source,": 'repair_partial'")
        && str_contains($source,"get_option(self::REPAIR_OPTION, null) === \$result"),
        'Admin repair notice must follow actual result and persisted repair journal.');
    if($failed!==[]){fwrite(STDERR,"FAILED File25 R1600–1603\n- ".implode("\n- ",$failed)."\n");exit(1);}
    echo "PASS File25 R1600–1603: real stale-repair storage failure yields partial, and admin notices are status-bound.\n";
}