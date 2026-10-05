<?php
/**
 * @link https://gitee.com/lcfcode/linklib
 * @link https://github.com/lcfcode/linklib
 */

namespace Swap\Utils;

class FunUtil
{
    private static ?FunUtil $instance = null;

    public static function getInstance(): FunUtil
    {
        return self::$instance ??= new FunUtil();
    }

    /**
     * ⚠️ 不常用的密码处理。
     * 注意：这不是安全哈希，只是 md5 的变体；仅用于兼容老数据。
     *
     * @param string $pwd
     * @return string 32 位小写十六进制
     */
    public function passwordEncrypt(string $pwd): string
    {
        return strtolower(md5(substr(md5($pwd), 0, -3)));
    }

    /**
     * 哈希密码加密（bcrypt）
     *
     * @param string $pwd
     * @return string|false
     */
    public function passwordHash(string $pwd)
    {
        return password_hash($this->passwordEncrypt($pwd), PASSWORD_BCRYPT);
    }

    /**
     * 哈希密码验证
     *
     * @param string $pwd
     * @param string $hash
     * @return bool
     */
    public function passwordVerify(string $pwd, string $hash): bool
    {
        return password_verify($this->passwordEncrypt($pwd), $hash);
    }

    /**
     * HTTP GET
     *
     * @param string $url
     * @param int    $timeOut
     * @param int    $connectTimeOut
     * @return array{status:bool, content:string, code:int, error:string}
     */
    public function httpGet(string $url, int $timeOut = 5, int $connectTimeOut = 5): array
    {
        $oCurl = curl_init();
        if ($oCurl === false) {
            return ['status' => false, 'content' => '', 'code' => 0, 'error' => 'curl_init failed'];
        }
        // 设置编码
//        $header = ['Content-Type:application/json;charset=UTF-8'];
//        curl_setopt($oCurl, CURLOPT_HTTPHEADER, $header);
        if ($this->isHttpUrl($url)) {
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, false);
        }

        curl_setopt_array($oCurl, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $timeOut,
            CURLOPT_CONNECTTIMEOUT => $connectTimeOut,
        ]);

        $sContent = curl_exec($oCurl);
        $aStatus  = curl_getinfo($oCurl);
        $error    = curl_error($oCurl);
        curl_close($oCurl);

        $code = (int)($aStatus['http_code'] ?? 0);
        if ($sContent === false) {
            // 网络层错误（DNS、超时、连接拒绝等）
            return ['status' => false, 'content' => '', 'code' => $code, 'error' => $error];
        }
        if ($code === 200) {
            return ['status' => true, 'content' => $sContent, 'code' => 200, 'error' => ''];
        }
        return ['status' => false, 'content' => $sContent, 'code' => $code, 'error' => $error];
    }

    /**
     * HTTP POST
     *
     * @param string       $url
     * @param string|array $param 字符串原样发送；数组会 http_build_query 后作为表单发送
     * @param int          $timeOut
     * @param int          $connectTimeOut
     * @return array{status:bool, content:string, code:int, error:string}
     */
    public function httpPost(string $url, $param, int $timeOut = 5, int $connectTimeOut = 5): array
    {
        $oCurl = curl_init();
        if ($oCurl === false) {
            return ['status' => false, 'content' => '', 'code' => 0, 'error' => 'curl_init failed'];
        }

        if ($this->isHttpUrl($url)) {
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, false);
        }

        if (is_array($param)) {
            $strPOST = http_build_query($param);
            $headers  = ['Content-Type: application/x-www-form-urlencoded'];
        } else {
            $strPOST = (string)$param;
            $headers  = [];
        }

        curl_setopt_array($oCurl, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_POSTFIELDS     => $strPOST,
            CURLOPT_TIMEOUT        => $timeOut,
            CURLOPT_CONNECTTIMEOUT => $connectTimeOut,
        ]);
        if ($headers) {
            curl_setopt($oCurl, CURLOPT_HTTPHEADER, $headers);
        }

        $sContent = curl_exec($oCurl);
        $aStatus  = curl_getinfo($oCurl);
        $error    = curl_error($oCurl);
        curl_close($oCurl);

        $code = (int)($aStatus['http_code'] ?? 0);
        if ($sContent === false) {
            return ['status' => false, 'content' => '', 'code' => $code, 'error' => $error];
        }
        if ($code === 200) {
            return ['status' => true, 'content' => $sContent, 'code' => 200, 'error' => ''];
        }
        return ['status' => false, 'content' => $sContent, 'code' => $code, 'error' => $error];
    }

    /**
     * 写日志
     *
     * @param array  $logCfg ['path' => 日志根目录, 'size' => 单文件大小上限(MB)]
     * @param string $file   文件名（不含 .log）
     * @param mixed  $info   日志内容
     * @return int|false 写入字节数或 false
     */
    public function log(array $logCfg, string $file, $info)
    {
        $dir  = rtrim($logCfg['path'] ?? '', "/\\");
        $size = (int)($logCfg['size'] ?? 0);
        if ($size <= 0) {
            $size = 10;
        }

        $dir = $dir . DIRECTORY_SEPARATOR . date('Ym') . DIRECTORY_SEPARATOR . date('d');
        if (!is_dir($dir)) {
            // 并发场景下可能已被其他进程创建，这里抑制警告并二次判断
            if (!@mkdir($dir, 0777, true) && !is_dir($dir)) {
                trigger_error('日志目录没有创建文件夹权限', E_USER_WARNING);
                return false;
            }
        }

        $context = json_encode(
            [
                'log_date' => '[' . date('Y-m-d H:i:s') . '][' . microtime() . ']',
                'log_info' => $info,
            ],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
        );
        if ($context === false) {
            $context = '{"log_date":"' . date('Y-m-d H:i:s') . '","log_info":"[json_encode failed]"}';
        }
        $context .= PHP_EOL;

        $fileName = $dir . DIRECTORY_SEPARATOR . $file . '.log';

        // 日志轮转：超过大小则改名，加毫秒避免同秒覆盖
        if (is_file($fileName) && filesize($fileName) >= $size * 1024 * 1024) {
            $suffix = date('YmdHis') . substr((string)microtime(), 1, 4);
            @rename($fileName, $fileName . '.' . $suffix . '.log');
        }

        $put = @file_put_contents($fileName, $context, FILE_APPEND | LOCK_EX);
        if ($put === false) {
            trigger_error('日志目录没有写入文件权限', E_USER_WARNING);
        }
        return $put;
    }

    /**
     * 判断是否为 http(s) URL（只看协议头）
     */
    private function isHttpUrl(string $url): bool
    {
        return stripos($url, 'http://') === 0 || stripos($url, 'https://') === 0;
    }
}