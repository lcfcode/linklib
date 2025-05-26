<?php
/**
 * @link https://gitee.com/lcfcode/linklib
 * @link https://github.com/lcfcode/linklib
 */

namespace Swap\Core;

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
        return $this->utils()->get($key, $default);
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
        return $this->utils()->post($key, $default);
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
        return $this->utils()->param($key, $default);
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
        return $this->utils()->file($key, $default);
    }

    /**
     * @return bool
     * @author LCF
     * @date 2019/8/17 18:25
     * 判断请求方式是否是post
     */
    public function isPost()
    {
        return $this->utils()->isPost();
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

    /**
     * @param $code
     * @param string $msg
     * @param array $data
     * @return array|string
     * @author LCF
     * @date 2020/1/10 11:39
     */
    public function msg($code, $msg = '', $data = [])
    {
        return ['code' => $code, 'msg' => $msg, 'data' => $data];
    }
}
