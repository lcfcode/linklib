<?php
/**
 * @link https://gitee.com/lcfcode/linklib
 * @link https://github.com/lcfcode/linklib
 */

namespace Swap\Utils;

class HttpUtil
{
    private static ?HttpUtil $instance = null;

    public static function getInstance(): HttpUtil
    {
        if (null === self::$instance) {
            self::$instance = new HttpUtil();
        }
        return self::$instance;
    }

    /**
     * @param $cookieKey
     * @return bool
     * @user LCF
     * @date 2019/3/13 14:28
     * 清空cookie
     */
    public function unsetCookieValue($cookieKey)
    {
        $result = $this->setCookieValue($cookieKey, []);
        if ($result) {
            return true;
        }
        return false;
    }

    /**
     * @param $cookieKey
     * @return string
     * @user LCF
     * @date 2019/3/13 14:28
     * 获取cookie
     */
    public function getCookieValue($cookieKey)
    {
        $cookieInfo = isset($_COOKIE[$cookieKey]) ? $_COOKIE[$cookieKey] : '';
        if (empty($cookieInfo)) {
            return '';
        }
        return json_decode($cookieInfo, true);
    }

    /**
     * @param $cookieKey
     * @param $cookieArr
     * @param int $expires
     * @param string $dir
     * @return bool
     * @user LCF
     * @date 2019/3/13 14:29
     * 设置cookie
     */
    public function setCookieValue($cookieKey, $cookieArr, $expires = 604800, $dir = '/')
    {
        $serialize = json_encode($cookieArr);
        $result = setcookie($cookieKey, $serialize, time() + $expires, $dir);
        if ($result) {
            return true;
        }
        return false;
    }

    /**
     * @param $key
     * @param $value
     * @user LCF
     * @date 2019/3/13 14:29
     * 设置session
     */
    public function setSessionValue($key, $value)
    {
        if (!isset($_SESSION)) {
            session_start();
        }
        $_SESSION[$key] = $value;
    }

    /**
     * @param $key
     * @param string $default
     * @return string|array
     * @user LCF
     * @date 2019/3/13 14:29
     * 获取session
     */
    public function getSessionValue($key, $default = '')
    {
        if (!isset($_SESSION)) {
            session_start();
        }
        return isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
    }

    /**
     * @param $key
     * @user LCF
     * @date 2019/3/13 14:29
     * 清楚session
     */
    public function unsetSessionValue($key)
    {
        if (!isset($_SESSION)) {
            session_start();
        }
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }

    /**
     * @param $key
     * @param $value
     * @param $expire
     * @user LCF
     * @date 2019/3/13 14:30
     * 设置session过期时间
     */
    public function setValAndExpire($key, $value, $expire)
    {
        ini_set('session.gc_maxlifetime', $expire);
        session_set_cookie_params($expire);
        if (!isset($_SESSION)) {
            session_start();
        }
        $_SESSION[$key] = $value;
    }

    /**
     * @param $logCfg
     * @param $file
     * @param $info
     * @return bool|int
     * @user LCF
     * @date 2019/3/13 14:30
     * 日志记录
     */
    public function log($logCfg, $file, $info)
    {
        return FunUtil::getInstance()->log($logCfg, $file, $info);
    }

    /**
     * @return array|false|string
     * @user LCF
     * @date 2019/3/13 14:32
     * 获取ip
     */
    public function getIp()
    {
        $defaultIp = '0.0.0.0';
        if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), $defaultIp)) {
            $ip = getenv("HTTP_CLIENT_IP");
        } else {
            if (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), $defaultIp)) {
                $ip = getenv("HTTP_X_FORWARDED_FOR");
            } else {
                if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), $defaultIp)) {
                    $ip = getenv("REMOTE_ADDR");
                } else {
                    if (isset ($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], $defaultIp)) {
                        $ip = $_SERVER['REMOTE_ADDR'];
                    } else {
                        $ip = $defaultIp;
                    }
                }
            }
        }
        return $ip;
    }

    /**
     * @return string
     * @user LCF
     * @date 2019/3/13 14:32
     */
    public function getHost()
    {
        return "http://" . $_SERVER['HTTP_HOST'];
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
        if (empty($key)) {
            return $_GET;
        }
        if (isset($_GET[$key])) {
            return trim($_GET[$key]);
        }
        return $default;
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
        if (empty($key)) {
            return $_POST;
        }
        if (isset($_POST[$key])) {
            return trim($_POST[$key]);
        }
        return $default;
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
        if (empty($key)) {
            return $_REQUEST;
        }
        if (isset($_REQUEST[$key])) {
            return trim($_REQUEST[$key]);
        }
        return $default;
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
        if (empty($key)) {
            return $_FILES;
        }
        if (isset($_FILES[$key])) {
            return trim($_FILES[$key]);
        }
        return $default;
    }

    /**
     * @return bool
     * @author LCF
     * @date 2019/8/17 18:25
     * 判断请求方式是否是post
     */
    public function isPost()
    {
        if (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST') {
            return true;
        }
        return false;
    }

}
