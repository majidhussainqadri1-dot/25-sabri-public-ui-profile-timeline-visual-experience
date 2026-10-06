<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/tools/build-staging-package.php';
require_once dirname(__DIR__) . '/tools/verify-staging-artifact.php';
$root=dirname(__DIR__);$failures=[];$check=static function(bool$c,string$m)use(&$failures):void{if(!$c){$failures[]=$m;}};$read=static fn(string$p):string=>(string)(file_get_contents($root.'/'.$p)?:'');
$main=$read('sabri-public-experience.php');preg_match("/define\('SABRI_PUBLIC_EXPERIENCE_VERSION',\s*'([^']+)'\)/",$main,$match);$runtime=(string)($match[1]??'');$check($runtime!=='','Runtime version missing.');
try{$payload=File25_Staging_Package_Builder::discover_payload($root);}catch(Throwable$e){$payload=[];$failures[]='Payload discovery failed: '.$e->getMessage();}
foreach(['sabri-public-experience.php','assets/css/companion-token-bridge.css','includes/class-native-integration.php','includes/class-current-companion-2026-08-10.php','config/staging-dependencies.json','config/staging-test-plan.json']as$r){$check(in_array($r,$payload,true),'Staging payload missing '.$r);}foreach($payload as$p){$check(!preg_match('#(?:^|/)(?:\.git|\.github|build|coverage|docs|node_modules|tests|tools|vendor)(?:/|$)#',$p),'Development path entered staging payload: '.$p);}
$matrix=json_decode($read('config/staging-dependencies.json'),true);$check(is_array($matrix)&&($matrix['schema_version']??null)===3&&($matrix['file']??null)===25&&($matrix['runtime_version']??'')===$runtime,'Dependency matrix identity mismatch.');$check(($matrix['environment']['live_changes_allowed']??true)===false,'Matrix must prohibit live changes.');$check(($matrix['artifact_verification']['staging_acceptance_implied']??true)===false,'Artifact verification cannot imply staging acceptance.');
$modules=[];foreach((array)($matrix['modules']??[])as$m){if(is_array($m)&&isset($m['file'])){$modules[(int)$m['file']]=$m;}}foreach([0,3,6,7,8,9,10,11,12,14,17,18,19,20,21,22,23,24,25,26]as$f){$check(isset($modules[$f]),'Dependency matrix missing File '.$f);}
$checks=[
0=>($modules[0]['reviewed_source_commit']??'')==='2fa7c022ee9cd1b65432e900579512f304532442'&&($modules[0]['reviewed_contract_version']??'')==='1.2.3',
3=>($modules[3]['reviewed_source_commit']??'')==='636e3ef965423887f810718abec3cd1c11c3659d'&&($modules[3]['required_contract_version']??'')==='1.4.0',
7=>($modules[7]['reviewed_source_branch']??'')==='main'&&($modules[7]['reviewed_source_commit']??'')==='67c32ec4af45a7de6e3d9c1dbf0f8614d6b5a844'&&($modules[7]['required_contract_version']??'')==='1.2.0',
8=>($modules[8]['reviewed_source_commit']??'')==='70541974ce0ffb16aebef557c3016eb7447662f4'&&($modules[8]['required_canonical_contract_version']??'')==='1.1.0'&&($modules[8]['required_public_contract_version']??'')==='1.1.0',
9=>($modules[9]['reviewed_source_commit']??'')==='a9ab697c671129be023414f5a3c32186567cb2bf'&&($modules[9]['required_contract_version']??'')==='1.1.0',
14=>($modules[14]['reviewed_source_version']??'')==='1.4.4'&&($modules[14]['reviewed_source_branch']??'')==='main'&&($modules[14]['reviewed_source_commit']??'')==='db60c4bc5c37a5c88126b78c31b34c75236f33d7'&&($modules[14]['required_primary_color']??'')==='#087A4E',
17=>($modules[17]['reviewed_source_commit']??'')==='8ae656e51796d1f05865d8be5dca2480443d79ca',
18=>($modules[18]['reviewed_source_version']??'')==='1.2.0-RC1',
19=>($modules[19]['reviewed_source_commit']??'')==='04078025b643ab7696e4cb4e37826bf152defa18',
20=>($modules[20]['reviewed_source_commit']??'')==='8a4dbcaf4fef8e926b9b834ecfde16c21a0f00ca',
21=>($modules[21]['reviewed_source_commit']??'')==='f2eb7e95ddea327af36ea725ffb923b029f885e6',
22=>($modules[22]['reviewed_source_commit']??'')==='b7a7f2e69411cbd32f0574fd12d766fb70c01b7a',
23=>($modules[23]['reviewed_source_commit']??'')==='dcae138e6073f4d0ff596623deb05b9940b8271b',
24=>($modules[24]['reviewed_source_commit']??'')==='a5b8d49968a7a5a7d6f3f4655bea541bf38a9acb',
25=>($modules[25]['candidate_version']??'')===$runtime&&($modules[25]['canonical_primary_color']??'')==='#087A4E'&&($modules[25]['design_token_owner']??'')==='file-25'&&($modules[25]['structural_shell_owner']??'')==='file-20',
26=>($modules[26]['reviewed_source_commit']??'')==='bbea3aad466792a4a6a62b53532bbd45c7c592de'];foreach($checks as$f=>$ok){$check($ok,'Current reviewed contract mismatch for File '.$f);}foreach($modules as$m){if(isset($m['staging_status'])){$check($m['staging_status']==='pending','Source evidence must not promote staging acceptance.');}}
$plan=json_decode($read('config/staging-test-plan.json'),true);$check(is_array($plan)&&($plan['schema_version']??null)===3,'Staging test plan schema 3 required.');$ids=[];foreach((array)($plan['scenarios']??[])as$s){if(is_array($s)){$ids[]=(string)($s['id']??'');}}foreach(['file00-assertions','file03-current-public-dto','file03-future-public-experience','file03-component-provider','file03-route-parity','file07-directory-visual-contract','file08-clinic-projection','file09-doctor-decision','file14-visual-consumer','file21-current-profile-timeline-contract','file22-create-edit-contract','file23-private-management-contract','file24-current-assurance-contract','file17-native-profile-actions','file19-notification-owner','file26-search-ranking-owner','companion-token-bridge']as$id){$check(in_array($id,$ids,true),'Staging plan missing '.$id);}
$verifier=$read('tools/verify-staging-artifact.php');foreach(['File25_Staging_Artifact_Verifier','Embedded manifest or dependency matrix differs from detached evidence.','Unsafe, duplicate or symbolic entry detected in workflow artifact.','Unsafe, duplicate or symbolic entry detected in inner ZIP.','MAX_INNER_TOTAL_BYTES','staging_accepted','production_accepted']as$m){$check(str_contains($verifier,$m),'Artifact verifier marker missing: '.$m);}$builder=$read('tools/build-staging-package.php');foreach(['SOURCE_DATE_EPOCH','STAGING-MANIFEST.json','ZipArchive','verify_archive','zip_entry_is_symlink','MAX_PAYLOAD_BYTES']as$m){$check(str_contains($builder,$m),'Package builder marker missing: '.$m);}
if($failures!==[]){fwrite(STDERR,"FAILED\n- ".implode("\n- ",$failures)."\n");exit(1);}echo "PASS: File 25 current release-engineering and dependency contracts\n";
