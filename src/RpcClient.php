<?php
/**
 *
 * @author jhx
 * @date 2021/10/20 10:33
 */

namespace Rpc;


use Illuminate\Support\Arr;
use Protocols\Json;

class RpcClient
{
    private $timeout = 5;

    /**
     * 异步调用发送数据前缀
     * @var string
     */
    const ASYNC_SEND_PREFIX = 'asend_';

    /**
     * 异步调用接收数据
     * @var string
     */
    const ASYNC_RECV_PREFIX = 'arecv_';

    private $addressArray = [];

    private $asyncInstances = [];

    private static $instances = [];

    private $connection;

    private $server;

    private $className;

    private $user = '';

    private $passwd = '';

    public function __construct($server, $className)
    {
        $this->server    = $server;
        $this->className = $className;
    }

    /**
     * 防止对象实例被克隆
     *
     * @return void
     */
    private function __clone()
    {
    }

    /**
     * 防止被反序列化
     *
     * @return void
     */
    private function __wakeup()
    {
    }

    public static function instance($server, $className)
    {
        if (!isset(self::$instances[$server])) {
            self::$instances[$server] = new self($server, $className);
        }

        return self::$instances[$server];
    }

    public function address($addressArray)
    {
        $this->addressArray = $addressArray;
        return $this;
    }

    public function __call($method, $arguments)
    {
        // 判断是否是异步发送
        if (0 === strpos($method, self::ASYNC_SEND_PREFIX)) {
            $realMethod  = substr($method, strlen(self::ASYNC_SEND_PREFIX));
            $instanceKey = $realMethod . serialize($arguments);
            if (isset($this->$asyncInstances[$instanceKey])) {
                throw new \Exception($this->className . "->$method(" . implode(',', $arguments) . ") have already been called");
            }
            $this->asyncInstances[$instanceKey] = new self($this->server, $this->className);
            return $this->asyncInstances[$instanceKey]->sendData($realMethod, $arguments);
        }
        // 如果是异步接受数据
        if (0 === strpos($method, self::ASYNC_RECV_PREFIX)) {
            $realMethod  = substr($method, strlen(self::ASYNC_RECV_PREFIX));
            $instanceKey = $realMethod . serialize($arguments);
            if (!isset($this->asyncInstances[$instanceKey])) {
                throw new \Exception($this->server . "@" . $this->className . "->asend_$realMethod(" . implode(',', $arguments) . ") have not been called");
            }
            $tmp = $this->asyncInstances[$instanceKey];
            unset($this->asyncInstances[$instanceKey]);
            return $tmp->recvData();
        }
        // 同步发送接收
        $this->sendData($method, $arguments);
        return $this->recvData();
    }

    public function sendData($method, $arguments)
    {
        $this->openConnection();

        $binData = Json::encode(array(
            'class'       => $this->className,
            'method'      => $method,
            'param_array' => $arguments,
            'user'        => $this->user,
            'passwd'      => $this->passwd
        ));

        if (fwrite($this->connection, $binData) !== strlen($binData)) {
            throw new \Exception('Can not send data');
        }
        return true;
    }

    public function recvData()
    {
        $ret = fgets($this->connection);
        $this->closeConnection();
        if (!$ret) {
            throw new \Exception("recvData empty");
        }
        return Json::decode($ret);
    }

    protected function openConnection()
    {
        $addressArr = $this->addressArray;
        if (empty($addressArr)) {
            throw new \Exception("addressArr empty");
        }

        $address = $addressArr[array_rand($addressArr)];

        $addressArr = explode('@', $address);

        if (isset($addressArr[1])) {
            $account      = explode(':', $addressArr[1]);
            $this->user   = Arr::get($account, 0, '');
            $this->passwd = Arr::get($account, 1, '');
        }

        $this->connection = stream_socket_client($address, $errNo, $errMsg);
        if (!$this->connection) {
            throw new \Exception("can not connect to $address , $errNo:$errMsg");
        }
        stream_set_blocking($this->connection, true);
        stream_set_timeout($this->connection, $this->timeout);
    }

    protected function closeConnection()
    {
        fclose($this->connection);
        $this->connection = null;
    }
}