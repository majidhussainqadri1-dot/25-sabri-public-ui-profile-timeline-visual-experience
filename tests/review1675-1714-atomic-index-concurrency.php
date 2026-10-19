<?php
declare(strict_types=1);
/** File25 Reviews 1675–1680: race exactly at conditional MySQL UPDATE/DELETE. */
if(!defined('ABSPATH')){define('ABSPATH',__DIR__.'/fixtures/');}
$GLOBALS['r1675_options']=[];
$GLOBALS['r1675_actions']=[];
$GLOBALS['r1675_cas_race']='';
$GLOBALS['r1675_calls']=0;
function get_option(string $key,mixed $default=false):mixed{
    return array_key_exists($key,$GLOBALS['r1675_options'])?$GLOBALS['r1675_options'][$key]:$default;
}
function add_option(string $key,mixed $value,string $deprecated='',mixed $autoload=null):bool{
    if(array_key_exists($key,$GLOBALS['r1675_options'])){return false;}
    $GLOBALS['r1675_options'][$key]=$value;return true;
}
function update_option(string $key,mixed $value,mixed $autoload=null):bool{
    $GLOBALS['r1675_options'][$key]=$value;return true;
}
function delete_option(string $key):bool{unset($GLOBALS['r1675_options'][$key]);return true;}
function sanitize_key(string $v):string{return (string)preg_replace('/[^a-z0-9_-]/','',strtolower($v));}
function wp_generate_uuid4():string{return 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';}
function do_action(string $action,mixed ...$args):void{$GLOBALS['r1675_actions'][]=[$action,$args];}
function apply_filters(string $hook,mixed $def,mixed ...$args):mixed{
    if($hook==='sabri_public_experience/timeline_index_build'){
        ++$GLOBALS['r1675_calls'];
        return ['accepted'=>true,'items'=>5,'checksum'=>str_repeat('b',64),'source_versions'=>['file-21'=>'1.2.3']];
    }
    return $def;
}
require_once __DIR__.'/fixtures/fake-atomic-index-wpdb.php';
$GLOBALS['wpdb']=new F25_Fake_Atomic_Wpdb('r1675_options');
require_once dirname(__DIR__).'/includes/class-plan-completion.php';
$svc=new \Sabri\PublicExperience\Plan_Completion();
$method=new ReflectionMethod($svc,'rebuild_index');$method->setAccessible(true);
$errors=[];$check=static function(bool $ok,string $msg)use(&$errors):void{if(!$ok){$errors[]=$msg;}};
$reset=static function():void{
    $GLOBALS['r1675_options']=[];$GLOBALS['r1675_actions']=[];
    $GLOBALS['r1675_calls']=0;$GLOBALS['r1675_cas_race']='';
};
$events=static function(string $name):array{
    return array_values(array_filter($GLOBALS['r1675_actions'],static fn(array $r):bool=>$r[0]===$name));
};
$index='sabri_public_experience_timeline_index';
$lock='sabri_public_experience_timeline_index_lock';

$reset();
$GLOBALS['r1675_cas_race']='overwrite_on_commit';
$after=$method->invoke($svc);
$check(($after['status']??'')==='failed' && ($after['error_code']??'')==='index_state_persistence_failed',
    'Concurrent replacement immediately at SQL compare+swap must deny old commit.');
$check(($GLOBALS['r1675_options'][$index]['generation']??null)==='rival',
    'Database CAS must not overwrite newest in-progress index generation.');
$check(($GLOBALS['r1675_options'][$lock]['generation']??null)==='rival',
    'Failed old-worker commit may not remove newer worker lock.');
$check($events('sabri_public_experience/timeline_index_rebuilt')===[],
    'No stale build may publish success.');

$reset();
$GLOBALS['r1675_cas_race']='replace_on_delete';
$finish=$method->invoke($svc);
$check(($finish['status']??'')==='complete',
    'Final correctly-fenced commit can succeed before a new lock is installed.');
$check(($GLOBALS['r1675_options'][$lock]['generation']??null)==='rival',
    'Newer lock swapped in at DELETE must NOT be removed.');
$check(($GLOBALS['r1675_options'][$index]['status']??'')==='complete',
    'Already-completed index must remain consistent when another worker claims a subsequent lease.');

$reset();
$GLOBALS['r1675_options'][$lock]=['generation'=>'expired-worker','expires'=>time()-10];
$GLOBALS['r1675_cas_race']='replace_on_delete';
$reclaim=$method->invoke($svc);
$check(($reclaim['status']??'')==='busy',
    'Atomic stale-lock reclaim must return busy if another worker took ownership.');
$check(($GLOBALS['r1675_options'][$lock]['generation']??null)==='rival',
    'Atomic stale-lock DELETE cannot remove newer worker lock.');
$check($GLOBALS['r1675_calls']===0,
    'Race-lost stale-lock reclaim must not invoke index builder.');

$reset();
$normal=$method->invoke($svc);
$check(($normal['status']??'')==='complete'
    && ($GLOBALS['r1675_options'][$index]['status']??'')==='complete'
    && !isset($GLOBALS['r1675_options'][$lock]),
    'Normal database-CAS index rebuild must complete and release its own lock.');
$check(count($events('sabri_public_experience/timeline_index_rebuilt'))===1,
    'Normal successful rebuild must publish exactly one completion event.');

$reset();
unset($GLOBALS['wpdb']);
$unavailable=$method->invoke($svc);
$check(($unavailable['status']??'')==='failed'
    && ($unavailable['error_code']??'')==='index_state_persistence_failed',
    'Missing database CAS backend must fail closed rather than silently fall back to unsafe writes.');
$GLOBALS['wpdb']=new F25_Fake_Atomic_Wpdb('r1675_options');

if($errors!==[]){fwrite(STDERR,"FAILED File25 R1675–1680:\n- ".implode("\n- ",$errors)."\n");exit(1);}
echo "PASS File25 R1675–1680: atomic generation CAS resists UPDATE/DELETE races, guarded reclaim and missing DB.\n";
