<?php
class Teste extends HyperMVCController{
	public $nome;
	
	public $mostraForm = true;
	
	public function __construct($nome = 'Olá mundo!') {
		$this->nome = $nome;
        $this->viewName = 'Teste';
        $this->objectName = 't';
	}

	public function ola(){
		return 'ola';
	}
	public function olaMundo(){
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

    public function index() {
        echo "Olá index mothod!";
        echo $_SERVER['REQUEST_METHOD'];
        var_dump($_GET);
    }

    public function __toString() {
        return $this->nome;
    }
}

?>