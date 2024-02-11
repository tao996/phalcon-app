<?php

namespace app\Modules\tao;

use app\Modules\tao\sdk\phaxui\HtmlAssets;
use app\Modules\tao\sdk\phaxui\Layui\Layui;

use Phax\Foundation\Response;
use Phax\Mvc\Controller;
use Phax\Mvc\Request;
use Phax\Support\Exception\BlankException;

/**
 * 定义各种响应格式
// * @method \Phalcon\Http\Response error($msg, array $data = [], int $code = 500)
// * @method \Phalcon\Http\Response success(string $msg, mixed $data = '')
 */
class BaseResponseController extends Controller
{
    /**
     * 当前默认主题
     */
    public static string $theme = 'layui';

    public static function getBaseViewDir(string $tpl = ''): string
    {
        if (static::$theme) {
            return __DIR__ . '/views/' . static::$theme . '/' . $tpl;
        } else {
            return __DIR__ . '/views/' . $tpl;
        }
    }

    protected function beforeJsonResponse(mixed &$data): bool
    {

        if (!isset($data['code']) && !isset($data['msg']) && !isset($data['data'])) {
            $this->success('', $data);
            return false;
        }
        return parent::beforeJsonResponse($data);
    }

    protected function beforeViewResponse(mixed &$data): bool
    {
        HtmlAssets::initWithCdn();
        $this->view->setVar('layui', Layui::getInstance());
        require_once __DIR__ . '/common/function.php';
        require_once PATH_TAO . 'views/function.php';
        return parent::beforeViewResponse($data);
    }

    /**
     * 定制 System 模块的统一错误响应
     * @param array|string $msg 错误提示信息
     * @param array $data 数据，如果为视图，可设置 redirect 作为返回上一页的数据
     */
    public static function error(array|string $msg, array $data = [], int $code = 500)
    {

        if (Request::isApiRequest()) {
            return \json([
                'code' => $code,
                'msg' => is_array($msg) ? join('<br>', $msg) : $msg,
                'data' => $data,
            ]);
        } else {
            echo Response::simpleView(static::getBaseViewDir('error.phtml'), [
                'msg' => (array)$msg, // 可能是数组
                'data' => $data,
            ]);
        }
    }


    /**
     * 定制 System 模块的统一成功响应
     * @param string $msg 提示信息
     * @param mixed $data 响应数据
     */
    public static function success(string $msg, mixed $data = '')
    {
        $result = [
            'code' => 0, 'msg' => $msg, 'data' => $data,
        ];
        if (Request::isApiRequest()) {
            return \json($result);
        } else {
            // 不要在视图中处理成功响应
            dd(__FILE__, '未处理成功响应页面');
        }
    }

    public function successPagination(int $count, array $rows): \Phalcon\Http\Response
    {
        return \json([
            'code' => 0, 'msg' => '', 'count' => $count, 'data' => $rows
        ]);
    }
// 处理异常响应
    public static function exception(\Exception $e): void
    {
        if ($e instanceof BlankException) {
            return;
        }
        static::error($e->getMessage() ?: 'something run wrong...');
    }

    public static function notFound(\Exception $e): void
    {
        static::error(['404 NotFound', $e->getMessage()]);
    }
}