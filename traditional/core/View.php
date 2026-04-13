<?php

namespace Core;

class View
{
  protected string $view;
  protected array $data = [];
  protected string $layout;

  public function __construct(string $view, string $layout = '', array $data = [])
  {
    $this->view = $view;
    $this->layout = $layout;

    $this->data = $data;
  }

  public function render(): void
  {
    // Tách biến ra từ mảng data để sử dụng trực tiếp trong view
    extract($this->data);
    //  dùng layout
    if ($this->layout) {
      $content = __DIR__ . '/../' . $this->view;
      require __DIR__ . '/../' . $this->layout;
    } else {
      require __DIR__ . '/../' . $this->view;
    }
  }

  // Hỗ trợ gọi view với dữ liệu
  public static function make(string $view, string $layout = '', array $data = []): self
  {
    return new self($view, $layout, $data);
  }
}
