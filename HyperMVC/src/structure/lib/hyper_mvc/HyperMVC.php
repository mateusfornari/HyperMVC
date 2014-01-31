<?php

class HyperMVC {

	/**
	 * @var string 
	 */
	protected static $viewName;

	/**
	 * @var string 
	 */
	protected static $includePath;

	/**
	 * @var DOMDocument 
	 */
	protected static $domDocument = null;

	/**
	 * @var DOMElement 
	 */
	protected static $contentTag;

	/**
	 * @var DOMDocument 
	 */
	protected static $viewElement;

	/**
	 * @var string
	 */
	private static $controllerName = 'Controller';

	/**
	 * @var string
	 */
	private static $viewRoot = '';

	/**
	 * @var string
	 */
	private static $controllerRoot = '';
	
	private static $noExecute = false;
	
	protected static $urlCustomVars = array();

	/**
	 * @var HyperMVCController
	 */
	protected static $controller;
	private static $elementsToRemove = array();

	const DATA_H_CONTENT = 'data-h-content';
	const DATA_H_ITEM = 'data-h-item';
	const DATA_H_SOURCE = 'data-h-source';
	const DATA_H_RENDER = 'data-h-render';
	const DATA_H_VIEW = 'data-h-view';
	const DATA_H_COMPONENT = 'data-h-component';

	protected static $attributes = array(self::DATA_H_CONTENT, self::DATA_H_ITEM, self::DATA_H_SOURCE, self::DATA_H_RENDER, self::DATA_H_VIEW, self::DATA_H_COMPONENT);

	private function __construct() {
		
	}

	/**
	 * 
	 * @param boolean $printOutput 
	 * @return string The result HTML.
	 */
	public static function render($printOutput = true) {
		
		$viewRoot = self::$includePath . 'view/' . self::$viewRoot.'/';
		if(isset($_GET['hmvcQuery'])){
			$query = $_GET['hmvcQuery'];
			if($query != ''){
				$qryParts = explode('/', $query);
				$qry = '';
				for($i = 0; $i < count($qryParts); $i++){
					$qry .= $qry == '' ? $qryParts[$i] : '/'.$qryParts[$i];
					if(!file_exists($viewRoot.$qry) && !file_exists($viewRoot.$qry.'.html')){
						$qry = '';
						self::$urlCustomVars[] = $qryParts[$i];
						//break;
					}
					if(file_exists($viewRoot.$qry.'.html')){
						array_merge(self::$urlCustomVars, array_slice($qryParts, $i + 1));
						break;
					}
				}
				if(is_dir($viewRoot.$qry))
					$qry .= substr($query, -1) == '/' ? 'index' : '/index';
			}else{
				$qry = 'index';
			}
			
			self::$viewName = $qry;
		}
		
		self::initDomDocument();
		self::findContentTag();
		self::insertViewInTemplate();
		self::includeController();
		if(!self::$noExecute)
			self::execute();

		foreach (self::$elementsToRemove as $e) {
			$e->parentNode->removeChild($e);
		}

		$output = str_replace('%amp%', '&', self::$domDocument->saveHTML());
		if ($printOutput)
			echo $output;
		return $output;
	}

	protected static function includeController() {
		$controller = self::$includePath . 'controller/' . self::$controllerRoot . '/' . self::$viewName . '.php';
		if (file_exists($controller)) {
			require_once $controller;
			if (class_exists(self::$controllerName)) {
				self::$controller = new self::$controllerName();
					if (is_a(self::$controller, 'HyperMVCController')){
						self::$controller->onStart();
					if (isset($_GET) && !empty($_GET)) {
						self::$controller->onGetRequest();
					}
					if (isset($_POST) && !empty($_POST)) {
						self::$controller->onPostRequest();
					}
					self::$controller->onFinish();
				}
			}
		}
	}

	protected static function initDomDocument() {
		$parts = explode('/', self::$viewName);
		if (sizeof($parts) > 1) {
			$dir = implode('/', array_slice($parts, 0, sizeof($parts) - 1));
		} else {
			$dir = '';
		}
		$template = self::$includePath . 'view/' . self::$viewRoot . '/' . $dir . '/template.html';
		if (file_exists($template)) {
			self::$domDocument = new DOMDocument();
			self::$domDocument->loadHTML(str_replace('&', '%amp%', file_get_contents($template)));
		} else {
			self::$domDocument = null;
		}
	}

