<?php

namespace app\Modules\tao\A0\city\Controllers\admin;

use app\Http\Projects\you\BaseSubTermController;
use app\Modules\tao\A0\city\Models\CitySponsor;
use Phax\Mvc\Request;
use Phax\Utils\Data;

/**
 * @property \app\Modules\tao\A0\city\Models\CitySponsor $model
 */
class SponsorController extends BaseSubTermController
{
    protected bool $console = true;
    protected array|string $superAdminActions = '*';
    protected array $saveWhiteList = ['term_id', 'name', 'money', 'date', 'other'];

    public function initialize(): void
    {
        $this->model = new CitySponsor();
        parent::initialize();
    }

    protected function indexActionQueryBuilder(\Phax\Db\QueryBuilder $queryBuilder): void
    {
        parent::indexActionQueryBuilder($queryBuilder);
        if ($year = $this->request->getQuery('year')) {
            if ($year < 2020 || $year > 2024) {
                throw new \Exception('年份范围 2020 ~ 2024');
            }
            $queryBuilder->opt('date', '>=', $year . '0101', \PDO::PARAM_INT)->opt('date', '<=', $year . '1231',
                \PDO::PARAM_INT);
        }
    }

    public function addAction()
    {
        if ($this->request->isPost()) {
            $data = Request::getData();
            Data::mustHasSet($data, ['term_id', 'date']);
            if (!empty($data['batch'])) {
                $date = Data::intYmdDate($data['date']);
                $termId = (int)$data['term_id'];
                $items = Data::splitLine($data['batch']);
                $batch = [];
                foreach ($items as $item) {
                    $ii = Data::splitSpace($item);
                    if (count($ii) != 2) {
                        return $this->error('格式错误:' . $item);
                    }
                    if (empty($ii[0])) {
                        return $this->error('赞助人不能为空');
                    }
                    if ($ii[1] < 1) {
                        return $this->error('金额不能小于1:' . $ii[1]);
                    }
                    $batch[] = [$termId, $date, $ii[0], $ii[1]];
                }
                CitySponsor::batchInsert($batch, ['term_id', 'date', 'name', 'money']);
                return $this->success('添加成功');
            } else {
                return $this->save($data);
            }
        }
        return [];
    }

    protected function beforeModelSaveAssign($data)
    {
        $data['date'] = Data::intYmdDate($data['date']);
        return $data;
    }

}