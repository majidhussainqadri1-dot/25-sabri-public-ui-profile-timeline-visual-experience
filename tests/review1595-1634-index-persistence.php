<?php
declare(strict_types=1);
/** File25 R1595-1599: actual rebuild lifecycle under failed WordPress option writes. */
namespace Sabri\PublicExperience {
    final class Profile_Router {
        public static function flush(): void { $GLOBALS['r1595_flushes']++; }
    }
}
namespace {
    if(!defined('ABSPATH')){define('ABSPATH',__DIR__.'/fixtures/');}
    $GLOBALS['r1595_options']=[];
    $GLOBALS['r1595_failure']='';
    $GLOBALS['r1595_builder_calls']=0;
    $GLOBALS['r1595_events']=[];
    $GLOBALS['r1595_flushes']=0;
    function get_option(string $name,mixed $default=false):mixed {
        return array_key_exists($name,$GLOBALS['r1595_options'])?$GLOBALS['r1595_options'][$name]:$default;
    }
    function add_option(string $name,mixed $value,string $deprecated='',mixed $autoload=null):bool {
        if(array_key_exists($name,$GLOBALS['r1595_options'])){return false;}
        $GLOBALS['r1595_options'][$name]=$value;return true;
    }
    function update_option(string $name,mixed $value,mixed $autoload=null):bool {
        if($name==='sabri_public_experience_timeline_index'
            && is_array($value)
            && (($GLOBALS['r1595_failure']==='running' && ($value['status']??'')==='running')
                || ($GLOBALS['r1595_failure']==='complete' && ($value['status']??'')==='complete'))
        ){return false;}
        $GLOBALS['r1595_options'][$name]=$value;return true;
    }
    function delete_option(string $name):bool {unset($GLOBALS['r1595_options'][$name]);return true;}
    function wp_generate_uuid4():string {return '11111111-2222-4333-8444-555555555555';}
    function sanitize_key(string $name):string {return (string)preg_replace('/[^a-z0-9_-]/','',strtolower($name));}
    function apply_filters(string $name,mixed $default,mixed ...$args):mixed {
        if($name==='sabri_public_experience/timeline_index_build'){
            ++$GLOBALS['r1595_builder_calls'];
            return ['accepted'=>true,'items'=>1,'checksum'=>str_repeat('a',64),'source_versions'=>['file-21'=>'1.2.3']];
        }
        return $default;
    }
    function do_action(string $hook,mixed ...$args):void {$GLOBALS['r1595_events'][]=[$hook,$args];}
    require_once __DIR__.'/fixtures/fake-atomic-index-wpdb.php';
$GLOBALS['wpdb']=new \F25_Fake_Atomic_Wpdb('r1595_options');
require_once dirname(__DIR__).'/includes/class-plan-completion.php';
    $service=new \Sabri\PublicExperience\Plan_Completion();
    $method=new \ReflectionMethod($service,'rebuild_index');
    $method->setAccessible(true);
    $errors=[];$check=static function(bool $ok,string $msg)use(&$errors):void{if(!$ok){$errors[]=$msg;}};
    $events=static fn(string $hook):array=>array_values(array_filter($GLOBALS['r1595_events'],static fn(array $e):bool=>$e[0]===$hook));
    $reset=static function():void {
        $GLOBALS['r1595_options']=[];$GLOBALS['r1595_builder_calls']=0;$GLOBALS['r1595_events']=[];
    };

    // If the running generation marker cannot be committed, the owner builder
    // must not be invoked, success must not be emitted, and the lock must end.
    $reset();$GLOBALS['r1595_failure']='running';
    $result=$method->invoke($service);
    $check(($result['status']??'')==='failed'&&($result['error_code']??'')==='index_state_persistence_failed',
        'Failed running-state write must return explicit storage failure.');
    $check($GLOBALS['r1595_builder_calls']===0,'Unrecorded generation must not invoke index builder.');
    $check($events('sabri_public_experience/timeline_index_rebuilt')===[],
        'Unrecorded generation may not emit rebuild success.');
    $check(!isset($GLOBALS['r1595_options']['sabri_public_experience_timeline_index_lock']),
        'Failed initial write must free bounded index lock.');

    $reset();$GLOBALS['r1595_failure']='complete';
    $result=$method->invoke($service);
    $check($GLOBALS['r1595_builder_calls']===1,'Terminal-storage failure should occur after one accepted build.');
    $check(($result['status']??'')==='failed'&&($result['error_code']??'')==='index_state_persistence_failed',
        'Failed terminal complete write may not report successful persisted index.');
    $check($events('sabri_public_experience/timeline_index_rebuilt')===[],
        'Unpersisted completed index may not emit successful rebuild event.');
    $check(count($events('sabri_public_experience/timeline_index_storage_failed'))===1,
        'Terminal persistence failure must emit dedicated bounded failure event.');
    $check(!isset($GLOBALS['r1595_options']['sabri_public_experience_timeline_index_lock']),
        'Terminal storage failure must not retain index lock.');

    $reset();$GLOBALS['r1595_failure']='';
    $result=$method->invoke($service);
    $check(($result['status']??'')==='complete'
        && (get_option('sabri_public_experience_timeline_index')['status']??'')==='complete',
        'A genuinely persisted accepted index rebuild must succeed.');
    $check(count($events('sabri_public_experience/timeline_index_rebuilt'))===1,
        'Persisted completed index must emit one actual success event.');
    $check(($result['source_versions']['file-21']??'')==='1.2.3',
        'Owner File21 projection version must be preserved after bounded validation.');

    if($errors!==[]){fwrite(STDERR,"FAILED File25 R1595–1599\n- ".implode("\n- ",$errors)."\n");exit(1);}
    echo "PASS File25 R1595–1599: storage-failed start/commit denied, native builder gated, real success only after index read-back.\n";
}