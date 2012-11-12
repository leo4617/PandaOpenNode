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
            $keys = array(
        		array('抱歉，没有找到与','相关的网页。'),
        		array('及其后面的字词均被忽略，因为百度的查询限制在38个汉字以内。'),
        		array('没有找到该URL。'),
        	);
            if(stripos($content,'content="text/html;charset=utf-8"')===false){
                $this->convert_to_gbk($keys);
            }
            foreach($keys as $key){
            	$find = 0;
            	if(is_array($key)){
            		foreach($key as $item){
		            	if(stripos($content,$item)>0){
			            	$find++;
		                }
            		}
            		
            	}
            	if($find==count($key)){
                	$this->result = array(
                    	'className'=>'error'
                    );
                    return;
            	}
            }            
            $dom = @DOMDocument::loadHTML($content);
            if($table = $dom->getElementById('1')){
                $className = 'accept';
                $title = '';
                $date = '';
                $spans = $table->getElementsByTagName('span');
                $as = $table->getElementsByTagName('a');
                if(!empty($as)){
                    $a = $as->item(0);
                    $title = getTextFromNode($a);
                }
                foreach($spans as $span){
                    if($span->getAttribute('class')=='g'){
                        $string = trim(getTextFromNode($span));
                        $date = substr($string,strrpos($string,' '));
                        break;
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
    function convert_to_gbk(&$str){
    	if(is_array($str)){
    		foreach($str as &$item){
	    		$this->convert_to_gbk($item);
    		}
    	} else {
	    	$str = mb_convert_encoding($str,'gbk','utf-8');
    	}
    }
    function exec(){
        if($this->result['className']!='accept' && $this->title_req){
            $this->result['title'] = $this->title_req->exec();
        }
        return $this->result;
    }
}