<?php
ini_set('date.timezone','Asia/Shanghai');
global $cfg;
$cfg = include('config.php');
include_once('monitor.php');
$router = new router($_GET);
echo $router->jsonp($router->exec());

class router{
    private $obj;
    private $callback;
    private $nojson = false;
    function __construct($get){
        if(isset($get['p3'])){
            $this->nojson = true;
        }
        if(isset($get['monitor'])){
            $this->obj = new monitor();
        } else if(isset($get['url'])){
            if($this->chk_source()){
                if(!isset($get['title']))
                    $get['title'] = false;
                elseif($get['title']>0)
                    $get['title']=true;
                $this->obj = new query($get['url'],$get['title']);
            }
            if(isset($get['callback']))
                $this->callback = $get['callback'];
        } else if(isset($get['version'])){
            if(isset($get['callback']))
                $this->callback = $get['callback'];
        	$this->obj = new version_info();
        } else {
	        include 'main.html';
	        exit;
        }
    }
    function chk_source(){
        global $cfg;
        $refererhost = strtolower(parse_url($_SERVER['HTTP_REFERER'],PHP_URL_HOST));
        foreach($cfg['source'] as $source){
        	$source = strtolower(parse_url('http://'.$source,PHP_URL_HOST));
            if($refererhost==$source){
                return true;
            }
        }
        return false;
    }
    function exec(){
        if(!empty($this->obj)){
            return $this->obj->exec();
        } else {
            return new query_error();
        }
    }
    function jsonp($var){
        if($this->nojson){
            return json_encode($var);
        } 
        if(!empty($this->callback)){
            return $this->callback.'('.json_encode($var).')';
        } else {
            return strval($var);
        }
    }
}
class version_info{
	function __construct(){
	}
	function exec(){
		global $cfg;
		return $cfg['version'];
	}
}
class query{
    private $url;
    private $request;
    private $title;
    function __construct($url,$title){
        include_once('requester.php');
        include_once('parser.php');
        $this->url = $url;
        $this->title = $title;
    }
    private function formart_url(){
        $url_info = parse_url(trim($this->url));
        $scheme = isset($url_info['scheme']) ? $url_info['scheme'] . '://' : '';
        $host = isset($url_info['host']) ? $url_info['host'] : ''; 
        $port = isset($url_info['port']) ? ':' . $url_info['port'] : ''; 
        $url_info['path'] = isset($url_info['path']) ? $url_info['path'] : ''; 
        $path = str_replace(array('//','\\','/./',), '/',$url_info['path']);
        $query = isset($url_info['query']) ? '?' . $url_info['query'] : '';
        $this->url = "$scheme$host$port$path$query";
    }
    function exec(){
        $this->formart_url();
        
        if($this->title){
            include_once('title.php');
            $title = new title($this->url);
        }
        $this->request = new requester($this->url);
        if($this->title){
            $this->parser = new parser($this->url,$this->request->exec(),$title);
        } else {
            $this->parser = new parser($this->url,$this->request->exec());
        }
        
        return $this->parser->exec();
    }
}

class query_error{
    function __construct($msg='发生错误'){
        $this->msg = $msg;
    }
    function __toString(){
        return $this->msg;
    }
}
