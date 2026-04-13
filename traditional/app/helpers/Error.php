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
      self::renderDebugPage($e);
    } else {
      self::renderErrorPage();
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

  private static function renderDebugPage(Throwable $e)
  {
?>
    <div style="background: #fdf2f2; color: #9b1c1c; padding: 20px; border: 1px solid #f8b4b4; font-family: sans-serif; line-height: 1.5; border-radius: 8px; margin: 20px;">
      <h2 style="margin-top: 0;">Fatal Error: <?= htmlspecialchars($e->getMessage()) ?></h2>
      <p><b>Location:</b> <?= htmlspecialchars($e->getFile()) ?> <b>on line</b> <?= $e->getLine() ?></p>
      <h3 style="margin-bottom: 5px;">Stack Trace:</h3>
      <pre style="background: #fff; padding: 15px; border-radius: 4px; overflow-x: auto; border: 1px solid #eee; font-size: 13px;"><?= htmlspecialchars($e->getTraceAsString()) ?></pre>
    </div>
<?php
  }

  private static function renderErrorPage()
  {
    echo "<div style='text-align:center; padding: 50px; font-family: sans-serif;'>
                <h1 style='font-size: 40px; color: #333;'>500 - Internal Server Error</h1>
                <p style='color: #666;'>Oops! Something went wrong on our end. Please try again later.</p>
              </div>";
  }
}
