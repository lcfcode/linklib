<?php
/**
 * @link https://gitee.com/lcfcode/linklib
 * @link https://github.com/lcfcode/linklib
 */

namespace Swap\Utils;

class RedisClass
{
    private $redisObj = [];
    private $objKey = '';

    /**
     * @param $config
     * @return \redis
     * @user LCF
     * @date 2019/3/15 22:36
     */
    public function connect($config)
    {
        $host = $config['host'];
        $port = $config['port'];
        $this->objKey = $host . ':' . $port;
        if (isset($this->redisObj[$this->objKey])) {
            try {
                if ($this->redisObj[$this->objKey]->ping() == '+PONG') {
                    return $this->redisObj[$this->objKey];
                }
                $this->redisObj[$this->objKey]->close();
            } catch (\Exception $e) {
                $this->redisObj[$this->objKey]->close();
            }
            unset($this->redisObj[$this->objKey]);
        }
        $db = isset($config['db']) ? $config['db'] : '0';
        $password = isset($config['password']) ? $config['password'] : '';
        $redis = new \Redis();
        $redis->connect($host, $port);
        if ($password) {
            $redis->auth($password);
        }
        $redis->select($db);
        $this->redisObj[$this->objKey] = $redis;
        return $this->redisObj[$this->objKey];
    }

    /**
     * @user LCF
     * @date 2019/3/15 22:36
     * 此方法基本用不上，上述connect直接获取连接的redis对象，调用redis->close()就可以直接关闭
     */
    public function close()
    {
        if (!empty($this->objKey) && isset($this->redisObj[$this->objKey])) {
            $this->redisObj[$this->objKey]->close();
        }
    }

    public function __destruct()
    {
        foreach ($this->redisObj as $item) {
            $item->close();
        }
    }
}