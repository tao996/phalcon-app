<?php

namespace app\Modules\tao\A0\cms\Controllers;

use app\Modules\tao\A0\cms\Models\CmsPage;
use app\Modules\tao\A0\cms\Services\CmsContentService;
use app\Modules\tao\BaseController;
use Phax\Mvc\Request;
use Phax\Utils\Data;

class OpenController extends BaseController
{
    protected array|string $openActions = '*';

    /**
     * 单页信息显示
     * @return array
     */
    public function pageAction()
    {
        $data = Request::getData();
        Data::mustHasSet($data, ['name']);
        $page = CmsPage::findFirst(['name' => $data['name']])?->toArray();
        if (empty($page)) {
            throw new \Exception('没有找到符合要求的页面');
        }
        $page['content'] = CmsContentService::getContentById($page['content_id']);
        return $page;
    }
}