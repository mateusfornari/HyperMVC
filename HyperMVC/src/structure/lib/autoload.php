<?php

function __autoload($name){
	$lib = findDir(__DIR__.'/', $name);
	if(!is_null($lib)){
		require_once $lib.$name.'.php';
	}else{
		$model = findDir(__DIR__.'/../model/', $name);
		if(!is_null($model)){
			require_once $model.$name.'.php';
		}
	}
}

function findDir($root, $name){
	if(file_exists($root.$name.'.php')){
		return $root;
	}else{
		$files = scandir($root);
		$dir = null;
		for($i = 2; $i < sizeof($files); $i++){
			if(is_dir($root.$files[$i])){
				$dir = findDir($root.$files[$i].'/', $name);
				if(!is_null($dir)) return $dir;
			}
		}
		return null;
	}
}
?>