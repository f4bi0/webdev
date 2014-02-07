<?php

class Frontend{

    static $config = array(
        'base_url' => '',
        'base_name' => ''
    );

    static $reqvars = array();

    static function render($title, $content, $tpl='inc.frontend.php'){
        if(!empty(self::$reqvars['nowrap'])){
            echo $content;
        } else {
            require($tpl);
        }
    }

    static function getBaseURL(){
        return self::$config['base_url'];
    }

    static function replaceConfigVars($string){
        foreach(self::$config as $k => $v){
            $string = str_replace('$'.$k, $v, $string);
        }
        return $string;
    }

    static $resolved_urls = array();

    static function resolveURL($pseudo_url){
        $url = self::replaceConfigVars($pseudo_url);
        return $url;
    }

    static function resolveURLOnce($url){
        if(in_array($url,self::$resolved_urls)){
            return;
        }
        $url = self::resolveURL($url);
        if(in_array($url,self::$resolved_urls)){
            return;
        }
        self::$resolved_urls[] = $url;
        return $url;
    }

    static function js($url){
        if($url = self::resolveURLOnce($url)){
            echo '<script src="'.$url.'"></script>';
        }
    }

    static function css($url){
        if($url = self::resolveURLOnce($url)){
            echo '<link href="'.$url.'" rel="stylesheet" />';
        }
    }



}

Frontend::$reqvars = $_REQUEST;

include_once(dirname(__FILE__) . '/config/Frontend.php');

?>