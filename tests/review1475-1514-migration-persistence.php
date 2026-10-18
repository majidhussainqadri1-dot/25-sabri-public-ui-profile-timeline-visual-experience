<?php
declare(strict_types=1);
/** File25 R1475–1482: actual migration inventory and persistence-failure behavior. */
namespace Sabri\PublicExperience {
    final class Profile_Router {
        public static function flush(): void { $GLOBALS['r1475_flushes']++; }
    }
}
namespace {
    if (! defined('ABSPATH')) { define('ABSPATH', __DIR__ . '/fixtures/'); }
    $GLOBALS['r1475_options'] = [];
    $GLOBALS['r1475_failed_option'] = '';
    $GLOBALS['r1475_flushes'] = 0;
    function get_option(string $key, mixed $default=false): mixed {
        return array_key_exists($key, $GLOBALS['r1475_options']) ? $GLOBALS['r1475_options'][$key] : $default;
    }
    function update_option(string $key, mixed $value, mixed $autoload=null): bool {
        if ($key === $GLOBALS['r1475_failed_option']) { return false; }
        $GLOBALS['r1475_options'][$key] = $value;
        return true;
    }
    function wp_json_encode(mixed $value, int $flags=0): string|false {
        return json_encode($value, $flags);
    }
    function home_url(string $path=''): string { return 'https://example.test' . ($path===''?'':$path); }
    require_once dirname(__DIR__) . '/includes/class-public-url.php';
    require_once dirname(__DIR__) . '/includes/class-plan-completion.php';
    $class = \Sabri\PublicExperience\Plan_Completion::class;
    $service = new $class();
    $validate = new \ReflectionMethod($class, 'validated_redirect_map');
    $validate->setAccessible(true);
    $migration = new \ReflectionMethod($class, 'migration');
    $migration->setAccessible(true);
    $fail = [];
    $check = static function (bool $good, string $reason) use (&$fail): void {
        if (! $good) { $fail[] = $reason; }
    };

    $many = [];
    for ($i = 0; $i < 501; $i++) {
        $many[] = ['from' => '/legacy-' . $i, 'to' => 'https://example.test/new-' . $i, 'status' => 301];
    }
    $overflow = $validate->invoke(null, $many);
    $check(($overflow['invalid'] ?? 0) > 0 && ($overflow['map'] ?? null) === [],
        '501-entry migration must be BLOCKED, not approve silent 500-entry subset.');
    $valid = $validate->invoke(null, array_slice($many, 0, 500));
    $check(($valid['invalid'] ?? -1) === 0 && ($valid['collisions'] ?? -1) === 0
        && count((array) ($valid['map'] ?? [])) === 500,
        '500 valid redirects should remain accepted under configured limit.');
    $old = [['from'=>'/old/', 'to'=>'https://example.test/old/', 'status'=>301]];
    $new = [['from'=>'/new/', 'to'=>'https://example.test/new/', 'status'=>301]];
    $journal = [
        'status'=>'ready', 'requested_action'=>'dry-run',
        'redirect_map'=>$new, 'map_checksum'=>hash('sha256', json_encode($new)),
        'collision_count'=>0, 'invalid_count'=>0,
        'previous_active_map'=>$old,
    ];
    $GLOBALS['r1475_options']['sabri_public_experience_migration_state']=$journal;
    $GLOBALS['r1475_options']['sabri_public_experience_legacy_redirects']=$old;
    $GLOBALS['r1475_failed_option']='sabri_public_experience_legacy_redirects';
    $blocked=$migration->invoke($service,'executed');
    $check(($blocked['status']??'')==='blocked'
        && ($blocked['error_code']??'')==='redirect_map_persistence_failed'
        && $GLOBALS['r1475_options']['sabri_public_experience_legacy_redirects']===$old,
        'Failed redirect activation must not be falsely marked executed.');
    $check($GLOBALS['r1475_flushes']===0,
        'Failed activation must not flush rewrite rules.');
    $GLOBALS['r1475_failed_option']='';
    $executed=$migration->invoke($service,'executed');
    $check(($executed['status']??'')==='executed'
        && $GLOBALS['r1475_options']['sabri_public_experience_legacy_redirects']===$new
        && $GLOBALS['r1475_flushes']===1,
        'Successful persisted migration must be executable.');
    $GLOBALS['r1475_options']['sabri_public_experience_migration_state']=$executed;
    $GLOBALS['r1475_failed_option']='sabri_public_experience_legacy_redirects';
    $blocked_rollback=$migration->invoke($service,'rolled-back');
    $check(($blocked_rollback['status']??'')==='blocked'
        && ($blocked_rollback['error_code']??'')==='redirect_map_restore_failed'
        && $GLOBALS['r1475_options']['sabri_public_experience_legacy_redirects']===$new,
        'Failed restore must not be falsely reported as rolled-back.');
    $GLOBALS['r1475_failed_option']='';
    $rollback=$migration->invoke($service,'rolled-back');
    $check(($rollback['status']??'')==='rolled-back'
        && $GLOBALS['r1475_options']['sabri_public_experience_legacy_redirects']===$old,
        'Successful rollback must restore previous active redirect map.');

    // The admin UI must read back the persisted migration journal before
    // claiming success, including when active redirects already changed.
    $source=file_get_contents(dirname(__DIR__).'/includes/class-plan-completion.php');
    $check(is_string($source)
        && str_contains($source, 'persist_migration_result(')
        && str_contains($source, "\$recorded !== \$result")
        && str_contains($source, "\$this->redirect_admin('migration_blocked');"),
        'Admin migration status must use a persisted-journal failure gate.');
    if ($fail!==[]) {
        fwrite(STDERR,"FAIL R1475–1482:\n- ".implode("\n- ",$fail)."\n");
        exit(1);
    }
    echo "PASS R1475–1482: exact 500-entry limit, failed writes and rollback journal guards.\n";
}