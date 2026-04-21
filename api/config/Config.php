<?php

namespace config;

class Config
{
  public static $ADMIN_CODE;
  public static function init()
  {
    self::$ADMIN_CODE = $_ENV['ADMIN_ROLE'];
  }
}
