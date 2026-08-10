<?php

declare(strict_types=1);

$root=dirname(__DIR__);$failures=[];
$check=static function(bool $c,string $m)use(&$failures):void{if(!$c){$failures[]=$m;}};
$read=static function(string $p)use($root,&$failures):string{$v=@file_get_contents($root.'/'.$p);if(!is_string($v)){$failures[]='Unreadable evidence: '.$p;return '';}return $v;};
$main=$read('sabri-public-experience.php');$native=$read('includes/class-native-integration.php');$current=$read('includes/class-current-companion-2026-08-10.php');$visibility=$read('includes/class-visibility-policy.php');$repository=$read('includes/class-profile-repository.php');$router=$read('includes/class-profile-router.php');$renderer=$read('includes/class-profile-renderer.php');$cards=$read('includes/class-content-cards.php');$sections=$read('includes/class-section-service.php');$rest=$read('includes/class-rest-controller.php');$design=$read('includes/class-design-system.php');$file20=$read('includes/class-file-20-integration.php');$file24=$read('includes/class-file-24-integration.php');$admin=$read('includes/class-admin-integration.php');$component=$read('includes/class-component-api.php');$assets=$read('includes/class-assets.php');$bridge=$read('assets/css/companion-token-bridge.css');$marketplace=$read('includes/providers/class-file-18-marketplace-provider.php');$matrix=json_decode($read('config/staging-dependencies.json'),true);$plan=json_decode($read('config/staging-test-plan.json'),true);
$check(str_contains($main,"SABRI_PUBLIC_EXPERIENCE_VERSION', '0.14.0'"),'Runtime must remain 0.14.0.');
foreach(["FILE_00_MINIMUM_VERSION = '1.2.38'","FILE_00_CONTRACT_VERSION = '1.2.2'","FILE_03_MINIMUM_VERSION = '1.2.0-rc2'","FILE_03_CONTRACT_VERSION = '1.4.0'","FILE_09_MINIMUM_VERSION = '1.3.0'","FILE_09_CONTRACT_VERSION = '1.1.0'","FILE_08_MINIMUM_VERSION = '1.2.0'","FILE_08_CANONICAL_CONTRACT = '1.1.0'","FILE_08_PUBLIC_PROJECTION_CONTRACT = '1.0.0'",'WCA_Contracts::PUBLIC_CLINIC_CONTRACT_VERSION','swc_get_public_clinic_projection',"FILE_18_MINIMUM_VERSION = '1.2.0-RC1'"] as $m){$check(str_contains($native,$m),'Authoritative native marker missing: '.$m);}
foreach(['$wpdb','SHOW TABLES','smc_get_profile','smc_clinics','calculated_age','license_expiry','SPD_Helpers::get'] as $f){$check(!str_contains($native,$f),'Forbidden native coupling remains: '.$f);}
foreach(['spd_get_personal_site_profile','sabri_file25_register_component_provider','professional_lifecycle','profile_translations','content_freshness','native_data_copy_allowed'] as $m){$check(str_contains($current,$m),'Current companion bridge missing: '.$m);}
$check(str_contains($visibility,'profile_contact($user_id, $field)'),'File 03 public contact DTO must remain authoritative.');
$check(str_contains($repository,"FOUNDER_DISPLAY_NAME = 'Dr. Allamah Majid Hussain Sabri Muhaddith Mursheed'"),'Founder spelling drift.');
$check(str_contains($repository,'Public_URL::sanitize_same_site')&&!str_contains($repository,'get_avatar_url'),'Profile media ownership boundary drift.');
$check(str_contains($router,'$non_file03_public_id'),'File 03 public-ID family must remain reserved.');
$check(str_contains($renderer,"'@type' => 'BreadcrumbList'")&&str_contains($renderer,'og:image:alt'),'Profile SEO/accessibility metadata missing.');
$check(str_contains($cards,'public static function normalize_public'),'Cards require public allowlist.');
$check(str_contains($sections,'get_public_section')&&str_contains($sections,'profile_cache_digest'),'Structured section/cache binding missing.');
foreach(["'ETag'","'Last-Modified'",'allow_public_request','sabri_public_experience/rest_rate_limit',"'status' => 429"] as $m){$check(str_contains($rest,$m),'REST contract marker missing: '.$m);}
$check(str_contains($file20,"REVIEWED_SOURCE_COMMIT = '291486b22c7ed94b8be041192375b6d9b077fac5'"),'File20 exact current contract missing.');
$check(str_contains($file24,"REVIEWED_SOURCE_COMMIT = '0be43b3f424d7b53865587b2770479ca33f51a0b'"),'File24 exact current contract missing.');
$check(str_contains($design,'sabri_shell_file25_visual_contract')&&str_contains($design,"'primary_color' => '#087A4E'"),'File25 canonical visual bridge/green missing.');
$check(str_contains($admin,"SHELL_PARENT_SLUG = 'sabri-shell'"),'File25 admin must attach to File20.');
$check(str_contains($component,'Component API cannot bind a second timeline registry'),'Component API one-registry law missing.');
$check(str_contains($assets,'companion-token-bridge.css'),'Companion token bridge must load.');
$check(!str_contains(strtolower($bridge),'#ff8a1f')&&!str_contains(strtolower($bridge),'#15803d'),'Stale primary color reintroduced.');
foreach(['$wpdb','SMP_DB::table','SELECT p.*'] as $f){$check(!str_contains($marketplace,$f),'Marketplace direct SQL forbidden: '.$f);}

