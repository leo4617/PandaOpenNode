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
        
		$doc = new DOMDocument();
        /* 解决编码识别问题 */
		$target_charset = 'utf-8';
		
		$gbkpos = stripos($content,'gbk');
		$gb2312pos = stripos($content,'gb2312');
		$utf8pos = stripos($content,'utf-8');
		$html1= curl_getinfo($ch); 
		if ($html1['content_type'] && stripos($html1['content_type'],'charset=')){ 
		    $arr= preg_split('/charset=/i',$html1['content_type']); 
		    $target_charset = strtolower(trim($arr[1]));
		} else if($utf8pos>0 && ($gbkpos>0 || $gb2312pos>0)){
			$compare = $gbkpos || $gb2312pos;
			$target_charset = $utf8pos<$compare ? 'utf-8' : 'gbk';
		} else if($utf8pos>0){
			$target_charset = 'utf-8';
		} else {
			$target_charset = 'gbk';
		}
		$content = str_ireplace('<head>','<head><meta http-equiv=Content-Type content="text/html;charset='.$target_charset.'">',$content);
		/* 编码识别结束 */
		@$doc->loadHTML($content);
		if(!empty($doc)){
			$xpath = new DOMXPath($doc);
			$titles = $xpath->query('*/title');
			if($titles->length > 0){
				$this->title = $titles->item(0)->nodeValue;
			}
		} else {
			$this->title = false;
		}
    }
    function exec(){
        return $this->title;
    }
}