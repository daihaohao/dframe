<?php
require "vendor/autoload.php";
$config = "app/config";
/**
 * Description: Rpc客户端
 */
class RpcClient
{

    /**
     * @var array
     * Description: 调用的地址
     */
    private $urlInfo = array();

    /**
     * RpcClient constructor.
     */
    private function __construct($url)
    {
        $this->urlInfo = parse_url($url);
    }

    /**
     * Description: 返回当前对象
     */
    public static function instance($url)
    {
        return new RpcClient($url);
    }

    public function __call($name, $arguments)
    {
        // TODO: Implement __call() method.
        //创建一个客户端
        $client = stream_socket_client("tcp://{$this->urlInfo['host']}:{$this->urlInfo['port']}", $errno, $errstr);
        if (!$client) {
            exit("{$errno} : {$errstr} \n");
        }
        $data = [
            'class' => basename($this->urlInfo['path']),
            'method' => $name,
            'params' => $arguments,
            'encrypt_type'=>'jwt',
            'source'=>'syl',
        ];
        $sign = \Firebase\JWT\JWT::encode($arguments,'syl_');
        $data['sign'] = $sign;
        //向服务端发送我们自定义的协议数据
        fwrite($client, json_encode($data,JSON_UNESCAPED_UNICODE));
        //读取服务端传来的数据
        $data = fread($client, 2048);
        //关闭客户端
        fclose($client);
        return $data;
    }
}

$cli = RpcClient::instance('http://127.0.0.1:9999/App');
echo $cli->test() . "\n";
echo $cli->test(array('name' => '戴浩浩', 'age' => 223));