$check(is_array($matrix)&&($matrix['schema_version']??null)===3,'Staging dependency matrix schema 3 required.');$modules=[];foreach((array)($matrix['modules']??[])as$module){if(is_array($module)&&isset($module['file'])){$modules[(int)$module['file']]=$module;}}
foreach([0,3,6,7,8,9,10,11,12,14,18,20,21,22,23,24,25]as$file){$check(isset($modules[$file]),'Dependency matrix missing File '.$file.'.');}
$checks=[
0=>($modules[0]['reviewed_source_commit']??'')==='c37d0b101d0912bef1f26d0daf51a414d67907c0',
3=>($modules[3]['reviewed_source_version']??'')==='1.2.0-rc2'&&($modules[3]['required_contract_version']??'')==='1.4.0'&&($modules[3]['reviewed_source_branch']??'')==='codex/file-03-second-fresh-80-review-20260810'&&($modules[3]['reviewed_source_commit']??'')==='b862efb94be87980e96a5b864bc5c7aec49183d9',
7=>($modules[7]['reviewed_source_commit']??'')==='67c32ec4af45a7de6e3d9c1dbf0f8614d6b5a844',
8=>($modules[8]['reviewed_source_version']??'')==='1.2.0'&&($modules[8]['reviewed_schema_version']??'')==='3.1.0'&&($modules[8]['reviewed_source_commit']??'')==='cb302617d2a2def23de1883b8d9fead10bce7ef3'&&($modules[8]['required_canonical_contract_version']??'')==='1.1.0'&&($modules[8]['required_public_contract_version']??'')==='1.0.0',
9=>($modules[9]['reviewed_source_branch']??'')==='codex/file09-1.3.0-rc6-80-round-review'&&($modules[9]['reviewed_source_commit']??'')==='9103310fc93d978b6e70661f024a079fc0971003'&&($modules[9]['required_contract_version']??'')==='1.1.0',
14=>($modules[14]['reviewed_source_commit']??'')==='b9045a4229d052103a5546477f664ac88b6ff034'&&($modules[14]['required_primary_color']??'')==='#087A4E',
18=>($modules[18]['reviewed_source_version']??'')==='1.2.0-RC1',20=>($modules[20]['reviewed_source_commit']??'')==='291486b22c7ed94b8be041192375b6d9b077fac5',21=>($modules[21]['reviewed_source_commit']??'')==='afeda8742d8e1ea62254823291a66f502058989c',22=>($modules[22]['reviewed_source_commit']??'')==='c3b775b66fbbda4a9dd9891d63c08c74e2178741',23=>($modules[23]['reviewed_source_commit']??'')==='a8a8c805f4730998ccb44bd95c87591836561759',24=>($modules[24]['reviewed_source_commit']??'')==='0be43b3f424d7b53865587b2770479ca33f51a0b',25=>($modules[25]['candidate_version']??'')==='0.14.0'&&($modules[25]['canonical_primary_color']??'')==='#087A4E'&&($modules[25]['design_token_owner']??'')==='file-25'&&($modules[25]['structural_shell_owner']??'')==='file-20'];
foreach($checks as$file=>$ok){$check($ok,'Current reviewed companion mismatch for File '.$file.'.');}foreach($modules as$module){if(isset($module['staging_status'])){$check($module['staging_status']==='pending','Source evidence may not promote staging acceptance.');}}
$check(is_array($plan)&&($plan['schema_version']??null)===3,'Staging test plan schema 3 required.');$ids=[];foreach((array)($plan['scenarios']??[])as$s){if(is_array($s)){$ids[]=(string)($s['id']??'');}}
foreach(['file03-current-public-dto','file03-future-public-experience','file03-component-provider','file03-route-parity','file08-clinic-projection','file07-directory-visual-contract','file09-doctor-decision','file14-visual-consumer','file20-current-shell-contract','file21-current-profile-timeline-contract','component-api-runtime','file22-create-edit-contract','file23-private-management-contract','file24-current-assurance-contract','companion-token-bridge']as$id){$check(in_array($id,$ids,true),'Current staging scenario missing: '.$id);}
if($failures!==[]){fwrite(STDERR,"FAILED\n- ".implode("\n- ",$failures)."\n");exit(1);}echo "PASS: File 25 current central/companion contract reconciliation\n";
