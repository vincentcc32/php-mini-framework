<?php

namespace App\Helpers;

use ErrorException;
use Throwable;

class Error
{
  protected static string $logFile = __DIR__ . '/../../storage/logs/php.log';

  public static function handle()
  {
    // 1. Luôn báo cáo tất cả lỗi, nhưng việc hiển thị sẽ do chúng ta kiểm soát
    error_reporting(E_ALL);
    ini_set('display_errors', '0'); // Tắt hiển thị mặc định của PHP

    // 2. Bắt các lỗi runtime (Warning, Notice...) và biến chúng thành Exception
    set_error_handler(function ($errno, $errstr, $errfile, $errline) {
      if (!(error_reporting() & $errno)) return;
      throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    });

    // 3. Bắt các Exception chưa được xử lý (Uncaught Exceptions)
    set_exception_handler(function (Throwable $e) {
      self::render($e);
    });

    // 4. Bắt các lỗi Fatal (Lỗi chết người khiến script dừng ngay lập tức)
    register_shutdown_function(function () {
      $error = error_get_last();
      // Chỉ bắt các lỗi nghiêm trọng (Fatal, Parse, Core, Compile)
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

    if (ob_get_length()) ob_clean();

    // Ghi log lỗi
    self::log($e);

    // Thiết lập HTTP Header 500
    if (!headers_sent()) {
      header('HTTP/1.1 500 Internal Server Error', true, 500);
    }

    $isDebug = filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN);

    if ($isDebug) {
      Response::json([
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
      ], 500);
    } else {
      Response::json(['error' => 'Internal Server Error'], 500);
    }

    exit;
  }

  private static function log(Throwable $e)
  {
    $shouldLog = filter_var($_ENV['APP_LOG'] ?? true, FILTER_VALIDATE_BOOLEAN);

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
