<?php
/**
 * @link https://gitee.com/lcfcode/linklib
 * @link https://github.com/lcfcode/linklib
 */

namespace Swap\Core;

trait Utiltrait
{
    /**
     * @param string $name
     * @return \Swap\Utils\AppUtil
     * @author LCF
     * @date
     * 获取工具方法
     */
    public function utils($name = 'AppUtil')
    {
        return $this->app->getUtils($name);
    }

    /**
     * @return mixed
     * @author LCF
     * @date
     * 获取配置
     */
    public function config()
    {
        return $this->app->config();
    }

    /**
     * @param $configKey
     * @param null $default
     * @return null
     * @author LCF
     * @date 2019/8/17 18:32
     * 获取配置的方法
     */
    public function getConfigValue($configKey, $default = null)
    {
        return $this->utils()->getConfigValue($configKey, $default);
    }

    /**
     * @param null $time
     * @return false|string
     * @author LCF
     * @date
     * mysql时间
     */
    public function getDate($time = null)
    {
        return $time ? date('Y-m-d H:i:s', $time) : date('Y-m-d H:i:s');
    }


    /**
     * @param $info
     * @param string $file
     * @return $this
     * @author LCF
     * @date 2019/8/17 18:33
     * 日志操作函数
     */
    public function logs($info, $file = null)
    {
        $this->utils()->logs($info, $file);
        return $this;
    }

    /**
     * @param $e \Exception
     * @param string $name
     * @return $this
     * @author LCF
     * @date 2019/8/17 18:33
     * 异常日志函数
     */
    public function catchLog($e, $name = '')
    {
        $this->utils()->catchLog($e, $name);
        return $this;
    }

    /**
     * @param array $config
     * @return \redis
     * @user LCF
     * @date 2019/3/15 22:27
     * 获取redis
     */
    public function getRedis($config = [])
    {
        return $this->utils()->getRedis($config);
    }

    /**
     * @return mixed
     * @author LCF
     * @date 2019/8/17 18:35
     * 当前工作目录
     */
    public function root()
    {
        return $this->utils()->root();
    }
}
