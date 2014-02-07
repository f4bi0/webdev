<?php

class Strings{

    static function parseDate($string){
        if(strstr($string,'/')) {
            return DateTime::createFromFormat('d/m/Y',$string);
        }
        if(ctype_digit("$string")){
            $string = date('Y-m-d', $string);
        }
        return new DateTime($string);
    }

    static function parseFloat($string){
        return (float)str_replace(',','.',$string);
    }

    static function unaccent($string){
        require_once(dirname(__FILE__) . '/inc.strings.php');
        return unaccent($string);
    }

    static function formatDate($string, $format='d/m/Y'){
        $date = self::parseDate($string);
        return $date->format($format);
    }

    static function render($template, $vars, $prefix='%',$sufix=''){
        $output = $template;
        foreach($vars as $k=>$v){
            $output = str_replace($prefix.$k.$sufix, $v, $output);
        }
        return $output;
    }

    static function getStringBetween($string, $start, $end){
        $string = " ".$string;
        $ini = strpos($string,$start);
        if ($ini == 0) return "";
        $ini += strlen($start);
        $len = strpos($string,$end,$ini) - $ini;
        return substr($string,$ini,$len);
    }

    // 10/12/2013
    public static function isTrue($string){
        $string = trim(strtolower("$string"));
        return $string!="n" && $string!="undefined" && $string!="null" && $string!="false";
    }

}