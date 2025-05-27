<?php
/**
 * @link https://gitee.com/lcfcode/linklib
 * @link https://github.com/lcfcode/linklib
 */

namespace Swap\Core;

use Swap\Utils\FunUtil;

class Error
{
    private $logCfg;

    public function init($logCfg)
    {
        $this->logCfg = $logCfg;
    }

    public function render($debug)
    {
        if ($debug === true) {
            ini_set('display_errors', 'On');
            error_reporting(E_ALL);
            set_error_handler([$this, 'errorHandlerDev']);
            set_exception_handler([$this, 'exceptHandleDev']);
            register_shutdown_function([$this, 'shutdownHandlerDev']);
        } else {
            error_reporting(0);
            set_error_handler([$this, 'errorHandlerOnline']);
            set_exception_handler([$this, 'exceptHandleOnline']);
            register_shutdown_function([$this, 'shutdownHandlerOnline']);
        }
    }

    /**
     * @param $errNo
     * @param $errMsg
     * @param $errFile
     * @param $errLine
     * @param $errContext
     * @author LCF
     * @date 2019/8/17 18:09
     * 处理错误函数
     */
    public function errorHandlerOnline($errNo, $errMsg, $errFile, $errLine, $errContext)
    {
        $info['错误级别'] = $this->friendlyErrorType($errNo);
        $info['错误行数'] = $errLine;
        $info['错误文件'] = $errFile;
        $info['错误信息'] = $errMsg;
        $info['错误代码'] = $errNo;
        if ($errContext) {
            $info['错误数组'] = $errContext;
        }
        $this->logs('errorHandlerOnline',  $info);
        $this->error404();
        exit(0);
    }

    /**
     * @param $e \Exception
     * @user LCF
     * @date 2019/6/9 15:33
     * 处理异常函数
     */
    public function exceptHandleOnline($e)
    {
        $info['异常行数'] = $e->getLine();
        $info['异常文件'] = $e->getFile();
        $info['异常代码'] = $e->getCode();
        $info['异常信息'] = $e->getMessage();
        $info['异常数组'] = $e->getTrace();
        $this->logs('exceptHandleOnline', $info);
        if ($e->getCode() == 404) {
            $this->error404();
        } else {
            $this->error500();
        }
        exit(0);
    }

    /**
     * @author LCF
     * @date 2019/8/17 18:10
     * php中止时执行的函数
     */
    public function shutdownHandlerOnline()
    {
        $error = error_get_last();
        if ($error) {
            $info['文件'] = $error['file'];
            $info['行数'] = $error['line'];
            $info['类型'] = $error['type'];
            $info['信息'] = $error['message'];
            $this->logs('shutdownHandlerOnline', $info);
            $this->error500();
            exit(0);
        }
        return;
    }

    public function errorHandlerDev($errNo, $errMsg, $fileName, $line)
    {
//        ob_end_clean();
        $str = '<b>捕获错误</b>';
        $str .= '<br>错误级别 : ' . $this->friendlyErrorType($errNo);
        $str .= '<br>错误行数 : ' . $line;
        $str .= '<br>错误文件 : ' . $fileName;
        $str .= '<br>错误信息 : ' . $errMsg;
        $str .= '<br>错误代码 : ' . $errNo;
        echo $str;
        echo '<hr><b>其他信息</b><pre>';
//        print_r($vars);
        echo '</pre>';
        exit(0);
    }

    /**
     * @param $e \Exception
     * @user LCF
     * @date 2019/6/9 15:33
     */
    public function exceptHandleDev($e)
    {
//        ob_end_clean();
        $str = '<b>捕获异常</b>';
        $str .= '<br>异常行数 : ' . $e->getLine();
        $str .= '<br>异常文件 : ' . $e->getFile();
        $str .= '<br>异常代码 : ' . $e->getCode();
        $str .= '<br>异常信息 : ' . $e->getMessage();
        echo $str . '<hr>';
        echo '<b>抛出异常信息数组：</b><br><br>';
        print_r($e);
        echo '<hr><pre>';
        print_r($e->getTrace());
        echo '</pre>';
        exit(0);
    }

    public function shutdownHandlerDev()
    {
        $e = error_get_last();
        if ($e) {
//            ob_end_clean();
            $str = '<hr>';
            $str .= '<br>级别 : ' . $this->friendlyErrorType($e['type']);
            $str .= '<br>行数 : ' . $e['line'];
            $str .= '<br>文件 : ' . $e['file'];
            $str .= '<br>信息 : ' . $e['message'];
            $str .= '<br>类型 : ' . $e['type'];
            echo $str . '<hr>';
            echo '<pre>';
            print_r($e);
            echo '</pre>';
            exit(0);
        }
        return;
    }

    /**
     * @author LCF
     * @date 2019/8/17 18:11
     * 404 错误提示页面
     */
    public function error404()
    {
        ob_end_clean();
        echo $this->html();
        return;
    }

    /**
     * @author LCF
     * @date 2019/8/17 18:11
     * 其他错误提示页面 即500
     */
    public function error500()
    {
        ob_end_clean();
        echo $this->html(500);
        return;
    }

    /**
     * @param int $code
     * @return string
     * @author LCF
     * @date 2020/1/11 17:21
     * 错误提示页面
     */
    private function html($code = 404)
    {
        return <<<ERR
<!DOCTYPE html><html lang="en" style="font-size: 18px;"><head><meta charset="UTF-8"><title>sorry {$code}</title></head><style> *{font-size: 5rem;margin: 0;padding: 0}</style><body><div style="position: absolute;margin: auto;left: 0;right: 0;top: 0;bottom: 0;width: 42.5rem;height: 20.5rem;text-align: center"><p style="font-size: 4rem">错误</p><br><p>{$code}</p></div></body></html>
ERR;
    }


    private function logs($file, $message)
    {
        return FunUtil::getInstance()->log($this->logCfg, $file, $message);
    }

    private function friendlyErrorType($type)
    {
        switch ($type) {
            case E_ERROR: // 1 //
                return 'E_ERROR';
            case E_WARNING: // 2 //
                return 'E_WARNING';
            case E_PARSE: // 4 //
                return 'E_PARSE';
            case E_NOTICE: // 8 //
                return 'E_NOTICE';
            case E_CORE_ERROR: // 16 //
                return 'E_CORE_ERROR';
            case E_CORE_WARNING: // 32 //
                return 'E_CORE_WARNING';
            case E_COMPILE_ERROR: // 64 //
                return 'E_COMPILE_ERROR';
            case E_COMPILE_WARNING: // 128 //
                return 'E_COMPILE_WARNING';
            case E_USER_ERROR: // 256 //
                return 'E_USER_ERROR';
            case E_USER_WARNING: // 512 //
                return 'E_USER_WARNING';
            case E_USER_NOTICE: // 1024 //
                return 'E_USER_NOTICE';
            case E_STRICT: // 2048 //
                return 'E_STRICT';
            case E_RECOVERABLE_ERROR: // 4096 //
                return 'E_RECOVERABLE_ERROR';
            case E_DEPRECATED: // 8192 //
                return 'E_DEPRECATED';
            case E_USER_DEPRECATED: // 16384 //
                return 'E_USER_DEPRECATED';
        }
        return "OTHER";
    }
}