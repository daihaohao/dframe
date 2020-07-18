<?php
require "../vendor/autoload.php";

\Aplication\Aplication::init(__DIR__.'/config');
var_dump( \Aplication\Config::get('db') );