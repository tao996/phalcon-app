<?php

namespace app\Modules\tao\Controllers\admin;

use app\Modules\tao\BaseController;
use app\Modules\tao\Config\Config;
use app\Modules\tao\Models\SystemRole;
use app\Modules\tao\Models\SystemUser;
use app\Modules\tao\sdk\phaxui\Layui\LayuiData;
use app\Modules\tao\Services\LogService;
use Phax\Db\QueryBuilder;
use Phax\Mvc\Request;
use Phax\Utils\Data;

/**
 * @rbac ({title:'系统用户管理'})
 * @property SystemUser $model
 */
class UserController extends BaseController
{
    protected string $pageTitle = '用户管理';

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
            $user->newPassword(Data::getString($data, 'password'));
            $user->mustUniqueEmail(Data::getString($data, 'email'), true);
            $user->mustUniquePhone(Data::getString($data, 'phone'), true);
            if ($user->email) {
                $user->email_valid = Data::getBool($data, 'email_valid') ? Config::STATUS_ACTIVE : 0;
            }
            if ($user->phone) {
                $user->phone_valid = Data::getBool($data, 'phone_valid') ? Config::STATUS_ACTIVE : 0;
            }
            if (!$user->hasLoginAccount()) {
                return $this->error('必须设置一个登录账号');
            }

            $user->head_img = Data::getString($data, 'head_img');
            $user->signature = Data::getString($data, 'signature');
            $user->nickname = Data::getString($data, 'nickname');
            $user->role_ids = join(',', Data::getIntsWith($data, 'role_ids'));

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

    protected $indexQueryColumns = 'id,head_img,nickname,email,email_valid,phone,phone_valid,status,created_at';

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
                $user->phone_valid = Data::getBool($data, 'phone_valid') ? Config::STATUS_ACTIVE : 0;
            }

            if (!empty($data['email'])) {
                $user->mustUniqueEmail($data['email'], true);
                $user->email_valid = Data::getBool($data, 'email_valid') ? Config::STATUS_ACTIVE : 0;
            }

            $user->assign($data, ['nickname', 'signature', 'head_img']);

            // 非超级管理员才需要设置权限
            if (!$user->isSuperAdmin()) {
                if (!empty($this->request->get('role_ids'))) {
                    $user->role_ids = join(',', Data::getIntsWith($data, 'role_ids'));
                }
            } else {
                $user->role_ids = '';
            }

            if ($user->save()) {
                $this->session->set('user', $user->toArray());
                return $this->success('保存成功');
            } else {
                return $this->error($user->getErrors());
            }
        }

        $this->pageTitle = '编辑会员';

        $data = $user->toArray();
        $data['role_ids'] = explode(',', $data['role_ids']);
        $data['auth_list'] = SystemRole::getActiveList();
        return $data;
    }

    protected function modifyActionCheckPostData(array $data): void
    {
        if (in_array($data['id'], Config::SUPER_ADMIN_IDS) && $data['field'] == 'status') {
            throw new \Exception('不允许修改超级管理员状态');
        }
    }

    protected function deleteActionBefore($queryBuilder, array $ids)
    {
        if (array_intersect(Config::SUPER_ADMIN_IDS, $ids)) {
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
        if (in_array($id, Config::SUPER_ADMIN_IDS) && $id != $this->loginUser->userId()) {
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
        $this->pageTitle = '修改密码';
        return [
            'user' => $user->toArray(),
        ];
    }
}