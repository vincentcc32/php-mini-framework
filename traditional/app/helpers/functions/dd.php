<?php

if (!function_exists('dd')) {
  function dd(...$vars)
  {
    // Xóa sạch buffer trước đó
    if (ob_get_length()) ob_clean();

    // Kiểm tra xem có phải yêu cầu JSON không (cho API)
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    $isJson = strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;

    if ($isAjax || $isJson) {
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode($vars, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } else {
      // Hiển thị giao diện đẹp trên trình duyệt
      echo '<style>
                pre.dump {
                    background-color: #18171B;
                    color: #FF8400;
                    line-height: 1.2em;
                    font: 14px Menlo, Monaco, Consolas, monospace;
                    word-wrap: break-word;
                    white-space: pre-wrap;
                    position: relative;
                    z-index: 99999;
                    word-break: break-all;
                    padding: 15px;
                    border-radius: 5px;
                    margin: 20px;
                    border-left: 5px solid #F27D0C;
                }
                pre.dump span { color: #56DB3A; } /* Strings */
                pre.dump b { color: #F27D0C; }    /* Keys/Objects */
                pre.dump i { color: #9FD5FF; }    /* Numbers */
            </style>';

      echo '<pre class="dump">';
      foreach ($vars as $var) {
        echo htmlspecialchars(print_r($var, true));
      }
      echo '</pre>';
    }

    die(1); // Dừng script ngay lập tức
  }
}
