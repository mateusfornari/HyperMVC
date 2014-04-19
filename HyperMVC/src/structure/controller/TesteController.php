<?php
class TesteController extends BasicController{
	public $nome;
	
	public $apelido = 'apelido';
	
	public $mostraForm = true;
    
    public $lista = array();
	
	public function __construct($nome = 'Olá mundo!', $apelido = '') {
		$this->nome = $nome;
        $this->apelido = $apelido;
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
//		return array();
        
        $data = array();
        for($i = 0; $i < 500; $i++){
            $data[] = new TesteController($i, $i);
        }
        return $data;
//		return array(new TesteController('abc'), new TesteController('def'), new TesteController('ghi'));
	}
	
	public function getDataArray(){
		
		$lista[0]['nome'] = 'abc';
		$lista[1]['nome'] = 0;
		$lista[2]['nome'] = 'ghi';
		$lista[0]['lista'][0]['nome'] = 'abc';
		$lista[0]['lista'][1]['nome'] = 'def';
		$lista[0]['lista'][2]['nome'] = 'ghi';
		$lista[1]['lista'][0]['nome'] = 'abc';
		$lista[1]['lista'][1]['nome'] = 0;
		$lista[1]['lista'][2]['nome'] = 'ghi';
		$lista[2]['lista'][0]['nome'] = 'abc';
		$lista[2]['lista'][1]['nome'] = 'def';
		$lista[2]['lista'][2]['nome'] = 'ghi';
		return $lista;
	}

    public function indexAction() {
        echo "Olá index mothod!";
        echo $_SERVER['REQUEST_METHOD'];
        Session::start();
        var_dump(Session::get('tes'));
        Session::set('tes', 'blibli');
    }
	
	public function testeAction(){
		
	}

	
    
    public function beforeAction() {
        parent::beforeAction();
    }
    
    public function beforeRender() {
        echo __METHOD__;
    }
    
    public function afterAction() {
        echo __METHOD__;
    }
    
    public function afterRender() {
        parent::afterRender();
        var_dump(HyperMVC::errorInfo());
    }
}

?>