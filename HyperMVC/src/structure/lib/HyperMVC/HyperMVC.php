<?php

class HyperMVC {

    /**
     * @var string 
     */
    protected $viewName;

    /**
     * @var string 
     */
    protected $includePath;

    /**
     * @var DOMDocument 
     */
    protected $domDocument = null;

    /**
     * @var DOMElement 
     */
    protected $contentTag;

    /**
     * @var DOMDocument 
     */
    protected $viewElement;

    /**
     * @var array|HyperMVCRoute 
     */
    protected static $routes = array();

    /**
     * @var string
     */
    private $controllerName = null;

    /**
     * @var string
     */
    private $viewRoot = '';

    /**
     * @var boolean
     */
    private $noExecute = false;

    
    /**
     * @var HyperMVCController
     */
    protected $controller;

    
    /**
     * @var string 
     */
    protected $output = '';
    
    /**
     * @var HyperMVCRoute 
     */
    protected $route = null;
    
	
    private $errors = array();
    
    private $visitedNodes = 0;
    
    private $elementsToInsert = array();


    /**
     *
     * @var HyperMVC
     */
    private static $instance = null;

    const DATA_H_ITEM = 'data-h-item';
    const DATA_H_SOURCE = 'data-h-source';
    const DATA_H_RENDER = 'data-h-render';
    const DATA_H_VIEW = 'data-h-view';
    const DATA_H_COMPONENT = 'data-h-component';
    const DATA_H_CHECKED = 'data-h-checked';
    const DATA_H_DISABLED = 'data-h-disabled';
    const DATA_H_SELECTED = 'data-h-selected';
    const DATA_H_REQUIRED = 'data-h-required';

    protected $attributes = array(self::DATA_H_ITEM, self::DATA_H_SOURCE, self::DATA_H_RENDER, self::DATA_H_VIEW, self::DATA_H_COMPONENT, self::DATA_H_CHECKED, self::DATA_H_DISABLED, self::DATA_H_SELECTED, self::DATA_H_REQUIRED);

    private function __construct() {
        
    }

    /**
     * 
     * @param boolean $printOutput 
     * @return string The result HTML.
     */
    public static function render($printOutput = true){
        self::$instance = new HyperMVC();
		
        self::$instance->process($printOutput);
    }
    
