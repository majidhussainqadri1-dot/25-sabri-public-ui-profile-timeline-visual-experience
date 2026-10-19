<?php
declare(strict_types=1);
/**
 * File25 R1675–1682: conditional SQL lease operations race-injection tests.
 * Models the WordPress options table and query-level CAS decision. This is
 * NOT proof of real InnoDB, object-cache topology or deployed MySQL schema.
 */
namespace Sabri\PublicExperience {
    final class Profile_Router {
        public static function flush():void { $GLOBALS['r1675_flushes']++; }
    }
    final class Safe_Mode {
        public static function enable(string $reason,\Throwable $e):void {
            $GLOBALS['r1675_safe'][] = [$reason,$e->getMessage()];
        }
    }
}
namespace {
    if (! defined('ABSPATH')) { define('ABSPATH',__DIR__.'/fixtures/'); }
    define('SABRI_PUBLIC_EXPERIENCE_SCHEMA_VERSION', '2');
    define('SABRI_PUBLIC_EXPERIENCE_VERSION', '0.15.0');
    $GLOBALS['r1675_options'] = [];
    $GLOBALS['r1675_actions'] = [];
    $GLOBALS['r1675_flushes'] = 0;
    $GLOBALS['r1675_safe'] = [];
    $GLOBALS['r1675_query_count'] = 0;
    $GLOBALS['r1675_uuid'] = 0;
    $GLOBALS['r1675_next_race'] = '';
    function maybe_serialize(mixed $value):string {
        return is_array($value) || is_object($value) ? serialize($value) : (string) $value;
    }
    function get_option(string $key,mixed $fallback=false):mixed {
        return array_key_exists($key,$GLOBALS['r1675_options'])
            ? $GLOBALS['r1675_options'][$key] : $fallback;
    }
    function add_option(string $key,mixed $value,string $unused='',mixed $autoload=null):bool {
        if (array_key_exists($key,$GLOBALS['r1675_options'])) { return false; }
        $GLOBALS['r1675_options'][$key]=$value;return true;
    }
    function update_option(string $key,mixed $value,mixed $autoload=null):bool {
        $GLOBALS['r1675_options'][$key]=$value;return true;
    }
    function delete_option(string $key):bool {
        if(!array_key_exists($key,$GLOBALS['r1675_options'])){return false;}
        unset($GLOBALS['r1675_options'][$key]);return true;
    }
    function wp_cache_delete(string $key,string $group=''):bool {
        $GLOBALS['r1675_invalidated'][] = [$key,$group];return true;
    }
    function wp_generate_uuid4():string {
        ++$GLOBALS['r1675_uuid'];
        return sprintf('00000000-0000-4000-8000-%012d',$GLOBALS['r1675_uuid']);
    }
    function sanitize_key(string $s):string {
        return (string) preg_replace('/[^a-z0-9_-]/','',strtolower($s));
    }
    function do_action(string $hook,mixed ...$args):void {
        $GLOBALS['r1675_actions'][]=[$hook,$args];
    }
    function apply_filters(string $hook,mixed $initial,mixed ...$args):mixed {
        if($hook==='sabri_public_experience/timeline_index_build'){
            return ['accepted'=>true,'items'=>2,'checksum'=>str_repeat('c',64),'source_versions'=>['file-21'=>'1.2.3']];
        }
        return $initial;
    }
    final class R1675FakeWpdb {
        public string $options='wp_options';
        public function prepare(string $query,mixed ...$arguments):string {
            return base64_encode(serialize([$query,$arguments]));
        }
        public function query(string $query):int|false {
            ++$GLOBALS['r1675_query_count'];
            $decoded=unserialize(base64_decode($query,true),['allowed_classes'=>false]);
            if(!is_array($decoded)||count($decoded)!==2){return false;}
            [$sql,$args]=$decoded;
            $race=$GLOBALS['r1675_next_race'];
            if($race!=='' && (($race==='before-index-commit' && str_starts_with($sql,'UPDATE '))
                || ($race==='before-index-delete' && str_starts_with($sql,'DELETE ')
                    && ($args[0]??'')==='sabri_public_experience_timeline_index_lock')
                || ($race==='before-upgrade-delete' && str_starts_with($sql,'DELETE ')
                    && ($args[0]??'')==='sabri_public_experience_upgrade_lock')
            )) {
                $GLOBALS['r1675_next_race']='';
                if($race==='before-index-commit'||$race==='before-index-delete'){
                    $GLOBALS['r1675_options']['sabri_public_experience_timeline_index_lock']=[
                        'generation'=>'new-generation','expires'=>time()+300,
                    ];
                    if($race==='before-index-commit'){
                        $GLOBALS['r1675_options']['sabri_public_experience_timeline_index']=[
                            'status'=>'running','generation'=>'new-generation'
                        ];
                    }
                } else {
                    $GLOBALS['r1675_options']['sabri_public_experience_upgrade_lock']=[
                        'token'=>'new-upgrader','created_at'=>time(),
                    ];
                }
            }
            if(str_starts_with($sql,'DELETE ')){
                [$name,$expected]=$args;
                if(!array_key_exists($name,$GLOBALS['r1675_options'])){return 0;}
                if(maybe_serialize($GLOBALS['r1675_options'][$name])!==$expected){return 0;}
                unset($GLOBALS['r1675_options'][$name]);return 1;
            }
            if(str_starts_with($sql,'UPDATE ')){
                [$lease_name,$new_index,$index_name,$old_index,$old_lease]=$args;
                if(!isset($GLOBALS['r1675_options'][$index_name],$GLOBALS['r1675_options'][$lease_name])
                    || maybe_serialize($GLOBALS['r1675_options'][$index_name])!==$old_index
                    || maybe_serialize($GLOBALS['r1675_options'][$lease_name])!==$old_lease){return 0;}
                $GLOBALS['r1675_options'][$index_name]=unserialize($new_index,['allowed_classes'=>false]);
                return 1;
            }
            return false;
        }
    }
    $wpdb=new R1675FakeWpdb();
    require_once dirname(__DIR__).'/includes/class-plan-completion.php';
    require_once dirname(__DIR__).'/includes/class-upgrade-manager.php';
    $file25=new \Sabri\PublicExperience\Plan_Completion();
    $rebuild=new \ReflectionMethod($file25,'rebuild_index');$rebuild->setAccessible(true);
    $problems=[];$check=static function(bool $yes,string $why)use(&$problems):void{
        if(!$yes){$problems[]=$why;}
    };
    $reset=static function():void{
        $GLOBALS['r1675_options']=[];$GLOBALS['r1675_actions']=[];
        $GLOBALS['r1675_next_race']='';$GLOBALS['r1675_query_count']=0;
    };

