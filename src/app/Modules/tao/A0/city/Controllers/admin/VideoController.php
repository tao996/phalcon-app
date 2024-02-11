<?php

namespace app\Modules\tao\A0\city\Controllers\admin;

use app\Modules\tao\A0\city\Models\CityVideo;
use app\Modules\tao\BaseController;
use app\Modules\tao\sdk\phaxui\Layui\LayuiData;
use Phax\Db\QueryBuilder;

/**
 * @property CityVideo $model
 */
class VideoController extends BaseController
{
    protected bool $console = true;
    protected array|string $superAdminActions = '*';
    protected bool $isSelect = false;

    public function initialize(): void
    {
        $this->model = new CityVideo();
        parent::initialize();
    }

    protected function indexActionQueryBuilder(QueryBuilder $queryBuilder): void
    {
        if ($tt = LayuiData::dateRange($this->request->getQuery('date'))) {
            $queryBuilder->range('date',
                $tt[0], $tt[1], \PDO::PARAM_INT);
        }
        $queryBuilder->int('platform', $this->request->getQuery('platform', 'int', 0));
        if ($this->isSelect) {
            $queryBuilder->int('status', 1);
        } else {
            $queryBuilder->int('status', $this->request->getQuery('status', 'int', 0));
        }
        $queryBuilder->like('title', $this->request->getQuery('keyword'));

    }

    /**
     * @rbac ({title:'选择导入回放'})
     */
    public function selectAction()
    {
        $this->isSelect = true;
        $this->disabledMainLayout();
        return $this->indexAction();
    }

    protected function beforeModelSaveAssign($data)
    {
        LayuiData::timestamp($data, ['date']);
        if (isset($data['db'])) {
            $this->model->status = 1;
        }
        return $data;
    }
}