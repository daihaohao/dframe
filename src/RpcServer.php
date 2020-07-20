<?php


namespace Aplication;


use Firebase\JWT\JWT;

class RpcServer
{
    /**
     * @var array
     * Description: 本类常用配置
     */
//    private static $config = [
//        'real_path' => '',
//        'max_size' => 2048 // 最大接收数据大小
//    ];
    private static $max_size = 2048;
    /**
     * @var null
     * Description:
     */
    private static $server = null;

    public static function init()
    {
        return new self();
    }

    public function createServer($rpc_config)
    {
        $protocol = isset($rpc_config['protocol']) ? $rpc_config['protocol'] : 'tcp';
        $server = stream_socket_server($protocol . "://{$rpc_config['host']}:{$rpc_config['port']}", $errno, $errstr);
        self::$server = $server;
        if (!$server) {
            exit('Not Link');
        }
        self::$server = $server;
        echo PHP_EOL.$protocol." start listen ".$rpc_config['port'].PHP_EOL;
        $this->serverPath($rpc_config);
    }

    private function serverPath($config)
    {
        if (isset($config['path']) && !empty($config['path'])) {
            $path = $config['path'];
            $realPath = realpath(__DIR__ . $path);
            if ($realPath === false || !file_exists($realPath)) {
                exit("{$path} error!");
            }
//        $this->config['real_path'] = $realPath;
        }
    }

    private function process()
    {
        while (true) {
            if (self::$server) {
                $read = [self::$server];
                $write = null;
                $except = null;
                $result = stream_select($read,$write,$except,null);
//                if ($client) {
                if($result>0){
                    $client = stream_socket_accept(self::$server,0);
                    echo "有新连接\n";
                    $buf = fread($client, self::$max_size);
                    print_r('接收到的原始数据:' . $buf . "\n");
                    // 自定义协议目的是拿到类方法和参数(可改成自己定义的)
                    $this->parseProtocol($buf, $class, $method, $params, $source, $encrypt_type, $sign);
                    $verify_data_res = $this->verifyParam($source,$encrypt_type,$params,$sign);
                    if ($verify_data_res['code']!='OK'){
                        fwrite($client,json_encode($verify_data_res,256));
                    }else {
                        // 执行方法
                        $this->execMethod($client, $class, $method, $params);
                    }
                    //关闭客户端
                    fclose($client);
                    echo "关闭了连接\n";
                }
            }
        }
    }
    private function verifyParam($source,$encrypt_type,$data,$sign){
//        var_dump($source,$encrypt_type,$data,$sign);die;
        switch ($encrypt_type){
            case 'jwt':
                $encrypt_config = Config::get('rpc_source');
                if (!isset($encrypt_config[$source])){
                    return ['code'=>'ENCRYPT_CONFIG_ERROR','msg'=>'没有配置该资源'];
                }
                $encrypt_key = $encrypt_config[$source]['key'];
                $data = JWT::encode($data,$encrypt_key);
                if ($data!=$sign){
                    return ['code'=>'ENCRYPT_ERROR','msg'=>'验签失败'];
                }
                return ['code'=>'OK','msg'=>'验签成功'];
                break;
            default:
                return ['code'=>'ENCRYPT_CONFIG_ERROR','msg'=>'不支持的验签方式'];
                break;
        }
    }
    /**
     * @param $class
     * @param $method
     * @param $params
     * Description: 执行方法
     */
    private function execMethod($client, $class, $method, $params)
    {
        if ($class && $method) {
            // 首字母转为大写
            $class = '\app\controller\\' . ucfirst($class) . 'Controller';
            var_dump($class);
            //实例化类，并调用客户端指定的方法
            $obj = new $class();
            //如果有参数，则传入指定参数
            if (!method_exists($obj, $method)) {
                return $method . "方法不存在";
            }
            $data = $obj->$method($params);
            // 打包数据
            $this->packProtocol($data);
            //把运行后的结果返回给客户端
            fwrite($client, $data);
        }
    }
    /**
     * Description: 解析协议
     * @param $buf
     * @param $class 类名
     * @param $method 方法名
     * @param $params 参数
     * @param $source 来源
     * @param $encrypt_type 加密方式
     * @param $sign 秘钥
     */
    private function parseProtocol($buf, &$class, &$method, &$params, &$source, &$encrypt_type, &$sign)
    {
        $buf = json_decode($buf, true);
        $class = $buf['class'];
        $method = $buf['method'];
        $params = $buf['params'];
        $encrypt_type = isset($buf['encrypt_type']) ? $buf['encrypt_type']: '';
        $sign = isset($buf['sign']) ? $buf['sign']: '';
        $source = isset($buf['source']) ? $buf['source']: '';
    }

    /**
     * @param $data
     * Description: 打包协议
     */
    private function packProtocol(&$data)
    {
        $data = json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    public function run()
    {
        $rpc_config = Config::get('rpc');
        $this->createServer($rpc_config);
        $this->serverPath($rpc_config);
        $this->process();
    }
}