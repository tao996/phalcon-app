<?php

namespace Phax\Foundation;

class Response
{
    /**
     * @var string 错误处理类，比 config('app.error') 优先级更高；
     * 使用场景，需要在运行过程中动态修改响应类;
     * 需要实现 notFound 和 exception
     */
    public static string $redirectResponseClass = '';

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
     * 发送内容
     * @param mixed $data 响应的内容
     * @param int $code
     * @return \Phalcon\Http\ResponseInterface
     */
    public static function send($data, int $code = 200)
    {
        return response()->setStatusCode($code)
            ->setContent($data)
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