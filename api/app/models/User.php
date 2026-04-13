<?php

namespace App\Models;

use Core\Database;

class User
{
  private $db;

  public function __construct()
  {
    $this->db = Database::getConnection();
  }
}
