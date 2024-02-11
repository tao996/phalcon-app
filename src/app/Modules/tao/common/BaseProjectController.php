<?php

namespace app\Modules\tao\common;


use app\Modules\tao\sdk\phaxui\HtmlAssets;
use app\Modules\tao\sdk\phaxui\Layui\LayuiHtml;

class BaseProjectController extends \app\Modules\tao\BaseController
{
    /**
     * @var bool 是否有控制台（后台管理控制器）
     */
    protected bool $console = false;
    /**
     * @var string 默认前端 Layout 模板（包含了导航/页头/页脚）
     */
    protected string $layoutView = 'base';

    /**
     * @var array|string 面包屑导航
     */
    protected array|string $breadcrumb = '';


    protected function beforeViewResponse(mixed &$data): bool
    {

        if ($this->breadcrumb) {
            LayuiHtml::breadcrumb($this->breadcrumb);
        }
        HtmlAssets::initWithCdn();
        // 控制臺不需要
        $this->addViewData('console',$this->console);

        if (!$this->console && $this->layoutView) {
            $this->view->setLayout($this->layoutView);
        }

        return parent::beforeViewResponse($data);
    }
}