<?php
/**
 * @link https://gitee.com/lcfcode/linklib
 * @link https://github.com/lcfcode/linklib
 */

namespace Swap\Core;

use Swap\Utils\HttpUtil;

abstract class Controller
{
    use Utiltrait;

    /**
     * @var App
     */
    protected $app;

    /**
     * @param $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;
    }


    /**
     * @param $key
     * @param string $default
     * @return string|array
     * @author LCF
     * @date 2019/8/17 18:25
     * 获取 $_GET 参数
     */
    public function get($key = '', $default = '')
    {
        return HttpUtil::getInstance()->get($key, $default);
    }

    /**
     * @param $key
     * @param string $default
     * @return string|array
     * @author LCF
     * @date 2019/8/17 18:25
     * 获取 $_POST 参数
     */
    public function post($key = '', $default = '')
    {
        return HttpUtil::getInstance()->post($key, $default);
    }

    /**
     * @param $key
     * @param string $default
     * @return string|array
     * @author LCF
     * @date 2020/4/30 17:11
     * 获取 $_REQUEST 参数
     */
    public function param($key = '', $default = '')
    {
        return HttpUtil::getInstance()->param($key, $default);
    }

    /**
     * @param $key
     * @param string $default
     * @return string|array
     * @author LCF
     * @date 2020/4/30 17:11
     * 获取 $_FILES 参数
     */
    public function file($key = '', $default = '')
    {
        return HttpUtil::getInstance()->file($key, $default);
    }

    /**
     * @return bool
     * @author LCF
     * @date 2019/8/17 18:25
     * 判断请求方式是否是post
     */
    public function isPost()
    {
        return HttpUtil::getInstance()->isPost();
    }

    /**
     * @return null
     * @author LCF
     * @date 2019/8/17 18:27
     * 请求前执行的方法，需要重写
     */
    public function beforeRequest()
    {
        return null;
    }

    /**
     * @return null
     * @author LCF
     * @date 2019/8/17 18:27
     * 请求后执行的方法，需要重写
     */
    public function afterRequest()
    {
        return null;
    }

}
