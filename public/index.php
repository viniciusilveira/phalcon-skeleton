<?php
// Setup composers autoloader
require_once(__DIR__.'/../vendor/autoload.php');

// Setup bootstrap
require_once(__DIR__.'/../app/Bootstrap.php');

// Initiate the application
$app = new \App\Bootstrap(new \Phalcon\DI\FactoryDefault());
echo $app->handle()->getContent();