    /**
     * 
     * @param boolean $printOutput 
     * @return string The result HTML.
     */
    private function process($printOutput = true) {
        
        if (is_null($this->includePath)) {
            $this->includePath = str_replace('lib/HyperMVC', '', __DIR__);
        }
        
        if(isset($_GET['hmvcQuery'])){
            $query = $_GET['hmvcQuery'];
            unset($_GET['hmvcQuery']);
        }else{
            $query = null;
        }
        
        Request::init();
        
        $vars = null;
        foreach (self::$routes as $r) {
            $r->setQuery($query);
            if ($r->match()) {
                $vars = $r->getVars();
                $this->route = $r;
                break;
            }
        }
        
        
        if(is_null($this->controllerName)){
            $this->controllerName = isset($vars[':controller']) ? $vars[':controller'].'Controller' : 'DefaultController';
        }
        
        $controllerFile = $this->getControllerFile();
        $basicControllerFile = str_replace('lib/HyperMVC', '', __DIR__).'controller/BasicController.php';
        if (!is_null($controllerFile) && file_exists($basicControllerFile) && strtolower($this->controllerName) != 'basiccontroller') {
            ob_start();
            require_once $basicControllerFile;
            require_once $controllerFile;
            $this->controller = new $this->controllerName;
            $this->output .= ob_get_clean();
        } else {
            return $this->notFoundPage($printOutput);
        }

        if (is_null($this->controller->getViewName())) {
            $viewName = str_replace('Controller', '', get_class($this->controller));
        } else {
            $viewName = $this->controller->getViewName();
        }
        
        $viewNameParts = explode('/', $viewName);
        if(count($viewNameParts) > 1){
            $this->viewRoot .= implode('/', array_slice($viewNameParts, 0, count($viewNameParts) - 1));
        }
        
        $viewDir = $this->includePath . 'view/'.($this->viewRoot != '' ? $this->viewRoot.'/' : '').$viewName . '/';
        
        if ($viewName != 'NotFound' && isset($vars[':action'])) {
            $action = preg_replace('/[^a-zA-Z0-9_]/', '', $vars[':action']);
        } else {
            $action = 'index';
        }

        $this->viewName = $viewDir . $action . '.html';
		
		$this->viewName = $this->getViewFile();
        
		$action .= 'Action';
		
        if($viewName != 'NotFound' && !method_exists($this->controller, $action)){
            return $this->notFoundPage($printOutput);
        }
        
        ob_start();
        $this->controller->beforeAction();
        $this->output .= ob_get_clean();
        
        ob_start();
        $this->controller->$action();
        
        $this->controller->afterAction();
        
        $this->output .= ob_get_clean();

        
        $this->initDomDocument();
        $this->findContentTag();
        $this->insertViewInTemplate();
		
        if (!$this->noExecute)
            $this->execute();

        if(!is_null($this->contentTag)){
			$this->output .= $this->domDocument->saveHTML();
		}else{
			$children = $this->domDocument->getElementsByTagName('body')->item(0)->childNodes;
			foreach ($children as $child){
				$this->output .= $this->domDocument->saveHTML($child);
			}
		}
        ob_start();
        $this->controller->afterRender();
        $this->output .= ob_get_clean();
        
        if ($printOutput)
            echo $this->output;
        
        return $this->output;
    }

    protected function notFoundPage($printOutput){
        if(file_exists($this->includePath.'controller/NotFoundController.php')){
            $component = new HyperMVC();
            $component->controllerName = 'NotFoundController';
            $component->includePath = $this->includePath;
            return $component->process($printOutput);
        } else {
            throw new \Exception("Controller ($this->controllerName) not found!");
        }
    }
    
    protected function initDomDocument() {

        if (!is_null($this->controller->getTemplateName())) {

            $templateName = $this->controller->getTemplateName();
            
            $templateFile = $this->includePath . 'view/' . ($this->viewRoot != '' ? $this->viewRoot . '/' : '') . $templateName . '.html';
            if (file_exists($templateFile)) {
                $this->domDocument = new \DOMDocument();
                $this->domDocument->preserveWhiteSpace = false;
                ob_start();
                include $templateFile;
                $templateString = ob_get_clean();
                
                @$this->domDocument->loadHTML($templateString);
                
            } else {
                
                $this->domDocument = null;
            }
        }

        
    }

    protected function findContentTag() {
        if (!is_null($this->domDocument)) {
            $body = $this->domDocument->getElementsByTagName('body');
            $e = $body->item(0);
            $this->contentTag = $this->getElementByAttribute($e, self::DATA_H_VIEW);
        }
    }

    protected function insertViewInTemplate() {
        
        if (file_exists($this->viewName)) {
            
            ob_start();
            include $this->viewName;
            $viewString = ob_get_clean();
            
            if (!is_null($this->domDocument) && !is_null($this->contentTag)) {
                $this->viewElement = new \DOMDocument();
                $this->viewElement->preserveWhiteSpace = false;
                @$this->viewElement->loadHTML('<html><meta charset="UTF-8"><body>' . $viewString . '</body></html>');
                $children = $this->viewElement->getElementsByTagName('body')->item(0)->childNodes;

                foreach ($children as $c) {

                    $c = $this->domDocument->importNode($c, true);
                    $this->contentTag->appendChild($c);
                }
                
            } else {
                $this->domDocument = new \DOMDocument();
                $this->domDocument->preserveWhiteSpace = false;
                @$this->domDocument->loadHTML($viewString);
            }
        }
		if (!is_null($this->contentTag)) {
			$this->contentTag->removeAttribute(self::DATA_H_VIEW);
		}
    }

