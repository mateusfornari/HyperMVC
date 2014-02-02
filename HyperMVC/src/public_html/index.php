<?php

define('INCLUDE_PATH', '../structure/');

include INCLUDE_PATH.'lib/autoload.php';

HyperMVC::setIncludePath(INCLUDE_PATH);
HyperMVC::setViewRoot('template1');
HyperMVC::setControllerRoot('template1');
HyperMVC::render();
?>