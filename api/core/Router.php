<?php

namespace Core;

class Router
{
  private static $routes = [];
  private static $middleware = [];

  public static function get($uri, $callback)
  {
    self::$routes['GET'][self::trimPath($uri)] = $callback;
  }

  public static function post($uri, $callback)
  {
    self::$routes['POST'][self::trimPath($uri)] = $callback;
  }

  public static function put($uri, $callback)
  {
    self::$routes['PUT'][self::trimPath($uri)] = $callback;
  }

  public static function delete($uri, $callback)
  {
    self::$routes['DELETE'][self::trimPath($uri)] = $callback;
  }

  public static function resolve()
  {
    $method = self::getMethod();
    $uri = self::getPath();

    $pathMatch = null;
    $params = [];
    foreach (self::$routes[$method] ?? [] as $route => $action) {
      $routePattern = preg_replace('~{\w+}~i', '([^/]+)', $route);
      preg_match('~^' . $routePattern . '$~i', $uri, $matches);
      if (!empty($matches)) {
        $pathMatch = $route;
        if (count($matches) > 1) {
          $params = array_slice($matches, 1);
        }
        break;
      }
    }
    $action = self::$routes[$method][$pathMatch] ?? false;
    if (!$action) {
      echo "404 Not Found";
      return;
    }
    // check middleware
    foreach (self::$middleware as $path => $callback) {
      if (strcmp($pathMatch, $path) === 0) {
        $middleware = new $callback();
        if (method_exists($middleware, 'handle')) {
          $response = $middleware->handle();
          if ($response === false) {
            return;
          }
        }
      }
    }

    [$controller, $method] = $action;
    $controller = new $controller();
    $request = new Request();
    // Kiểm tra xem phương thức có cần Request hay không
    $reflectionMethod = new \ReflectionMethod($controller, $method);
    $parameters = $reflectionMethod->getParameters();

    // Kiểm tra nếu phương thức yêu cầu đối tượng Request
    if (count($parameters) > 0 && $parameters[0]->getType() && $parameters[0]->getType()->getName() === 'Core\Request') {
      // Nếu cần Request, truyền nó vào
      call_user_func([$controller, $method], $request, ...$params);
    } else {
      // Nếu không cần Request, chỉ truyền các tham số còn lại
      call_user_func([$controller, $method], ...$params);
    }
  }

  public static function middleware($path, $callback)
  {
    self::$middleware[$path] = $callback;
  }

  private static function getPath()
  {
    $path = $_SERVER['REQUEST_URI'] ?? '/';
    if ($path !== '/') {
      $path = rtrim($path, '/');
    }
    return parse_url($path, PHP_URL_PATH);
  }

  public static function getMethod()
  {
    return $_SERVER['REQUEST_METHOD'] ?? 'GET';
  }

  private static function trimPath($path)
  {
    if ($path !== '/') {
      $path = rtrim($path, '/');
    }
    return $path;
  }
}
