<?php

class Imoveis extends HyperMVCController{
	
	private $lista = array();
	
	public $mostrar = true;
	
	function __construct() {
		$this->objectName = 'i';
	}

	
	public function index() {
		$this->lista = array('abc', 'def', 'ghi');
	}

	public function getLista() {
		return $this->lista;
	}


}
