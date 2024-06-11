<?php

namespace App\Modules\tao\Controllers\admin;

use App\Modules\tao\BaseController;
use App\Modules\tao\Config\Config;
use App\Modules\tao\Models\SystemRole;
use App\Modules\tao\Models\SystemUser;
use App\Modules\tao\sdk\phaxui\Layui\LayuiData;
use App\Modules\tao\Services\LogService;
use Phax\Db\QueryBuilder;
use Phax\Mvc\Request;
use Phax\Utils\MyData;

/**
 * @rbac ({title:'用户管理'})
 * @property SystemUser $model
 */
class UserController extends BaseController
{
    protected string $htmlTitle = '用户管理';

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new SystemUser();
    }

    /**
     * @rbac ({title:"添加用户"})
     */
    public function addAction()
    {
        if ($this->request->isPost()) {
            $data = Request::getData();
            $user = new SystemUser();
            $user->newPassword(MyData::getString($data, 'password'));
            $user->mustUniqueEmail(MyData::getString($data, 'email'), true);
            $user->mustUniquePhone(MyData::getString($data, 'phone'), true);
            if ($user->email) {
                $user->email_valid = (int)MyData::getBool($data, 'email_valid');
            }
            if ($user->phone) {
                $user->phone_valid = (int)MyData::getBool($data, 'phone_valid');
            }
            if (!$user->hasLoginAccount()) {
                return $this->error('必须设置一个登录账号');
            }

            $user->head_img = MyData::getString($data, 'head_img');
            $user->signature = MyData::getString($data, 'signature');
            $user->nickname = MyData::getString($data, 'nickname');
            $user->role_ids = join(',', MyData::getIntsWith($data, 'role_ids'));

            if ($user->create()) {
                return $this->success('添加用户成功');
            } else {
                return $this->error($user->getErrors());
            }
        }

        return [
            'auth_list' => SystemRole::getActiveList()
        ];
    }

    protected $indexQueryColumns = 'id,role_ids,head_img,nickname,email,email_valid,phone,phone_valid,binds,status,created_at';

    protected function indexActionQueryBuilder(QueryBuilder $queryBuilder): void
    {
        $queryBuilder->int('id', $this->request->getQuery('id', 'int'))
            ->int('status', $this->request->getQuery('status', 'int'))
            ->like('phone', $this->request->getQuery('phone', 'string'))
            ->like('email', $this->request->getQuery('email', 'string'));

        if ($tt = LayuiData::dateRange($this->request->getQuery('created_at'))) {
            $queryBuilder->range('created_at',
                $tt[0], $tt[1], \PDO::PARAM_INT);
        }

    }

    protected function indexActionGetResult(int $count, QueryBuilder $queryBuilder): array
    {
        $rows = parent::indexActionGetResult($count, $queryBuilder);
        $roleIds = [];
        foreach ($rows as $index => $row) {
            $row['binds'] = json_decode($row['binds'], true);
            $row['role_ids'] = $row['role_ids'] ? explode(',', $row['role_ids']) : [];
            if (!empty($row['role_ids'])) {
                $roleIds = array_merge($roleIds, $row['role_ids']);
            }
            $rows[$index] = $row;
        }
        if ($roleIds) {
            $roleIds = array_unique(MyData::getInts($roleIds));
            $roles = SystemRole::queryBuilder()
                ->inInt('id', $roleIds)->int('status', 1)
                ->findColumn('id,name,title', 'id');
            foreach ($rows as $index => $row){
                $row['roles'] = [];
                if (!empty($row['role_ids'])){
                    foreach ($row['role_ids'] as $role_id){
                        if (isset($roles[$role_id])){
                            $row['roles'][] = $roles[$role_id];
                        }
                    }
                }
                unset($row['role_ids']);
                $rows[$index] = $row;
            }
        }
        return $rows;
    }

    /**
     * @rbac ({title:"编辑用户"})
     */
    public function editAction()
    {
        $id = Request::getQueryInt('id');
        /**
         * @var $user SystemUser
         */
        $user = SystemUser::findFirst($id);
        $this->checkModelActionAccess($user);

        if ($this->request->isPost()) {

            $data = $this->request->getPost();

            if (!empty($data['phone'])) {
                $user->mustUniquePhone($data['phone'], true);
                $user->phone_valid = (int)MyData::getBool($data, 'phone_valid');
            }

            if (!empty($data['email'])) {
                $user->mustUniqueEmail($data['email'], true);
                $user->email_valid = (int)MyData::getBool($data, 'email_valid');
            }

            $user->assign($data, ['nickname', 'signature', 'head_img']);

            // 非超级管理员才需要设置权限
            if (!$user->isSuperAdmin()) {
                if (!empty($this->request->get('role_ids'))) {
                    $user->role_ids = join(',', MyData::getIntsWith($data, 'role_ids'));
                }
            } else {
                $user->role_ids = '';
            }

            if ($user->save()) {
//                WebSessionService::save('user', $user->toArray());
                return $this->success('保存成功');
            } else {
                return $this->error($user->getErrors());
            }
        }

        $this->htmlTitle = '编辑会员';

        $data = $user->toArray();
        $data['role_ids'] = explode(',', $data['role_ids']);
        $data['auth_list'] = SystemRole::getActiveList();
        return $data;
    }

    protected function modifyActionCheckPostData(array $data): void
    {
        if (in_array($data['id'], Config::superAdminIds()) && $data['field'] == 'status') {
            throw new \Exception('不允许修改超级管理员状态');
        }
    }

    protected function deleteActionBefore($queryBuilder, array $ids)
    {
        if (array_intersect(Config::superAdminIds(), $ids)) {
            throw new \Exception('不允许删除超级管理员');
        }
    }

    /**
     * @rbac ({title:'修改用户密码'})
     * @throws \Exception
     */
    public function passwordAction()
    {
        $id = Request::getInt('id');
        if (in_array($id, Config::superAdminIds()) && $id != $this->loginUser->userId()) {
            throw new \Exception('没有修改超级管理员密码的权限');
        }
        $data = $this->request->get();
        // 新密码与确认密码必须一致
        if ($this->request->isPost()) {
            Request::mustHasSet($data, ['old_password', 'password']);
        }
        $user = SystemUser::findFirst($id);
        $this->checkModelActionAccess($user);

        if ($this->request->isPost()) {
            // 不是超级管理员，则必须提供正确的旧密码
            if (!$this->loginUser->isSuperAdmin()) {
                if (!empty($user->password) && empty($data['old_password'])) {
                    throw new \Exception('必须提供旧密码');
                }
                if (!empty($user->password)) {
                    if (security()->checkHash($data['old_password'], $user->password)) {
                        throw new \Exception('旧密码错误');
                    }
                }
            }
            $user->newPassword($data['password']);
            if ($user->save()) {
                LogService::insert($user->tableTitle(), '修改密码');
                return self::success('修改密码成功');
            } else {
                return self::error($user->getErrors());
            }
        }
        $this->htmlTitle = '修改密码';
        return [
            'user' => $user->toArray(),
        ];
    }
}