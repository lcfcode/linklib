<?php
/**
 * @link https://gitee.com/lcfcode/linklib
 * @link https://github.com/lcfcode/linklib
 */

declare(strict_types=1);

namespace Swap\Utils;

class StaticUtil
{
    // ---------- 字符串 ----------

    public static function snakeToCamel(string $str, bool $ucfirst = false): string
    {
        $result = str_replace('_', '', ucwords($str, '_'));
        return $ucfirst ? ucfirst($result) : lcfirst($result);
    }

    public static function removeBom(string $str): string
    {
        if (strlen($str) >= 3
            && ord($str[0]) === 0xEF
            && ord($str[1]) === 0xBB
            && ord($str[2]) === 0xBF) {
            return substr($str, 3);
        }
        return $str;
    }

    public static function removeChinese(string $str): string
    {
        return preg_replace('/[\x{4e00}-\x{9fa5}]/u', '', $str);
    }

    public static function toUtf8($str)
    {
        $encode = mb_detect_encoding($str, ['ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5']);
        if ($encode && strtoupper($encode) !== 'UTF-8') {
            $str = mb_convert_encoding($str, 'UTF-8', $encode);
        }
        return $str;
    }

    public static function utf8ToGbk($str)
    {
        $encode = mb_detect_encoding($str, ['ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5']);
        if ($encode && strtoupper($encode) === 'UTF-8') {
            $str = iconv('UTF-8', 'GBK//IGNORE', $str);
        }
        return $str;
    }

    public static function toCodePoints(string $str): array
    {
        // 注意：原实现是逐字节取 ord，这里保留原行为但改名提示
        $bytes = [];
        $len = strlen($str);
        for ($i = 0; $i < $len; $i++) {
            $bytes[] = ord($str[$i]);
        }
        return $bytes;
    }

    /**
     * 真正的 Unicode 码点（按字符，不是按字节）
     */
    public static function toRealCodePoints(string $str): array
    {
        return array_map('mb_ord', mb_str_split($str, 1, 'UTF-8'));
    }

    // ---------- 目录 / 文件 ----------

