<?php

use App\Helpers\Error;
use config\Config;
use Core\Router;
use Core\Database;
use Dotenv\Dotenv;

if ($_ENV['APP_TIMEZONE']) {
  date_default_timezone_set($_ENV['APP_TIMEZONE']);
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../routes/api.php';

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

Error::handle();

Config::init();

if (filter_var($_ENV['DB'], FILTER_VALIDATE_BOOLEAN)) {
  Database::getConnection();
}


Router::resolve();
