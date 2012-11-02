<?php

class cache{
    public static function get_instace(){
        if(class_exists('Redis',false)){
            return new redis_cache();
        } else if(class_exists('Memcache',false)){
            return new memcache_cache(); 
        }
    }
    public static function set($url,$date){
        $cache = self::get_instace();
        if(!preg_match('/\d+-\d+-\d+/',$date)){
            $date = date('Y-m-d');
        }
        $cache->set(md5($url),$date,3600*24);
    }
    public static function get($url){
        $cache = self::get_instace();
        return $cache->get(md5($url));
    }
}
class redis_cache{
    private $redis;
    function __construct(){
        $this->redis = new Redis();
        $this->redis->connect('127.0.0.1');
    }
    function __destruct(){
        $this->redis->close();
    }
    function set($key,$var,$expire=0){
        return $this->redis->set($key,$var,$expire);
    }
    function get($key){
        return $this->redis->get($key);
    }
}
class memcache_cache{
    private $memcache;
    function __construct(){
        $this->memcache = new Memcache();
        $this->memcache->init();
    }
    function __destruct(){
        $this->memcache->close();
    }
    function set($key,$var,$expire=0){
        return $this->memcache->set($key,$var,MEMCACHE_COMPRESSED,$expire);
    }
    function get($key){
        return $this->memcache->get($key);
    }
}
