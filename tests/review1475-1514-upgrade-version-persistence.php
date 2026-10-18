<?php
declare(strict_types=1);
/** File25 R1483–1484: do not advance runtime version after failed schema persistence. */
namespace Sabri\PublicExperience {
    final class Profile_Router {
        public static function flush(): void { $GLOBALS['r1483_flushes']++; }
    }
    final class Safe_Mode {
        public static function enable(string $reason, \Throwable $error): void {
            $GLOBALS['r1483_safe_modes'][] = [$reason, $error->getMessage()];
        }
    }
}
namespace {
    if (! defined('ABSPATH')) { define('ABSPATH', __DIR__ . '/fixtures/'); }
    if (! defined('SABRI_PUBLIC_EXPERIENCE_SCHEMA_VERSION')) {
        define('SABRI_PUBLIC_EXPERIENCE_SCHEMA_VERSION', '2');
    }
    if (! defined('SABRI_PUBLIC_EXPERIENCE_VERSION')) {
        define('SABRI_PUBLIC_EXPERIENCE_VERSION', '0.15.0');
    }
    $GLOBALS['r1483_options'] = ['sabri_public_experience_schema_version' => '1'];
    $GLOBALS['r1483_fail'] = '';
    $GLOBALS['r1483_safe_modes'] = [];
    $GLOBALS['r1483_flushes'] = 0;
    function get_option(string $key, mixed $default=false): mixed {
        return array_key_exists($key, $GLOBALS['r1483_options'])
            ? $GLOBALS['r1483_options'][$key] : $default;
    }
    function add_option(string $key, mixed $value, string $deprecated='', mixed $autoload=null): bool {
        if (array_key_exists($key, $GLOBALS['r1483_options'])) { return false; }
        $GLOBALS['r1483_options'][$key] = $value;
        return true;
    }
    function update_option(string $key, mixed $value, mixed $autoload=null): bool {
        if ($key === $GLOBALS['r1483_fail']) { return false; }
        $GLOBALS['r1483_options'][$key] = $value;
        return true;
    }
    function delete_option(string $key): bool {
        unset($GLOBALS['r1483_options'][$key]);
        return true;
    }
    function wp_generate_uuid4(): string { return '22222222-2222-4222-8222-222222222222'; }
    require_once dirname(__DIR__) . '/includes/class-upgrade-manager.php';
    $fail=[];
    $check=static function(bool $ok,string $msg)use(&$fail):void{if(!$ok){$fail[]=$msg;}};
    $GLOBALS['r1483_fail']='sabri_public_experience_schema_version';
    \Sabri\PublicExperience\Upgrade_Manager::maybe_upgrade();
    $check(($GLOBALS['r1483_options']['sabri_public_experience_schema_version']??'')==='1',
        'Failed schema write must not advance the installed schema.');
    $check(! isset($GLOBALS['r1483_options']['sabri_public_experience_runtime_version']),
        'Runtime version must not advance after a failed schema write.');
    $check(count($GLOBALS['r1483_safe_modes'])===1
        && $GLOBALS['r1483_safe_modes'][0][0]==='upgrade-exception',
        'Failed schema write must enter safe mode.');
    $check(! isset($GLOBALS['r1483_options']['sabri_public_experience_upgrade_lock']),
        'Failure must release owned upgrade lock.');
    $GLOBALS['r1483_fail']='sabri_public_experience_runtime_version';
    \Sabri\PublicExperience\Upgrade_Manager::maybe_upgrade();
    $check(($GLOBALS['r1483_options']['sabri_public_experience_schema_version']??'')==='2'
        && ! isset($GLOBALS['r1483_options']['sabri_public_experience_runtime_version']),
        'Failed runtime option must not be reported as persisted.');
    $check(count($GLOBALS['r1483_safe_modes'])===2,
        'Runtime write failure must also raise safe mode.');
    $check(! isset($GLOBALS['r1483_options']['sabri_public_experience_upgrade_lock']),
        'Second failure must release owned upgrade lock.');
    // Retry with the schema version not yet reached: a valid fresh upgrade
    // must persist BOTH schema and runtime versions without safe mode.
    $GLOBALS['r1483_options']['sabri_public_experience_schema_version']='1';
    $GLOBALS['r1483_fail']='';
    \Sabri\PublicExperience\Upgrade_Manager::maybe_upgrade();
    $check(($GLOBALS['r1483_options']['sabri_public_experience_schema_version']??'')==='2'
        && ($GLOBALS['r1483_options']['sabri_public_experience_runtime_version']??'')==='0.15.0',
        'Successful schema upgrade must persist both versions.');
    $check(count($GLOBALS['r1483_safe_modes'])===2,
        'Successful upgrade must not create a third safe mode event.');
    if ($fail!==[]) {
        fwrite(STDERR,"FAIL R1483–1484:\n- ".implode("\n- ",$fail)."\n");
        exit(1);
    }
    echo "PASS R1483–1484: failed schema/runtime persistence fails closed, success and lock release.\n";
}