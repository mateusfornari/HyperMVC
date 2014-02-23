<?php

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
