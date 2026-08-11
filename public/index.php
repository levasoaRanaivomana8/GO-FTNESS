<?php

declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

session_start();

require __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . '/../config/config.php';
define('BASE_URL', $config['base_url']);

use Core\Router;

$router = new Router();
require __DIR__ . '/../config/routes.php';

$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);