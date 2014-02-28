<?php

class Session{
    
	public static function start($sessionName = null) {
		
		if(is_null($sessionName)){
			$sessionName = 'HyperMVCSession';
		}
		$secure = isset($_SERVER['HTTPS']);
		$httpOnly = true;
		ini_set('session.use_only_cookies', 1);
		$cookieParams = session_get_cookie_params();
		session_set_cookie_params($cookieParams['lifetime'], $cookieParams['path'], $cookieParams['domain'], $secure, $httpOnly);
		session_name($sessionName);
		session_start();
	}

	
	public static function renew() {
		
	}
    
}