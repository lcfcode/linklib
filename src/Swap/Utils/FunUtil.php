<?php
/**
 * @link https://gitee.com/lcfcode/linklib
 * @link https://github.com/lcfcode/linklib
 */

namespace Swap\Utils;


class FunUtil
{
    /**
     * @param $pwd
     * @return string
     * @author LCF
     * @date
     * 不常用的密码处理
     */
    public function passwordEncrypt($pwd)
    {
        return strtolower(md5(substr(md5($pwd), 0, -3)));
    }
    
    /**
     * @param $pwd
     * @return bool|string
     * @author LCF
     * @date 2019/8/17 21:02
     * 哈希密码加密
     */
    public function passwordHash($pwd)
    {
        $password = $this->passwordEncrypt($pwd);
        return password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * @param $pwd
     * @param $hash
     * @return bool
     * @author LCF
     * @date 2019/8/17 21:01
     * 哈希密码验证
     */
    public function passwordVerify($pwd, $hash)
    {
        $password = $this->passwordEncrypt($pwd);
        return password_verify($password, $hash);
    }


    /**
     * @param $url
     * @param int $timeOut
     * @param int $connectTimeOut
     * @return array
     * @user LCF
     * @date 2019/4/10 23:42
     */
    public function httpGet($url, $timeOut = 5, $connectTimeOut = 5): array
    {
        $oCurl = curl_init();
        if (stripos($url, "http://") !== FALSE || stripos($url, "https://") !== FALSE) {
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
        }
        // 设置编码
//        $header = ['Content-Type:application/json;charset=UTF-8'];
//        curl_setopt($oCurl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($oCurl, CURLOPT_TIMEOUT, $timeOut);
        curl_setopt($oCurl, CURLOPT_CONNECTTIMEOUT, $connectTimeOut);
        $sContent = curl_exec($oCurl);
        $aStatus = curl_getinfo($oCurl);
        $error = curl_error($oCurl);
        curl_close($oCurl);
        if (intval($aStatus ["http_code"]) == 200) {
            return ['status' => true, 'content' => $sContent, 'code' => 200,];
        }
        return ['status' => false, 'content' => json_encode(["error" => $error, "url" => $url]), 'code' => $aStatus ["http_code"],];
    }

    /**
     * @param $url
     * @param $param
     * @param int $timeOut
     * @param int $connectTimeOut
     * @return array
     * @user LCF
     * @date 2019/4/10 23:42
     */
    public function httpPost($url, $param, $timeOut = 5, $connectTimeOut = 5): array
    {
        $oCurl = curl_init();
        if (stripos($url, "http://") !== FALSE || stripos($url, "https://") !== FALSE) {
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, false);
        }
        if (is_string($param)) {
            $strPOST = $param;
        } else {
            $aPOST = array();
            foreach ($param as $key => $val) {
                $aPOST [] = $key . "=" . urlencode($val);
            }
            $strPOST = join("&", $aPOST);
        }
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($oCurl, CURLOPT_POST, true);
        curl_setopt($oCurl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($oCurl, CURLOPT_POSTFIELDS, $strPOST);
        curl_setopt($oCurl, CURLOPT_TIMEOUT, $timeOut);
        curl_setopt($oCurl, CURLOPT_CONNECTTIMEOUT, $connectTimeOut);
        $sContent = curl_exec($oCurl);
        $aStatus = curl_getinfo($oCurl);
        $error = curl_error($oCurl);
        curl_close($oCurl);
        if (intval($aStatus ["http_code"]) == 200) {
            return ['status' => true, 'content' => $sContent, 'code' => 200,];
        }
        return ['status' => false, 'content' => json_encode(["error" => $error, "url" => $url]), 'code' => $aStatus ["http_code"],];
    }

    public function log($logCfg, $file, $info)
    {
        $dir = $logCfg['path'];
        $size = intval($logCfg['size']);
        if ($size <= 0) {
            $size = 10;
        }
        $dir = rtrim($dir, "/\\");
        $dir = $dir . DIRECTORY_SEPARATOR . date('Ym') . DIRECTORY_SEPARATOR . date('d');
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0777, true)) {
                trigger_error('日志目录没有创建文件夹权限', E_USER_ERROR);
            }
        }
        $context = json_encode([
                'log_date' => '[' . date('Y-m-d H:i:s') . '][' . microtime() . ']',
                'log_info' => $info
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
//        $files = strtr($file, ['\\' => '_', '::' => '_', '/' => '_']);
        $fileName = $dir . DIRECTORY_SEPARATOR . $file . '.log';
        if (is_file($fileName)) {
            if (filesize($fileName) >= $size * 1024 * 1024) {
                rename($fileName, $fileName . '.' . date('YmdHis') . '.log');
            }
        }
        $put = @file_put_contents($fileName, $context, FILE_APPEND | LOCK_EX);
        if (false === $put) {
            trigger_error('日志目录没有写入文件权限', E_USER_ERROR);
        }
        return $put;
    }
}
