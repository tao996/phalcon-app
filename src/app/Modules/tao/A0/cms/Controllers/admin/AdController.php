<?php

namespace app\Modules\tao\A0\cms\Controllers\admin;

use app\Modules\tao\A0\cms\Models\CmsAd;
use app\Modules\tao\BaseController;
use app\Modules\tao\sdk\phaxui\Layui\LayuiData;
use Phax\Db\QueryBuilder;
use Phax\Mvc\Request;
use Phax\Support\Validate;
use Phax\Utils\Data;

/**
 * @property CmsAd $model
 */
class AdController extends BaseController
{
    protected array|string $superAdminActions = '*';
    protected array $appendModifyFields = ['at_banner', 'at_index', 'at_list', 'at_page', 'tag', 'live', 'ad'];

    public function initialize(): void
    {
        $this->model = new CmsAd();
        parent::initialize();
    }

    protected function indexActionQueryBuilder(QueryBuilder $queryBuilder): void
    {
        if ($this->isResetSearch()) {
            return;
        }
        $status = $this->request->getQuery('status', 'int', 0);
        if ($beginAt = $this->request->getQuery('begin_at')) {
            $queryBuilder->opt('begin_at', '>=', strtotime($beginAt), \PDO::PARAM_INT);
        }
        if (Data::isBool($this->request->getQuery('active'))) {
            $status = 1;
            $now = time();
            $queryBuilder->and("(begin_at=0 AND end_at=0) OR (begin_at=0 AND end_at >= {$now}) OR (begin_at <= {$now} AND end_at=0) OR (begin_at <= {$now} AND end_at >= {$now})", true);
        }
        $queryBuilder->string('tag', $this->request->getQuery('tag'));
        $queryBuilder->int('status', $status);
    }

    public function addAction()
    {
        if ($this->request->isPost()) {
            return parent::addAction();
        }
        return [];
    }

    protected array $saveWhiteList = [
        'begin_at', 'end_at', 'cover', 'title', 'link',
        'at_index', 'at_list', 'at_page', 'at_banner',
        'tag', 'sort', 'remark', 'live', 'ad'
    ];

    protected function beforeModelSaveAssign($data)
    {
        LayuiData::boolData($data, ['at_index', 'at_list', 'at_page', 'at_banner', 'live', 'ad']);
        LayuiData::timestamp($data, ['begin_at', 'end_at']);
        return $data;
    }


    public function editAction()
    {
        if ($this->request->isPost()) {
            return parent::editAction();
        }

        $id = Request::getInt('id');
        $this->model = CmsAd::mustFindFirst($id);
        $row = $this->model->toArray();
        $row['begin_at'] = $row['begin_at'] ? date('Y-m-d H:i:s', $row['begin_at']) : '';
        $row['end_at'] = $row['end_at'] ? date('Y-m-d H:i:s', $row['end_at']) : '';

        return $row;
    }
}