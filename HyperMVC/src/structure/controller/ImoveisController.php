<?php

class ImoveisController extends BasicController{
	
	private $lista = array();
	
	public $mostrar = true;
	
	function __construct() {
		
	}

	
	public function indexAction() {
		$this->lista = array('abc', 'def', 'ghi');
	}

	public function testeCaseAction(){
		$this->lista = array('abc', 'def', 'ghi');
		$this->templateName = null;
	}


	public function getLista() {
		return $this->lista;
	}
	
	


}
