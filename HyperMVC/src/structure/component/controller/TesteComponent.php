<?php
class TesteComponent extends BasicComponent{
    
    public $teste = 'componente teste!';
    
    public function __construct() {
        $this->objectName = 'tc';
    }


    public function indexAction() {
        
    }

}