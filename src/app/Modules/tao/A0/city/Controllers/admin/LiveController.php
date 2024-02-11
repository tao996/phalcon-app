<?php

namespace app\Modules\tao\A0\city\Controllers\admin;

use app\Modules\tao\A0\city\Models\CityLive;
use app\Modules\tao\common\BaseProjectController;
use Phax\Db\QueryBuilder;

/**
 * @property \app\Modules\tao\A0\city\Models\CityLive $model
 */
class LiveController extends BaseProjectController
{
    protected bool $console = true;
    protected array|string $superAdminActions = '*';

    public function initialize(): void
    {
        $this->model = new CityLive();
        parent::initialize();
    }

    protected bool $isSelect = false;

    protected function indexActionQueryBuilder(QueryBuilder $queryBuilder): void
    {
        if ($this->isSelect) {
            $queryBuilder->int('status', 1);
        }

        parent::indexActionQueryBuilder($queryBuilder);

        $queryBuilder
            ->int('platform', $this->request->getQuery('platform', 'int', 0))
            ->int('user_id', $this->request->getQuery('user_id', 'int', 0))
            ->like('name', $this->request->getQuery('keyword', 'string', ''));
    }

    /**
     * @rbac ({title:'直播选择'})
     */
    public function selectAction()
    {
        $this->disabledMainLayout();
        $this->isSelect = true;
        return $this->indexAction();
    }

}