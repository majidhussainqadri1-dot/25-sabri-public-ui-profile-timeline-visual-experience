<?php
declare(strict_types=1);
/** File25 R1635–1641: generation overtake and metadata strictness on real index method. */
if (! defined('ABSPATH')) { define('ABSPATH', __DIR__ . '/fixtures/'); }
$GLOBALS['r1635_options'] = [];
$GLOBALS['r1635_actions'] = [];
$GLOBALS['r1635_mode'] = 'normal';
$GLOBALS['r1635_invocations'] = 0;
function get_option(string $name,mixed $default=false):mixed {
    return array_key_exists($name,$GLOBALS['r1635_options'])?$GLOBALS['r1635_options'][$name]:$default;
}
function add_option(string $name,mixed $value,string $deprecated='',mixed $autoload=null):bool {
    if(array_key_exists($name,$GLOBALS['r1635_options'])){return false;}
    $GLOBALS['r1635_options'][$name]=$value;return true;
}
function delete_option(string $name):bool {
    if(!array_key_exists($name,$GLOBALS['r1635_options'])){return false;}
    unset($GLOBALS['r1635_options'][$name]);return true;
}
function update_option(string $name,mixed $value,mixed $autoload=null):bool {
    $GLOBALS['r1635_options'][$name]=$value;return true;
}
function wp_generate_uuid4():string{return '11111111-2222-4333-8444-555555555555';}
function sanitize_key(string $name):string {return (string) preg_replace('/[^a-z0-9_-]/','',strtolower($name));}
function do_action(string $hook,mixed ...$args):void {$GLOBALS['r1635_actions'][]=[$hook,$args];}
function apply_filters(string $hook,mixed $initial,mixed ...$args):mixed {
    if($hook!=='sabri_public_experience/timeline_index_build'){return $initial;}
    ++$GLOBALS['r1635_invocations'];
    if($GLOBALS['r1635_mode']==='overtake') {
        $GLOBALS['r1635_options']['sabri_public_experience_timeline_index_lock']=[
            'generation'=>'newer-owner','expires'=>time()+300,
        ];
        $GLOBALS['r1635_options']['sabri_public_experience_timeline_index']=[
            'status'=>'running','generation'=>'newer-owner','rebuildable'=>true,
        ];
    }
    if($GLOBALS['r1635_mode']==='replace-lock-only') {
        $GLOBALS['r1635_options']['sabri_public_experience_timeline_index_lock']=[
            'generation'=>'newer-owner','expires'=>time()+300,
        ];
    }
    return [
        'accepted'=>true,
        'items'=>3,
        'checksum'=>str_repeat('a',64),
        'source_versions'=>$GLOBALS['r1635_mode']==='bad-version'
            ? ['File21-invalid'=>'bad_version'] : ['file-21'=>'1.2.3'],
    ];
}
require_once __DIR__.'/fixtures/fake-atomic-index-wpdb.php';
$GLOBALS['wpdb']=new \F25_Fake_Atomic_Wpdb('r1635_options');
require_once dirname(__DIR__).'/includes/class-plan-completion.php';
$service=new \Sabri\PublicExperience\Plan_Completion();
$rebuild=new ReflectionMethod($service,'rebuild_index');$rebuild->setAccessible(true);
$errors=[];$check=static function(bool $ok,string $msg)use(&$errors):void{if(!$ok){$errors[]=$msg;}};
$events=static function(string $name):array{
    return array_values(array_filter($GLOBALS['r1635_actions'],static fn(array $record):bool=>$record[0]===$name));
};
$reset=static function(string $mode):void {
    $GLOBALS['r1635_options']=[];$GLOBALS['r1635_actions']=[];
    $GLOBALS['r1635_mode']=$mode;$GLOBALS['r1635_invocations']=0;
};
foreach(['overtake','replace-lock-only'] as $scenario) {
    $reset($scenario);
    $result=$rebuild->invoke($service);
    $check(($result['status']??'')==='failed'
        && ($result['error_code']??'')==='index_generation_superseded',
        "$scenario must not report an old worker's completion.");
    $lock=get_option('sabri_public_experience_timeline_index_lock',[]);
    $check(($lock['generation']??'')==='newer-owner',
        "$scenario must not delete a newer owner's lease.");
    $index=get_option('sabri_public_experience_timeline_index',[]);
    $check(($index['status']??null)==='running',
        "$scenario must not complete/overwrite a superseded generation.");
    $check($events('sabri_public_experience/timeline_index_rebuilt')===[],
        "$scenario must not publish a stale success event.");
}
$reset('bad-version');
$result=$rebuild->invoke($service);
$check(($result['status']??'')==='failed'
    && ($result['error_code']??'')==='builder_contract_unavailable',
    'Invalid source_versions cannot be silently dropped to produce complete index.');
$check($events('sabri_public_experience/timeline_index_rebuilt')===[],
    'Invalid source version metadata must never produce a success event.');
$check(get_option('sabri_public_experience_timeline_index_lock',false)===false,
    'Failed, correctly owned generation should release its lease.');
$reset('normal');
$result=$rebuild->invoke($service);
$check(($result['status']??'')==='complete' && ($result['source_versions']['file-21']??null)==='1.2.3',
    'Valid owner-linked source version can complete.');
$check(get_option('sabri_public_experience_timeline_index_lock',false)===false,
    'Successful, correctly owned generation should release its lease.');
$check(count($events('sabri_public_experience/timeline_index_rebuilt'))===1,
    'Only persisted successfully completed index should publish one event.');
if($errors!==[]){fwrite(STDERR,"FAIL File25 R1635–1641\n- ".implode("\n- ",$errors)."\n");exit(1);}
echo "PASS File25 R1635–1641: superseded generation cannot overwrite/delete replacement; invalid provider version denied.\n";
