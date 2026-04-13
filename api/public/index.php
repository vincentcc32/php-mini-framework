<?php

use Core\Router;
use Core\Database;
use Dotenv\Dotenv;

session_start();
if ($_ENV['APP_TIMEZONE']) {
  date_default_timezone_set($_ENV['APP_TIMEZONE']);
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../routes/api.php';

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

if ($_ENV['APP_DEBUG']) {
  error_reporting(E_ALL);
  ini_set('display_errors', '1');
} else {
  error_reporting(0);
  ini_set('display_errors', '0');
}

if ($_ENV['DB']) {
  Database::getConnection();
}


Router::resolve();
