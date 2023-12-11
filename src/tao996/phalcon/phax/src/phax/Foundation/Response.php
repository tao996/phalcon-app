<?php

namespace Phax\Foundation;

class Response
{
    /**
     * 设置跳转
     */
    public static function redirectIn(string $action, string $controller = ''): void
    {
        if ($controller == '') {
            $controller = router()->getControllerName();
        }
        response()->redirect($controller . '/' . $action)
            ->send();
    }

    /**
     * 地址跳转
     * @param $location
     * @param bool $externalRedirect
     * @param int $statusCode
     * @return void
     */
    public static function redirect($location = null, bool $externalRedirect = false, int $statusCode = 302): void
    {
        response()->redirect($location, $externalRedirect, $statusCode)
            ->send();
    }

    /**
     * 渲染指定路径的视图模板
     * @param string $pathTpl 模板路径
     * @param array $data
     * @return string
     */
    public static function simpleView(string $pathTpl, array $data = []): string
    {
        $simpleView = new \Phalcon\Mvc\View\Simple();
        return $simpleView->render($pathTpl, $data);
    }
}