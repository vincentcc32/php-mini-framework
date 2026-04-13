<?php

namespace Core;

class Request
{
  public static function params($key, $default = null)
  {
    return $_GET[$key] ?? $default;
  }

  public static function input($key, $default = null)
  {
    return $_POST[$key] ?? $default;
  }

  public static function all()
  {
    return array_merge($_GET, $_POST);
  }
}
