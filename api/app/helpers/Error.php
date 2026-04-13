<?php

namespace App\Helpers;

use ErrorException;
use Throwable;

class Error
{
  protected static string $logFile = __DIR__ . '/../../storage/logs/api.log';

  public static function handle()
  {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');

    // Biến các lỗi hệ thống (Warning, Notice) thành Exception
    set_error_handler(function ($errno, $errstr, $errfile, $errline) {
      if (!(error_reporting() & $errno)) return;
      throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    });

    // Xử lý các Exception chưa được catch
    set_exception_handler(function (Throwable $e) {
      self::render($e);
    });

    // Xử lý lỗi Fatal (lỗi chết người)
    register_shutdown_function(function () {
      $error = error_get_last();
      if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        self::render(new ErrorException(
          $error['message'],
          0,
          $error['type'],
          $error['file'],
          $error['line']
        ));
      }
    });
  }

  public static function render(Throwable $e)
  {
    // Xóa sạch buffer để không lẫn lộn giữa JSON và các output trước đó
    if (ob_get_length()) ob_clean();

    // Luôn ghi log để truy vết sau này
    self::log($e);

    // Thiết lập header trả về JSON
    header('Content-Type: application/json; charset=utf-8');

    // Xác định HTTP Status Code (Mặc định 500)
    $statusCode = method_exists($e, 'getCode') && $e->getCode() >= 400 && $e->getCode() < 600
      ? $e->getCode()
      : 500;

    http_response_code($statusCode);

    $isDebug = filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN);

    $response = [
      'status'  => 'error',
      'code'    => $statusCode,
      'message' => $isDebug ? $e->getMessage() : 'Internal Server Error'
    ];

    // Nếu đang ở môi trường phát triển (Debug), trả về chi tiết lỗi
    if ($isDebug) {
      $response['debug'] = [
        'type'    => get_class($e),
        'file'    => $e->getFile(),
        'line'    => $e->getLine(),
        'trace'   => explode("\n", $e->getTraceAsString()) // Tách dòng cho dễ đọc trên Postman
      ];
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
  }

  private static function log(Throwable $e)
  {
    $shouldLog = filter_var($_ENV['APP_LOG'] ?? false, FILTER_VALIDATE_BOOLEAN);

    if ($shouldLog) {
      $logDir = dirname(self::$logFile);
      if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
      }

      $message = sprintf(
        "[%s] %s | File: %s:%d\nStack Trace:\n%s\n%s\n",
        date('Y-m-d H:i:s'),
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        $e->getTraceAsString(),
        str_repeat('-', 50)
      );

      file_put_contents(self::$logFile, $message, FILE_APPEND);
    }
  }
}
