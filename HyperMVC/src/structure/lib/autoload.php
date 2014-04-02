<?php
spl_autoload_register('HyperMVCAutolaod::autoload');

class HyperMVCAutolaod{
	
	public static function autoload($name){
		$name = str_replace('\\', '/', $name);
		$lib = self::findDir(__DIR__.'/', $name);
		if(!is_null($lib)){
			require_once $lib.$name.'.php';
			return true;
		}
		$model = self::findDir(__DIR__.'/../model/', $name);
		if(!is_null($model)){
			require_once $model.$name.'.php';
			return true;
		}
		$controller = self::findDir(__DIR__.'/../controller/', $name);
		if(!is_null($controller)){
			require_once $controller.$name.'.php';
			return true;
		}
		$vendor = self::findDir(__DIR__.'/../vendors/', $name);
		if(!is_null($vendor)){
			require_once $vendor.$name.'.php';
			return true;
		}

	}

	private static function findDir($root, $name){
		if(file_exists($root.$name.'.php')){
			return $root;
		}else{
			$files = scandir($root);
			$dir = null;
			for($i = 2; $i < sizeof($files); $i++){
				if(is_dir($root.$files[$i])){
					$dir = self::findDir($root.$files[$i].'/', $name);
					if(!is_null($dir)) return $dir;
				}
			}
			return null;
		}
	}
	
}