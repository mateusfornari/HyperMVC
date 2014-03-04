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
     * @var array
     */
    private $elementsToRemove = array();
    
    /**
     * @var string 
     */
    protected $output = '';


    /**
     *
     * @var HyperMVC
     */
    private static $instance = null;

    const DATA_H_CONTENT = 'data-h-content';
    const DATA_H_ITEM = 'data-h-item';
    const DATA_H_SOURCE = 'data-h-source';
    const DATA_H_RENDER = 'data-h-render';
    const DATA_H_VIEW = 'data-h-view';
    const DATA_H_COMPONENT = 'data-h-component';
    const DATA_H_CHECKED = 'data-h-checked';
    const DATA_H_DISABLED = 'data-h-disabled';
    const DATA_H_SELECTED = 'data-h-selected';
    const DATA_H_REQUIRED = 'data-h-required';

    protected $attributes = array(self::DATA_H_CONTENT, self::DATA_H_ITEM, self::DATA_H_SOURCE, self::DATA_H_RENDER, self::DATA_H_VIEW, self::DATA_H_COMPONENT, self::DATA_H_CHECKED, self::DATA_H_DISABLED, self::DATA_H_SELECTED, self::DATA_H_REQUIRED);

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
        
        if (isset($vars[':action'])) {
            $action = preg_replace('/[^a-zA-Z0-9_]/', '', $vars[':action']);
        } else {
            $action = 'index';
        }

        $this->viewName = $viewDir . $action . '.html';
        
        if(!method_exists($this->controller, $action)){
            return $this->notFoundPage($printOutput);
        }
        
        ob_start();
        $this->controller->beforeAction();
        $this->output .= ob_get_clean();
        
        ob_start();
        $this->controller->$action();
        
        $this->controller->afterAction();
        
        $this->controller->beforeRender();
        $this->output .= ob_get_clean();

        
        $this->initDomDocument();
        $this->findContentTag();
        $this->insertViewInTemplate();
        if (!$this->noExecute)
            $this->execute();

        foreach ($this->elementsToRemove as $e) {
            $e->parentNode->removeChild($e);
        }

        $this->output .= str_replace('%amp%', '&', $this->domDocument->saveHTML());
        
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

                ob_start();
                include $templateFile;
                $templateString = ob_get_clean();

                $this->domDocument->loadHTML(str_replace('&', '%amp%', $templateString));
               
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
                $this->viewElement->loadHTML('<html><meta charset="UTF-8">' . str_replace('&', '%amp%', $viewString) . '</html>');
                $children = $this->viewElement->getElementsByTagName('body')->item(0)->childNodes;

                foreach ($children as $c) {

                    $c = $this->domDocument->importNode($c, true);
                    $this->contentTag->appendChild($c);
                }
                $this->contentTag->removeAttribute(self::DATA_H_VIEW);
            } else {
                $this->domDocument = new \DOMDocument();
                $this->domDocument->loadHTML(str_replace('&', '%amp%', $viewString));
            }
        } else {
            $this->notFoundPage(true);
            exit();
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

    /**
     * 
     * @param type $element
     * @param type $attribute
     * @param type $value
     * @param type $result
     * @return DOMElement
     */
    protected function getElementByAttribute($element, $attribute, $value = null, $result = null) {
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

    protected function execute() {

        $this->treatElements($this->domDocument->documentElement);
    }

    private function processValue($attribute, $element, $attributeValue, $obj = null, $objName = null) {
        
        if (!in_array($attribute->name, $this->attributes) || $attribute->name == self::DATA_H_CONTENT) {
            $value = $this->getValue($attributeValue, $obj, $objName);
            $pos = strpos($attribute->value, $attributeValue);
            $len = strlen($attributeValue);
            $val = substr($attribute->value, 0, $pos) . $value . substr($attribute->value, $pos + $len);
            $attribute->value = $val;
        } else {
            if ($attribute->name == self::DATA_H_SOURCE) {
                $this->treatDataSource($element, $attribute, $obj, $objName);
            } elseif ($attribute->name == self::DATA_H_RENDER) {
                $value = $this->getValue($attributeValue, $obj, $objName);
                if (!$value) {
                    $this->elementsToRemove[] = $element;
                }
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
                $value = $this->getValue($attributeValue, $obj, $objName);
                if ($value) {
                    $element->setAttribute('checked', 'checked');
                }#{t->mostraForm}
                $element->removeAttribute(self::DATA_H_CHECKED);
            } elseif ($attribute->name == self::DATA_H_DISABLED) {
                $value = $this->getValue($attributeValue, $obj, $objName);
                if ($value) {
                    $element->setAttribute('disabled', 'disabled');
                }
                $element->removeAttribute(self::DATA_H_DISABLED);
            } elseif ($attribute->name == self::DATA_H_SELECTED) {
                $value = $this->getValue($attributeValue, $obj, $objName);
                if ($value) {
                    $element->setAttribute('selected', 'selected');
                }
                $element->removeAttribute(self::DATA_H_SELECTED);
            } elseif ($attribute->name == self::DATA_H_REQUIRED) {
                $value = $this->getValue($attributeValue, $obj, $objName);
                if ($value) {
                    $element->setAttribute('required', 'required');
                }
                $element->removeAttribute(self::DATA_H_REQUIRED);
            }
        }
    }

    private function processNodeValue($element, $nodeValue, $obj = null, $objName = null) {
        $value = $this->getValue($nodeValue, $obj, $objName);
        $pos = strpos($element->nodeValue, $nodeValue);
        $len = strlen($nodeValue);
        $val = substr($element->nodeValue, 0, $pos) . $value . substr($element->nodeValue, $pos + $len);
        $element->nodeValue = $val;
    }

    private function getValue($attributeValue, $obj = null, $objName = null) {

        if (is_string($obj)) {
            return $obj;
        }
        $attrValue = preg_replace('/[#{}]/', '', $attributeValue);
        $not = substr($attrValue, 0, 1) == '!';
        if ($not)
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
                    } elseif (preg_match('/\[(.+)?\]/', $part, $matches)) {
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
                    if ($className != $this->controller->getObjectName()) {
                        $obj = new $className();
                    } else {
                        $obj = $this->controller;
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
                    if ($className != $this->controller->getObjectName()) {
                        if (class_exists($className))
                            $obj = new $className();
                        else
                            return $obj;
                    }else {
                        $obj = $this->controller;
                    }
                    $value = $obj;
                } else {
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
                } elseif (preg_match('/\[(.+)?\]/', $part, $matches)) {
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
                    } elseif (preg_match('/\[(.+)?\]/', $part, $matches)) {
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
            } elseif (preg_match('/\[(.+)?\]/', $part, $matches)) {
                $index = isset($matches[1]) ? $matches[1] : '';
                $part = preg_replace('/\[(.+)?\]/', '', $part);
                $value = $value->$part[$index];
            } else {
                $value = $value->$part;
            }
        }
        return $not ? !$value : $value;
    }

    private function compileValue($value, $part) {
        if (preg_match('/\((.+)?\)/', $part)) {
            $part = preg_replace('/\((.+)?\)/', '', $part);
            $value = $value::$part();
        } elseif (preg_match('/\[(.+)?\]/', $part, $matches)) {
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

    protected function treatElements($root, $obj = null, $objName = null) {

        if ($root instanceof \DOMElement) {
            $attributes = array();
            foreach ($root->attributes as $a) {

                if (preg_match_all('/#{[^#{}]+}/', $a->value, $matches)) {
                    if (isset($matches[0])) {
                        foreach ($matches[0] as $attributeValue) {
                            if (!is_null($obj) && !is_null($objName)) {
                                $value = trim(preg_replace('/[#{}]/', '', $attributeValue));
                                if (preg_match('/^' . $objName . '[-> ^[]+/', $value) || $objName == $value) {
                                    $this->processValue($a, $root, $attributeValue, $obj, $objName);
                                    if (in_array($a->name, $this->attributes)) {
                                        $attributes[] = $a->name;
                                    }
                                }
                            } else {
                                $this->processValue($a, $root, $attributeValue, $obj, $objName);
                                if (in_array($a->name, $this->attributes)) {
                                    $attributes[] = $a->name;
                                }
                            }
                        }
                    }
                }else{
					if($a->name == 'src' || $a->name == 'href' || $a->name == 'action'){
						$value = $a->value;
						if(strpos($value, '://') === false && $value[0] != '?' && $value[0] != '#'){
                            if($value[0] == '/'){
                                $value = substr($value, 1);
                            }
							$a->value = Request::baseUrl().$value;
						}
					}
				}
            }
            foreach ($attributes as $a) {
                if ($a == self::DATA_H_CONTENT) {
                    $root->appendChild($this->domDocument->createTextNode($root->getAttribute($a)));
                }
                $root->removeAttribute($a);
            }
            foreach ($root->childNodes as $node) {
                if ($node->nodeType != XML_TEXT_NODE)
                    continue;
                if (preg_match_all('/#{[^#{}]+}/', $node->nodeValue, $matches)) {

                    if (isset($matches[0])) {
                        foreach ($matches[0] as $nodeValue) {
                            if (!is_null($obj) && !is_null($objName)) {
                                $value = trim(preg_replace('/[#{}]/', '', $nodeValue));
                                if (preg_match('/^' . $objName . '[-> ^[]+/', $value) || $objName == $value) {
                                    $this->processNodeValue($node, $nodeValue, $obj, $objName);
                                }
                            } else {
                                $this->processNodeValue($node, $nodeValue, $obj, $objName);
                            }
                        }
                    }
                }
            }
            foreach ($root->childNodes as $c) {
                $this->treatElements($c, $obj, $objName);
            }
        }
    }

    protected function treatDataSource($element, $attribute, $obj = null, $objName = null) {
        $list = $this->getValue($attribute->value, $obj, $objName);

        $item = $this->getElementByAttribute($element, self::DATA_H_ITEM);

        $itemName = $item->getAttribute(self::DATA_H_ITEM);

        $item->removeAttribute(self::DATA_H_ITEM);

        foreach ($list as $l) {

            $i = $item->cloneNode(true);

            $this->treatElements($i, $l, $itemName);

            $item->parentNode->appendChild($i);
        }

        $item->parentNode->removeChild($item);
    }

    public static function setNoExecute($noExecute) {
        $this->noExecute = $noExecute;
    }

    public static function setViewRoot($dirName) {
        self::$instance->viewRoot = $dirName;
    }
    
    /**
     * 
     * @param string $route
     * @param array $varsFormat
     * @return HyperMVCRoute
     */
    public static function addRoute($route, $varsFormat = array()) {
        $route = new HyperMVCRoute($route, '', $varsFormat);
        self::$routes[] = $route;
        return $route;
    }

}
