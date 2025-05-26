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
     * @param string $env 全局配置文件
     */
    public function __construct(string $env = 'dev')
    {
        $config = Config::globalConfig($env . '.php');
        $config['root.path'] = Config::root();
        $config['app.path'] = 'app';
        $config['run.debug'] = $env == 'dev';
        $this->handleException($config);
        $this->config = $this->route($config);
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
        $error->init($config['logs']);
        $error->render($config['run.debug']);
    }

    /**
     * @return mixed
     * @author LCF
     * @date 2019/8/17 18:13
     * 获取全局配置文件
     */
    public function config()
    {
        return $this->config;
    }

    /**
     * @param $config
     * @return array
     * @date 2019/8/17 18:13
     * 路由处理
     */
    private function route($config)
    {
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
     * @author LCF
     * @date 2019/8/17 18:14
     * 开始执行代码
     */
    public function run()
    {
        $html = $this->action($this->config());
        if (is_string($html)) {
            echo $html;
        } else {
            header('Content-Type:application/json;charset=UTF-8');
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

    /**
     * @return array
     * @user LCF
     * @date 2019/5/23 21:36
     * 获取模块配置文件
     */
    public function getModuleConfig()
    {
        $config = $this->config();
        $module = $config['request.module'];
        $app = $config['app.path'];
        if (!isset($config['module_file'])) {
            return [];
        }
        $moduleConfig = $config['module_file'];
        $key = 'run.config.' . $module . $app . $moduleConfig;
        if (isset($this->objects[$key])) {
            return $this->objects[$key];
        }
        $moduleFile = $config['root.path'] . DIRECTORY_SEPARATOR . $app . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . $moduleConfig;
        if (is_file($moduleFile)) {
            $this->objects[$key] = require $moduleFile;
        } else {
            $this->objects[$key] = [];
        }
        return $this->objects[$key];
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
        $functionName = $action . 'Action';
        $moduleObj = new $className($this);
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

    public function configAll($configKey = null, $default = null)
    {
        $config = $this->getModuleConfig();
        if (isset($config[$configKey])) {
            return $config[$configKey];
        }
        $configs = $this->config();
        if (isset($configs[$configKey])) {
            return $configs[$configKey];
        }
        if (true === $configKey) {
            return [
                'global.config' => $configs,
                'module.config' => $config,
            ];
        }
        if (empty($configKey)) {
            return array_merge($configs, $config);
        }
        return $default;
    }

    public function instance($class, ...$args)
    {
        if (isset($this->objects[$class])) {
            return $this->objects[$class];
        }
        $this->objects[$class] = new $class(...$args);
        return $this->objects[$class];
    }
}
