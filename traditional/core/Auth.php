<?php

namespace Core;

class Auth
{
  public static function check()
  {
    return isset($_SESSION['user']);
  }

  public static function user()
  {
    return $_SESSION['user'] ?? null;
  }

  public static function attempt($email, $password)
  {
    // Implementation for attempting authentication
  }

  public static function logout()
  {
    unset($_SESSION['user']);
  }
}
