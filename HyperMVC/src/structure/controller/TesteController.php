<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of TesteController
 *
 * @author mateusfornari
 */
class TesteController extends BasicController {
	
    public $list = array(1,2,3,4,5);
    
	public function indexAction() {
		//$this->templateName = null;
		
		if(isset(Request::$post->enviar)){
			Request::reload();
		}
		
		
	}

//put your code here
}
