<?php

namespace App\Helpers;

class Response
{

  public static function success($data, $message = '', $status = 200)
  {
    header('Content-Type: application/json');
    http_response_code($status);
    echo json_encode(['message' => $message, 'statusCode' => $status, 'success' => true, 'data' => $data]);
    exit;
  }
  public static function error($message, $status = 500)
  {
    header('Content-Type: application/json');
    http_response_code($status);
    echo json_encode(['message' => $message, 'statusCode' => $status, 'success' => false]);
    exit;
  }
}
