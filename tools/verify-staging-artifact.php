<?php

declare(strict_types=1);

/** Independent, fail-closed File 25 staging-artifact verifier. */
final class File25_Staging_Artifact_Verifier
{
    private const PACKAGE_ROOT = 'sabri-public-experience';
    private const INNER_MANIFEST = 'STAGING-MANIFEST.json';
    private const INNER_MATRIX = 'config/staging-dependencies.json';
    private const MAX_OUTER_FILES = 8;
    private const MAX_INNER_FILES = 256;
    private const MAX_OUTER_FILE_BYTES = 30_000_000;
    private const MAX_INNER_FILE_BYTES = 5_000_000;
    private const MAX_INNER_TOTAL_BYTES = 25_000_000;

    /** @return array<string,mixed> */
    public static function verify(string $artifact, string $expected_outer_sha256 = ''): array
    {
        if (! class_exists('ZipArchive')) { throw new RuntimeException('The PHP Zip extension is required to verify the staging artifact.'); }
        if ($artifact === '' || str_contains($artifact, "\0") || is_link($artifact)) { throw new InvalidArgumentException('Artifact path is invalid or symbolic.'); }
        $outer_sha256 = '';
        if (is_dir($artifact)) { $files=self::read_directory($artifact); $mode='directory'; }
        elseif (is_file($artifact)) {
            $real=realpath($artifact); if(!is_string($real)){throw new RuntimeException('Unable to resolve artifact file.');}
            $outer_sha256=(string)hash_file('sha256',$real);$expected_outer_sha256=strtolower(trim($expected_outer_sha256));
            if($expected_outer_sha256!==''&&!hash_equals($expected_outer_sha256,$outer_sha256)){throw new RuntimeException('Outer artifact SHA-256 does not match the expected digest.');}
            $files=self::read_outer_zip($real);$mode='zip';
        } else { throw new InvalidArgumentException('Artifact path does not exist.'); }
        $bundle=self::identify_bundle($files);$result=self::verify_bundle($bundle);$result['artifact_mode']=$mode;$result['outer_artifact_sha256']=$outer_sha256;return$result;
    }

    /** @return array<string,string> */
    private static function read_directory(string $directory): array
    {
        $real=realpath($directory);if(!is_string($real)){throw new RuntimeException('Unable to resolve artifact directory.');}$entries=scandir($real);if(!is_array($entries)){throw new RuntimeException('Unable to list artifact directory.');}$files=[];
        foreach($entries as$entry){if($entry==='.'||$entry==='..'){continue;}if(!self::outer_name_is_safe($entry)){throw new RuntimeException('Unsafe artifact filename: '.$entry);}$path=$real.DIRECTORY_SEPARATOR.$entry;if(is_link($path)||!is_file($path)){throw new RuntimeException('Artifact directory may contain regular files only.');}$bytes=filesize($path);if(!is_int($bytes)||$bytes<0||$bytes>self::MAX_OUTER_FILE_BYTES){throw new RuntimeException('Artifact file exceeds permitted size.');}$contents=file_get_contents($path);if(!is_string($contents)||strlen($contents)!==$bytes||isset($files[$entry])){throw new RuntimeException('Unable to uniquely read artifact file.');}$files[$entry]=$contents;}
        if($files===[]||count($files)>self::MAX_OUTER_FILES){throw new RuntimeException('Artifact directory contains an invalid number of files.');}return$files;
    }

    /** @return array<string,string> */
    private static function read_outer_zip(string $path): array
    {
        $zip=new ZipArchive();if($zip->open($path,ZipArchive::RDONLY)!==true){throw new RuntimeException('Unable to open workflow artifact.');}
        try{if($zip->numFiles<1||$zip->numFiles>self::MAX_OUTER_FILES){throw new RuntimeException('Workflow artifact contains invalid entry count.');}$files=[];for($i=0;$i<$zip->numFiles;$i++){$name=$zip->getNameIndex($i);$stat=$zip->statIndex($i);if(!is_string($name)||!is_array($stat)||!self::outer_name_is_safe($name)||isset($files[$name])||self::zip_entry_is_symlink($stat)){throw new RuntimeException('Unsafe, duplicate or symbolic entry detected in workflow artifact.');}$size=(int)($stat['size']??-1);$contents=$zip->getFromIndex($i);if($size<0||$size>self::MAX_OUTER_FILE_BYTES||!is_string($contents)||strlen($contents)!==$size){throw new RuntimeException('Unable to safely read workflow artifact entry.');}$files[$name]=$contents;}return$files;}finally{$zip->close();}
    }

