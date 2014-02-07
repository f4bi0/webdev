<?php

require_once('class.System.php');

session_start();
session_write_close();

System::enableAutoInclude();
System::enableErrorHandling();
System::enableShutdownHandling();

System::onException(function(Exception $e){
    echo get_class($e).': '.$e->getMessage();
    Dev::pre($e->getTrace());
});

/*
System::onError(function($type,$msg,$file,$line){
    Events::add('system_error', array('msg'=>$msg,'file'=>$file,'line'=>$line));
}); */




?>