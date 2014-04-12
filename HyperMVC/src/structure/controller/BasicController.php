<?php

abstract class BasicController extends HyperMVCController{
    
    public function afterRender() {
        parent::afterRender();
        var_dump(HyperMVC::errorInfo());
    }
    
}

