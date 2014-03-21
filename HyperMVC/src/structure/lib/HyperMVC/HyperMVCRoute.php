<?php

class HyperMVCRoute {

    private $route;
    private $query;
    private $vars = array();
    private $config = array();

    function __construct($route, $query = '', $config = array()) {
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
        $this->config = $config;
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
        if ($query[0] == '/') {
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
                if (isset($values[$i]) && $values[$i] != '') {
                    $varName = str_replace('?', '', $vars[$i]);
                    if($varName == ':controller' || $varName == ':action'){
                        $values[$i] = preg_replace('/[^a-zA-Z0-9_]/', '', $values[$i]);
                    }
                    $this->vars[$varName] = $values[$i];
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
        for ($i = 0; $i < count($vars); $i++) {
            if(!isset($values[$i]) && $vars[$i][0] != '?'){
                return false;
            }
        }
		if(count($values) > count($vars)){
			return false;
		}
        $vars = $this->getVars();
        foreach ($vars as $var => $val) {
            if ($var[0] != ':' && $var != $val){
                return false;
            } elseif (key_exists($var, $this->config) && !preg_match($this->config[$var], $val)) {
                return false;
            }

        }
        return true;
    }

    public function getVar($varName) {
        $vars = $this->getVars();
        if (isset($vars[$varName]))
            return $vars[$varName];
        return null;
    }

    /**
     * Sets the format of a var for validation.
     * @param string $varName The var name.
     * @param string $format Regular expression for validate the value.
     */
    public function setVarFormat($varName, $format){
        $this->config[$varName] = $format;
    }
    
}
