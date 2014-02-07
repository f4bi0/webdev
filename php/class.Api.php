<?php

class ApiRequestException extends Exception{}
class ApiRunTimeException extends Exception{}
class ApiAuthException extends Exception{}

class Api{

    var $reqvars = array();

    function __construct($reqvars){
        $this->reqvars = $reqvars;
    }

    static function output($data){
        echo json_encode($data);
    }

    static function dispatchIfRequested(){

    }

}



?>