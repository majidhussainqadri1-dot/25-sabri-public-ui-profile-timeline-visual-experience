<?php
declare(strict_types=1);
/**
 * R1675-1679: exercise SQL compare-and-swap lease + projection lifecycle with
 * a deterministic fake of WordPress wpdb. This is not a live MySQL stress test.
 */
if (! defined('ABSPATH')) { define('ABSPATH', __DIR__ . '/fixtures/'); }
if (! defined('WPINC')) { define('WPINC', 'wp-includes'); }
$GLOBALS['r1675_options'] = [];
$GLOBALS['r1675_events'] = [];
$GLOBALS['r1675_cache_deletes'] = [];
$GLOBALS['r1675_action'] = 'normal';
$GLOBALS['r1675_builder_calls'] = 0;
function get_option(string $key, mixed $default=false): mixed {
    return array_key_exists($key,$GLOBALS['r1675_options']) ? $GLOBALS['r1675_options'][$key] : $default;
}
function add_option(string $key,mixed $val,string $unused='',mixed $autoload=null): bool {
    if(array_key_exists($key,$GLOBALS['r1675_options'])){return false;}
    $GLOBALS['r1675_options'][$key]=$val;return true;
}
function update_option(string $key,mixed $val,mixed $autoload=null): bool {
    $GLOBALS['r1675_options'][$key]=$val;return true;
}
function delete_option(string $key): bool {
    if(!array_key_exists($key,$GLOBALS['r1675_options'])){return false;}
    unset($GLOBALS['r1675_options'][$key]);return true;
}
function maybe_serialize(mixed $v): string {
    return is_array($v) || is_object($v) ? serialize($v) : (string)$v;
}
function wp_cache_delete(string $key,string $group=''):bool {
    $GLOBALS['r1675_cache_deletes'][]=[$key,$group];return true;
}
function wp_generate_uuid4():string{return 'aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee';}
function sanitize_key(string $val):string{return (string) preg_replace('/[^a-z0-9_-]/','',strtolower($val));}
function do_action(string $hook,mixed ...$args):void{$GLOBALS['r1675_events'][]=[$hook,$args];}
function apply_filters(string $hook,mixed $default,mixed ...$args):mixed {
    if($hook!=='sabri_public_experience/timeline_index_build'){return $default;}
    ++$GLOBALS['r1675_builder_calls'];
    return ['accepted'=>true,'items'=>2,'checksum'=>str_repeat('a',64),'source_versions'=>['file-21'=>'1.0.3']];
}
final class FakeFile25Wpdb {
    public string $options='wp_options';
    public array $statements=[];
    private array $params=[];
    public function prepare(string $query,mixed ...$args):string {
        $this->params=$args;return $query;
    }
    public function query(string $sql):int|false {
        $params=$this->params;
        $this->statements[]=[$sql,$params];
        if($GLOBALS['r1675_action']==='race-before-reclaim' && str_starts_with($sql,'DELETE')){
            $GLOBALS['r1675_options']['sabri_public_experience_timeline_index_lock']=[
                'generation'=>'new-owner','expires'=>time()+300,
            ];
            $GLOBALS['r1675_action']='normal';
        }
        if($GLOBALS['r1675_action']==='race-before-final-update' && str_starts_with($sql,'UPDATE')){
            $GLOBALS['r1675_options']['sabri_public_experience_timeline_index_lock']=[
                'generation'=>'new-owner','expires'=>time()+300,
            ];
            $GLOBALS['r1675_options']['sabri_public_experience_timeline_index']=[
                'status'=>'running','generation'=>'new-owner',
            ];
            $GLOBALS['r1675_action']='normal';
        }
        if($GLOBALS['r1675_action']==='fail-write' && (str_starts_with($sql,'UPDATE') || str_starts_with($sql,'INSERT'))) {
            return false;
        }
        if(str_starts_with($sql,'DELETE')) {
            [$key,$serialized]=$params;
            if(!array_key_exists($key,$GLOBALS['r1675_options'])
                || maybe_serialize($GLOBALS['r1675_options'][$key])!==$serialized){return 0;}
            unset($GLOBALS['r1675_options'][$key]);return 1;
        }
        if(str_starts_with($sql,'INSERT')) {
            [$key,$val,$leaseKey,$leaseSerialized,$mustNotExist]=$params;
            if(array_key_exists($mustNotExist,$GLOBALS['r1675_options'])
                || maybe_serialize($GLOBALS['r1675_options'][$leaseKey]??'')!==$leaseSerialized){return 0;}
            $GLOBALS['r1675_options'][$key]=unserialize($val,['allowed_classes'=>false]);return 1;
        }
        if(str_starts_with($sql,'UPDATE')) {
            [$leaseKey,$val,$key,$prev,$leaseSerialized]=$params;
            if(!array_key_exists($key,$GLOBALS['r1675_options'])
                || maybe_serialize($GLOBALS['r1675_options'][$key])!==$prev
                || maybe_serialize($GLOBALS['r1675_options'][$leaseKey]??'')!==$leaseSerialized){return 0;}
            $GLOBALS['r1675_options'][$key]=unserialize($val,['allowed_classes'=>false]);return 1;
        }
        return false;
    }
}
$GLOBALS['wpdb']=new FakeFile25Wpdb();
require_once dirname(__DIR__).'/includes/class-plan-completion.php';
$instance=new \Sabri\PublicExperience\Plan_Completion();
$method=new ReflectionMethod($instance,'rebuild_index');$method->setAccessible(true);
$errors=[];
$check=static function(bool $pass,string $message)use(&$errors):void{if(!$pass){$errors[]=$message;}};
$reset=static function(string $action='normal'):void{
    $GLOBALS['r1675_options']=[];$GLOBALS['r1675_events']=[];
    $GLOBALS['r1675_cache_deletes']=[];$GLOBALS['r1675_builder_calls']=0;
    $GLOBALS['r1675_action']=$action;$GLOBALS['wpdb']->statements=[];
};
$events=static fn(string $event):array=>array_values(array_filter(
    $GLOBALS['r1675_events'],static fn(array $e):bool=>$e[0]===$event
));

