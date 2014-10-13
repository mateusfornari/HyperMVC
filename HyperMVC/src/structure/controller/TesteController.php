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
	
	public function indexAction() {
		$this->templateName = null;
		
		var_dump(Request::$post);
		
		
	}

//put your code here
}
