<?php

use App\Helpers\Error;
use Core\Router;
use Core\Database;
use Core\Session;
use Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../routes/web.php';

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

Error::handle();

Session::start();

if (filter_var($_ENV['DB'], FILTER_VALIDATE_BOOLEAN)) {
  Database::getConnection();
}


Router::resolve();
