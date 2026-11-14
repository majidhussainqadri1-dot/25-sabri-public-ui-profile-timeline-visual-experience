<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$data=json_decode((string)file_get_contents($root.'/config/v12-3-file25-performance-source-inventory-2026-11-14.json'),true,512,JSON_THROW_ON_ERROR);
foreach(['source_only'=>true,'initial_budgets_not_release_acceptance'=>true,'installed_staging_measured'=>false,
'actual_route_transfer_measured'=>false,'field_p75_verified'=>false,'cdn_or_server_compression_verified'=>false] as $k=>$v){
 if(($data[$k]??null)!==$v){throw new RuntimeException('Performance evidence overstated: '.$k);}
}
foreach(['lab_lcp_ms','field_lcp_p75_ms','field_inp_p75_ms','field_cls_p75'] as $key){
 if(!array_key_exists($key,$data)||$data[$key]!==null){throw new RuntimeException('Invented measured performance result: '.$key);}
}
$routes=['Home'=>[200,280],'Search'=>[225,210],'Lesson/Book'=>[275,230],
'Doctor/Appointment'=>[250,250],'Settings'=>[225,160],'Composer/Preview'=>[300,200]];
foreach(($data['route_budgets']??[]) as $entry){
 $name=$entry['name']??'';if(!isset($routes[$name])){throw new RuntimeException('Unknown route family');}
 if(($entry['html_css_js_kib']??null)!==$routes[$name][0] || ($entry['font_image_kib']??null)!==$routes[$name][1] ||
 ($entry['field_status']??'')!=='not_measured'){throw new RuntimeException('Incorrect V12.3 initial budget for '.$name);}
 unset($routes[$name]);
}
if($routes!==[]){throw new RuntimeException('Missing V12.3 route family budgets');}
$sum=0;$count=0;$seen=[];
foreach(($data['source_assets']??[]) as $a){
 $relative=$a['path']??'';
 if(!is_string($relative)||!preg_match('#^assets/(?:css|js)/[a-z0-9.-]+\.(?:css|js)$#D',$relative)||
 isset($seen[$relative])){throw new RuntimeException('Unbounded source asset path');}
 $seen[$relative]=true;$file=$root.'/'.$relative;
 if(!is_file($file)||filesize($file)!==($a['raw_bytes']??null)||hash_file('sha256',$file)!==($a['sha256']??null)){
   throw new RuntimeException('File25 binary source asset changed without source performance inventory: '.$relative);
 }
 $sum+=filesize($file);$count++;
}
$onDisk=[];
foreach(['css','js'] as $kind){foreach(glob($root.'/assets/'.$kind.'/*.'.$kind)?:[] as $file){$onDisk[]='assets/'.$kind.'/'.basename($file);}}
sort($onDisk);$inventory=array_keys($seen);sort($inventory);
if($onDisk!==$inventory || $count!==8 || $sum!==($data['source_asset_totals']['raw_bytes']??-1) ||
 $sum>($data['file25_css_js_raw_budget_bytes']??0)){
 throw new RuntimeException('Incomplete source asset inventory or too-large File25 owned CSS/JS payload');
}
echo "V12.3 W10: 8 exact-hash File25 source assets ($sum raw bytes) pass bounded source inventory, field/route performance UNVERIFIED\n";
