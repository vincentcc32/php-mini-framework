<?php

namespace Core;

class Database
{
  private static $pdo;

  public static function getConnection()
  {
    if (self::$pdo === null) {
      try {
        $config = require __DIR__ . '/../config/database.php';

        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']}";

        self::$pdo = new \PDO($dsn, $config['username'], $config['password']);
        self::$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        // echo "Database connection established successfully.";
      } catch (\PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
      }
    }

    return self::$pdo;
  }

  public static function closeConnection()
  {
    self::$pdo = null;
  }
}