	protected static function findContentTag() {
		if (!is_null(self::$domDocument)) {
			$body = self::$domDocument->getElementsByTagName('body');
			$e = $body->item(0);
			self::$contentTag = self::getElementByAttribute($e, self::DATA_H_VIEW);
		}
	}

	protected static function insertViewInTemplate() {
		$view = self::$includePath . 'view/' . self::$viewRoot . '/' . self::$viewName . '.html';
		if (file_exists($view)) {
			if (!is_null(self::$domDocument)) {
				self::$viewElement = new DOMDocument();
				self::$viewElement->loadHTML('<html><meta charset="UTF-8">'.str_replace('&', '%amp%', file_get_contents($view)).'</html>');

				$children = self::$viewElement->getElementsByTagName('body')->item(0)->childNodes;
			
				foreach ($children as $c) {
			
					$c = self::$domDocument->importNode($c, true);
					self::$contentTag->appendChild($c);
				}
				self::$contentTag->removeAttribute(self::DATA_H_VIEW);
			} else {
				self::$domDocument = new DOMDocument();
				self::$domDocument->loadHTML(str_replace('&', '%amp%', file_get_contents($view)));
			}
		} else {
			throw new Exception('View file (' . self::$viewName . ') not found!');
		}
	}

	/**
	 * 
	 * @param type $element
	 * @param type $attribute
	 * @param type $value
	 * @param type $result
	 * @return DOMElement
	 */
	protected static function getElementByAttribute($element, $attribute, $value = null, $result = null) {
		foreach ($element->childNodes as $c) {
			if (is_a($c, 'DOMElement')) {
				if ($c->hasAttribute($attribute) && (is_null($value) || $c->getAttribute($attribute) == $value)) {
					return $c;
				} else {
					$result = self::getElementByAttribute($c, $attribute, $value, $result);
				}
			}
		}
		return $result;
	}

	protected static function execute() {

		self::treatElements(self::$domDocument->documentElement);
	}

	private static function processValue($attribute, $element, $attributeValue, $obj = null, $objName = null) {

		if (!in_array($attribute->name, self::$attributes) || $attribute->name == self::DATA_H_CONTENT) {
			$value = self::getValue($attributeValue, $obj, $objName);
			$pos = strpos($attribute->value, $attributeValue);
			$len = strlen($attributeValue);
			$val = substr($attribute->value, 0, $pos).$value.substr($attribute->value, $pos + $len);
			$attribute->value = $val;
		} else {
			if ($attribute->name == self::DATA_H_SOURCE) {
				self::treatDataSource($element, $attribute, $obj, $objName);
			} else if ($attribute->name == self::DATA_H_RENDER) {
				$value = self::getValue($attributeValue, $obj, $objName);
				if (!$value) {
					self::$elementsToRemove[] = $element;
				}
			}
		}
	}
	private static function processNodeValue($element, $nodeValue, $obj = null, $objName = null) {
		$value = self::getValue($nodeValue, $obj, $objName);
		$pos = strpos($element->nodeValue, $nodeValue);
		$len = strlen($nodeValue);
		$val = substr($element->nodeValue, 0, $pos).$value.substr($element->nodeValue, $pos + $len);
		$element->nodeValue = $val;
	}