    /** @param array<string,string> $files @return array<string,string> */
    private static function identify_bundle(array $files): array
    {
        $zips=[];foreach(array_keys($files)as$name){if(preg_match('/^sabri-public-experience-(\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?)\.zip$/',$name,$m)===1){$zips[$name]=$m[1];}}
        if(count($zips)!==1){throw new RuntimeException('Artifact must contain exactly one versioned File 25 plugin ZIP.');}$zip_name=(string)array_key_first($zips);$version=(string)$zips[$zip_name];$names=[$zip_name,"sabri-public-experience-$version.sha256","sabri-public-experience-$version-manifest.json",'staging-dependencies.json'];$actual=array_keys($files);sort($actual);sort($names);if($actual!==$names){throw new RuntimeException('Artifact file set does not match governed staging bundle.');}
        return['version'=>$version,'zip_name'=>$zip_name,'zip_bytes'=>$files[$zip_name],'checksum_bytes'=>$files["sabri-public-experience-$version.sha256"],'manifest_bytes'=>$files["sabri-public-experience-$version-manifest.json"],'matrix_bytes'=>$files['staging-dependencies.json']];
    }

    /** @param array<string,string> $bundle @return array<string,mixed> */
    private static function verify_bundle(array $bundle): array
    {
        if(preg_match('/^([a-f0-9]{64})  ([A-Za-z0-9._+-]+\.zip)\n?$/',$bundle['checksum_bytes'],$m)!==1||!hash_equals($bundle['zip_name'],$m[2])){throw new RuntimeException('Detached package checksum has invalid format or filename.');}$package_sha256=hash('sha256',$bundle['zip_bytes']);if(!hash_equals(strtolower($m[1]),$package_sha256)){throw new RuntimeException('Plugin ZIP SHA-256 does not match detached checksum.');}
        $manifest=self::decode_json_object($bundle['manifest_bytes'],'detached manifest');self::validate_manifest($manifest,$bundle['version']);$matrix=self::decode_json_object($bundle['matrix_bytes'],'dependency matrix');self::validate_matrix($matrix,$bundle['version']);$inner=self::verify_inner_zip($bundle['zip_bytes'],$bundle['manifest_bytes'],$manifest,$bundle['matrix_bytes']);
        return['verified'=>true,'version'=>$bundle['version'],'commit_sha'=>(string)$manifest['commit_sha'],'package_sha256'=>$package_sha256,'payload_file_count'=>$inner['payload_file_count'],'payload_bytes'=>$inner['payload_bytes'],'dependency_contract_status'=>'sixth-cycle-current-source-contracts-pending-hostinger-staging','staging_accepted'=>false,'production_accepted'=>false];
    }

    /** @param array<string,mixed> $manifest */
    private static function validate_manifest(array $manifest,string $version):void
    {
        if(($manifest['schema_version']??null)!==1||($manifest['package']??'')!==self::PACKAGE_ROOT||($manifest['file_number']??null)!==25||($manifest['version']??'')!==$version||preg_match('/^[a-f0-9]{40}$/',(string)($manifest['commit_sha']??''))!==1){throw new RuntimeException('Detached manifest identity does not match File 25.');}$epoch=$manifest['source_date_epoch']??null;if(!is_int($epoch)||$epoch<315532800||$epoch>4102444800||($manifest['generated_at_utc']??'')!==gmdate('Y-m-d\TH:i:s\Z',$epoch)){throw new RuntimeException('Detached manifest deterministic timestamp is invalid.');}$files=$manifest['files']??null;if(!is_array($files)||$files===[]||count($files)>self::MAX_INNER_FILES){throw new RuntimeException('Detached manifest contains invalid payload map.');}foreach($files as$relative=>$meta){if(!is_string($relative)||!self::relative_payload_name_is_safe($relative)||!is_array($meta)||preg_match('/^[a-f0-9]{64}$/',(string)($meta['sha256']??''))!==1||!is_int($meta['bytes']??null)||$meta['bytes']<0||$meta['bytes']>self::MAX_INNER_FILE_BYTES){throw new RuntimeException('Detached manifest contains invalid payload entry.');}}
    }

