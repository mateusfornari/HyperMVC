<?php
class TesteComponent extends HyperMVCController{
    
    public $teste = 'componente teste!';
    
    public function __construct() {
        $this->objectName = 'tc';
    }


    public function index() {
        
    }

}