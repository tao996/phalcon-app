<?php

namespace app\Modules\tao\A0\city\Controllers\admin;

use app\Modules\tao\A0\city\Models\CityPlayer;
use app\Modules\tao\A0\city\Services\CityTermService;
use app\Modules\tao\common\BaseProjectController;

class PlayerController extends BaseProjectController
{
    protected bool $console = true;
    protected array|string $superAdminActions = '*';
    protected array $saveWhiteList = ['term_id', 'avatar', 'name', 'code', 'sex', 'tiktok'];

    public function initialize(): void
    {
        $this->model = new CityPlayer();
        parent::initialize();
    }

    protected function indexActionGetResult(int $count, \Phax\Db\QueryBuilder $queryBuilder): array
    {
        $rows = parent::indexActionGetResult($count, $queryBuilder);
        CityTermService::appendTerm($rows);
        return $rows;
    }
}