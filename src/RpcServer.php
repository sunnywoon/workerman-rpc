<?php
/**
 *
 * @author jhx
 * @date 2021/10/20 8:54
 */

namespace Rpc;


use Illuminate\Support\Arr;
use Workerman\Worker;

class RpcServer
{
    private $config;

    public function __construct()
    {

    }

    /**
     * @return mixed
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param mixed $config
     */
    public function setConfig($config)
    {
        $this->config = $config;
        return $this;
    }

    public function createWorker()
    {
        $socketName = Arr::get($this->getConfig(), 'socket_name', 'json://0.0.0.0:9501');
        $worker     = new Worker($socketName);

        $worker->count = Arr::get($this->getConfig(), 'process_count', 1);

        $worker->name = Arr::get($this->getConfig(), 'worker_name', 'rpc_server');

        $worker->onMessage = function ($connection, $data) {

            if (empty($data['class']) || empty($data['method']) || !isset($data['param_array'])) {
                // 发送数据给客户端，请求包错误
                return $connection->send(['code' => 400, 'msg' => 'bad request', 'data' => null]);
            }

            $servicePath = Arr::get($this->getConfig(), 'service_path');
            $class       = Arr::get($data, 'class');
            $method      = Arr::get($data, 'method');
            $paramArray  = Arr::get($data, 'param_array');

            // 账号密码
            $account = Arr::get($this->getConfig(), 'account', []);
            if (!empty($account)) {
                $user   = Arr::get($data, 'user');
                $passwd = Arr::get($data, 'passwd');
                if (empty($user) || empty($passwd) || !array_key_exists($user, $account) || ($passwd != Arr::get($account, $user))) {
                    return $connection->send(['code' => 400, 'msg' => 'user or passwd is error', 'data' => null]);
                }
            }

            $class = "{$servicePath}\\{$class}";

            // 判断类对应文件是否载入
            if (class_exists($class) && method_exists($class, $method)) {
                try {
                    $class = new $class;
                    $res   = call_user_func_array([$class, $method], $paramArray);
                    return $connection->send(['code' => 0, 'msg' => 'ok', 'data' => $res]);
                } catch (\Exception $e) {
                    $code = $e->getCode() ? $e->getCode() : 500;
                    return $connection->send(['code' => $code, 'msg' => $e->getMessage(), 'data' => $e]);
                }
            }

            $code = 404;
            $msg  = "class $class or method $method not found";
            // 发送数据给客户端 类不存在
            return $connection->send(['code' => $code, 'msg' => $msg, 'data' => null]);

        };

        return $this;
    }

    public function run()
    {
        Worker::runAll();
    }


}