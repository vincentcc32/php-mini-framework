<?php

use Core\Router;
use App\Controllers\HomeController;
use App\Middleware\AuthMiddleware;

// Define routes
Router::get('/', [HomeController::class, 'index']);
Router::post('/', [HomeController::class, 'create']);
Router::get('/login', [HomeController::class, 'login']);
Router::get('/{id}', [HomeController::class, 'show']);
Router::get('/{id}/{slug}', [HomeController::class, 'show2']);
// define middleware
// Router::middleware('/', AuthMiddleware::class);
