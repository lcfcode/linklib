<?php
/**
 * @link https://gitee.com/lcfcode/linklib
 * @link https://github.com/lcfcode/linklib
 */

namespace Swap\View;

class Page
{
    private $data;
    private $all;
    private $_content;
    private $_layout;
    private $config;

    public function __construct($config, $transfer, $content = '', $layout = '')
    {
        $this->config = $config;
        $this->data = $transfer['data'];
        $this->all = $transfer;
        $this->_content = $content;
        $this->_layout = $layout;
    }

    /**
     * @param bool $flag
     * @return false|string
     * @author LCF
     * @date
     */
    public function views($flag = true)
    {
        $data = $this->data;
        $all = $this->all;
        ob_start();
        ob_implicit_flush(0);
        $flag === true ? require $this->_layout : require $this->_content;
        return ob_get_clean();
    }

    /**
     * @param string $key
     * @param null $default
     * @return null
     * @author LCF
     * @date 2019/8/17 18:18
     * 返回控制器吐出数据给前端方法
     */
    public function data($key = '', $default = null)
    {
        if (empty($key)) {
            return $this->data;
        }
        return isset($this->data[$key]) ? $this->data[$key] : $default;
    }

    /**
     * @param string $key
     * @param null $default
     * @return null
     * @author LCF
     * @date 2019/8/17 18:18
     * 返回全部后台吐出来的数据
     */
    public function all($key = '', $default = null)
    {
        if (empty($key)) {
            return $this->all;
        }
        return isset($this->all[$key]) ? $this->all[$key] : $default;
    }

}
