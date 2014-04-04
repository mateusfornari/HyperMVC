<?php

class Request{
    
    public static $post = null;
    
    public static $get = null;
    
	public static $files = null;
    
    public static $method;
    
    private static $baseUrl = null;
    
    
    public static function init() {
        if(isset($_GET)){
            self::$get = (object) $_GET;
            unset($_GET);
        }
        if(isset($_POST)){
            self::$post = (object) $_POST;
            unset($_POST);
            self::$files = (object) $_FILES;
            unset($_FILES);
        }
        
        self::$method = $_SERVER['REQUEST_METHOD'];
        
        self::$baseUrl = self::baseUrl();
    }
    
    public static function isPost(){
        return self::$method == 'POST';
    }
    
    public static function isGet(){
        return self::$method == 'GET';
    }
    
    public static function baseUrl(){
        if(is_null(self::$baseUrl)){
            $path = implode('/', array_slice(explode('/', $_SERVER['PHP_SELF']), 0, -1)).'/';
            $host = $_SERVER['HTTP_HOST'];
            $protocol = ((isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443) ? "https" : "http";
            self::$baseUrl = "$protocol://$host$path";
        }
        return self::$baseUrl;
    }
    
    public static function redirect($location){
        header("Location: $location");
        exit();
    }
    
    public static function reload(){
        self::redirect(self::baseUrl() . HyperMVC::getRoute()->getQuery() . (self::queryString() ? '?' . self::queryString() : ''));
    }


    public static function queryString(){
        $array = (array)self::$get;
        $data = array();
        foreach ($array as $k => $v){
            $data[] = "$k=$v";
        }
        return implode('&', $data);
    }
    
}
