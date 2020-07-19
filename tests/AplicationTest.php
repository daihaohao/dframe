<?php
require "vendor/autoload.php";
$config = "app/config";
\Aplication\Aplication::init($config)->run();
//var_dump( \Aplication\Config::get('db') );