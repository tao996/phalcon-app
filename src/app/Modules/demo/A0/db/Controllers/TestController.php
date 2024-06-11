<?php

namespace App\Modules\demo\A0\Db\Controllers;

use App\Modules\demo\Models\Cat;
use App\Modules\demo\Models\User;
use Phax\Db\QueryBuilder;
use Phax\Mvc\Controller;
use Phax\Support\Validate;

/**
 * @rbac ({title:'DbTest'})
 */
class TestController extends Controller
{
    public function indexAction()
    {
        /**
         * @var $user User
         */
        $user = User::findFirst(1);
        dd([
            'hasMany' => $user->articles()->toArray(),
            'hasOne' => $user->profile()->toArray(),
            'hasManyToMany' => $user->roles()->toArray(),
        ]);
    }

    /**
     * @rbac ({title:'时间戳和添加记录'})
     * @return void
     */
    public function insertAction()
    {
        $cat = new Cat();
        $result = $cat->assign([
            'name' => 'gray',
            'title' => '小灰',
            'age' => rand(1, 100)
        ])->save();
        pr($cat->toArray());
        if (false === $result) {
            dd($cat->getErrors());
        } else {
            dd($cat->id);
        }
    }


    /**
     * 修改器
     * @return void
     */
    public function getAction()
    {
        $cat = new Cat();
        $cat->title = '红K';
        dd($cat->save(), $cat->status_text, $cat->getErrors(), $cat->toArray());
    }

    /**
     * 软删除
     * @link http://localhost:8071/api/m/demo/index/remove
     * @return void
     * @throws \Exception
     */
    public function removeAction()
    {
        $cats = Cat::findOnlyTrashed();
        dd($cats->getFirst()->isDelete(), $cats->toArray());

        /**
         * @var $cat Cat
         */
        $cat = Cat::findFirst();
        pr($cat?->toArray()); // toArray(['name', 'title'])
        dd($cat->delete());
    }

    /**
     * 记录查询
     * @return void
     */
    public function listAction()
    {
        $cat = new Cat();
        $p = QueryBuilder::with($cat,false); // 全部包含软删除
        $p->excludeColumns(['created_at', 'updated_at']);
//        $p->conditions('age > :min: AND age < :min:',['min'=>10, 'max'=>100],['min'=>\PDO::PARAM_INT,'max'=>\PDO::PARAM_INT]);
//        $p->condition('age > :min:',10,\PDO::PARAM_INT);
//        $p->condition('age < :max:',100,\PDO::PARAM_INT);
//        $p->string('name', 'gray')->int('age', 84);
//        dd($p->parameter());
        dd($p->find(), $p->softDelete()->find());
    }

    /**
     * 表单验证
     */
    public function formAction()
    {
        if ($this->request->isPost()) {
            if (security()->checkToken()) {
                echo 'token 验证成功';
            } else {
                dd('token 失败');
            }

            Validate::check($this->request->getPost(), [
                'accept' => 'accepted',
                'email' => 'require|email',
            ], [
                'accept.accepted' => '必须接受条款'
            ]);
        }
        return [];
    }

}