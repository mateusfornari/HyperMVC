<?php
class TesteController extends BasicController{
	public $nome;
	
	public $mostraForm = true;
	
	public function __construct($nome = 'Olá mundo!') {
		$this->nome = $nome;
        HyperMVC::setViewRoot('template1');
        $this->templateName = '../template';
	}

	public function ola(){
		return 'ola';
	}
	public function olaMundo(){
		return 'ola';
	}
	
	public function getData(){
		return array(new TesteController('abc'), new TesteController('def'), new TesteController('ghi'));
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
        Session::start();
        var_dump(Session::get('tes'));
        Session::set('tes', 'blibli');
    }

    public function __toString() {
        return $this->nome;
    }
    
    public function beforeAction() {
        echo __METHOD__;
    }
    
    public function beforeRender() {
        echo __METHOD__;
    }
    
    public function afterAction() {
        echo __METHOD__;
    }
    
    public function afterRender() {
        echo __METHOD__;
    }
}

?>