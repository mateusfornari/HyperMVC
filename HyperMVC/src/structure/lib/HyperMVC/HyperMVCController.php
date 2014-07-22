<?php

abstract class HyperMVCController {

	/**
	 * @var HyperMVC
	 */
	protected $instance;
	protected $viewName = null;
    protected $templateName = 'template';
    private $objectName = 'obj';
    
	public function setInstance(HyperMVC $instance) {
		$this->instance = $instance;
	}


	abstract public function indexAction();

    public function getViewName() {
        return $this->viewName;
    }

    public function getTemplateName() {
        return $this->templateName;
    }

    public function setViewName($viewName) {
        $this->viewName = $viewName;
    }

    public function setTemplateName($templateName) {
        $this->templateName = $templateName;
    }

    public function getObjectName() {
        return $this->objectName;
    }

    public function setObjectName($objectName) {
        $this->objectName = preg_replace('/[^a-zA-Z0-9_]/', '', $objectName);
        if($this->objectName == 'this'){
            throw new Exception("Invalid controller object name (this)! ");
        }
    }
    
    public function beforeAction(){}
    
    public function beforeRender(){}
    
    public function afterAction(){}
    
    public function afterRender(){}
    
    


    
}