    private function getControllerFile() {
        $dir = $this->includePath . 'controller/';
        
        if (file_exists($dir . $this->controllerName . '.php')) {
            return $dir . $this->controllerName . '.php';
        }
        $files = glob($dir . '*');

        $contrllerLower = strtolower($dir . $this->controllerName . '.php');
        foreach ($files as $f) {
            $fLower = strtolower($f);
            if ($fLower == $contrllerLower) {
                return $f;
            }
        }
        return null;
    }
	
	private function getViewFile() {
        $dir = implode('/', array_slice(explode('/', $this->viewName), 0, -1));
        
        if (file_exists($this->viewName)) {
            return $this->viewName;
        }
        $files = glob($dir . '/*');

        $viewLower = strtolower($this->viewName);
        foreach ($files as $f) {
            $fLower = strtolower($f);
            if ($fLower == $viewLower) {
                return $f;
            }
        }
        return null;
    }


    /**
     * 
     * @param type $element
     * @param type $attribute
     * @param type $value
     * @param type $result
     * @return DOMElement
     */
    protected function getElementByAttribute($element, $attribute, $value = null, $result = null) {
		if ($element->hasAttribute($attribute) && (is_null($value) || $element->getAttribute($attribute) == $value)) {
			return $element;
		}
        foreach ($element->childNodes as $c) {
            if ($c instanceof \DOMElement) {
                if ($c->hasAttribute($attribute) && (is_null($value) || $c->getAttribute($attribute) == $value)) {
                    return $c;
                } else {
                    $result = $this->getElementByAttribute($c, $attribute, $value, $result);
                }
            }
        }
        return $result;
    }
    /**
     * 
     * @param type $element
     * @param type $attribute
     * @param type $value
     * @param type $result
     * @return DOMElement
     */
    protected function getItemsDataSource($element, &$items = array()) {
		if ($element->hasAttribute(self::DATA_H_ITEM)) {
			$items[] = $element;
		}else{
			foreach ($element->childNodes as $c) {
				if ($c instanceof \DOMElement) {
					if ($c->hasAttribute(self::DATA_H_ITEM)) {
						$items[] = $c;
					} elseif($c->hasAttribute(self::DATA_H_SOURCE)) {
						break;
					} else {
						$items = $this->getItemsDataSource($c, $items);
					}
				}
			}
		}
		return $items;
    }
	
    protected function execute() {
        $this->treatElements($this->domDocument->getElementsByTagName('html')->item(0));
        
        foreach ($this->elementsToInsert as $e){
            foreach ($e as $d){
                $pos = $d['pos'];
                $parent = $d['parent'];
                if(isset($d['elements'])){
                    foreach ($d['elements'] as $element){
                        if($pos){
                            $parent->insertBefore($element, $pos);
                        }else{
                            $parent->appendChild($element);
                        }
                    }
                }
            }
        }
    }

