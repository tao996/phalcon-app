<?php

namespace App\Modules\tao\A0\cms\Controllers;

use App\Modules\tao\A0\cms\Models\CmsPage;
use App\Modules\tao\A0\cms\Services\CmsContentService;
use App\Modules\tao\BaseController;
use Phax\Mvc\Request;
use Phax\Utils\MyData;

class OpenController extends BaseController
{
    protected array|string $openActions = '*';
    public bool $disableUpdateActions = true;

    /**
     * 单页信息显示
     * @return array
     */
    public function pageAction()
    {
        $this->disabledMainLayout();
        $data = Request::getData();
        MyData::mustHasSet($data, ['name']);

        $page = CmsPage::findFirst(['name' => $data['name'], 'status' => 1])?->toArray();
        if (empty($page)) {
            throw new \Exception('没有找到符合要求的页面');
        }
        $page['content'] = CmsContentService::getContentById($page['content_id']);
        return $page;
    }
}