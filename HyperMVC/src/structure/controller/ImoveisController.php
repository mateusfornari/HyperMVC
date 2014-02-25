<?php

class ImoveisController extends BasicController{
	
	private $lista = array();
	
	public $mostrar = true;
	
	function __construct() {
		
	}

	
	public function index() {
		$this->lista = array('abc', 'def', 'ghi');
	}

	public function getLista() {
		return $this->lista;
	}


}
