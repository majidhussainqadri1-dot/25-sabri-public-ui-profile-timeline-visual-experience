<?php
declare(strict_types=1);
$path=dirname(__DIR__).'/config/v12-3-file25-owner-source-freeze-2026-11-14.json';
$r=json_decode((string)file_get_contents($path),true,512,JSON_THROW_ON_ERROR);
if(($r['source_only']??false)!==true || ($r['staging_native_acceptance']??true)!==false ||
   ($r['live_native_acceptance']??true)!==false || ($r['no_installed_plugin_assertions']??false)!==true){
 throw new RuntimeException('Repository data cannot be promoted to installed compatibility');
}
$expected=['03','08','09','17','20','21','24','26'];
$actual=[];$changed=0;
foreach(($r['rows']??[]) as $row){
 $id=(string)($row['file_id']??'');if($id===''||in_array($id,$actual,true)){throw new RuntimeException('Bad duplicate owner');}
 $actual[]=$id;
 if(!preg_match('/^[0-9a-f]{40}$/D',(string)($row['main_source_sha']??''))||
    !is_string($row['plugin_header_version']??null)||$row['plugin_header_version']===''){
   throw new RuntimeException('Unverified owner source header: '.$id);
 }
 foreach(['source_contract_behavior_accepted','live_parity_verified','staging_verified'] as $flag){
  if(($row[$flag]??true)!==false){throw new RuntimeException('Premature owner acceptance: '.$id);}
 }
 foreach(['installed_plugin_version','installed_schema_version'] as $field){
  if(($row[$field]??'not null')!==null){throw new RuntimeException('Invented installed version: '.$id);}
 }
 if(($row['source_drift_from_record']??'')==='ahead'){
  $changed++;
  $c=$row['comparison_from_old']??[];
  if((int)($c['ahead_commits']??0)<1 || (int)($c['changed_files']??0)<1 ||
     (int)($c['behind_commits']??-1)!==0){throw new RuntimeException('Unsupported drift counts: '.$id);}
 }
}
sort($expected);sort($actual);
if($actual!==$expected || $changed!==4 || ($r['changed_old_source_pointers_count']??null)!==4 ||
   ($r['owner_count']??null)!==8){throw new RuntimeException('W08 source owner scope mismatch');}
echo "V12.3 W08: eight native repository-only owner headers and four stale-source pointers validated; staging/live unknown\n";
