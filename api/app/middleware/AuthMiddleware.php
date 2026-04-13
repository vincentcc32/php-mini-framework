<?php

namespace App\Middleware;

use Core\Auth;

class AuthMiddleware
{
  public function handle()
  {
    // Check if user is authenticated
    if (!Auth::check()) {
      header('Location: /login');
      exit;
    }
  }
}
