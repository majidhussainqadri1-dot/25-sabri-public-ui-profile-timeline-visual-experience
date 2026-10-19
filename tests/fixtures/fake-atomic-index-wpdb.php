<?php
declare(strict_types=1);

/**
 * Standalone deterministic SQL CAS fixture. This fake uses the same FIVE
 * compare/replace operands as File25's guarded MySQL UPDATE JOIN and the same
 * TWO operands as its DELETE. It is not a real multi-process MySQL test.
 */
if (! function_exists('maybe_serialize')) {
    function maybe_serialize(mixed $data): string {
        return is_array($data) || is_object($data) ? serialize($data) : (string) $data;
    }
}
if (! function_exists('wp_cache_delete')) {
    function wp_cache_delete(string $key,string $group=''):bool {return true;}
}

final class F25_Fake_Atomic_Wpdb {
    public string $options = 'wp_options';
    public function __construct(private string $scope) {}
    public function prepare(string $query, mixed ...$args): string {
        return 'F25-CAS:' . base64_encode(serialize([$query, $args]));
    }
    public function query(string $query): int|false {
        if(!str_starts_with($query,'F25-CAS:')){return false;}
        $decoded=@unserialize((string)base64_decode(substr($query,8),true), ['allowed_classes'=>false]);
        if(!is_array($decoded)||count($decoded)!==2){return false;}
        [$sql,$args]=$decoded;
        $store=&$GLOBALS[$this->scope];
        if(!is_array($store)){return false;}
        if(str_starts_with(ltrim($sql),'UPDATE') && count($args)===5) {
            [$lockKey,$newSerialized,$indexKey,$oldSerialized,$expectedLock]=$args;
            $new=@unserialize($newSerialized,['allowed_classes'=>false]);
            if(!is_array($new)){return false;}
            // Force an actual race BETWEEN the caller's preliminary read and
            // this guarded database statement. CAS must refuse to overwrite.
            if(($GLOBALS['r1675_cas_race']??'')==='overwrite_on_commit'
                && ($new['status']??'')==='complete') {
                $GLOBALS['r1675_cas_race']='';
                $store[$lockKey]=['generation'=>'rival','expires'=>time()+300];
                $store[$indexKey]=['status'=>'running','generation'=>'rival'];
            }
            if($this->scope==='r1595_options' && ($GLOBALS['r1595_failure']??'')==='complete'
                && ($new['status']??'')==='complete') {
                return 0;
            }
            if(!array_key_exists($lockKey,$store)||!array_key_exists($indexKey,$store)
                ||maybe_serialize($store[$lockKey])!==$expectedLock
                ||maybe_serialize($store[$indexKey])!==$oldSerialized){
                return 0;
            }
            $store[$indexKey]=$new;
            return 1;
        }
        if(str_starts_with(ltrim($sql),'DELETE') && count($args)===2) {
            [$key,$expected]=$args;
            if(($GLOBALS['r1675_cas_race']??'')==='replace_on_delete') {
                $GLOBALS['r1675_cas_race']='';
                $store[$key]=['generation'=>'rival','expires'=>time()+300];
            }
            if(!array_key_exists($key,$store)||maybe_serialize($store[$key])!==$expected){
                return 0;
            }
            unset($store[$key]);return 1;
        }
        return false;
    }
}