    $reset();
    $success=$rebuild->invoke($file25);
    $check(($success['status']??null)==='complete','Atomic option CAS: normal rebuild completes.');
    $check($GLOBALS['r1675_query_count']>=2,'Normal rebuild must run conditional SQL update and DELETE.');
    $check(!isset($GLOBALS['r1675_options']['sabri_public_experience_timeline_index_lock']),
        'Normal owner releases its lease after persisted index commit.');

    $reset();
    $GLOBALS['r1675_next_race']='before-index-commit';
    $superseded=$rebuild->invoke($file25);
    $check(($superseded['status']??null)==='failed'
        && ($superseded['error_code']??null)==='index_state_persistence_failed',
        'Race after PHP owner-read but before SQL commit must not report index complete.');
    $check(($GLOBALS['r1675_options']['sabri_public_experience_timeline_index']['generation']??null)==='new-generation',
        'Atomic SQL join compare must retain the newer index state.');
    $check(($GLOBALS['r1675_options']['sabri_public_experience_timeline_index_lock']['generation']??null)==='new-generation',
        'Stale finalizer must not delete the new owner after CAS failure.');

    $reset();
    $old=['generation'=>'expired-worker','expires'=>time()-1];
    $GLOBALS['r1675_options']['sabri_public_experience_timeline_index_lock']=$old;
    $GLOBALS['r1675_next_race']='before-index-delete';
    $stale=$rebuild->invoke($file25);
    $check(($stale['status']??null)==='busy',
        'Atomic DELETE race before stale lease reclaim must return busy.');
    $check(($GLOBALS['r1675_options']['sabri_public_experience_timeline_index_lock']['generation']??null)==='new-generation',
        'Expired owner cannot erase a new lease during conditional SQL DELETE.');

    $reset();
    $GLOBALS['r1675_options']['sabri_public_experience_schema_version']='1';
    $GLOBALS['r1675_options']['sabri_public_experience_upgrade_lock']=[
        'token'=>'old-upgrader','created_at'=>time()-1000
    ];
    $GLOBALS['r1675_next_race']='before-upgrade-delete';
    \Sabri\PublicExperience\Upgrade_Manager::maybe_upgrade();
    $check(($GLOBALS['r1675_options']['sabri_public_experience_upgrade_lock']['token']??null)==='new-upgrader',
        'Upgrade stale lock reclaim must not remove the newer worker.');
    $check(($GLOBALS['r1675_options']['sabri_public_experience_schema_version']??null)==='1',
        'Upgrade must not start when its conditional lease acquire loses a race.');

    if($problems!==[]){
        fwrite(STDERR,"FAILED File25 R1675–1682\n- ".implode("\n- ",$problems)."\n");
        exit(1);
    }
    echo "PASS File25 R1675–1682: SQL CAS race injections preserve replacement index and upgrade lease.\n";
}