<?php

namespace Phax\Helper;

use Phax\Foundation\Router;

class Debug
{
    /**
     * 打印当前请求和路由信息
     * @return void
     */
    public static function info(): void
    {
        // 如果全部路由个数 > 1，则检查 routes/web.php 下是否有指定匹配
        pr('全部路由', \router()->getRoutes(),
            '匹配路由', \router()->getMatchedRoute(),
            false);

        dd(
            [
                'REQUEST_URI' => $_SERVER['REQUEST_URI'],
                '_url' => $_GET['url'] ?? '',
            ],
            [
                'route' => [
                    'namespace' => \router()->getNamespaceName(),
                    'controller' => \router()->getControllerName(),
                    'action' => \router()->getActionName(),
                ],
                'dispatcher' => [
                    'class' => dispatcher()->getHandlerClass(),
                ]
            ],
            Router::matchOptions(),
            false,
        );
    }
}