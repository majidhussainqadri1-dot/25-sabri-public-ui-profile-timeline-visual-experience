<?php
declare(strict_types=1);
/** File25 R1755–1766 — plan-ordered legacy redirect validation and runtime. */
if (!defined('ABSPATH')) { define('ABSPATH', __DIR__ . '/fixtures/'); }
$GLOBALS['r1755_options'] = [];
$GLOBALS['r1755_redirect_count'] = 0;
function get_option(string $key, mixed $default=false): mixed {
    return array_key_exists($key,$GLOBALS['r1755_options'])?$GLOBALS['r1755_options'][$key]:$default;
}
function home_url(string $path=''): string { return 'https://example.test' . ($path === '' ? '' : $path); }
function is_admin(): bool { return false; }
function wp_doing_ajax(): bool { return false; }
function wp_unslash(string $value): string { return $value; }
function sanitize_key(string $value): string { return (string)preg_replace('/[^a-z0-9_-]/','',strtolower($value)); }
function wp_json_encode(mixed $value,int $flags=0):string|false {return json_encode($value,$flags);}
class File25RedirectObserved extends RuntimeException {
    public function __construct(public string $to, public int $status) { parent::__construct('intercepted redirect'); }
}
function wp_safe_redirect(string $url,int $status=302):bool {
    ++$GLOBALS['r1755_redirect_count'];
    // Test the actual public method without allowing PHP's terminating exit.
    throw new File25RedirectObserved($url,$status);
}
require_once dirname(__DIR__) . '/includes/class-public-url.php';
require_once dirname(__DIR__) . '/includes/class-plan-completion.php';
$subject=new \Sabri\PublicExperience\Plan_Completion();
$method=new ReflectionMethod($subject,'validated_redirect_map');
$method->setAccessible(true);
$errors=[];
$check=static function(bool $passed,string $reason)use(&$errors):void{if(!$passed){$errors[]=$reason;}};
$entry=static fn(string $from,string $to,int $status=301):array=>['from'=>$from,'to'=>$to,'status'=>$status];
foreach ([
    ['/old','https://example.test/old','absolute-vs-relative same path'],
    ['/old/','https://example.test/old?query=1','slash alias with query'],
    ['/old','/old?query=1','relative same path query'],
    ['/old','?query=1','query-only destination'],
    ['/wp%2dadmin/index.php','/safe/','encoded WordPress admin'],
    ['/w%70-login.php','/safe/','encoded WordPress login'],
    ['/w%2570-login.php','/safe/','double-encoded WordPress login'],
    ['/wp%2djson/wp/v2/','/safe/','encoded WordPress REST core'],
    ['/legacy/%2e%2e/wp-admin/','/safe/','encoded traversal source'],
    ['/legacy/%2funsafe','/safe/','encoded slash source'],
] as [$from,$to,$reason]) {
    $result=$method->invoke(null,[$entry($from,$to)]);
    $check(($result['invalid']??0)>0 && ($result['map']??null)===[],
        'Dry-run rejected unsafe redirect: '.$reason);
}
$valid=$method->invoke(null,[
    $entry('/legacy/','https://example.test/profile/new/'),
    $entry('/topic-old','/topic-new/?from=old',308),
]);
$check(($valid['invalid']??-1)===0 && ($valid['collisions']??-1)===0 && count($valid['map'])===2,
    'Two safe, independent same-site routes must remain valid.');
$aliasCollision=$method->invoke(null,[
    $entry('/legacy','/new-a'),
    $entry('/legacy/','/new-b'),
]);
$check(($aliasCollision['collisions']??0)>0,'Slash-only aliases cannot be duplicate source entries.');
$chain=$method->invoke(null,[
    $entry('/legacy','/stage'),
    $entry('/stage','/new'),
]);
$check(($chain['collisions']??0)>0,'Multi-hop redirects still denied.');
$oversized=array_fill(0,501,$entry('/a','/b'));
$check(($method->invoke(null,$oversized)['invalid']??0)>0,
    '501-entry inventories must still fail closed.');
$run=static function(string $uri,array $map)use($subject):?File25RedirectObserved {
    $GLOBALS['r1755_options']['sabri_public_experience_legacy_redirects']=$map;
    $_SERVER['REQUEST_URI']=$uri;
    try { $subject->apply_legacy_redirects(); } catch (File25RedirectObserved $observed) {return $observed;}
    return null;
};
foreach ([
    ['/wp-login.php',[$entry('/wp-login.php','/new')], 'core login active-map injection'],
    ['/w%70-login.php',[$entry('/w%70-login.php','/new')], 'encoded core login active-map injection'],
    ['/old',[$entry('/old','https://example.test/old')], 'absolute same path stored injection'],
    ['/old?x=1',[$entry('/old','/old?query=1')], 'runtime same path query'],
    ['/old',[$entry('/old','?query=1')], 'runtime query-only target'],
    ['/legacy/%2e%2e',[$entry('/legacy/%2e%2e','/safe')], 'unsafe incoming traversal'],
] as [$uri,$map,$reason]) {
    $check($run($uri,$map)===null,'Runtime invalid/stale map cannot redirect: '.$reason);
}
$tooMany=array_fill(0,501,$entry('/other','/different'));
$tooMany[500]=$entry('/old','/new');
$check($run('/old',$tooMany)===null,'Oversized active WordPress option map cannot redirect.');
foreach ([
    ['/legacy/', '/legacy', '/new',301],
    ['/legacy', '/legacy/', '/new',301],
    ['/old?source=1','/old','/new?tracking=1',308],
] as [$uri,$from,$to,$status]) {
    $out=$run($uri,[$entry($from,$to,$status)]);
    $check($out instanceof File25RedirectObserved && $out->to===$to && $out->status===$status,
        'Valid alias request should redirect once: '.$uri.' from '.$from);
}
$check($run('/not-in-map',[$entry('/old','/new')])===null,
    'Unmatched route must never redirect.');
if($errors!==[]){
    fwrite(STDERR,"FAIL File25 R1755–1766:\n- ".implode("\n- ",$errors)."\n");
    exit(1);
}
echo "PASS File25 R1755–1766: self-loops, encoded core sources, runtime corruption and slash aliases.\n";
