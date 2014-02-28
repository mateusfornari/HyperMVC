<?php

class Request{
    
    public static $post = null;
    
    public static $get = null;
    
    private static $method;
    
    private static $baseUrl = null;
    
    
    public static function init() {
        if(isset($_GET)){
            self::$get = (object) $_GET;
            unset($_GET);
        }
        if(isset($_POST)){
            self::$post = (object) $_POST;
            unset($_POST);
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
            $protocol = 'http';
            self::$baseUrl = $protocol.'://'.$host.$path;
        }
        return self::$baseUrl;
    }
    
}
