<?php

namespace App\Modules\tao\A0\open\Controllers\admin;

use App\Modules\tao\A0\open\Models\OpenOrder;
use App\Modules\tao\BaseController;

/**
 * @rbac ({title:'订单管理')
 */
class OrderController extends BaseController
{
    protected $indexQueryColumns = ['id','created_at','user_id','channel','trade_type','appid','mchid','amount','status','success_time'];

    public function initialize(): void
    {
        $this->model = new OpenOrder();
        parent::initialize();
    }
}