$reset();
$normal=$method->invoke($instance);
$check(($normal['status']??'')==='complete','Normal atomic SQL path completes after a successful native builder.');
$check(get_option('sabri_public_experience_timeline_index')['status'] === 'complete',
    'Normal atomic SQL path persists final index record.');
$check(get_option('sabri_public_experience_timeline_index_lock',false)===false,
    'Normal SQL compare-delete releases the original owner lock.');
$check(count($events('sabri_public_experience/timeline_index_rebuilt'))===1,
    'Normal atomic success publishes exactly one event.');
$check(count($GLOBALS['r1675_cache_deletes'])>=2,
    'Atomic SQL mutations must invalidate WordPress option caches.');

$reset('race-before-final-update');
$stale=$method->invoke($instance);
$check(($stale['status']??'')==='failed'
    && ($stale['error_code']??'')==='index_state_persistence_failed',
    'If new worker replaces lock after preflight but before SQL UPDATE, CAS must refuse stale commit.');
$check((get_option('sabri_public_experience_timeline_index_lock')['generation']??'')==='new-owner',
    'CAS must preserve newer lock in final-write race.');
$check((get_option('sabri_public_experience_timeline_index')['generation']??'')==='new-owner',
    'CAS must preserve newer index in final-write race.');
$check(count($events('sabri_public_experience/timeline_index_rebuilt'))===0,
    'Stale worker cannot publish a success event.');

$reset('race-before-reclaim');
$GLOBALS['r1675_options']['sabri_public_experience_timeline_index_lock']=[
    'generation'=>'expired-old','expires'=>time()-50,
];
$busy=$method->invoke($instance);
$check(($busy['status']??'')==='busy','Stale lease CAS that loses to a new worker returns busy.');
$check((get_option('sabri_public_experience_timeline_index_lock')['generation']??'')==='new-owner',
    'Stale lease cleanup must not delete a concurrent new lease.');
$check($GLOBALS['r1675_builder_calls']===0,'Failed lease reclaim must never start native builder.');

$reset('fail-write');
$failure=$method->invoke($instance);
$check(($failure['status']??'')==='failed'
    && ($failure['error_code']??'')==='index_state_persistence_failed',
    'A rejected DB write must fail closed.');
$check($GLOBALS['r1675_builder_calls']===0,'Failed CAS initial write must not invoke builder.');
$check(get_option('sabri_public_experience_timeline_index_lock',false)===false,
    'Failed CAS initial write must free own lock using conditional SQL DELETE.');

$reset();
$GLOBALS['wpdb']=null;
$noDb=$method->invoke($instance);
$check(($noDb['status']??'')==='failed'
    && $GLOBALS['r1675_builder_calls']===0,
    'Bootstrapped WordPress without atomic wpdb capability must fail closed, not use unsafe option fallback.');

if($errors!==[]){fwrite(STDERR,"FAIL File25 SQL CAS R1675–1679\n- ".implode("\n- ",$errors)."\n");exit(1);}
echo "PASS File25 R1675–1679: conditional DB lock reclaim/release, atomic generation-index writes and no unsafe production fallback.\n";
