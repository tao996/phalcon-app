<?php

namespace app\Modules\tao\A0\cms\Controllers\admin;

use app\Modules\tao\A0\cms\Models\CmsLink;
use app\Modules\tao\BaseController;

/**
 * @property CmsLink $model
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