    /** @param array<string,mixed> $matrix */
    private static function validate_matrix(array $matrix,string $version):void
    {
        if(($matrix['schema_version']??null)!==3||($matrix['file']??null)!==25||($matrix['runtime_version']??'')!==$version||($matrix['environment']['live_changes_allowed']??true)!==false||($matrix['environment']['registration_disabled_required']??false)!==true||($matrix['environment']['search_indexing_disabled_required']??false)!==true){throw new RuntimeException('Staging dependency matrix identity or environment policy is invalid.');}
        $modules=[];foreach((array)($matrix['modules']??[])as$module){if(!is_array($module)||!is_int($module['file']??null)||isset($modules[$module['file']])){throw new RuntimeException('Dependency matrix contains invalid or duplicate File number.');}$modules[$module['file']]=$module;}foreach([0,3,6,7,8,9,10,11,12,14,18,20,21,22,23,24,25]as$file){if(!isset($modules[$file])){throw new RuntimeException('Staging dependency matrix is missing File '.$file.'.');}}foreach($modules as$module){if(isset($module['staging_status'])&&$module['staging_status']!=='pending'){throw new RuntimeException('Dependency matrix falsely promotes staging acceptance.');}}
        $checks=[
0=>($modules[0]['reviewed_source_commit']??'')==='c37d0b101d0912bef1f26d0daf51a414d67907c0'&&($modules[0]['required_contract_version']??'')==='1.2.2',
3=>($modules[3]['reviewed_source_commit']??'')==='b862efb94be87980e96a5b864bc5c7aec49183d9'&&($modules[3]['required_contract_version']??'')==='1.4.0',
7=>($modules[7]['reviewed_source_branch']??'')==='fix/file-07-second-fresh-80-review-20260810'&&($modules[7]['reviewed_source_commit']??'')==='b37c7c7b46f3f57b7325e075601c10bf3ced72ba'&&($modules[7]['required_contract_version']??'')==='1.2.0',
8=>($modules[8]['reviewed_source_commit']??'')==='cb302617d2a2def23de1883b8d9fead10bce7ef3'&&($modules[8]['required_canonical_contract_version']??'')==='1.1.0'&&($modules[8]['required_public_contract_version']??'')==='1.0.0',
9=>($modules[9]['reviewed_source_commit']??'')==='9103310fc93d978b6e70661f024a079fc0971003'&&($modules[9]['required_contract_version']??'')==='1.1.0',
14=>($modules[14]['reviewed_source_version']??'')==='1.4.3'&&($modules[14]['reviewed_source_branch']??'')==='review/file14-fifth-80-round-2026-08-10'&&($modules[14]['reviewed_source_commit']??'')==='907a04b25567a607e5ecb11d26e50bd0f07852be'&&($modules[14]['required_primary_color']??'')==='#087A4E',
18=>($modules[18]['reviewed_source_version']??'')==='1.2.0-RC1',20=>($modules[20]['reviewed_source_commit']??'')==='291486b22c7ed94b8be041192375b6d9b077fac5'&&($modules[20]['required_contract_version']??'')==='1.0.0',21=>($modules[21]['reviewed_source_commit']??'')==='afeda8742d8e1ea62254823291a66f502058989c',22=>($modules[22]['reviewed_source_commit']??'')==='c3b775b66fbbda4a9dd9891d63c08c74e2178741',23=>($modules[23]['reviewed_source_commit']??'')==='a8a8c805f4730998ccb44bd95c87591836561759',24=>($modules[24]['reviewed_source_commit']??'')==='0be43b3f424d7b53865587b2770479ca33f51a0b',25=>($modules[25]['candidate_version']??'')===$version&&($modules[25]['canonical_primary_color']??'')==='#087A4E'&&($modules[25]['design_token_owner']??'')==='file-25'&&($modules[25]['structural_shell_owner']??'')==='file-20'];foreach($checks as$file=>$valid){if(!$valid){throw new RuntimeException('File '.$file.' reviewed contract or lifecycle truth is inaccurate.');}}
    }

