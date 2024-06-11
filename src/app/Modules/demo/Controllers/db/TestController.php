<?php

namespace App\Modules\demo\Controllers\db;

use App\Modules\demo\Models\Cat;
use Phax\Db\Db;
use Phax\Mvc\Controller;

/**
 * @rbac ({title:'DbTest'})
 */
class TestController extends Controller
{
    /**
     * @rbac ({title:'HELLO'})
     */
    public function helloAction()
    {
        view(['name' => 'phax admin']);
    }

    /**
     * @rbac ({title:'RBAC事务'})
     */
    public function transAction()
    {
        Db::transaction(function () {
            $cat = Cat::findFirst(1);
            pr('cat 1', $cat->toArray(), false);
            $cat->age += 10;
            if ($cat->save() === false) {
                throw new \Exception($cat->getFirstError());
            }
            $cat2 = Cat::findFirst(2);
            pr('cat 2', $cat2->toArray(), false);
            $cat2->age += 5;
            if ($cat2->save() === false) {
                throw new \Exception($cat2->getFirstError());
            }

            throw new \Exception('异常取消事务');
        });
        dd('结果查询');
    }
}