<?php

namespace Core;

class Cookie
{
  public static function set(
    string $name,
    string $value,
    int $minutes = 0,
    string $path = '/',
    string $domain = '',
    bool $secure = false,
    bool $httponly = true,
    string $samesite = 'Lax'
  ) {
    $expire = $minutes > 0 ? time() + ($minutes * 60) : 0;

    setcookie($name, $value, [
      'expires' => $expire,
      'path' => $path,
      'domain' => $domain,
      'secure' => $secure,
      'httponly' => $httponly,
      'samesite' => $samesite
    ]);
  }

  public static function get(string $name, $default = null)
  {
    return $_COOKIE[$name] ?? $default;
  }

  public static function has(string $name): bool
  {
    return isset($_COOKIE[$name]);
  }

  public static function remove(string $name)
  {
    setcookie($name, '', time() - 3600, '/');
    unset($_COOKIE[$name]);
  }
}
