<?php
class title{
    private $title=false;
    function __construct($url){
        $reg = '/\<title\>(.+?)\<\/title\>/ims';
        $ch = curl_init($url);   
        curl_setopt_array(
            $ch,array(
                CURLOPT_RETURNTRANSFER=>true,
                CURLOPT_HEADER=>false,
                CURLOPT_TIMEOUT=>10,
            )
        );
        $content = curl_exec($ch);
        if(curl_getinfo($ch,CURLINFO_HTTP_CODE)!=200){
            $this->title = '';
            return;
        }
        if($content && preg_match($reg, $content, $result)){
            $title = $result[1];
            if(preg_match('/^.*$/u',$title)<1){
                $title = mb_convert_encoding($title,"UTF-8","gbk");
            }
            if($pos = strpos($title, '_')){
                $title = trim(substr($title,0,$pos));
            } else if($pos = strpos($title, '-')){
                $title = trim(substr($title,0,$pos));
            }
            $this->title = $title;
        }    
    }
    function exec(){
        return $this->title;
    }
}