    /** @param array<string,mixed> $manifest @return array{payload_file_count:int,payload_bytes:int} */
    private static function verify_inner_zip(string $zip_bytes,string $manifest_bytes,array $manifest,string $matrix_bytes):array
    {
        $tmp=tempnam(sys_get_temp_dir(),'file25-');if(!is_string($tmp)||file_put_contents($tmp,$zip_bytes,LOCK_EX)!==strlen($zip_bytes)){throw new RuntimeException('Unable to create temporary package file.');}
        try{$zip=new ZipArchive();if($zip->open($tmp,ZipArchive::RDONLY)!==true){throw new RuntimeException('Unable to open inner File 25 ZIP.');}try{$expected=array_map(static fn(string$r):string=>self::PACKAGE_ROOT.'/'.$r,array_keys((array)$manifest['files']));$expected[]=self::PACKAGE_ROOT.'/'.self::INNER_MANIFEST;sort($expected);$actual=[];$seen=[];$total=0;for($i=0;$i<$zip->numFiles;$i++){$name=$zip->getNameIndex($i);$stat=$zip->statIndex($i);if(!is_string($name)||!is_array($stat)||!self::inner_name_is_safe($name)||isset($seen[$name])||self::zip_entry_is_symlink($stat)){throw new RuntimeException('Unsafe, duplicate or symbolic entry detected in inner ZIP.');}$seen[$name]=true;$size=(int)($stat['size']??-1);$total+=max(0,$size);if($size<0||$size>self::MAX_INNER_FILE_BYTES||$total>self::MAX_INNER_TOTAL_BYTES){throw new RuntimeException('Inner ZIP exceeds expansion limits.');}$actual[]=$name;}sort($actual);if($actual!==$expected){throw new RuntimeException('Inner ZIP file set differs from detached manifest.');}$embedded_manifest=$zip->getFromName(self::PACKAGE_ROOT.'/'.self::INNER_MANIFEST);$embedded_matrix=$zip->getFromName(self::PACKAGE_ROOT.'/'.self::INNER_MATRIX);if(!is_string($embedded_manifest)||!hash_equals($manifest_bytes,$embedded_manifest)||!is_string($embedded_matrix)||!hash_equals($matrix_bytes,$embedded_matrix)){throw new RuntimeException('Embedded manifest or dependency matrix differs from detached evidence.');}$bytes=0;foreach((array)$manifest['files']as$relative=>$meta){$contents=$zip->getFromName(self::PACKAGE_ROOT.'/'.$relative);if(!is_string($contents)||strlen($contents)!==(int)$meta['bytes']||!hash_equals((string)$meta['sha256'],hash('sha256',$contents))){throw new RuntimeException('Manifest payload integrity mismatch: '.$relative);}$bytes+=strlen($contents);}return['payload_file_count'=>count((array)$manifest['files']),'payload_bytes'=>$bytes];}finally{$zip->close();}}finally{@unlink($tmp);}
    }

    /** @return array<string,mixed> */
    private static function decode_json_object(string $json,string $label):array{try{$value=json_decode($json,true,64,JSON_THROW_ON_ERROR);}catch(JsonException$e){throw new RuntimeException('Unable to decode '.$label.': '.$e->getMessage());}if(!is_array($value)||array_is_list($value)){throw new RuntimeException(ucfirst($label).' must be a JSON object.');}return$value;}
    private static function outer_name_is_safe(string $name):bool{return$name!==''&&basename($name)===$name&&!str_contains($name,'..')&&preg_match('/^[A-Za-z0-9._+-]+$/',$name)===1;}
    private static function relative_payload_name_is_safe(string $name):bool{return$name!==''&&!str_starts_with($name,'/')&&!str_contains($name,'..')&&!str_contains($name,'\\')&&!str_contains($name,"\0")&&preg_match('#^[A-Za-z0-9._/+-]+$#',$name)===1;}
    private static function inner_name_is_safe(string $name):bool{return str_starts_with($name,self::PACKAGE_ROOT.'/')&&self::relative_payload_name_is_safe(substr($name,strlen(self::PACKAGE_ROOT)+1));}
    /** @param array<string,mixed> $stat */
    private static function zip_entry_is_symlink(array $stat):bool{$a=(int)($stat['external_attributes']??$stat['externalAttributes']??0);return(($a>>16)&0xF000)===0xA000;}
}

if(PHP_SAPI==='cli'&&realpath((string)($_SERVER['SCRIPT_FILENAME']??''))===__FILE__){$artifact='';$expected='';foreach(array_slice($argv??[],1)as$arg){if(str_starts_with($arg,'--artifact=')){$artifact=substr($arg,11);}elseif(str_starts_with($arg,'--artifact-sha256=')){$expected=substr($arg,18);}}try{echo json_encode(File25_Staging_Artifact_Verifier::verify($artifact,$expected),JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES),PHP_EOL;}catch(Throwable$e){fwrite(STDERR,'FAILED: '.$e->getMessage().PHP_EOL);exit(1);}}
