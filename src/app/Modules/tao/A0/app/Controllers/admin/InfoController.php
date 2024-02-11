<?php

namespace app\Modules\tao\A0\app\Controllers\admin;


use app\Modules\tao\A0\app\Models\AppInfo;

/**
 * @property AppInfo $model
 */
class InfoController extends \app\Modules\tao\BaseController
{
    protected array|string $superAdminActions = '*';
    protected array $appendModifyFields = ['title'];

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new AppInfo();
    }

    protected array $saveWhiteList = [
        'tag', 'title', 'remark'
    ];

}