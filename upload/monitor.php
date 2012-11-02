<?php

class monitor{
    public static function add_retry(){
        $m = new monitor();
        $m->init();
        return $m->incr('retry:'.date('ymdhi'),5*60);
    }
    public static function add_warn(){
        $m = new monitor();
        $m->init();
        return $m->incr('err:'.date('ymdhi'),5*60);
    }
    public static function add_count(){
        $m = new monitor();
        $m->init();
        return $m->incr(date('ymdhi'),5*60);
    }
    function exec(){
        $this->init();
        $errors = $this->get('err:'.date('ymdhi'));
        $requests = $this->get(date('ymdhi'));
        $retrys = $this->get('retry:'.date('ymdhi'));
        if(empty($errors)) $errors=0;
        if(empty($requests)) $requests=0;
        if(empty($retrys)) $retrys=0;
        $xml = "<pre>errors:$errors\nrequests:$requests\nretrys:$retrys</pre>";
        return $xml;
    }
    function available(){
        return ;
    }
    function init(){
    	if(!class_exists('Redis') && !class_exists('Memcache')){
    		$this->obj = new monitor_empty();
	    	return false;
    	}
        $this->obj = class_exists('Redis') ? new monitor_redis() : new monitor_memcache();
        $this->obj->init();
    }
    function incr($key,$expire=300){
        return $this->obj->incr($key,$expire);
    }
    function get($key){
        return $this->obj->get($key);
    }
}

//兼容未开启redis和memcache的环境
class monitor_empty{
    function init(){
    	return false;
    }
    function incr($key,$expire=300){
    	return false;
    }
    function get($key){
    	return false;
    }
	
}

class monitor_redis{
    private $redis;
    function init(){
        if(!$this->redis){
            $this->redis = new Redis();
        }
        $this->redis->pconnect('127.0.0.1');
        return $this->redis;
    }
    function incr($key,$expire=300){
        $rt_code = $this->redis->incr($key);
        $this->redis->EXPIRE($key, $expire);
        return $rt_code;
    }
    function get($key){
        return $this->redis->GET($key);
    }
}

class monitor_memcache{
    private $mem;
    function init(){
        if(!$this->mem){
            $this->mem = new Memcache;
        }
        $mem = $this->mem;
        if(is_callable(array($mem,'init'))){
            $mem->init();
        } else {
            $mem->connect('127.0.0.1');
        }
        return $mem;
    }
    function incr($key,$expire=300){
        $mem = $this->mem;
        if(!$mem->add($key,1,false,$expire)){
            return $mem->increment($key);
        }
    }
    function get($key){
        $mem = $this->mem;
        return $mem->get($key);
    }
}