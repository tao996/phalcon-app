<?php

namespace app\Modules\tao\A0\city\Controllers\admin;

use app\Modules\tao\A0\city\Models\CityTerm;
use app\Modules\tao\common\BaseProjectController;
use Phax\Db\QueryBuilder;
use Phax\Mvc\Request;

class HelperController extends BaseProjectController
{
    protected bool $console = true;
    protected array|string $superAdminActions = '*';


    /**
     * @rbac ({title:'球队选择'})
     */
    public function termAction()
    {
        if (Request::isApiRequest()) {
            $this->model = new CityTerm();
            $name = $this->request->get('keyword', 'string', '');
            $b = QueryBuilder::with($this->model)
                ->like('name', $name)->int('status', 1);
            $count = $b->count();
            $rows = Request::pagination($b)->order('sort desc,id desc')->find();
            return $this->successPagination($count, $rows);
        }
        $this->disabledMainLayout();
        return [];
    }
}