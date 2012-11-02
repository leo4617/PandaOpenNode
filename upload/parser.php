<?php
function getTextFromNode($Node, $Text = "") { 
    if (!isset($Node->tagName)) 
        return $Text.$Node->textContent; 

    $Node = $Node->firstChild; 
    if ($Node != null) 
        $Text = getTextFromNode($Node, $Text); 

    while($Node->nextSibling != null) { 
        $Text = getTextFromNode($Node->nextSibling, $Text); 
        $Node = $Node->nextSibling; 
    } 
    return $Text; 
} 


class parser{
    private $result;
    private $title_req=false;
    function __construct($url,$content,$title_req=false){
        $this->title_req = $title_req;
        if(empty($content)){
            $this->result = array(
                'className'=>'warn'
            );
        } else {
            $key1 = '您可以直接访问';
            $key2 = '以下是网页中包含';
            if(stripos($content,'content="text/html;charset=utf-8"')===false){
                $key2 = mb_convert_encoding($key2,'gbk','utf-8');
                $key1 = mb_convert_encoding($key1,'gbk','utf-8');
            }
            if(stripos($content,$key1)>0){
                $this->result = array(
                    'className'=>'error'
                );
                return;
            }
            
            $dom = @DOMDocument::loadHTML($content);
            if($table = $dom->getElementById('1')){
                $className = 'accept';
                $title = '';
                $date = '';
                $spans = $table->getElementsByTagName('span');
                $as = $table->getElementsByTagName('a');
                $archived = false;
                if(!empty($as)){
                    $a = $as->item(0);
                    $title = getTextFromNode($a);
                    $archived = $this->is_the_same($url,$a->getAttribute('href'),true);
                }
                foreach($spans as $span){
                    if($span->getAttribute('class')=='g'){
                        $string = trim(getTextFromNode($span));
                    	if(!$archived){
	                        $archive_url = implode(' ',explode(' ', $string, -1));
	                        $url = substr($url,7);
	                        if(substr($archive_url,-4)==' ...'){
	                            $archive_url = substr($archive_url,0,-4);
	                            $url = substr($url,0,strlen($archive_url));
	                        }
		                    $archived = $this->is_the_same($url,$archive_url);
                    	}
                        if($archived) {
                            $date = substr($string,strrpos($string,' '));
                            break;
                        }
                    }
                }
                if(empty($date)){
                    $className='error';
                    $title='';
                } else {
                    if(preg_match('/^.*$/u',$date)<1){
                        $date = mb_convert_encoding($date,"UTF-8","gbk");
                    }
                    if(preg_match('/^.*$/u',$title)<1){
                        $title = mb_convert_encoding($title,"UTF-8","gbk");
                    }
                }
                $this->result = array(
                    'className'=>$className,
                    'date'=>$date,
                    'title'=>$title,
                );
            } else {
                $this->result = array(
                    'className'=>'error'
                );
            }
            return;
        }
    }
    function is_the_same($url,$archive_url,$relocate=false){
        $archive_url = strtolower(trim($archive_url,'/'));
        if($relocate){
			$ch = curl_init($archive_url);
			curl_setopt($ch, CURLOPT_NOBODY, true );
			curl_setopt($ch,CURLOPT_HEADER,true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$header = curl_exec($ch); 
			$arr = explode("\n",$header);
			foreach($arr as $item){
				$item = trim($item);
				if(substr($item,0,10)=='Location: '){
					$archive_url= strtolower(substr($item,10));
					if($pos = strpos($archive_url,'&__bd_tkn__=')){
						$archive_url = substr($archive_url, 0,$pos);
					}
				}
			}
			curl_close($ch);
        }
        $url = strtolower(trim($url,'/'));
        $s_archive_url = substr(strstr($archive_url,'.'),1);
        $s_url = substr(strstr($url,'.'),1);
    	if( $url==$archive_url || $s_archive_url==$url || $s_url==$archive_url ||$s_url==$s_archive_url){
        	return true;
    	} else {
	    	return false;
    	}
	    
    }
    function convert_to_gbk($str){
        if(preg_match('/^.*$/u', $str) > 0){
            return mb_convert_encoding($str,"gbk","UTF-8");
        } else {
            return $str;
        }
    }
    function my_urlencode($string) {
        $entities = array('%21', '%2A', '%27', '%28', '%29', '%3B', '%3A', '%40', '%26', '%3D', '%2B', '%24', '%2C', '%2F', '%3F', '%25', '%23', '%5B', '%5D');
        $replacements = array('!', '*', "'", "(", ")", ";", ":", "@", "&", "=", "+", "$", ",", "/", "?", "%", "#", "[", "]");
        return str_replace($entities, $replacements, urlencode($string));
    }
    function exec(){
        if($this->result['className']!='accept' && $this->title_req){
            $this->result['title'] = $this->title_req->exec();
        }
        return $this->result;
    }
}