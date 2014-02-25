<?php

abstract class HyperMVCController {

    protected $viewName = null;
    protected $templateName = 'template';
    protected $objectName = 'this';
    
    /**
     *
     * @var HyperMVCRequest
     */
    protected $request = null;
    
    abstract public function index();

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
        $this->objectName = $objectName;
    }
    
    public function beforeAction(){}
    
    public function beforeRender(){}
    
    public function afterAction(){}
    
    public function afterRender(){}
    
    /**
     * 
     * @return HyperMVCRequest
     */
    public function getRequest() {
        return $this->request;
    }

    public function setRequest(HyperMVCRequest $request) {
        $this->request = $request;
    }


    
}
