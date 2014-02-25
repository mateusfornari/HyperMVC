<?php

class HyperMVCRequest{
    
    public $post = null;
    
    public $get = null;
    
    private $method;
    
    private $baseUrl = null;
    
    
    public function __construct() {
        if(isset($_GET)){
            $this->get = (object) $_GET;
            unset($_GET);
        }
        if(isset($_POST)){
            $this->post = (object) $_POST;
            unset($_POST);
        }
        
        $this->method = $_SERVER['REQUEST_METHOD'];
        
        $this->baseUrl = $this->baseUrl();
    }
    
    public function isPost(){
        return $this->method == 'POST';
    }
    
    public function isGet(){
        return $this->method == 'GET';
    }
    
    public function baseUrl(){
        if(is_null($this->baseUrl)){
            $path = implode('/', array_slice(explode('/', $_SERVER['PHP_SELF']), 0, -1)).'/';
            $host = $_SERVER['HTTP_HOST'];
            $protocol = 'http';
            $this->baseUrl = $protocol.'://'.$host.$path;
        }
        return $this->baseUrl;
    }
    
}
