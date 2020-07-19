<?php


namespace Aplication;

class Config
{
    public static $config;

    public static function init($config_path)
    {
        self::$config = new \Noodlehaus\Config($config_path);
        return new static();
    }

    public static function get($key)
    {
        return self::$config->get($key);
    }

    public static function all()
    {
        return self::$config->all();
    }
}