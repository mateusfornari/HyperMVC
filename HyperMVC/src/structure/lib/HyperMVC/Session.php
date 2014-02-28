<?php

class Session{
    
    private $data;
    
    private static $instance = null;
    
	public static function start($sessionName = null) {
		if(is_null(self::$instance) && session_status() != PHP_SESSION_ACTIVE){
            self::$instance = new Session();
            if(is_null($sessionName)){
                $sessionName = 'HyperMVCSession';
            }
            $secure = isset($_SERVER['HTTPS']);
            $httpOnly = true;
            ini_set('session.use_only_cookies', 1);
            $cookieParams = session_get_cookie_params();
            session_set_cookie_params($cookieParams['lifetime'], $cookieParams['path'], $cookieParams['domain'], $secure, $httpOnly);
            session_name($sessionName);
            if(session_start()){
                self::$instance->data = $_SESSION;
                $_SESSION = array();
                return true;
            }
            return false;
        }
        return true;
	}

	
	public static function renew() {
		return session_regenerate_id(true);
	}
    
    public static function set($key, $value){
        if(!is_null($key) && $key != ''){
            self::$instance->data[$key] = $value;
            return true;
        }
        return false;
    }
    
    public static function get($key){
        if(array_key_exists($key, self::$instance->data)){
            return self::$instance->data[$key];
        }
        return null;
    }
    
    public static function commit(){
        $_SESSION = self::$instance->data;
        return session_write_close();
    }
    
    public static function destroy(){
        return session_destroy();
    }
    
    public static function delete($key){
        if(array_key_exists($key, self::$instance->data)){
            unset(self::$instance->data[$key]);
            return true;
        }
        return false;
    }
    
    public static function flush(){
        self::$instance->data = array();
    }

    public function __destruct() {
        return self::commit();
    }
    
}