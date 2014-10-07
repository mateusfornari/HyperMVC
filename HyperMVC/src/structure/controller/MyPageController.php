<?php

class MyPageController extends BasicController{
	
	public $myList = array('Item 1', 'Item 2', 'Item 3');
	
	public $baseUrl = '';
	
	public $id = null;
	
	public function indexAction() {
		$this->baseUrl = Request::baseUrl().'my-page/details/';
	}
	
	public function detailsAction(){
		$this->id = $this->instance->getRoute()->getVar(':id');
	}

}
