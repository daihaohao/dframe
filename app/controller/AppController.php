<?php


namespace app\controller;


class AppController
{
    public function test($input)
    {
        $input['desc'] = '我是app/test';
        return $input;
    }
}