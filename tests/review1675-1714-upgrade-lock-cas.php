<?php
declare(strict_types=1);
/** File25 upgrade-lease SQL CAS regression on consolidated PR37 parent. */
namespace Sabri\PublicExperience {
    final class Profile_Router {public static function flush():void{++$GLOBALS['r1714_flush'];}}
    final class Safe_Mode {public static function enable(string $reason,\Throwable $e):void{$GLOBALS['r1714_safe'][]=$reason;}}
}
namespace {
    if(!defined('ABSPATH')){define('ABSPATH',__DIR__.'/fixtures/');}
    define('SABRI_PUBLIC_EXPERIENCE_SCHEMA_VERSION','2');
    define('SABRI_PUBLIC_EXPERIENCE_VERSION','0.15.0');
    $GLOBALS['r1714_opts']=[];$GLOBALS['r1714_flush']=0;$GLOBALS['r1714_safe']=[];
    $GLOBALS['r1714_race']=false;$GLOBALS['r1714_sql_queries']=0;$GLOBALS['r1714_sequence']=0;
    function maybe_serialize(mixed $v):string{return is_array($v)||is_object($v)?serialize($v):(string)$v;}
    function get_option(string $k,mixed $fallback=false):mixed {
        return array_key_exists($k,$GLOBALS['r1714_opts'])?$GLOBALS['r1714_opts'][$k]:$fallback;
    }
    function add_option(string $k,mixed $v,string $deprec='',mixed $autoload=null):bool {
        if(array_key_exists($k,$GLOBALS['r1714_opts'])){return false;}
        $GLOBALS['r1714_opts'][$k]=$v;return true;
    }
    function update_option(string $k,mixed $v,mixed $autoload=null):bool{$GLOBALS['r1714_opts'][$k]=$v;return true;}
    function delete_option(string $k):bool {if(!array_key_exists($k,$GLOBALS['r1714_opts'])){return false;}unset($GLOBALS['r1714_opts'][$k]);return true;}
    function wp_cache_delete(string $k,string $group=''):bool{return true;}
    function wp_generate_uuid4():string{return 'r1714-upgrader-'.(++$GLOBALS['r1714_sequence']);}
    final class R1714Wpdb {
        public string $options='wp_options';
        public function prepare(string $sql,mixed ...$params):string{return base64_encode(serialize([$sql,$params]));}
        public function query(string $sql):int|false {
            ++$GLOBALS['r1714_sql_queries'];
            $parts=unserialize(base64_decode($sql,true),['allowed_classes'=>false]);
            if(!is_array($parts)){return false;}
            [$statement,$args]=$parts;
            if(!str_starts_with($statement,'DELETE ')){return false;}
            if($GLOBALS['r1714_race']){
                $GLOBALS['r1714_race']=false;
                $GLOBALS['r1714_opts']['sabri_public_experience_upgrade_lock']=[
                    'token'=>'new-owner-after-read','created_at'=>time(),
                ];
            }
            [$key,$expected]=$args;
            if(!isset($GLOBALS['r1714_opts'][$key])||maybe_serialize($GLOBALS['r1714_opts'][$key])!==$expected){return 0;}
            unset($GLOBALS['r1714_opts'][$key]);return 1;
        }
    }
    $wpdb=new R1714Wpdb();
    require_once dirname(__DIR__).'/includes/class-upgrade-manager.php';
    $fail=[];$check=static function(bool $ok,string $reason)use(&$fail):void{if(!$ok){$fail[]=$reason;}};
    $GLOBALS['r1714_opts']=[
        'sabri_public_experience_schema_version'=>'1',
        'sabri_public_experience_upgrade_lock'=>['token'=>'expired-upgrader','created_at'=>time()-1200],
    ];
    $GLOBALS['r1714_race']=true;
    \Sabri\PublicExperience\Upgrade_Manager::maybe_upgrade();
    $check(($GLOBALS['r1714_opts']['sabri_public_experience_upgrade_lock']['token']??null)==='new-owner-after-read',
        'Expired worker must not erase replacement upgrade lease in SQL race.');
    $check(($GLOBALS['r1714_opts']['sabri_public_experience_schema_version']??null)==='1',
        'Upgrade must not execute after a failed atomic lock reclamation.');
    $check($GLOBALS['r1714_sql_queries']===1,'Upgrade stale lease requires SQL conditional delete.');

    $GLOBALS['r1714_opts']['sabri_public_experience_upgrade_lock']=[
        'token'=>'expired-worker','created_at'=>time()-1200,
    ];
    \Sabri\PublicExperience\Upgrade_Manager::maybe_upgrade();
    $check(($GLOBALS['r1714_opts']['sabri_public_experience_schema_version']??null)==='2'
        && ($GLOBALS['r1714_opts']['sabri_public_experience_runtime_version']??null)==='0.15.0',
        'After safe claim upgrade stores exact target schema and runtime versions.');
    $check(!isset($GLOBALS['r1714_opts']['sabri_public_experience_upgrade_lock']),
        'Upgrade releases precisely its own lease using the conditional DELETE.');
    $check($GLOBALS['r1714_sql_queries']===3,
        'Successful stale reclaim and owned cleanup both require conditional SQL.');
    $check($GLOBALS['r1714_safe']===[], 'Successful upgrade retry must not enter safe mode.');
    if($fail!==[]){fwrite(STDERR,"FAILED File25 R1680-1682:\n- ".implode("\n- ",$fail)."\n");exit(1);}
    echo "PASS File25 R1680–1682: conditional SQL upgrading lease reclaim and release under replacement race.\n";
}