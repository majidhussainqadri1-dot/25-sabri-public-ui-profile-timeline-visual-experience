<?php
declare(strict_types=1);
/** Reviews 1035–1074: real Native_Integration File00/File03 visibility gate. */
if (! defined('ABSPATH')) { define('ABSPATH', __DIR__ . '/fixtures/'); }
define('SMC_VERSION','1.2.38');
define('SMC_CONTRACT_VERSION','1.2.3');
define('SPD_VERSION','1.2.0-rc2');
define('SPD_CONTRACT_VERSION','1.4.0');
$GLOBALS['f25_r1035_visibility']='public';
class SMC_Contracts {
    public static function assertions(int $id): array {
        return [
            'contract_version'=>'1.2.3','user_id'=>$id,
            'account_class'=>'member','membership_type'=>'member','status'=>'approved',
            'approved'=>true,'eligible'=>true,'suspended'=>false,
            'public_profile_allowed'=>true
        ];
    }
}
function smc_founder_user_id(): int { return 1; }
function smc_is_founder(int $id): bool { return false; }
function spd_get_profile_contract_manifest(): array {
    return ['owner_key'=>'file03','contract_version'=>'1.4.0',
        'queries'=>['get_public_profile'=>'spd_get_public_profile']];
}
function spd_get_public_profile(int $id,int $viewer): array {
    return ['contract_version'=>'1.4.0','profile_type'=>'member',
        'state'=>'public','canonical_url'=>'https://example.test/members/member-42/',
        'public_id'=>'p-42','display_name'=>'Owner-approved member'];
}
function apply_filters(string $hook,mixed $value,mixed ...$args): mixed {
    return $hook==='sabri_public_experience/profile_visibility'
        ? $GLOBALS['f25_r1035_visibility'] : $value;
}
function sanitize_key(string $value): string {
    return (string) preg_replace('/[^a-z0-9_-]/','',strtolower($value));
}
function sanitize_textarea_field(string $value): string { return trim(strip_tags($value)); }
function home_url(string $path=''): string {
    return 'https://example.test' . ($path===''?'':$path);
}
function wp_parse_url(string $url,int $part=-1): mixed {
    return parse_url($url,$part);
}
require_once dirname(__DIR__) . '/includes/class-native-integration.php';
$native = new \Sabri\PublicExperience\Native_Integration();
$errors=[];
$check=static function(bool $ok,string $reason)use(&$errors):void{
    if(!$ok){$errors[]=$reason;}
};
$check($native->public_visibility(42)==='public',
    'Valid File00+File03 owner-approved public profile should remain visible.');
$GLOBALS['f25_r1035_visibility']='members';
$check($native->public_visibility(42)==='members',
    'An explicitly more restricted members-only visibility must be honored.');
$GLOBALS['f25_r1035_visibility']='private';
$check($native->public_visibility(42)==='private',
    'A valid privacy revocation must remain private.');
foreach (['invalid','', 'PUBLIC', false, null, 1, ['public'], new stdClass()] as $invalid) {
    $GLOBALS['f25_r1035_visibility']=$invalid;
    $check($native->public_visibility(42)==='private',
        'Malformed privacy-filter response must never restore public: '.get_debug_type($invalid));
}
if($errors!==[]){
    fwrite(STDERR,"FAILED R1035–1074 File03 native visibility fail-closed\n- ".implode("\n- ",$errors)."\n");
    exit(1);
}
echo "PASS R1035–1074: File03 public eligibility with invalid privacy restrictions fails closed\n";
