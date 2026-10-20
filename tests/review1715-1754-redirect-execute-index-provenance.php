<?php
declare(strict_types=1);
/** File25 R1715–1721: reopen approved map under current guards; bound index provenance. */
if (! defined('ABSPATH')) { define('ABSPATH', __DIR__.'/fixtures/'); }
$GLOBALS['r1715_options']=[];
$GLOBALS['r1715_builder']=['accepted'=>true,'items'=>1,'checksum'=>'','source_versions'=>[]];
$GLOBALS['r1715_events']=[];
$GLOBALS['r1675_cas_race']='';
function get_option(string $k,mixed $fallback=false):mixed {
    return array_key_exists($k,$GLOBALS['r1715_options'])?$GLOBALS['r1715_options'][$k]:$fallback;
}
function add_option(string $k,mixed $v,string $deprecated='',mixed $autoload=null):bool {
    if(array_key_exists($k,$GLOBALS['r1715_options'])){return false;}
    $GLOBALS['r1715_options'][$k]=$v;return true;
}
function update_option(string $k,mixed $v,mixed $autoload=null):bool {
    $GLOBALS['r1715_options'][$k]=$v;return true;
}
function delete_option(string $k):bool{if(!array_key_exists($k,$GLOBALS['r1715_options']))return false;unset($GLOBALS['r1715_options'][$k]);return true;}
function sanitize_key(string $v):string{return (string)preg_replace('/[^a-z0-9_-]/','',strtolower($v));}
function home_url(string $path=''):string{return 'https://example.test'.$path;}
function wp_json_encode(mixed $v,int $flags=0):string|false{return json_encode($v,$flags);}
function wp_generate_uuid4():string{return '17150000-aaaa-4bbb-8ccc-000000000001';}
function do_action(string $hook,mixed ...$args):void{$GLOBALS['r1715_events'][]=[$hook,$args];}
function apply_filters(string $hook,mixed $default,mixed ...$args):mixed{
    if($hook==='sabri_public_experience/timeline_index_build'){return $GLOBALS['r1715_builder'];}
    return $default;
}
require_once __DIR__.'/fixtures/fake-atomic-index-wpdb.php';
$GLOBALS['wpdb']=new F25_Fake_Atomic_Wpdb('r1715_options');
require_once dirname(__DIR__).'/includes/class-public-url.php';
require_once dirname(__DIR__).'/includes/class-index-atomic-options.php';
require_once dirname(__DIR__).'/includes/class-plan-completion.php';
$subject=new \Sabri\PublicExperience\Plan_Completion();
$migrate=new ReflectionMethod($subject,'migration');$migrate->setAccessible(true);
$validate=new ReflectionMethod($subject,'validated_redirect_map');$validate->setAccessible(true);
$rebuild=new ReflectionMethod($subject,'rebuild_index');$rebuild->setAccessible(true);
$errs=[];$assert=static function(bool $v,string $desc)use(&$errs):void{if(!$v)$errs[]=$desc;};
$entry=static fn(string $from,string $to):array=>['from'=>$from,'to'=>$to,'status'=>301];
$make_ready=static function(array $map):void{
    $GLOBALS['r1715_options']=[
       'sabri_public_experience_migration_state'=>[
        'status'=>'ready','redirect_map'=>$map,'map_checksum'=>hash('sha256',(string)wp_json_encode($map)),
        'collision_count'=>0,'invalid_count'=>0,'updated_at_utc'=>gmdate('Y-m-d H:i:s'),
       ],
       'sabri_public_experience_legacy_redirects'=>[],
    ];
};
// A stored ready checksum is insufficient if the current approved owner law
// would refuse the same map.
foreach ([
    [$entry('/wp-login.php','https://example.test/other/')],
    [$entry('/old-a/','https://example.test/old-b/'),$entry('/old-b/','https://example.test/final/')],
] as $map) {
    $make_ready($map);
    $out=$migrate->invoke($subject,'executed');
    $assert(($out['status']??null)==='blocked'
        && ($out['error_code']??null)==='approved_map_revalidation_failed',
        'Previously ready migration must be revalidated under current owner/chain rules.');
    $assert(get_option('sabri_public_experience_legacy_redirects',null)===[],
        'Unapproved current redirects must not be activated.');
}
$duplicate=$validate->invoke(null,[
    $entry('/old-a','https://example.test/new-a/'),
    $entry('/old-a/','https://example.test/new-b/'),
]);
$assert($duplicate['collisions']>0,
    'Trailing slash aliases of the same legacy route must be counted as a collision.');
$accepted=[$entry('/old-a/','https://example.test/profile-new/')];
$make_ready($accepted);
$out=$migrate->invoke($subject,'executed');
$assert(($out['status']??'')==='executed',
    'An approved valid profile map should still execute.');
$assert(get_option('sabri_public_experience_legacy_redirects',[])===$accepted,
    'Approved map must be persisted exactly.');
$events=static fn(string $hook):array=>array_values(array_filter($GLOBALS['r1715_events'],static fn($e)=>$e[0]===$hook));
$run=static function(array $builder)use($rebuild,$subject):array{
    $GLOBALS['r1715_options']=[];
    $GLOBALS['r1715_events']=[];
    $GLOBALS['r1715_builder']=$builder;
    return $rebuild->invoke($subject);
};
$base=['accepted'=>true,'items'=>1,'checksum'=>str_repeat('a',64),'source_versions'=>[]];
$no_prov=$run($base);
$assert(($no_prov['status']??'')==='failed'&&($no_prov['error_code']??'')==='builder_contract_unavailable',
    'Positive item count with no canonical owner provenance cannot complete.');
$assert($events('sabri_public_experience/timeline_index_rebuilt')===[],
    'No-provenance builder must not emit successful rebuild.');
$too_many=[];for($i=0;$i<65;$i++){$too_many['file-'.$i]='1.2.3';}
$over=$run(array_replace($base,['source_versions'=>$too_many]));
$assert(($over['status']??'')==='failed','65 source owner versions must be bounded/fail closed.');
$long=$run(array_replace($base,['source_versions'=>['file-21'=>str_repeat('1',65)]]));
$assert(($long['status']??'')==='failed','Oversized version string must be refused.');
$long_id=$run(array_replace($base,['source_versions'=>[str_repeat('a',65)=>'1.2.3']]));
$assert(($long_id['status']??'')==='failed','Oversized owner identifier must be refused.');
$good=$run(array_replace($base,['source_versions'=>['file-21'=>'1.2.3']]));
$assert(($good['status']??'')==='complete'
    &&($good['source_versions']['file-21']??'')==='1.2.3',
    'Normal versioned provider pointers still complete.');
$assert(count($events('sabri_public_experience/timeline_index_rebuilt'))===1,
    'Valid persisted index must emit one completed event.');
if($errs!==[]){fwrite(STDERR,"FAIL R1715–1721:\n- ".implode("\n- ",$errs)."\n");exit(1);}
echo "PASS R1715–1721: revalidated migration, slash-equivalent source denial, bounded provenance and valid index.\n";
