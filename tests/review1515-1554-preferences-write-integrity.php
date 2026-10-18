<?php
declare(strict_types=1);
/** File25 R1518-1524: real File25 preference writes under simulated WP storage failure. */
if(!defined('ABSPATH')){define('ABSPATH',__DIR__.'/fixtures/');}
$GLOBALS['r1518_options']=[];
$GLOBALS['r1518_failed']='';
$GLOBALS['r1518_events']=[];
function get_option(string $key,mixed $default=false):mixed {
    return array_key_exists($key,$GLOBALS['r1518_options'])?$GLOBALS['r1518_options'][$key]:$default;
}
function update_option(string $key,mixed $value,mixed $autoload=null):bool {
    if($key===$GLOBALS['r1518_failed']){return false;}
    $GLOBALS['r1518_options'][$key]=$value;return true;
}
function add_option(string $key,mixed $value,string $deprecated='',mixed $autoload=null):bool {
    if(array_key_exists($key,$GLOBALS['r1518_options'])){return false;}
    $GLOBALS['r1518_options'][$key]=$value;return true;
}
function get_transient(string $key):mixed {return false;}
function wp_json_encode(mixed $data,int $flags=0):string|false{return json_encode($data,$flags);}
function get_current_user_id():int{return 11;}
function wp_generate_uuid4():string{return 'aaaaaaaa-bbbb-4ccc-8ddd-ffffffffffff';}
function do_action(string $hook,mixed ...$values):void {
    $GLOBALS['r1518_events'][]=[$hook,$values];
}
class WP_REST_Request {
    public function __construct(private array $body=[],private array $headers=[]){}
    public function get_header(string $key):string{return (string)($this->headers[$key]??'');}
    public function get_json_params():array{return $this->body;}
}
class WP_REST_Response {
    private array $headers=[];
    public function __construct(private mixed $data,private int $status){}
    public function get_status():int{return $this->status;}
    public function get_data():mixed{return $this->data;}
    public function get_headers():array{return $this->headers;}
    public function header(string $name,string $value):void{$this->headers[$name]=$value;}
}
require_once dirname(__DIR__).'/includes/class-plan-completion.php';
$service=new \Sabri\PublicExperience\Plan_Completion();
$method=new ReflectionMethod($service,'write_preferences');
$method->setAccessible(true);
$fail=[];$check=static function(bool $ok,string $msg)use(&$fail):void{if(!$ok){$fail[]=$msg;}};
$make_state=static function():void{
    $GLOBALS['r1518_options']=[
        'sabri_public_experience_preferences'=>['public_visibility'=>true,'public_metrics'=>true],
        'sabri_public_experience_preferences_meta'=>['revision'=>5,'updated_at_utc'=>'2026-10-01 00:00:00','source'=>'seed','hash'=>str_repeat('a',64)],
        'sabri_public_experience_preferences_history'=>[],
    ];
    $GLOBALS['r1518_events']=[];
};
foreach(['sabri_public_experience_preferences','sabri_public_experience_preferences_meta','sabri_public_experience_preferences_history'] as $blocked) {
    $make_state();
    $before=$GLOBALS['r1518_options'];
    $GLOBALS['r1518_failed']=$blocked;
    $result=$method->invoke($service,['public_visibility'=>false],true,5,'rest');
    $check(($result['status']??'')==='failed' && ($result['compensated']??null)===true,
        "Blocked $blocked should report failed, compensated instead of updated.");
    $check($GLOBALS['r1518_options']===$before,
        "Blocked $blocked must restore initial owned option/journal records.");
    $updated=array_filter($GLOBALS['r1518_events'],
        static fn(array $event):bool=>$event[0]==='sabri_public_experience/preferences_updated');
    $check($updated===[],"Blocked $blocked must not fire success event.");
    $found=array_filter($GLOBALS['r1518_events'],
        static fn(array $event):bool=>$event[0]==='sabri_public_experience/preferences_commit_failed');
    $check($found!==[],"Blocked $blocked must emit bounded failure event.");
}
$make_state();
$GLOBALS['r1518_failed']='';
$success=$method->invoke($service,['public_visibility'=>false],true,5,'rest');
$check(($success['status']??'')==='updated'&&($success['revision']??null)===6,
    'Committed preference mutation should advance revision exactly once.');
$check(($GLOBALS['r1518_options']['sabri_public_experience_preferences']['public_visibility']??null)===false,
    'Committed privacy opt-out must be stored.');
$check(($GLOBALS['r1518_options']['sabri_public_experience_preferences_meta']['revision']??null)===6,
    'Committed revision metadata must agree with preference write.');
$check(count($GLOBALS['r1518_options']['sabri_public_experience_preferences_history'])===1,
    'One prior revision must be preserved in history.');
$make_state();
$GLOBALS['r1518_failed']='';
$bad=$service->rest_preferences(new WP_REST_Request(
    ['public_visibility'=>false,'revision'=>'malformed'],
    ['Idempotency-Key'=>'r1518-invalid']
));
$check($bad->get_status()===400 && ($bad->get_data()['code']??'')==='invalid_revision',
    'Explicit malformed REST revision must be rejected with 400.');
$check(($GLOBALS['r1518_options']['sabri_public_experience_preferences']['public_visibility']??null)===true,
    'Invalid revision must not change public visibility.');
$make_state();
$GLOBALS['r1518_failed']='sabri_public_experience_preferences';
$api=$service->rest_preferences(new WP_REST_Request(
    ['public_visibility'=>false,'revision'=>5],
    ['Idempotency-Key'=>'r1518-failure']
));
$check($api->get_status()===503 && ($api->get_data()['code']??'')==='preferences_persistence_failed',
    'Failed REST write must return retryable 503 rather than 200.');
$make_state();$GLOBALS['r1518_failed']='';
$conflict=$method->invoke($service,['public_visibility'=>false],true,4,'rest');
$check(($conflict['status']??'')==='conflict','Valid stale revisions still require 409/conflict.');
if($fail!==[]){fwrite(STDERR,"FAIL R1518-1524:\n- ".implode("\n- ",$fail)."\n");exit(1);}
echo "PASS R1518–1524: preference/history/meta failures compensated and malformed revisions denied.\n";