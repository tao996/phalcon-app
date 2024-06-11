<?php

namespace App\Http;

use Phax\Foundation\Router;
use Phax\Helper\Debug;
use Phax\Mvc\Request;

class Response
{
    /**
     * 异常时响应
     * @param \Exception $e
     */
    public static function exception(\Exception $e)
    {
        // 如果是 api 则返回错误信息，否是携带错误信息重新渲染视图
        if (Request::isApiRequest()) {
            return json([
                'code' => $e->getCode() || 500,
                'msg' => $e->getMessage(),
                'data' => null,
            ]);
        } else {
            if (Router::isMultipleModules()) {
                echo "TODO 多模块异常响应:", $e->getMessage();
                return true;
            }
            $data = array_merge($_GET, $_POST);
            // 在原视图模板上显示错误信息
            if ($tpl = Router::getPathViewTPL()) {
                flash()->error($e->getMessage());
                \Phax\Mvc\Response::simpleView($tpl, $data);
            } else {
                echo '未处理的错误信息：',$e->getMessage();
            }
            return true;
        }
    }

    /**
     * 路由没有匹配到
     * @param \Exception $e
     * @return void
     */
    public static function notFound(\Exception $e)
    {
        dd($e->getMessage(), false);
        Debug::info();
    }
}