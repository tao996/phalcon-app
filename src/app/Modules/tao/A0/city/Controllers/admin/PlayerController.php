<?php

namespace app\Modules\tao\A0\city\Controllers\admin;

use app\Http\Projects\you\BaseSubTermController;
use app\Modules\tao\A0\city\Models\CityPlayer;

class PlayerController extends BaseSubTermController
{
    protected bool $console = true;
    protected array|string $superAdminActions = '*';
    protected array $saveWhiteList = ['term_id', 'avatar', 'name', 'code', 'sex', 'tiktok'];

    public function initialize(): void
    {
        $this->model = new CityPlayer();
        parent::initialize();
    }
}