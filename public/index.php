<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Core\Application;

$app = Application::getInstance();

// Run the application
$app->run();
