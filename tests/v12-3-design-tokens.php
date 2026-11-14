<?php
declare(strict_types=1);
if (! defined('ABSPATH')) { define('ABSPATH', __DIR__ . '/fixtures/'); }
require_once dirname(__DIR__) . '/includes/class-design-system.php';
$root = dirname(__DIR__);
$css = file_get_contents($root . '/assets/css/design-system.css');
$public = file_get_contents($root . '/assets/css/public.css');
$spec = json_decode((string)file_get_contents($root.'/config/v12-3-design-token-contract.json'),true,512,JSON_THROW_ON_ERROR);
if(!is_string($css)||!is_string($public)){throw new RuntimeException('Missing CSS');}
function f25map(string $s):array {preg_match_all('/(--sabri-visual-[\w-]+)\s*:\s*([^;]+);/', $s,$m,PREG_SET_ORDER);$o=[];foreach($m as $x){$o[substr($x[1],15)]=trim($x[2]);}return $o;}
preg_match('/^:root\s*\{([^}]+)\}/ms',$css,$r);
preg_match('/body\.sabri-visual-system\.sabri-shell-theme-dark\s*\{([^}]+)\}/s',$css,$d);
preg_match('/body\.sabri-visual-system\.sabri-visual-shell-degraded\s*\{([^}]+)\}/s',$css,$sys);
if(empty($r[1])||empty($d[1])||empty($sys[1])){throw new RuntimeException('Missing light/dark scope');}
foreach(['light'=>f25map($r[1]),'dark'=>f25map($d[1])] as $mode=>$actual){
 foreach($spec[$mode] as $name=>$hex){if(($actual[$name]??null)!==$hex){throw new RuntimeException("$mode $name mismatch");}}
 if($mode==='dark')foreach($spec[$mode] as $name=>$hex){if((f25map($sys[1])[$name]??null)!==$hex){throw new RuntimeException("system dark $name mismatch");}}
}
$t=Sabri\PublicExperience\Design_System::tokens();
foreach(['color-text'=>'#15251E','color-page'=>'#F4F7F5','color-border'=>'#D6E1DA','color-control-border'=>'#6B8476'] as $k=>$v){if(($t[$k]['fallback']??null)!==$v){throw new RuntimeException("PHP $k mismatch");}}
$s=Sabri\PublicExperience\Design_System::filter_shell_visual_contract([]);
if(($s['tokens']['background']??'')!=='#F4F7F5'){throw new RuntimeException('File20 visual consumer mismatch');}
if(!str_contains($css,'border: 1px solid var(--sabri-visual-control-border)')||!str_contains($public,'border-color: var(--spux-control-border)')){throw new RuntimeException('Essential control borders not migrated');}
function f25lum(string $h):float{$h=ltrim($h,'#');$x=[];foreach([0,2,4] as $i){$v=hexdec(substr($h,$i,2))/255;$x[]=($v<=.04045)?$v/12.92:(($v+.055)/1.055)**2.4;}return .2126*$x[0]+.7152*$x[1]+.0722*$x[2];}
function f25cr(string $a,string $b):float{$x=f25lum($a);$y=f25lum($b);return (max($x,$y)+.05)/(min($x,$y)+.05);}
$contrast_pairs = [
 'light' => [
  ['text','page',4.5], ['text','surface',4.5], ['muted','surface',4.5],
  ['primary','surface',4.5], ['hover','surface',4.5],
  ['primary-strong','primary-soft',4.5], ['control-border','surface',3.0],
  ['control-border','page',3.0], ['success','primary-soft',4.5],
  ['warning','surface',4.5], ['danger','surface',4.5], ['information','surface',4.5],
 ],
 'dark' => [
  ['text','page',4.5], ['text','surface',4.5], ['muted','surface',4.5],
  ['primary','page',4.5], ['hover','page',4.5],
  ['primary-strong','primary-soft',4.5], ['control-border','surface',3.0],
  ['control-border','page',3.0], ['success','page',4.5],
  ['warning','page',4.5], ['danger','page',4.5], ['information','page',4.5],
 ]
];
$contrast_count = 0;
foreach($contrast_pairs as $mode=>$pairs){
 foreach($pairs as [$foreground,$background,$min]){
  $actual=f25cr($spec[$mode][$foreground],$spec[$mode][$background]);
  if($actual<$min){throw new RuntimeException("Contrast failure $mode $foreground/$background ratio=$actual minimum=$min");}
  $contrast_count++;
 }
}
if($contrast_count!==24){throw new RuntimeException('Incomplete 24-pair contrast suite');}
foreach(['sabri-ui-page-title','sabri-ui-section-title','sabri-ui-body-text','sabri-ui-reading-width.sabri-ui-urdu-nastaliq'] as $selector) {
 if(!str_contains($css,$selector)){throw new RuntimeException('Missing V12.3 type scale CSS selector: '.$selector);}
}
$typography = $spec['typography'] ?? [];
if(($typography['reading_px']??null)!==18 || ($typography['reading_line_px']??null)!==34
   || ($typography['remote_font_required']??true)!==false
   || ($typography['requires_real_rendered_font_glyph_qa']??false)!==true){
 throw new RuntimeException('Unverified or incomplete V12.3 typography contract');
}
if(($spec['staging_accepted']??true)!==false||($spec['live_verified']??true)!==false){throw new RuntimeException('Source-only release state misrepresented');}
echo "V12.3 authored CSS/PHP + 24 contrast assertions passed (NOT staging/live)\n";
