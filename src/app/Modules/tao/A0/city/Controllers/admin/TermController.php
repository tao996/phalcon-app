<?php

namespace app\Modules\tao\A0\city\Controllers\admin;

use app\Modules\tao\A0\city\Models\CityTerm;
use app\Modules\tao\common\BaseProjectController;

class TermController extends BaseProjectController
{
    protected bool $console = true;
    protected array|string $superAdminActions = '*';

    protected array $saveWhiteList = ['avatar', 'name', 'nickname', 'address', 'leader'];

    public function initialize(): void
    {
        $this->model = new CityTerm();
        parent::initialize();
    }
}