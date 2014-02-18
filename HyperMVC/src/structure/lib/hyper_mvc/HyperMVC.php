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
     * @var array|HyperMVCRoute 
     */
    protected static $routes = array();

    /**
     * @var string
     */
    private static $controllerName = 'HyperMVCController';

    /**
     * @var string
     */
    private static $viewRoot = '';

    /**
     * @var boolean
     */
    private static $noExecute = false;

    
    /**
     * @var HyperMVCController
     */
    protected static $controller;

    /**
     * @var array
     */
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

        if (is_null(self::$includePath)) {
            self::$includePath = str_replace('lib/hyper_mvc', '', __DIR__);
        }

        $query = $_GET['hmvcQuery'];
        unset($_GET['hmvcQuery']);
        $vars = null;
        foreach (self::$routes as $r) {
            $r->setQuery($query);
            if ($r->match($query)) {
                $vars = $r->getVars();
                break;
            }
        }

        self::$controllerName = isset($vars[':controller']) ? preg_replace('/[^a-zA-Z0-9_]/', '', $vars[':controller']) : 'HyperMVCController';

        $controllerFile = self::getControllerFile();

        if (!is_null($controllerFile)) {
            require_once $controllerFile;
            self::$controller = new self::$controllerName;
        } else {
            throw new Exception('Controller (' . self::$controllerName . ') not found!');
        }

        if (is_null(self::$controller->getViewName())) {
            $viewName = self::$controllerName;
        } else {
            $viewName = self::$controller->getViewName();
        }

        $viewDir = self::$includePath . '/view/' . (self::$viewRoot != '' ? self::$viewRoot . '/' : '') . $viewName . '/';

        if (isset($vars[':action'])) {
            $action = preg_replace('/[^a-zA-Z0-9_]/', '', $vars[':action']);
        } else {
            $action = 'index';
        }

        self::$viewName = $viewDir . $action . '.html';

        self::$controller->$action();

        self::initDomDocument();
        self::findContentTag();
        self::insertViewInTemplate();
        if (!self::$noExecute)
            self::execute();

        foreach (self::$elementsToRemove as $e) {
            $e->parentNode->removeChild($e);
        }

        $output = str_replace('%amp%', '&', self::$domDocument->saveHTML());
        if ($printOutput)
            echo $output;
        return $output;
    }

    protected static function initDomDocument() {

        if (is_null(self::$controller->getTemplateName())) {
            $templateName = 'template';
        } else {
            $templateName = self::$controller->getTemplateName();
        }

        $templateFile = self::$includePath . '/view/' . (self::$viewRoot != '' ? self::$viewRoot . '/' : '') . $templateName . '.html';

        if (file_exists($templateFile)) {
            self::$domDocument = new DOMDocument();
            self::$domDocument->loadHTML(str_replace('&', '%amp%', file_get_contents($templateFile)));
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
        if (file_exists(self::$viewName)) {
            if (!is_null(self::$domDocument)) {
                self::$viewElement = new DOMDocument();
                self::$viewElement->loadHTML('<html><meta charset="UTF-8">' . str_replace('&', '%amp%', file_get_contents(self::$viewName)) . '</html>');

                $children = self::$viewElement->getElementsByTagName('body')->item(0)->childNodes;

                foreach ($children as $c) {

                    $c = self::$domDocument->importNode($c, true);
                    self::$contentTag->appendChild($c);
                }
                self::$contentTag->removeAttribute(self::DATA_H_VIEW);
            } else {
                self::$domDocument = new DOMDocument();
                self::$domDocument->loadHTML(str_replace('&', '%amp%', file_get_contents(self::$viewName)));
            }
        } else {
            throw new Exception('View file (' . self::$viewName . ') not found!');
        }
    }

    private static function getControllerFile() {
        $dir = self::$includePath . '/controller/';
        if (file_exists($dir . self::$controllerName . '.php')) {
            return $dir . self::$controllerName . '.php';
        }
        $files = glob($dir . '*');

        $contrllerLower = strtolower($dir . self::$controllerName . '.php');
        $contrllerNoSpace = preg_replace('/[\-_]/', '', $contrllerLower);
        $contrllerUnder = preg_replace('/[\-]/', '_', $contrllerLower);
        $contrllerNoSpaceUnder = preg_replace('/[\-]/', '', $contrllerLower);
        foreach ($files as $f) {
            $fLower = strtolower($f);
            if ($fLower == $contrllerLower || $fLower == $contrllerNoSpace || $fLower == $contrllerNoSpaceUnder || $fLower == $contrllerUnder) {
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
    protected static function getElementByAttribute($element, $attribute, $value = null, $result = null) {
        foreach ($element->childNodes as $c) {
            if ($c instanceof DOMElement) {
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
            $val = substr($attribute->value, 0, $pos) . $value . substr($attribute->value, $pos + $len);
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
        $val = substr($element->nodeValue, 0, $pos) . $value . substr($element->nodeValue, $pos + $len);
        $element->nodeValue = $val;
    }

    private static function getValue($attributeValue, $obj = null, $objName = null) {

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
                    if ($className != self::$controller->getObjectName()) {
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
                    if ($className != self::$controller->getObjectName()) {
                        if (class_exists($className))
                            $obj = new $className();
                        else
                            return $obj;
                    }else {
                        $obj = self::$controller;
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

    private function compileValue($value, $part) {
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
                            if (!is_null($obj) && !is_null($objName)) {
                                $value = trim(preg_replace('/[#{}]/', '', $attributeValue));
                                if (preg_match('/^' . $objName . '[-> ^[]+/', $value) || $objName == $value) {
                                    self::processValue($a, $root, $attributeValue, $obj, $objName);
                                    if (in_array($a->name, self::$attributes)) {
                                        $attributes[] = $a->name;
                                    }
                                }
                            } else {
                                self::processValue($a, $root, $attributeValue, $obj, $objName);
                                if (in_array($a->name, self::$attributes)) {
                                    $attributes[] = $a->name;
                                }
                            }
                        }
                    }
                }
            }
            foreach ($attributes as $a) {
                if ($a == self::DATA_H_CONTENT) {
                    $root->appendChild(self::$domDocument->createTextNode($root->getAttribute($a)));
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
                                    self::processNodeValue($node, $nodeValue, $obj, $objName);
                                }
                            } else {
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

    public static function setNoExecute($noExecute) {
        self::$noExecute = $noExecute;
    }

    public static function addRoute($route) {
        self::$routes[] = new HyperMVCRoute($route);
    }

}

abstract class HyperMVCController {

    protected $viewName = null;
    protected $templateName = null;
    protected $objectName = 'controller';

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

}

class HyperMVCRoute {

    private $route;
    private $query;
    private $vars = array();

    function __construct($route, $query = '') {
        if (substr($route, 0, 1) == '/') {
            $route = substr($route, 1);
        }
        if (substr($route, -1) == '/') {
            $route = substr($route, 0, -1);
        }
        if (substr($query, 0, 1) == '/') {
            $query = substr($query, 1);
        }
        if (substr($query, -1) == '/') {
            $query = substr($query, 0, -1);
        }
        $this->route = $route;
        $this->query = $query;
    }

    public function getRoute() {
        return $this->route;
    }

    public function setRoute($route) {
        if (substr($route, 0, 1) == '/') {
            $route = substr($route, 1);
        }
        if (substr($route, -1) == '/') {
            $route = substr($route, 0, -1);
        }
        $this->route = $route;
    }

    public function getQuery() {
        return $this->query;
    }

    public function setQuery($query) {
        if (substr($query, 0, 1) == '/') {
            $query = substr($query, 1);
        }
        if (substr($query, -1) == '/') {
            $query = substr($query, 0, -1);
        }
        $this->query = $query;
    }

    public function getVars() {
        if (count($this->vars) == 0) {
            $vars = explode('/', $this->route);
            $values = explode('/', $this->query);
            for ($i = 0; $i < count($vars); $i++) {
                if (isset($values[$i])) {
                    $this->vars[$vars[$i]] = $values[$i];
                } else {
                    break;
                }
            }
        }
        return $this->vars;
    }

    public function match() {
        $vars = explode('/', $this->route);
        $values = explode('/', $this->query);
        if (count($values) == count($vars)) {
            $vars = $this->getVars();
            foreach ($vars as $var => $val) {
                if ($var[0] != ':' && $var != $val)
                    return false;
            }
            return true;
        }
        return false;
    }

    public function getVar($varName) {
        $vars = $this->getVars();
        if (isset($vars[$varName]))
            return $vars[$varName];
        return null;
    }

}

?>