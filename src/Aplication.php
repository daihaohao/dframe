<?php


namespace Aplication;

/**
 * Class Aplication
 * @package Aplication
 */
class Aplication
{
//    public static $config;
    private function __construct()
    {
    }
    private function __clone()
    {
        // TODO: Implement __clone() method.
    }

    public static function init($config_path){
        Config::init($config_path);
        return new self();
    }

    public function run(){
        if (Config::get('server_type')==='rpc'){
            $RpcServer = RpcServer::init();
            $RpcServer->run();
        }
    }
}