    private function processValue($attribute, &$element, $attributeValue, $obj = null, $objName = null, $key = null, $keyName = null) {
        if (!in_array($attribute->name, $this->attributes)) {
            $value = $this->getValue($attributeValue, $obj, $objName, $key, $keyName);
            if(is_object($value) && !method_exists($value, '__toString')){
                $this->errors[] = "Object of type '".get_class($value)."' could not be converted to string. Tag: '".$this->domDocument->saveHTML($element)."'. Instruction: $attributeValue.";
                $value = '';
            }
            $pos = strpos($attribute->value, $attributeValue);
            $len = strlen($attributeValue);
            $val = substr($attribute->value, 0, $pos) . $value . substr($attribute->value, $pos + $len);
            @$attribute->value = $val;
        } else {
            if ($attribute->name == self::DATA_H_SOURCE) {
				$element->removeAttribute(self::DATA_H_SOURCE);
                return $this->treatDataSource($element, $attribute, $obj, $objName, $key, $keyName);
                
            } elseif ($attribute->name == self::DATA_H_RENDER) {
                $value = $this->getValue($attributeValue, $obj, $objName, $key, $keyName);
                if (!$value) {
                    if($element->parentNode)
    					$element->parentNode->removeChild($element);
                    $element = null;
                    return false;
                }
                $element->removeAttribute(self::DATA_H_RENDER);
            } elseif ($attribute->name == self::DATA_H_COMPONENT) {
                
                $component = new HyperMVC();
                $component->controllerName = preg_replace('/[#{}]/', '', $attribute->value);
                $component->includePath = str_replace('lib/HyperMVC', '', __DIR__).'component/';
                require_once $component->includePath.'controller/BasicComponent.php';
                $result = $component->process(false);
                $domComponent = new \DOMDocument();
                $domComponent->loadHTML($result);
                
                $children = $domComponent->getElementsByTagName('body')->item(0)->childNodes;

                foreach ($children as $c) {
                    $c = self::$instance->domDocument->importNode($c, true);
                    $element->appendChild($c);
                }
                $element->removeAttribute(self::DATA_H_COMPONENT);
            } elseif ($attribute->name == self::DATA_H_CHECKED) {
                $value = $this->getValue($attributeValue, $obj, $objName, $key, $keyName);
                if ($value) {
                    $element->setAttribute('checked', 'checked');
                }
                $element->removeAttribute(self::DATA_H_CHECKED);
            } elseif ($attribute->name == self::DATA_H_DISABLED) {
                $value = $this->getValue($attributeValue, $obj, $objName, $key, $keyName);
                if ($value) {
                    $element->setAttribute('disabled', 'disabled');
                }
                $element->removeAttribute(self::DATA_H_DISABLED);
            } elseif ($attribute->name == self::DATA_H_SELECTED) {
                $value = $this->getValue($attributeValue, $obj, $objName, $key, $keyName);
                if ($value) {
                    $element->setAttribute('selected', 'selected');
                }
                $element->removeAttribute(self::DATA_H_SELECTED);
            } elseif ($attribute->name == self::DATA_H_REQUIRED) {
                $value = $this->getValue($attributeValue, $obj, $objName, $key, $keyName);
                if ($value) {
                    $element->setAttribute('required', 'required');
                }
                $element->removeAttribute(self::DATA_H_REQUIRED);
            }
        }
        return true;
    }

    private function processNodeValue(&$element, $nodeValue, $obj = null, $objName = null, $key = null, $keyName = null) {
        $value = $this->getValue($nodeValue, $obj, $objName, $key, $keyName);
        if(is_object($value) && !method_exists($value, '__toString')){
            $this->errors[] = "Object of type '".get_class($value)."' could not be converted to string. Tag: '".$this->domDocument->saveHTML($element->parentNode)."'. Instruction: $nodeValue.";
            $value = '';
        }
        $pos = strpos($element->nodeValue, $nodeValue);
        $len = strlen($nodeValue);
        $val = substr($element->nodeValue, 0, $pos) . $value . substr($element->nodeValue, $pos + $len);
        $element->nodeValue = $val;
    }

    private function getValue($hmvcAttributeValue, $hmvcValueObject = null, $hmvcObjectName = null, $hmvcValueKey = null, $hmvcKeyName = null){
        
		if(preg_match('/^#{(.+)}$/', trim($hmvcAttributeValue), $matches)){
			$hmvcAttributeValue = trim($matches[1]);
		}
		
        if($hmvcObjectName){
            foreach ($hmvcObjectName as $on){
                $$on = $hmvcValueObject[$on];
            }
        }
		if($hmvcKeyName){
            foreach ($hmvcKeyName as $kn){
                $$kn = $hmvcValueKey[$kn];
            }
        }
        $controllerObjName = $this->controller->getObjectName();
        
        $$controllerObjName = $this->controller;
        
        ob_start();
        $return = eval("return $hmvcAttributeValue;");
        $error = ob_get_clean();
        if($error != ''){
            $this->errors[] = 'There is something wrong in this instruction '.$hmvcAttributeValue;
        }
        return $return;
    }
    
