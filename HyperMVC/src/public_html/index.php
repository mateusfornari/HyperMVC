<?php
include __DIR__.'/../structure/lib/autoload.php';


HyperMVC::addRoute(':estado/:cidade/?:controller/?:action', array(':estado' => '/^[a-z]{2}$/', ':cidade' => '/^[a-z\-]+$/'));

HyperMVC::render();
?>