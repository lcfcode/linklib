<?php
/**
 * @link https://gitee.com/lcfcode/linklib
 * @link https://github.com/lcfcode/linklib
 */

namespace Swap\Utils;

class StaticUtil
{

    public static function snakeToCamel($str, $flag): string
    {
        $result = str_replace('_', '', ucwords($str, '_'));
        if ($flag) {
            $result = ucwords($result);
        }
        return $result;
    }

    public static function getArrFile(string $src, $suffix = null): array
    {
        $dir = opendir($src);
        $arr = [];
        while (false !== ($file = readdir($dir))) {
            $newFile = $src . DIRECTORY_SEPARATOR . $file;
            if ('.' == $file || '..' == $file || is_dir($newFile)) {
                continue;
            }
            $isFile = is_file($newFile);
            if (!empty($suffix)) {
                $len = strlen($suffix);
                $tmpFix = substr($file, -1 * $len);
                $isFile = is_file($newFile) && strtolower($tmpFix) == strtolower($suffix);
            }
            if ($isFile) {
                $arr[$file] = $newFile;
            }
        }
        closedir($dir);
        return $arr;
    }

    private static function getArrFiles(string $src, &$arr, $suffix = null, $exclude = [])
    {
        array_push($exclude, '.', '..');
        $exclude = array_keys(array_flip(array_map('strtolower', $exclude)));
        $dir = opendir($src);
        while (false !== ($file = readdir($dir))) {
            if (in_array(strtolower($file), $exclude)) {
                continue;
            }
            $newFile = $src . DIRECTORY_SEPARATOR . $file;
            $isFile = is_file($newFile);
            if (!empty($suffix)) {
                $len = strlen($suffix);
                $tmp = substr($file, -1 * $len);
                $isFile = is_file($newFile) && strtolower($tmp) == strtolower($suffix);
            }
            if ($isFile) {
                $arr[] = $newFile;
            } else if (is_dir($newFile)) {
                self::getArrFiles($newFile, $arr, $suffix, $exclude);
            }
        }
        closedir($dir);
    }

    public static function getArrDir(string $src): array
    {
        $dir = opendir($src);
        $arr = [];
        while (false !== ($file = readdir($dir))) {
            $newFile = $src . DIRECTORY_SEPARATOR . $file;
            if ('.' == $file || '..' == $file || is_file($newFile)) {
                continue;
            }
            if (is_dir($newFile)) {
                $arr[$file] = $newFile;
            }
        }
        closedir($dir);
        return $arr;
    }

