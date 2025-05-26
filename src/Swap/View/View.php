<?php
/**
 * @link https://gitee.com/lcfcode/linklib
 * @link https://github.com/lcfcode/linklib
 */

namespace Swap\View;

class View
{
    private $context;

    public function __construct($data = [])
    {
        $this->context['data.volume.data'] = $data;//给前端返回数据的
        $this->context['data.volume.set'] = [
            'page' => null,
            'controller' => null,
            'layout' => null,
            'head' => true,
        ];
    }

    /**
     * @return $this
     * @author LCF
     * @date 2020/1/16 11:09
     * 关闭layout  不是使用
     */
    public function closeLayout()
    {
        $this->context['data.volume.set']['head'] = false;
        return $this;
    }

    /**
     * @param $page
     * @param null $controller
     * @return $this
     * @author LCF
     * @date
     *设置指定页面或者指定的控制器
     */
    public function setView($page, $controller = null)
    {
        $this->context['data.volume.set']['page'] = $page;
        $this->context['data.volume.set']['controller'] = $controller;
        return $this;
    }

    /**
     * @param $layout
     * @return $this
     * @author LCF
     * @date 2020/1/16 11:22
     * 设置置顶的共同头文件
     */
    public function setLayout($layout)
    {
        $this->context['data.volume.set']['layout'] = $layout;
        return $this;
    }

    /**
     * @return mixed
     * @author LCF
     * @date 2020/1/10 11:54
     * 此方法不能在控制器中使用
     */
    public function get()
    {
        return $this->context;
    }

    public function run($config, $transfer)
    {
        $module = $config['request.module'];
        $controller = $config['request.controller'];
        $action = $config['request.action'];
        $data = $this->get();
        $set = $data['data.volume.set'];
        $views['default.path'] = $config['root.path'] . DIRECTORY_SEPARATOR . $config['app.path'] . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR;
        $views['default.controller'] = $controller;
        $views['default.page'] = $action;

        $layoutFile = $views['default.path'] . 'Layout' . DIRECTORY_SEPARATOR . 'layout.phtml';
        if ($set['layout'] !== null) {
            $layoutFile = $views['default.path'] . 'Layout' . DIRECTORY_SEPARATOR . $set['layout'] . '.phtml';
        }
        //请求大小写规则跟url规则方法名一样
        $path = $views['default.path'] . $views['default.controller'];
        if ($set['controller'] !== null) {
            $path = $views['default.path'] . $set['controller'];
        }
        if ($set['page'] !== null) {
            $page = $set['page'];
        } else {
            $page = $views['default.page'];
        }
        $content = $path . DIRECTORY_SEPARATOR . $page . '.phtml';
        if (!is_file($content)) {
            throw new \RuntimeException($content . ':view is not found', 500);
        }
        if ($set['head'] === true) {
            if (!is_file($layoutFile)) {
                throw new \RuntimeException($layoutFile . ':view is not found', 500);
            }
        } else {
            $layoutFile = '';
        }
        $pageObj = new Page($config, $transfer, $content, $layoutFile);
        return $pageObj->views($set['head']);
    }
}