    protected function treatElements(&$root, $obj = null, $objName = null, $key = null, $keyName = null) {
		$this->visitedNodes++;
        
        if ($root instanceof \DOMElement) {
			
            if($this->treatAttributes($root, $obj, $objName, $key, $keyName)){
                $childrenNodes = iterator_to_array($root->childNodes);

                foreach ($childrenNodes as $node){
                    $this->treatNode($node, $obj, $objName, $key, $keyName);
                    $this->treatElements($node, $obj, $objName, $key, $keyName);
                }
            }
        }
        
    }
    
    private function treatAttributes(&$root, $obj = null, $objName = null, $key = null, $keyName = null){
        if($root->hasAttribute(self::DATA_H_RENDER)){
            if(!$this->processValue($root->getAttributeNode(self::DATA_H_RENDER), $root, $root->getAttribute(self::DATA_H_RENDER), $obj, $objName, $key, $keyName)){
                return false;
            }
        }
        $length = $root->attributes->length;
        for($i = 0; $i < $length; $i++) {
            if($root->attributes->length < $length || (isset($a) && !$root->hasAttribute($a->name))){
                $i--;
            }
            $length = $root->attributes->length;
            $a = $root->attributes->item($i);
            if ($a && preg_match_all('/(#{[^#{}]+})|(#{[^#{}]*"[^"]+"[^#{}]*})|(#{[^#{}]*\'[^\']+\'[^#{}]*})/', trim($a->value), $matches)) {
                if (isset($matches[0])) {
                    foreach ($matches[0] as $attributeValue) {
                        if(!$this->processValue($a, $root, $attributeValue, $obj, $objName, $key, $keyName)){
                            return false;
                        }
                    }
                }
            }
            if($a && ($a->name == 'src' || $a->name == 'href' || $a->name == 'action')){
                $value = $a->value;
                if($value != ''){
                    if(strpos($value, '://') === false && $value[0] != '?' && $value[0] != '#'){
                        if($value[0] == '/'){
                            $value = substr($value, 1);
                        }
                        $a->value = Request::baseUrl().$value;
                    }
                }
            }
        }
        return true;
    }
    
    private function treatNode(&$node, $obj = null, $objName = null, $key = null, $keyName = null){
        if ($node->nodeType == XML_TEXT_NODE || $node->nodeType == XML_CDATA_SECTION_NODE){
            if (preg_match_all('/(#{[^#{}]+})|(#{[^#{}]*"[^"]+"[^#{}]*})|(#{[^#{}]*\'[^\']+\'[^#{}]*})/', $node->nodeValue, $matches)) {
                if (isset($matches[0])) {
                    foreach ($matches[0] as $nodeValue) {
                        $this->processNodeValue($node, $nodeValue, $obj, $objName, $key, $keyName);
                    }
                }
            }
        }
    }


