<?php
class MissingArgumentException extends Exception {}

class System{

    static $error_handlers = array();
    static $shutdown_handlers = array();
    static $include_handlers = array();
    static $error_handling = false;
    static $base_date = 'now';

    static function getDate($format=null){
        $date = new DateTime(self::$base_date);
        if($format){
            return $date->format($format);
        }
        return $date;
    }

    static function onError($func){
        self::$error_handlers[] = $func;
    }

    static function onException($func){
        set_exception_handler($func);
    }

    static function onShutdown($func){
        self::$shutdown_handlers[] = $func;
    }

    static function onIncluded($func){
        self::$include_handlers[] = $func;
    }

    static function addIncludePath($path){
        set_include_path(
            get_include_path().PATH_SEPARATOR.$path
        );
    }

    static function enableAutoInclude(){
        function __autoload($classname){
            require_once("class.$classname.php");
            foreach(System::$include_handlers as $handler){
                $handler($classname);
            }
        }
    }

    static function enableErrorHandling(){
        error_reporting(E_ALL);
        ini_set('display_errors','1');
        set_error_handler(function($type,$msg,$file,$line){
            foreach(System::$error_handlers as $handler){
                $handler($type,$msg,$file,$line);
            }
            throw new ErrorException($msg);
        });
    }

    static function disableErrorHandling(){
        error_reporting(0);
        //init_set('display_errors','0');
        set_error_handler(function(){});
    }

    static function enableShutdownHandling(){
        register_shutdown_function(function(){
            foreach(System::$shutdown_handlers as $handler){
                $handler();
            }
        });
    }

    // 05/12/2013
    static function callMethodAssoc($class_or_object, $method, $arr){
        $class = is_object($class_or_object) ? get_class($class_or_object) : $class_or_object;
        $ref = new ReflectionMethod($class, $method);
        $params = array();
        foreach( $ref->getParameters() as $p ){
            if( $p->isOptional() ){
                if( isset($arr[$p->name]) ){
                    $params[] = $arr[$p->name];
                }else{
                    $params[] = $p->getDefaultValue();
                }
            }else if( isset($arr[$p->name]) ){
                $params[] = $arr[$p->name];
            }else{
                throw new MissingArgumentException("$class::$method() - Missing parameter $p->name");
            }
        }
        $object = is_object($class_or_object) ? $class_or_object : null;
        return $ref->invokeArgs( $object, $params );
    }

}

?>