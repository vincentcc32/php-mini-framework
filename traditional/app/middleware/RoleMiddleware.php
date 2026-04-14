<?php

namespace App\Middleware;

use Core\Auth;

class RoleMiddleware
{
  public function handle()
  {
    // Check if user is authenticated
    if (!Auth::check()) {
      // header('Location: /login');
      echo "Unauthorized access. Please log in.";
      exit;
    }
  }
}
