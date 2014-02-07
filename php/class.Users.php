<?php

class UsersException extends Exception{}
class Users{

    static function isLogged(){
        return !empty($_SESSION['user_id']);
    }

    static function getId(){
        if(!self::isLogged()){
            throw new UsersException("User session not identified!");
        }
        return $_SESSION['user_id'];
    }

    static function isAdmin(){
        if(!self::isLogged()) return false;
        return in_array($_SESSION['user_id'],array(396));
    }

    static function isDev(){
        if(!self::isLogged()) return false;
        return in_array($_SESSION['user_id'],array(396,389));
    }

    // 09/12/2013
    static function getCacheResult($id, $func){
        if(!empty($_SESSION['cache'][$id])){
            return $_SESSION['cache'][$id];
        }
        session_start();
        $_SESSION['cache'][$id] = $func();
        session_write_close();
        return $_SESSION['cache'][$id];
    }

}

require_once(dirname(__FILE__).'/config/Users.php');