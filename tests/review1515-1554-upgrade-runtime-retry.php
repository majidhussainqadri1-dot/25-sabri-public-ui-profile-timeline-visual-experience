<?php
declare(strict_types=1);
/** File25 R1515-1517: retry a stale runtime marker after schema success. */
namespace Sabri\PublicExperience {
    final class Profile_Router {
        public static function flush(): void { $GLOBALS['r1515_flushes']++; }
    }
    final class Safe_Mode {
        public static function enable(string $reason, \Throwable $error): void {
            $GLOBALS['r1515_safemode'][] = [$reason,$error->getMessage()];
        }
    }
}
namespace {
    if (! defined('ABSPATH')) { define('ABSPATH', __DIR__ . '/fixtures/'); }
    define('SABRI_PUBLIC_EXPERIENCE_SCHEMA_VERSION','2');
    define('SABRI_PUBLIC_EXPERIENCE_VERSION','0.15.0');
    $GLOBALS['r1515_options']=[
        'sabri_public_experience_schema_version'=>'2',
        'sabri_public_experience_runtime_version'=>'0.14.0',
    ];
    $GLOBALS['r1515_write_fail']=false;
    $GLOBALS['r1515_flushes']=0;
    $GLOBALS['r1515_safemode']=[];
    function get_option(string $key,mixed $default=false):mixed {
        return array_key_exists($key,$GLOBALS['r1515_options'])?$GLOBALS['r1515_options'][$key]:$default;
    }
    function add_option(string $key,mixed $value,string $deprecated='',mixed $autoload=null):bool {
        if(array_key_exists($key,$GLOBALS['r1515_options'])){return false;}
        $GLOBALS['r1515_options'][$key]=$value;return true;
    }
    function update_option(string $key,mixed $value,mixed $autoload=null):bool {
        if($GLOBALS['r1515_write_fail'] && $key==='sabri_public_experience_runtime_version'){return false;}
        $GLOBALS['r1515_options'][$key]=$value;return true;
    }
    function delete_option(string $key):bool {unset($GLOBALS['r1515_options'][$key]);return true;}
    function wp_generate_uuid4():string{return '11111111-4444-4444-8444-111111111111';}
    require_once dirname(__DIR__).'/includes/class-upgrade-manager.php';
    $fail=[];$check=static function(bool $yes,string $message)use(&$fail):void{if(!$yes){$fail[]=$message;}};
    $GLOBALS['r1515_write_fail']=true;
    \Sabri\PublicExperience\Upgrade_Manager::maybe_upgrade();
    $check(get_option('sabri_public_experience_runtime_version')==='0.14.0',
        'Failed runtime version write should remain stale.');
    $check(count($GLOBALS['r1515_safemode'])===1,
        'Failed runtime-only repair must record safe-mode error.');
    $check($GLOBALS['r1515_flushes']===0,
        'Runtime marker repair must not reflush already-upgraded schema routes.');
    $check(!isset($GLOBALS['r1515_options']['sabri_public_experience_upgrade_lock']),
        'Failed attempt must release owned upgrade lock.');
    $GLOBALS['r1515_write_fail']=false;
    \Sabri\PublicExperience\Upgrade_Manager::maybe_upgrade();
    $check(get_option('sabri_public_experience_runtime_version')==='0.15.0',
        'Stale runtime marker MUST be retried even when schema already target.');
    $check($GLOBALS['r1515_flushes']===0,
        'Version metadata reconciliation must not flush rewrite rules.');
    $check(count($GLOBALS['r1515_safemode'])===1,
        'Successful retry must not create further safe-mode alerts.');
    \Sabri\PublicExperience\Upgrade_Manager::maybe_upgrade();
    $check($GLOBALS['r1515_flushes']===0 && !isset($GLOBALS['r1515_options']['sabri_public_experience_upgrade_lock']),
        'Matching schema/runtime versions must be idempotent and unlocked.');
    if($fail!==[]){fwrite(STDERR,"FAIL R1515-1517\n- ".implode("\n- ",$fail)."\n");exit(1);}
    echo "PASS R1515–1517: stale runtime marker safely recovers without schema downgrade or repeated flush.\n";
}