    /**
     * 获取目录下指定后缀的文件
     * @param string       $src    目录
     * @param string|array $suffix 后缀，如 'mp4' 或 ['mp4','mkv']
     * @return array [文件名 => 完整路径]
     */
    public static function getArrFile(string $src, $suffix = null): array
    {
        $arr = [];
        if (!is_dir($src) || ($dir = opendir($src)) === false) {
            return $arr;
        }
        $suffixArr = self::normalizeSuffix($suffix);
        while (false !== ($file = readdir($dir))) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $newFile = $src . DIRECTORY_SEPARATOR . $file;
            if (!is_file($newFile)) {
                continue;
            }
            if ($suffixArr && !in_array(self::fileExt($file), $suffixArr, true)) {
                continue;
            }
            $arr[$file] = $newFile;
        }
        closedir($dir);
        return $arr;
    }

    /**
     * 递归获取目录下指定后缀的文件（按引用收集）
     * @param array $arr 结果收集数组，返回完整路径列表
     */
    public static function getArrFiles(string $src, array &$arr, $suffix = null, array $exclude = []): void
    {
        $suffixArr = self::normalizeSuffix($suffix);
        $exclude = array_unique(array_map('strtolower', array_merge($exclude, ['.', '..'])));
        self::scanFiles($src, $arr, $suffixArr, $exclude);
    }

    private static function scanFiles(string $src, array &$arr, array $suffixArr, array $exclude): void
    {
        if (!is_dir($src) || ($dir = opendir($src)) === false) {
            return;
        }
        while (false !== ($file = readdir($dir))) {
            if (in_array(strtolower($file), $exclude, true)) {
                continue;
            }
            $newFile = $src . DIRECTORY_SEPARATOR . $file;
            if (is_file($newFile)) {
                if (!$suffixArr || in_array(self::fileExt($file), $suffixArr, true)) {
                    $arr[] = $newFile;
                }
            } elseif (is_dir($newFile)) {
                self::scanFiles($newFile, $arr, $suffixArr, $exclude);
            }
        }
        closedir($dir);
    }

    /**
     * 获取一级子目录
     * @return array [目录名 => 完整路径]
     */
    public static function getArrDir(string $src): array
    {
        $arr = [];
        if (!is_dir($src) || ($dir = opendir($src)) === false) {
            return $arr;
        }
        while (false !== ($file = readdir($dir))) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $newFile = $src . DIRECTORY_SEPARATOR . $file;
            if (is_dir($newFile)) {
                $arr[$file] = $newFile;
            }
        }
        closedir($dir);
        return $arr;
    }

    /**
     * 复制文件或目录
     */
    public static function copy(string $src, string $dst): bool
    {
        if (!file_exists($src)) {
            return false;
        }
        if (is_file($src)) {
            $parent = dirname($dst);
            if (!is_dir($parent) && !mkdir($parent, 0777, true) && !is_dir($parent)) {
                return false;
            }
            return copy($src, $dst);
        }
        // 目录
        if (!is_dir($dst) && !mkdir($dst, 0777, true) && !is_dir($dst)) {
            return false;
        }
        $ok = true;
        foreach (scandir($src) ?: [] as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $s = $src . DIRECTORY_SEPARATOR . $file;
            $d = $dst . DIRECTORY_SEPARATOR . $file;
            $ok = is_dir($s) ? self::copy($s, $d) : copy($s, $d);
            if (!$ok) {
                return false;
            }
        }
        return $ok;
    }

    /**
     * 递归删除文件或目录
     */
    public static function delete(string $dir): bool
    {
        if (!file_exists($dir)) {
            return true;
        }
        if (is_file($dir) || is_link($dir)) {
            return unlink($dir);
        }
        foreach (scandir($dir) ?: [] as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($path) && !is_link($path)) {
                self::delete($path);
            } else {
                unlink($path);
            }
        }
        return rmdir($dir);
    }

    // ---------- 杂项 ----------

    public static function getUuid(string $prefix = ''): string
    {
        return strtolower(md5(uniqid($prefix . php_uname('n') . mt_rand(), true)));
    }

    /**
     * 纳秒级时间字符串
     */
    public static function getMicrotime(): string
    {
        [$usec, $sec] = explode(' ', microtime());
        return sprintf('%d%09d', $sec, (int)round($usec * 1e9));
    }

    /**
     * 多列排序
     * sortByMultiCols($arr, ['id' => SORT_ASC]);
     */
    public static function sortByMultiCols(array $arr, array $args): array
    {
        if (!$arr || !$args) {
            return $arr;
        }
        $params = [];
        foreach ($args as $field => $dir) {
            $params[] = array_column($arr, $field);
            $params[] = $dir;
        }
        $params[] = &$arr;
        array_multisort(...$params);
        return $arr;
    }

    /**
     * 简单对称加密（⚠️ 非安全加密，仅用于混淆）
     */
    public static function encrypt(string $data, string $key): string
    {
        $key = md5($key);
        $len = strlen($data);
        $kl = strlen($key);
        $str = '';
        for ($i = 0; $i < $len; $i++) {
            $str .= chr((ord($data[$i]) + ord($key[$i % $kl])) % 256);
        }
        return base64_encode($str);
    }

    public static function decrypt(string $data, string $key): string
    {
        $key = md5($key);
        $data = base64_decode($data, true);
        if ($data === false) {
            return '';
        }
        $len = strlen($data);
        $kl = strlen($key);
        $str = '';
        for ($i = 0; $i < $len; $i++) {
            $diff = ord($data[$i]) - ord($key[$i % $kl]);
            $str .= chr($diff < 0 ? $diff + 256 : $diff);
        }
        return $str;
    }

    // ---------- 私有工具 ----------

    /**
     * 统一后缀格式：小写、不带点
     * @param string|array|null $suffix
     */
    private static function normalizeSuffix($suffix): array
    {
        if (empty($suffix)) {
            return [];
        }
        $arr = is_array($suffix) ? $suffix : [$suffix];
        return array_values(array_unique(array_map(
            static fn($s) => strtolower(ltrim((string)$s, '.')),
            $arr
        )));
    }

    private static function fileExt(string $file): string
    {
        return strtolower(pathinfo($file, PATHINFO_EXTENSION));
    }
}