<?php

if(!defined('HttpResponseException')){
    class HttpResponseException extends Exception{}
}

class Http{

    static $last_url = '';

    // 10/12/2013
    static function resolveProtocolRelativeURL($url){
        if(substr($url,0,2)=='//'){
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http';
            return $protocol.':'.$url;
        }
        return $url;
    }

    static function get($url, $params=array()){
        $url = self::resolveProtocolRelativeURL($url);
        if(!strstr($url,'?')){
            $url .= '?';
        }
        $url .= '&'.http_build_query($params);
        self::$last_url = $url;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        return curl_exec($ch);
    }

    static function getJSON($url, $params=array()){
        $result = self::get($url,$params);
        try{
            return json_decode($result,true);
        } catch(Exception $e){
            throw new HttpResponseException("Http response could not be json_decoded, requested URL was: ".self::$last_url);
        }
    }

    static function post($url, $params=array()){
        $url = self::resolveProtocolRelativeURL($url);
        $ch = curl_init($url);
        curl_setopt_array($ch,array(
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $params
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        static::$last_url = $url;
        return curl_exec($ch);
    }

    static function postGetJSON($url, $params=array()){
        $result = static::post($url, $params);
        if(!$result){
            throw new HttpResponseException("Http response was empty, requested URL was: ".self::$last_url);
        }
        try{
            return json_decode($result,true);
        } catch(Exception $e){
            throw new HttpResponseException("Http response could not be json_decoded, requested URL was: ".self::$last_url);
        }
    }

}