	private static function getValue($attributeValue, $obj = null, $objName = null) {

		if (is_string($obj)) {
			return $obj;
		}
		$attrValue = preg_replace('/[#{}]/', '', $attributeValue);
		$not = substr($attrValue, 0, 1) == '!';
		if($not)
			$attrValue = substr($attrValue, 1);
		$attrParts = explode('->', $attrValue);

		if (is_null($obj)) {

			if (preg_match('/::/', $attrParts[0])) {
				$classParts = explode('::', $attrParts[0]);
				$value = $classParts[0];
				for ($i = 1; $i < sizeof($classParts); $i++) {
					$part = $classParts[$i];
					if (preg_match('/\((.+)?\)/', $part)) {
						$part = preg_replace('/\((.+)?\)/', '', $part);
						$value = $value::$part();
					} else if (preg_match('/\[(.+)?\]/', $part, $matches)) {
						$index = isset($matches[1]) ? $matches[1] : '';
						$part = preg_replace('/\[(.+)?\]/', '', $part);
						$part = str_replace('$', '', $part);
						$value = $value::$$part;
						$value = $value[$index];
					} else {
						$part = str_replace('$', '', $part);
						$value = $value::$$part;
					}
				}
			} else {
				if (!preg_match('/\[(.+)?\]/', $attrParts[0]) && !preg_match('/\(\)/', $attrParts[0])) {
					$className = $attrParts[0];
					if ($className != self::$controllerName) {
						$obj = new $className();
					} else {
						$obj = self::$controller;
					}
					$value = $obj;
				} else {
					if (preg_match('/\(\)/', $attrParts[0])) {
						$function = preg_replace('/[\(\)]/', '', $attrParts[0]);
						$value = $function();
					} else {
						return null;
					}
				}
			}
		} else {
			if ($objName != preg_replace('/\[(.+)?\]/', '', $attrParts[0])) {
				if (!preg_match('/\[(.+)?\]/', $attrParts[0])) {
					$className = $attrParts[0];
					if ($className != self::$controllerName){
						if(class_exists($className))
							$obj = new $className();
						else
							return $obj;
					}else{
						$obj = self::$controller;
					}
					$value = $obj;
				}else {
					return $obj;
				}
			} else {
				if (preg_match('/\[(.+)?\]/', $attrParts[0], $matches)) {
					$index = isset($matches[1]) ? $matches[1] : '';
					$part = preg_replace('/\[(.+)?\]/', '', $attrParts[0]);
					$value = $obj[$index];
				} else {
					$value = $obj;
				}
			}
		}

		for ($i = 1; $i < sizeof($attrParts); $i++) {
			if (preg_match('/::/', $attrParts[$i])) {
				$classParts = explode('::', $attrParts[$i]);
				$part = $classParts[0];
				if (preg_match('/\((.+)?\)/', $part)) {
					$part = preg_replace('/\((.+)?\)/', '', $part);
					$value = $value->$part();
				} else if (preg_match('/\[(.+)?\]/', $part, $matches)) {
					$index = isset($matches[1]) ? $matches[1] : '';
					$part = preg_replace('/\[(.+)?\]/', '', $part);
					$part = str_replace('$', '', $part);
					$value = $value->$part;
					$value = $value[$index];
				} else {
					$part = str_replace('$', '', $part);
					$value = $value->$part;
				}
				for ($j = 1; $j < sizeof($classParts); $j++) {
					$part = $classParts[$j];
					if (preg_match('/\((.+)?\)/', $part)) {
						$part = preg_replace('/\((.+)?\)/', '', $part);
						$value = $value::$part();
					} else if (preg_match('/\[(.+)?\]/', $part, $matches)) {
						$index = isset($matches[1]) ? $matches[1] : '';
						$part = preg_replace('/\[(.+)?\]/', '', $part);
						$part = str_replace('$', '', $part);
						$value = $value::$$part;
						$value = $value[$index];
					} else {
						$part = str_replace('$', '', $part);
						$value = $value::$$part;
					}
				}
			}
			$part = $attrParts[$i];
			if (preg_match('/\((.+)?\)/', $part)) {
				$part = preg_replace('/\((.+)?\)/', '', $part);
				$value = $value->$part();
			} else if (preg_match('/\[(.+)?\]/', $part, $matches)) {
				$index = isset($matches[1]) ? $matches[1] : '';
				$part = preg_replace('/\[(.+)?\]/', '', $part);
				$value = $value->$part[$index];
			} else {
				$value = $value->$part;
			}
		}
		return $not ? !$value : $value;
	}

