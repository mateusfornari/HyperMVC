<?php
class Teste extends HyperMVCController{
	public $nome;
	
	public $mostraForm = false;
	
	public function __construct($nome = 'Olá mundo!') {
		$this->nome = $nome;
	}

	public function ola(){
		return 'ola';
	}
	
	public function getData(){
		return array(new Teste('abc'), new Teste('def'), new Teste('ghi'));
	}
	
	public function getDataArray(){
		
		$lista[0]['nome'] = 'abc';
		$lista[1]['nome'] = 'def';
		$lista[2]['nome'] = 'ghi';
		$lista[0]['lista'][0]['nome'] = 'abc';
		$lista[0]['lista'][1]['nome'] = 'def';
		$lista[0]['lista'][2]['nome'] = 'ghi';
		$lista[1]['lista'][0]['nome'] = 'abc';
		$lista[1]['lista'][1]['nome'] = 'def';
		$lista[1]['lista'][2]['nome'] = 'ghi';
		$lista[2]['lista'][0]['nome'] = 'abc';
		$lista[2]['lista'][1]['nome'] = 'def';
		$lista[2]['lista'][2]['nome'] = 'ghi';
		return $lista;
	}
	
	public function onStart() {
		echo 'start';
	}
	
	public function onGetRequest() {
		var_dump($_GET);
	}
	
	public function onPostRequest() {
		var_dump($_POST);
	}
	
	public function onFinish() {
		
	}
}

HyperMVC::setControllerName('Teste');
?>