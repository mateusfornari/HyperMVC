<?php

class Session{
    
    private $data;
    
    private $flashData = array();
    
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
                if(isset(self::$instance->data['hmvcFlashData'])){
                    self::$instance->flashData = self::$instance->data['hmvcFlashData'];
                }
                foreach (self::$instance->flashData as $key => $flash){
                    if($flash['control'] >= 1){
                        unset(self::$instance->flashData[$key]);
                    }else{
                        self::$instance->flashData[$key]['control']++;
                    }
                }
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
        }elseif(array_key_exists($key, self::$instance->flashData)){
            return self::$instance->flashData[$key]['value'];
        }
        return null;
    }
    
    public static function commit(){
        self::$instance->data['hmvcFlashData'] = self::$instance->flashData;
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
    
    public static function setFlash($key, $value){
        if(!is_null($key) && $key != ''){
            self::$instance->flashData[$key]['value'] = $value;
            self::$instance->flashData[$key]['control'] = 0;
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