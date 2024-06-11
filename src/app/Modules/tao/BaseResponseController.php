<?php

namespace App\Modules\tao;

use App\Modules\tao\sdk\phaxui\HtmlAssets;
use App\Modules\tao\sdk\phaxui\Layui\Layui;
use Phax\Db\QueryBuilder;
use Phax\Mvc\Controller;
use Phax\Mvc\Request;
use Phax\Mvc\Response;
use Phax\Support\Exception\BlankException;

/**
 * 定义各种响应格式
 * // * @method \Phalcon\Http\Response error($msg, array $data = [], int $code = 500)
 * // * @method \Phalcon\Http\Response success(string $msg, mixed $data = '')
 */
class BaseResponseController extends Controller
{
    /**
     * 是否为演示环境
     * @var bool
     */
    protected bool $isDemo = false;

    protected function prepareInitialize(): void
    {
        // 是否演示环境
        $this->isDemo = config('app.demo', false);
    }

    /**
     * 处理分页数据
     * @param QueryBuilder $queryBuilder
     * @return void
     */
    protected function pagination(QueryBuilder $queryBuilder): void
    {
        $page = $this->request->get('page', 'int', 1);
        $limit = $this->request->get('limit', 'int', 15);
        $queryBuilder->pagination($page - 1, $limit);
    }

    /**
     * 为了解决 layui table.reload 会保存上次搜索条件的问题 <br>
     * 当搜索 reset 时，会追加 reset=1 此时会忽略搜索条件
     * @return bool
     */
    protected function isResetSearch(): bool
    {
        return $this->request->getQuery('reset', 0) == 1;
    }

    /**
     * 当前默认主题
     */
    public static string $theme = 'layui';
    /**
     * 目录，通常用于查询视图模板
     * @var string
     */
    public static string $baseDIR = __DIR__;

    /**
     * 指向模板文件，通常用在 index.phtml 中，用来包含通用的模板 <br>
     * include_once BaseResponseController::getBaseViewDir('index.phtml')
     * @param string $tpl
     * @return string
     */
    public static function getBaseViewDir(string $tpl): string
    {
        if (static::$theme) {
            return self::$baseDIR . '/views/' . static::$theme . '/' . $tpl;
        } else {
            return self::$baseDIR . '/views/' . $tpl;
        }
    }

    protected function beforeJsonResponse(mixed &$data): void
    {

        if (!isset($data['code']) && !isset($data['msg']) && !isset($data['data'])) {
            $this->success('', $data);
            throw new BlankException();
        }
        parent::beforeJsonResponse($data);
    }

    protected function beforeViewResponse(mixed &$data): void
    {
        HtmlAssets::initWithCdn();
        $this->view->setVar('layui', Layui::getInstance());
        require_once __DIR__ . '/Common/function.php';
        require_once PATH_TAO . 'views/function.php';

        $viewDir = $this->view->getViewsDir() . self::$theme; // 主题
        $this->view->setViewsDir($viewDir);
        parent::beforeViewResponse($data);
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
        throw new BlankException();
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

    /**
     * 通常用在显示列表数据
     * @param int $count
     * @param array $rows
     * @return \Phalcon\Http\Response
     */
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

    /**
     * 处理 NotFound 异常
     * @param \Exception $e
     * @return void
     * @throws BlankException
     */
    public static function notFound(\Exception $e): void
    {
        static::error(['404 NotFound', $e->getMessage()]);
    }
}