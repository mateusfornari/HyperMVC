<?php
include __DIR__.'/../structure/lib/autoload.php';

HyperMVC::addRoute('?:controller/?:action/?:id', array(':id' => '/^[0-9]+$/'));

$hyperMVC = new HyperMVC();

$hyperMVC->render();
?>