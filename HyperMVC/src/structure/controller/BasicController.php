<?php

abstract class BasicController extends HyperMVCController{
    private $tempoIni;
    private $tempoFim;
    
    public function beforeAction() {
        parent::beforeAction();
        $this->tempoIni = microtime(true);
    }
    
    public function afterRender() {
        parent::afterRender();
		
		var_dump($this->instance->getRoute());
		
        $this->tempoFim = microtime(true);
        var_dump(memory_get_peak_usage(true));
        var_dump(memory_get_usage(true));
        var_dump($this->instance->getVisitedNodes());
        var_dump($this->tempoIni);
        var_dump($this->tempoFim);
        var_dump($this->tempoFim - $this->tempoIni);
    }
    
}

