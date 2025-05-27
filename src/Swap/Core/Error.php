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
    private $debug;

    public function init($logCfg, $debug)
    {
        $this->logCfg = $logCfg;
        $this->debug = $debug;
    }

    public function render()
    {
        if ($this->debug === true) {
            ini_set('display_errors', 'On');
            error_reporting(E_ALL);
        } else {
            ini_set('display_errors', 'Off');
            error_reporting(0);
        }
        set_error_handler([$this, 'errorHandler']);
        set_exception_handler([$this, 'exceptHandle']);
        register_shutdown_function([$this, 'shutdownHandler']);
    }

    public function errorHandler($errNo, $errMsg, $file, $line, $errContext = null)
    {
        $info['错误级别'] = $this->friendlyErrorType($errNo);
        $info['错误行数'] = $line;
        $info['错误文件'] = $file;
        $info['错误信息'] = $errMsg;
        $info['错误代码'] = $errNo;
        if ($errContext) {
            $info['错误数组'] = $errContext;
        }
        $this->printError($info, '404', 'error_handler');
    }

    public function exceptHandle($e)
    {
        $info['异常行数'] = $e->getLine();
        $info['异常文件'] = $e->getFile();
        $info['异常代码'] = $e->getCode();
        $info['异常信息'] = $e->getMessage();
        $info['异常数组'] = $e->getTrace();
        $this->printError($info, $e->getCode(), 'except_handle');
    }

    public function shutdownHandler()
    {
        $error = error_get_last();
        if ($error) {
            $info['文件'] = $error['file'];
            $info['行数'] = $error['line'];
            $info['类型'] = $error['type'];
            $info['信息'] = $error['message'];
            $this->printError($info, '500', 'shutdown_handler');
        }
        return;
    }

    private function printError($info, $code, $logFile)
    {
        if ($this->debug === true) {
            if ('cli' == PHP_SAPI) {
                print_r($info);
            } else {
                echo '<pre>';
                print_r($info);
                echo '</pre>';
            }
        } else {
            FunUtil::getInstance()->log($this->logCfg, 'run_' . $logFile, $info);
            if ('cli' == PHP_SAPI) {
                print_r($info);
            } else {
                $this->html($code);
                exit(0);
            }
        }
    }

    /**
     * @param int $code
     * @author LCF
     * @date 2020/1/11 17:21
     * 错误提示页面
     */
    private function html($code = 404)
    {
        ob_end_clean();
        $html = <<<ERR
<!DOCTYPE html><html lang="en" style="font-size: 18px;"><head><meta charset="UTF-8"><title>sorry {$code}</title></head><style> *{font-size: 5rem;margin: 0;padding: 0}</style><body><div style="position: absolute;margin: auto;left: 0;right: 0;top: 0;bottom: 0;width: 42.5rem;height: 20.5rem;text-align: center"><p style="font-size: 4rem">错误</p><br><p>{$code}</p></div></body></html>
ERR;
        echo $html;
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