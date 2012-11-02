<?php
/**
 * 百度查询类
 * version 1.2.0
 */
class requester{
    private $baidu_path = '/s?wd=';
    private $baidu_host = '';
    function __construct($url){
        $this->get_ip();
        $baidu_url = $this->baidu_host.$this->baidu_path.rawurlencode($url);
        $this->curl = curl_init($baidu_url);
        curl_setopt($this->curl,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($this->curl,CURLOPT_HEADER,false);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, array("Host: www.baidu.com"));
        curl_setopt($this->curl, CURLOPT_ENCODING, 'gzip,deflate');
        global $cfg;
        $timeout = isset($cfg['timeout']) ? intval($cfg['timeout']) : 3;
        curl_setopt($this->curl,CURLOPT_TIMEOUT,$timeout);
    }
    function exec(){
        $content = @curl_exec($this->curl);
        if(empty($content) || stripos($content,'http://verify.baidu.com/')!==false || stripos($content,'<html></html>')!==false){
            monitor::add_warn();
            return false;
        } else {
            monitor::add_count();
            return $content;
        }
    }
    function get_ip(){
        static $request_times = 0;
        if($request_times>3)
            return false;
        else
            $request_times++;
        global $cfg;
        static $ips = null;
        if(is_null($ips))
            $ips = $cfg['baiduip'];
        if(empty($ips))
            return false;
        shuffle($ips);
        $ip = array_pop($ips);
        $this->baidu_host = 'http://'.$ip;
        return true;
    }
}