<?php
/**
 * @link https://gitee.com/lcfcode/linklib
 * @link https://github.com/lcfcode/linklib
 */

namespace Swap\Core;

class Config
{
    private static array $objects = [];
    private static string|null $root = null;

    public static function root()
    {
        if (empty(self::$root)) {
            self::$root = dirname(__DIR__, 6);
        }
        return self::$root;
    }

    public static function set($alias, $object)
    {
        self::$objects[$alias] = $object;
    }

    public static function get($alias)
    {
        if (isset(self::$objects[$alias])) {
            return self::$objects[$alias];
        }
        throw new \RuntimeException('没有找到对应的实例', 500);
    }

    public static function _unset($alias)
    {
        unset(self::$objects[$alias]);
    }

    public static function getAll()
    {
        return self::$objects;
    }

    public static function globalConfig($configFile = 'dev.php')
    {
        $key = 'run.global.config.' . $configFile;
        if (isset(self::$objects[$key])) {
            return self::$objects[$key];
        }
        try {
            $path = self::root() . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR;
            if (is_file($path . $configFile)) {
                self::$objects[$key] = require $path . $configFile;;
                return self::$objects[$key];
            }
            exit('no file [' . $path . $configFile . ']');
        } catch (\Exception $e) {
            exit('no file [' . $path . $configFile . '] ,exception:' . $e->getMessage());
        }
    }

}