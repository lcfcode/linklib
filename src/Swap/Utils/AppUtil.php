<?php
/**
 * @link https://gitee.com/lcfcode/linklib
 * @link https://github.com/lcfcode/linklib
 */

namespace Swap\Utils;

class AppUtil
{
    private $app;

    /**
     * @param $app
     */
    public function __construct(\Swap\Core\App $app)
    {
        $this->app = $app;
    }

    /**
     * @return mixed
     * @author LCF
     * @date
     * 全局配置
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
        $globalConfig = $this->config();
        if ($globalConfig[$configKey]) {
            return $globalConfig[$configKey];
        }
        return $default;
    }

    /**
     * @param $info
     * @param string $file
     * @return bool|int
     * @author LCF
     * @date
     * 日常日志操作处理
     */
    public function logs($info, $file = null)
    {
        $file = $file ?: $this->config()['request.log.file'];
        return FunUtil::getInstance()->log($this->config()['logs'], $file, $info);
    }

    /**
     * @param $e
     * @param string $name
     * @return bool|int
     * @author LCF
     * @date
     * 异常日志函数
     */
    public function catchLog($e, $name = null)
    {
        $file = $name ? $name : $this->config()['request.log.file'] . '_exception';
        $log['异常,信息如下'] = [
            '异常文件' => $e->getFile(),
            '异常行数' => $e->getLine(),
            '异常代码' => $e->getCode(),
            '异常信息' => $e->getMessage(),
            '异常数组' => $e->getTrace(),
        ];
        return FunUtil::getInstance()->log($this->config()['logs'], $file, $log);
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
        if (empty($config)) {
            $config = $this->config()['global.config']['redis'];
        }
        return $this->app->getUtils('RedisClass')->connect($config);
    }

    /**
     * @return mixed
     * @author LCF
     * @date 2019/8/17 18:35
     * 根目录
     */
    public function root()
    {
        return $this->config()['root.path'];
    }

}