    /**
     * 
     * @param DomElement $element
     * @param type $attribute
     * @param type $obj
     * @param type $objName
     * @param type $key
     * @param type $keyName
     * @throws Exception
     */
	protected function treatDataSource(&$element, $attribute, $obj = null, $objName = null, $key = null, $keyName = null) {
        
        $list = $this->getValue($attribute->value, $obj, $objName);
            
        if(is_array($list)){
            $listSize = count($list);
        }elseif($list instanceof Iterator){
            $listSize = iterator_count($list);
        }else{
            $listSize = 0;
            $this->errors[] = "Data source $attribute->value at tag '".$this->domDocument->saveHTML($element)."' is not an array.";
        }

        $items = $this->getItemsDataSource($element);
        
        if($listSize > 0){
            
            $itemNames = array();
            $keyNames = array();
            
            
            if(count($items) > 0){
                
                $elementsToInsert[0]['index'] = 0;
                $elementsToInsert[0]['pos'] = $items[0]->nextSibling;
                $elementsToInsert[0]['parent'] = $items[0]->parentNode;
                for($i = 0; $i < count($items); $i++){
                    
                    for($j = $i + 1; $j < count($items); $j++){
                        if($items[$i]->parentNode->isSameNode($items[$j]->parentNode)){
                            $elementsToInsert[$i]['pos'] = $items[$j]->nextSibling;
                            $elementsToInsert[$j] = $elementsToInsert[$i];
                        }else{
                            $elementsToInsert[$j]['index'] = $j;
                            $elementsToInsert[$j]['pos'] = $items[$j]->nextSibling;
                            $elementsToInsert[$j]['parent'] = $items[$j]->parentNode;
                        }
                    }
                    
                    $item = $items[$i];
                    
                    $name = $item->getAttribute(self::DATA_H_ITEM);

                    $nameParts = explode('=>', $name);
                    if(count($nameParts) > 1){
                        $name = preg_replace('/[^a-zA-Z0-9_]/', '', $nameParts[1]);
                        $kName = preg_replace('/[^a-zA-Z0-9_]/', '', $nameParts[0]);
                    }else{
                        $name = preg_replace('/[^a-zA-Z0-9_]/', '', $nameParts[0]);
                        $kName = null;
                    }

                    if($name == $this->controller->getObjectName()){
                        throw new Exception("Item has the same name ($name) as controller! ");
                    }
                    $itemNames[] = $name;
                    $keyNames[] = $kName;
                    $item->removeAttribute(self::DATA_H_ITEM);
                    if($item->parentNode)
                        $item->parentNode->removeChild($item);
                }
                
                foreach ($list as $k => $l) {

                    for ($j = 0; $j < count($items); $j++){
                        
                        $clone = clone $items[$j];
                        
                        $obj[$itemNames[$j]] = $l;
                        $objName[$itemNames[$j]] = $itemNames[$j];

                        $key[$keyNames[$j]] = $k;
                        $keyName[$keyNames[$j]] = $keyNames[$j];
                        
                        $this->treatElements($clone, $obj, $objName, $key, $keyName);
                        
                        if($clone)
                            $elementsToInsert[$elementsToInsert[$j]['index']]['elements'][] = $clone;
                    }
                }
                
                $this->elementsToInsert[] = $elementsToInsert;
                if($items[0]->isSameNode($element)){
                    return false;
                }
            }else{
                $this->errors[] = "No items found for data source $attribute->value at tag '".$this->domDocument->saveHTML($element)."'.";
            }
            
        }else{
            if(count($items) > 0){
                foreach ($items as $item){
                    if($item)
                        $item->parentNode->removeChild($item);
                }
            }else{
                $this->errors[] = "No items found for data source $attribute->value at tag '".$this->domDocument->saveHTML($element)."'.";
            }
        }
        return true;
    }

    public static function setNoExecute($noExecute) {
        $this->noExecute = $noExecute;
    }

    public static function setViewRoot($dirName) {
        self::$instance->viewRoot = $dirName;
    }
    
    public static function errorInfo(){
        return self::$instance->errors;
    }

    /**
     * 
     * @param string $routeitemNames
     * @param array $varsFormat
     * @return HyperMVCRoute
     */
    public static function addRoute($route, $varsFormat = array()) {
        $route = new HyperMVCRoute($route, '', $varsFormat);
        self::$routes[] = $route;
        return $route;
    }

    /**
     * 
     * @return HyperMVCRoute
     */
    public static function getRoute(){
        return self::$instance->route;
    }
    
    public static function getVisitedNodes(){
        return self::$instance->visitedNodes;
    }
}