	private function compileValue($value, $part){
		if (preg_match('/\((.+)?\)/', $part)) {
			$part = preg_replace('/\((.+)?\)/', '', $part);
			$value = $value::$part();
		} else if (preg_match('/\[(.+)?\]/', $part, $matches)) {
			$index = isset($matches[1]) ? $matches[1] : '';
			$part = preg_replace('/\[(.+)?\]/', '', $part);
			$part = str_replace('$', '', $part);
			$value = $value::$$part;
			$value = $value[$index];
		} else {
			$part = str_replace('$', '', $part);
			$value = $value::$$part;
		}
		return $value;
	}


	
	protected static function treatElements($root, $obj = null, $objName = null) {
		
		if (is_a($root, 'DOMElement')) {
			$attributes = array();
			foreach ($root->attributes as $a) {
				
				if (preg_match_all('/#{[^#{}]+}/', $a->value, $matches)) {
					if (isset($matches[0])) {
						foreach ($matches[0] as $attributeValue) {
							if(!is_null($obj) && !is_null($objName)){
								$value = trim(preg_replace('/[#{}]/', '', $attributeValue));
								if(preg_match('/^'.$objName.'[-> ^[]+/', $value) || $objName == $value){
									self::processValue($a, $root, $attributeValue, $obj, $objName);
									if(in_array($a->name, self::$attributes)){
										$attributes[] = $a->name;
									}
								}
							}else{
								self::processValue($a, $root, $attributeValue, $obj, $objName);
								if(in_array($a->name, self::$attributes)){
									$attributes[] = $a->name;
								}
							}
						}
					}

				}
			}
			foreach ($attributes as $a){
				if($a == self::DATA_H_CONTENT){
					$root->appendChild(self::$domDocument->createTextNode($root->getAttribute($a)));
				}
				$root->removeAttribute($a);
			}
			foreach($root->childNodes as $node) {
				if ($node->nodeType != XML_TEXT_NODE)
					continue;
				if (preg_match_all('/#{[^#{}]+}/', $node->nodeValue, $matches)) {
				
					if (isset($matches[0])) {
						foreach ($matches[0] as $nodeValue) {
							if(!is_null($obj) && !is_null($objName)){
								$value = trim(preg_replace('/[#{}]/', '', $nodeValue));
								if(preg_match('/^'.$objName.'[-> ^[]+/', $value) || $objName == $value){
									self::processNodeValue($node, $nodeValue, $obj, $objName);
								}
							}else{
								self::processNodeValue($node, $nodeValue, $obj, $objName);
							}
						}
					}
				}
			}
			foreach ($root->childNodes as $c) {
				self::treatElements($c, $obj, $objName);
			}
		}
		
	}

	protected static function treatDataSource($element, $attribute, $obj = null, $objName = null) {
		$list = self::getValue($attribute->value, $obj, $objName);
		
		$item = self::getElementByAttribute($element, self::DATA_H_ITEM);
		
		$itemName = $item->getAttribute(self::DATA_H_ITEM);
		
		$item->removeAttribute(self::DATA_H_ITEM);
		
		foreach ($list as $l) {
			
			$i = $item->cloneNode(true);
			
			self::treatElements($i, $l, $itemName);

			$item->parentNode->appendChild($i);
		}
		
		$item->parentNode->removeChild($item);
		
	}

	public static function getIncludePath() {
		return self::$includePath;
	}

	public static function setIncludePath($includePath) {
		self::$includePath = $includePath;
	}

	public static function getViewName() {
		return self::$viewName;
	}

	public static function setViewName($viewName) {
		self::$viewName = $viewName;
	}

	public static function setControllerName($controllerName) {
		self::$controllerName = $controllerName;
	}

	public static function getViewRoot() {
		return self::$viewRoot;
	}

	public static function setViewRoot($viewRoot) {
		self::$viewRoot = $viewRoot;
	}

	public static function getControllerRoot() {
		return self::$controllerRoot;
	}

	public static function setControllerRoot($controllerRoot) {
		self::$controllerRoot = $controllerRoot;
	}
	
	public static function setNoExecute($noExecute) {
		self::$noExecute = $noExecute;
	}
	public static function getUrlCustomVars() {
		return self::$urlCustomVars;
	}



}

abstract class HyperMVCController {

	abstract public function onStart();

	abstract public function onPostRequest();

	abstract public function onGetRequest();

	abstract public function onFinish();
}
?>