<?php
/**
 * @link https://gitee.com/lcfcode/linklib
 * @link https://github.com/lcfcode/linklib
 */

namespace Swap\Core;

class App
{
    private $config;
    private $objects = [];

    /**
     * Linker constructor.
     * @param string $env 主配置文件
     * @param string $app 代码目录
     */
    public function __construct(string $env = 'dev', string $app = 'app')
    {
        $config = Config::getConfig($env . '.php');
        $config['root.path'] = Config::root();
        $config['app.path'] = $app;
        $config['run.debug'] = $env === 'dev';
        $this->handleException($config);
        $this->config = $this->route($config);
    }

    /**
     * @return array
     * @author LCF
     * @date 2019/8/17 18:13
     * 获取全局配置文件
     */
    public function config()
    {
        return $this->config;
    }

    /**
     * @author LCF
     * @date 2019/8/17 18:14
     * 开始执行代码
     */
    public function http()
    {
        $html = $this->action($this->config());
        if (is_string($html)) {
            echo $html;
        } else {
            echo json_encode($html, JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * @param $config
     * @param string $read
     * @return mixed
     * @author LCF
     * @date 2019/10/26 16:37
     * 返回db类
     */
    public function dbInstance($config, $read = 'read_write')
    {
        $ojbKey = $config['host'] . ':' . $config['user'] . ':' . $config['database'] . ':' . $read;
        if (isset($this->objects[$ojbKey])) {
            return $this->objects[$ojbKey];
        }
        switch ($config['drive']) {
            case 'mongo':
                $class = 'Swap\\Utils\\MongoClass';
                break;
            case 'mssql':
                $class = 'Swap\\Utils\\MssqlClass';
                break;
            default:
                $class = 'Swap\\Utils\\MysqliClass';
                break;
        }
        $this->objects[$ojbKey] = new $class($config);
        return $this->objects[$ojbKey];
    }

    /**
     * @param $name
     * @return mixed
     * @author LCF
     * @date 2019/8/17 18:22
     * 返回工具类的实例
     */
    public function getUtils($name = 'AppUtil')
    {
        $class = 'Swap\\Utils\\' . ucwords($name);
        if (isset($this->objects[$class])) {
            return $this->objects[$class];
        }
        $this->objects[$class] = new $class($this);
        return $this->objects[$class];
    }

    public function instance($class, ...$args)
    {
        if (isset($this->objects[$class])) {
            return $this->objects[$class];
        }
        $this->objects[$class] = new $class(...$args);
        return $this->objects[$class];
    }

    /**
     * @param $config
     * @author LCF
     * @date 2019/8/17 18:07
     * 异常处理操作
     */
    private function handleException($config)
    {
        $error = new Error();
        $error->init($config['logs'], $config['run.debug']);
        $error->render();
    }

    /**
     * @param $config
     * @return array
     * @date 2019/8/17 18:13
     * 路由处理
     */
    private function route($config)
    {
        if ('cli' === PHP_SAPI) {
            $argv = $_SERVER['argv'];
            $config['request.log.file'] = 'cmd_run.' . basename($argv[0]);
            return $config;
        }
        $requestUrl = $_SERVER['REQUEST_URI'];
        $index = strpos($requestUrl, '?');
        $uri = $index > 0 ? substr($requestUrl, 0, $index) : $requestUrl;
        $route = $config['default_route'];
        $routeArr = $uri ? explode('/', trim($uri, '/')) : [];
        $module = isset($routeArr[0]) && !empty($routeArr[0]) ? $routeArr[0] : $route['module'];
        $controller = isset($routeArr[1]) ? $routeArr[1] : $route['controller'];
        $action = isset($routeArr[2]) ? $routeArr[2] : $route['action'];

        $controller = ucfirst($controller);//请求重第一个字母为小写将它转为大写，类文件默认大写开头
        $config['request.module'] = $module;
        $config['request.controller'] = $controller;
        $config['request.action'] = $action;
        $config['request.log.file'] = $module . '_' . $controller . '_' . $action;
        return $config;
    }

    /**
     * @param $config
     * @return array|string|null
     * @author LCF
     * @date 2019/8/17 18:15
     * 执行模块文件函数反回数据方法
     */
    private function action($config)
    {
        $module = $config['request.module'];
        $controller = $config['request.controller'];
        $action = $config['request.action'];
        $className = $config['app.path'] . '\\' . $module . '\\controller\\' . $controller . 'Controller';
        if (!class_exists($className)) {
            throw new \RuntimeException('未定义的类:' . $className, 500);
        }
        $functionName = $action . 'Action';
        $moduleObj = new $className($this);
        if (!method_exists($moduleObj, $functionName)) {
            throw new \RuntimeException($className . ' 未定义的方法:' . $functionName, 500);
        }
        $before = $moduleObj->beforeRequest();
        if ($before instanceof ResponseHandler) {
            $moduleObj->afterRequest();
            return $before->get();
        }
        $context = $moduleObj->$functionName();
        $transfer['before'] = $before;
        $transfer['after'] = $moduleObj->afterRequest();
        if (!($context instanceof \Swap\View\View)) {
            if (is_array($context)) {
                $context['before'] = $before;
                $context['after'] = $transfer['after'];
            }
            return $context;
        }
        $data = $context->get();
        $transfer['data'] = $data['data.volume.data'];
        return $context->run($config, $transfer);
    }

}
