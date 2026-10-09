<?php
declare(strict_types=1);
$root=dirname(__DIR__);$f=[];$c=static function(bool $x,string $m)use(&$f){if(!$x)$f[]=$m;};$r=static function(string $p)use($root,&$f):string{$v=@file_get_contents($root.'/'.$p);if(!is_string($v)){$f[]='Unreadable '.$p;return '';}return $v;};
$ledger=json_decode($r('config/review715-734-provider-contract-rebase-ledger.json'),true);
$c(($ledger['review_range']??null)===['first'=>715,'last'=>734,'count'=>20],'review range');
$c(($ledger['defect_rounds']??null)===range(717,731),'defect rounds');
$c(($ledger['clean_rounds']??null)===[715,716,732,733,734],'clean rounds');
foreach(['hostinger_staging_accepted','founder_staging_acceptance','production_accepted','live_deployed','operational','exact_deployed_code_verified','db_version_verified_live','migration_state_verified_live'] as $g)$c(($ledger['external_truth'][$g]??null)===false,'external gate '.$g);
$contract=$r('includes/contracts/interface-timeline-provider.php');
foreach(['get_provider_id','get_provider_version','is_available','get_maturity_level','get_public_author_items','normalize_public_item','get_canonical_url','get_visibility_state','get_public_actions','get_public_metrics','get_correction_state','register_sync_events','get_health_status'] as $m)$c(str_contains($contract,'function '.$m.'('),'contract '.$m);
foreach(['includes/providers/class-file-21-provider.php','includes/providers/class-wordpress-posts-provider.php'] as $p){$s=$r($p);foreach(['normalize_public_item','get_canonical_url','get_visibility_state','get_public_actions','get_public_metrics','get_correction_state','register_sync_events'] as $m)$c(str_contains($s,'function '.$m.'('),$p.' '.$m);}
$registry=$r('includes/class-timeline-registry.php');$c(str_contains($registry,'$provider->register_sync_events();'),'sync registration');
$service=$r('includes/class-timeline-service.php');
foreach(['most_viewed','most_saved','review_state','source_state','public_metric_scores','privacy_safe','available_metric_sorts'] as $m)$c(str_contains($service,$m),'service '.$m);
$c(str_contains($service,'MAX_CANDIDATES_PER_PROVIDER'),'current-main safety cap preserved');
$c(str_contains($service,'provider_count'),'multi-provider pagination preserved');
$c(str_contains($service,'PHP_INT_MAX'),'overflow guard preserved');
$template=$r('templates/partials/timeline.php');
foreach(['Review state','Source state','Most viewed','Most saved','Load More','spux-pagination__next-fallback'] as $m)$c(str_contains($template,$m),'template '.$m);
$rest=$r('includes/class-rest-controller.php');foreach(['most_viewed','most_saved',"'review_state' =>","'source_state' =>",'available_metric_sorts'] as $m)$c(str_contains($rest,$m),'REST '.$m);
$renderer=$r('includes/class-profile-renderer.php');foreach(['timeline_review_state','timeline_source_state','timeline_metric_sorts'] as $m)$c(str_contains($renderer,$m),'renderer '.$m);
$deps=json_decode($r('config/staging-dependencies.json'),true);$mods=[];foreach((array)($deps['modules']??[])as$m)if(is_array($m)&&isset($m['file']))$mods[(int)$m['file']]=$m;
$c(($mods[7]['current_repository_head']??'')==='2f4a89707724fd2b9946600afe10ddab27ec3c2d','File07 head');
$c(($mods[7]['current_source_version_observed']??'')==='1.2.1','File07 version');
$c(($deps['file25_review715_734_audit']['baseline_main_sha']??'')==='347a4ff4d4c233c5ea6cd82c7786ee5398ea9d1e','audit baseline');
$c(($deps['file25_review715_734_audit']['baseline_main_ci']['run_number']??null)===1837,'audit baseline CI');
$plan=json_decode($r('config/staging-test-plan.json'),true);$ids=array_map(static fn($x)=>(string)($x['id']??''),(array)($plan['scenarios']??[]));foreach(['timeline-provider-plan-contract','timeline-metric-review-source-refinements']as$id)$c(in_array($id,$ids,true),'scenario '.$id);
if($f){fwrite(STDERR,"FAILED Reviews 715-734\n- ".implode("\n- ",$f)."\n");exit(1);}echo "PASS: File 25 Reviews 715-734 provider-contract rebase corrections preserved\n";
