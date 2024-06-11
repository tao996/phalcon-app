<?php

namespace App\Modules\tao\Controllers\admin;

use App\Modules\tao\BaseController;
use App\Modules\tao\Models\SystemRole;
use App\Modules\tao\Models\SystemRoleNode;
use Phax\Db\QueryBuilder;
use Phax\Mvc\Request;

/**
 * @rbac ({title:'角色管理'})
 * @property SystemRole $model
 */
class RoleController extends BaseController
{

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new SystemRole();
    }

    protected $indexQueryColumns = 'id,name,title,sort,status,remark,created_at';

    protected function indexActionQueryBuilder(QueryBuilder $queryBuilder): void
    {
        $queryBuilder->int('status', $this->request->getQuery('status', 'int', 0));
        $queryBuilder->like('name', $this->request->getQuery('name', 'string'));
    }

    /**
     * @rbac ({title:'编辑角色'})
     * @throws \Exception
     */
    public function editAction()
    {
        $id = Request::getQueryInt('id');
        $role = SystemRole::findFirst($id);
        $this->checkModelActionAccess($role);

        if ($this->request->isPost()) {
            $data = Request::getData();
            $role->assign($data, [
                'title', 'name', 'remark',
            ]);
            if ($role->save()) {
                return $this->success('保存成功');
            } else {
                return $this->error($role->getErrors());
            }
        }
        return $role->toArray();
    }

    /**
     * @rbac ({title:'添加角色'})
     */
    public function addAction()
    {
        if ($this->request->isPost()) {
            $data = Request::getData();
            $this->model->assign($data, ['title', 'name', 'remark']);
            if ($this->model->create()) {
                return $this->success('创建成功');
            } else {
                return $this->error($this->model->getErrors());
            }
        }
        return [];
    }

    /**
     * @rbac ({title:'角色授权'})
     */
    public function authorizeAction()
    {
        $id = Request::getQueryInt('id'); // 角色 ID
        $role = SystemRole::findFirst($id);
        $this->checkModelActionAccess($role);

        if (Request::isApiRequest()) {
            if ($this->request->isGet()) {
                $list = $role->getAuthorizeNodeListByRoleId();
                return $this->success('', $list);
            } elseif ($this->request->isPost()) {
                $nodes = Request::tryGetInts('node', false); // 授权节点
                // 移除原来的绑定
                SystemRoleNode::queryBuilder()->int('role_id', $id)->delete();
                if ($nodes) {
                    $rows = [];
                    foreach ($nodes as $nodeId) {
                        $rows[] = [$id, $nodeId];
                    }
                    SystemRoleNode::batchInsert($rows, ['role_id', 'node_id']);
                }

                return $this->success('保存授权成功');
            }
        }
        return $role->toArray();
    }


}