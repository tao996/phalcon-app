<?php

namespace App\Modules\tao\A0\cms\Controllers\admin;

use App\Modules\tao\A0\cms\Models\CmsLink;
use App\Modules\tao\BaseController;

/**
 * @property CmsLink $model
 * @rbac ({title:'链接管理'})
 */
class LinkController extends BaseController
{
    protected array $appendModifyFields = ['tag'];

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new CmsLink();
    }
}