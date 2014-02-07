<?php

class Dev{

    static function alert($value){
        echo "<script>alert('$value');</script>";
    }

    static function pre($array){
        echo "<pre>".print_r($array,1)."</pre>";
    }

    public static function getDump($var){
        ob_start();var_dump($var);
        return ob_get_clean();
    }

    static function mail($content){

    }
}