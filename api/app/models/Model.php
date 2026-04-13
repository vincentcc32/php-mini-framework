<?php

namespace App\Models;

use Core\Database;
use Core\QueryBuilder;
use PDO;

abstract class Model
{
  protected static ?PDO $db = null;
  protected string $table;

  public function __construct()
  {
    self::$db = Database::getConnection();
  }

  public static function query(): QueryBuilder
  {
    $instance = new static();
    $query = new QueryBuilder(self::$db);
    return $query->table($instance->table);
  }
}