    public static function copy($src, $dst)
    {
        if (is_file($src)) {
            $dest = dirname($dst);
            if (!is_dir($dest)) {
                mkdir($dest, 0777, true);
            }
            copy($src, $dst);
            return;
        }
        if (!is_dir($dst)) {
            mkdir($dst, 0777, true);
        }
        $dir = opendir($src);
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . DIRECTORY_SEPARATOR . $file)) {
                    self::copy($src . DIRECTORY_SEPARATOR . $file, $dst . DIRECTORY_SEPARATOR . $file);
                } else {
                    copy($src . DIRECTORY_SEPARATOR . $file, $dst . DIRECTORY_SEPARATOR . $file);
                }
            }
        }
        closedir($dir);
    }

    public static function delete($dir)
    {
        if (!file_exists($dir)) {
            return;
        }
        if (is_file($dir)) {
            unlink($dir);
            return;
        }
        foreach (scandir($dir) as $file) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($path)) {
                self::delete($path);
            } else if (is_file($path)) {
                unlink($path);
            }
        }
        rmdir($dir);
    }


    public static function toUtf8($str): bool|array|string
    {
        // 检测字符的编码格式
        $encode = mb_detect_encoding($str, array('ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5'));
        // 转换编码格式
        if (strtoupper($encode) != 'UTF-8') {
            $str = mb_convert_encoding($str, 'UTF-8', $encode);
        }
        return $str;
    }

    public static function utf8ToGbk($str): bool|array|string
    {
        // 检测字符的编码格式
        $encode = mb_detect_encoding($str, array('ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5'));
        // 转换编码格式
        if (strtoupper($encode) == 'UTF-8') {
            $str = iconv("UTF-8", "GBK//IGNORE", $str);
        }
        return $str;
    }

    public static function removeBom($str)
    {
        $charset[1] = substr($str, 0, 1);
        $charset[2] = substr($str, 1, 1);
        $charset[3] = substr($str, 2, 1);
        if (ord($charset[1]) == 239 && ord($charset[2]) == 187 && ord($charset[3]) == 191) {
            $str = substr($str, 3);
        }
        return $str;
    }

    /**
     * @param $str
     * @return array|string|null
     * 移出汉字
     */
    public static function removeChinese($str): array|string|null
    {
        return preg_replace('/[\x{4e00}-\x{9fa5}]/u', '', $str);
    }

    public static function phpNameStyle($str, $flag = false)
    {
        $resArr = [];
        //preg_match_all("/\\$[^\s\\]\\[=)}.,;\\-]+\s?/", $str, $res);
        preg_match_all("/\\$[^\s\\]\\[=)}.,;\\-+]+/", $str, $resArr);
        if (empty($resArr)) {
            return "";
        }
        $varName = $resArr[0];
        //php预定义变量
        $predefine = ['$GLOBALS', '$_SERVER', '$_GET', '$_POST', '$_FILES', '$_COOKIE', '$_SESSION', '$_REQUEST', '$_ENV', '$_COOKIE', '$php_errormsg', '$HTTP_RAW_POST_DATA', '$http_response_header', '$argc', '$argv'];
        foreach ($varName as $field) {
            if (in_array(trim($field), $predefine)) {
                continue;
            }
            if ($flag === true) {
                $newName = '$' . strtolower(trim(preg_replace("/[A-Z]/", "_\\0", $field), '$_'));
            } else {
                $newName = preg_replace_callback('/_([a-zA-Z])/', function ($match) {
                    return strtoupper($match[1]);
                }, $field);
            }
            $str = str_replace($field, $newName, $str);
        }
        return $str;
    }

    public static function getUuid($prefix = null): string
    {
        return strtolower(md5(uniqid($prefix . php_uname('n') . mt_rand(), true)));
    }

    public static function toCodePoints($str): array
    {
        $ips = [];
        $len = strlen($str);
        for ($i = 0; $i < $len; $i++) {
            $ips[] = ord($str[$i]);
        }
        return $ips;
    }

    public static function getMicrotime()
    {
        $micr = explode(' ', microtime());
        return $micr[1] . $micr[0] * 1000000000;
    }

    /**
     * @param $rowset
     * @param $args
     * @return mixed
     * sortByMultiCols($arr, ['id' => SORT_ASC]); 根据id升序
     * sortByMultiCols($arr, ['id' => SORT_DESC]); 根据id降序
     */
    public static function sortByMultiCols($arr, $args)
    {
        $sortArray = [];
        $sortRule = '';
        foreach ($args as $sortField => $sortDir) {
            foreach ($arr as $offset => $row) {
                $sortArray[$sortField][$offset] = $row[$sortField];
            }
            $sortRule .= '$sortArray[\'' . $sortField . '\'], ' . $sortDir . ', ';
        }
        if (empty($sortArray) || empty($sortRule)) {
            return $arr;
        }
        $run = 'array_multisort(' . $sortRule . '$arr);';
        eval($run);
        return $arr;
    }

    public static function encrypt($data, $key): string
    {
        $key = md5($key);
        $x = 0;
        $len = strlen($data);
        $l = strlen($key);
        $char = '';
        for ($i = 0; $i < $len; $i++) {
            if ($x == $l) {
                $x = 0;
            }
            $char .= $key[$x];
            $x++;
        }
        $str = '';
        for ($i = 0; $i < $len; $i++) {
            $str .= chr(ord($data[$i]) + (ord($char[$i])) % 256);
        }
        return base64_encode($str);
    }

    public static function decrypt($data, $key): string
    {
        $key = md5($key);
        $x = 0;
        $data = base64_decode($data);
        $len = strlen($data);
        $l = strlen($key);
        $char = '';
        for ($i = 0; $i < $len; $i++) {
            if ($x == $l) {
                $x = 0;
            }
            $char .= substr($key, $x, 1);
            $x++;
        }
        $str = '';
        for ($i = 0; $i < $len; $i++) {
            if (ord(substr($data, $i, 1)) < ord(substr($char, $i, 1))) {
                $str .= chr((ord(substr($data, $i, 1)) + 256) - ord(substr($char, $i, 1)));
            } else {
                $str .= chr(ord(substr($data, $i, 1)) - ord(substr($char, $i, 1)));
            }
        }
        return $str;
    }
}
