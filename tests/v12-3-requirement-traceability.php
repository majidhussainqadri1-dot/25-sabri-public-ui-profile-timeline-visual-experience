<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$source=$root.'/config/v12-3-file25-requirement-traceability.json';
$register=json_decode((string)file_get_contents($source),true,512,JSON_THROW_ON_ERROR);
if(($register['source_only']??false)!==true || ($register['full_site_acceptance']??true)!==false ||
 ($register['installed_deployed_code_verified']??true)!==false){
 throw new RuntimeException('Requirement register falsely claims deployed/stage acceptance');
}
$required=['F25-CEN-01','F25-CEN-02','V12-012','V12-013','M01','M02','M03','QA-15','QA-16','QA-17','AJ-39','UX-01','UX-12','CEN-REL-001'];
$seen=[];
foreach(($register['records']??[]) as $row){
 $id=$row['id']??'';if(!is_string($id)||$id===''||isset($seen[$id])){throw new RuntimeException('Duplicate/invalid ID');}$seen[$id]=true;
 if(!in_array(($row['status']??''),($register['allowable_statuses']??[]),true)){throw new RuntimeException('Unapproved status '.$id);}
 if(empty($row['owner'])||!is_array($row['owner'])||empty($row['acceptance'])||empty($row['remaining'])){throw new RuntimeException('Missing owner/criterion/remaining for '.$id);}
 foreach(($row['sources']??[]) as $relative){
  if(!is_string($relative)||str_contains($relative,'..')||str_starts_with($relative,'/')||!is_file($root.'/'.$relative)){
   throw new RuntimeException('Unverified source path for '.$id.': '.(string)$relative);
  }
 }
}
sort($required);$actual=array_keys($seen);sort($actual);
if($actual!==$required || ($register['relevant_identifiers_count']??0)!==count($required)){throw new RuntimeException('Scope mismatch');}
if(($register['records'][0]['status']??'')==='live_verified'){throw new RuntimeException('No live verification exists');}
echo 'File25 V12.3: '.count($required)." scoped requirement-evidence rows validated (source only, no WCAG/staging/live acceptance)\n";
