<?php

namespace App\Modules\tao\Controllers\admin;

use App\Modules\tao\BaseController;
use App\Modules\tao\Config\Data;
use App\Modules\tao\Models\SystemMenu;
use App\Modules\tao\Models\SystemNode;
use App\Modules\tao\Models\SystemUser;
use App\Modules\tao\sdk\phaxui\Layui\LayuiData;
use App\Modules\tao\Services\EventService;
use App\Modules\tao\Services\MenuService;
use App\Modules\tao\Services\UserMenuService;
use Phax\Db\QueryBuilder;
use Phax\Foundation\Router;
use Phax\Mvc\Request;
use Phax\Support\Validate;
use Phax\Utils\MyData;

/**
 * @rbac ({title:'菜单管理'})
 * @property SystemMenu $model
 */
class MenuController extends BaseController
{
    protected string $htmlTitle = '菜单';

    protected array $allowModifyFields = ['sort', 'status', 'roles', 'remark', 'href', 'params', 'remark'];
    protected $indexQueryColumns = ['id', 'pid', 'title', 'icon', 'href', 'type', 'sort', 'status', 'roles', 'params'];

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new SystemMenu();
    }

    protected function indexActionGetResult(int $count, QueryBuilder $queryBuilder): array
    {
        $rows = $queryBuilder->notEqual('pid', Data::HOME_PID)
            ->columns($this->indexQueryColumns)
            ->order('pid asc, sort desc, id asc')
            ->disabledPagination()->find();

        foreach ($rows as $index => $item) {
            if ($item['href']) {
                $rows[$index]['href'] = MenuService::href($item['href'], $item['type'], $item['params']);
            }
        }
        return LayuiData::treeTable($rows);
    }

    /**
     * @return array|\int[][]|\string[][]
     * @throws \Exception
     */
    private function options()
    {
        $list = SystemMenu::queryBuilder()
            ->int('status', 1)
            ->notEqual('pid', Data::HOME_PID)
            ->columns('id,pid,title')
            ->find();
        $optionList = LayuiData::selectOptions(0, $list);
        return array_merge([[
            'id' => 0, 'pid' => 0, 'title' => '顶级菜单'
        ]], $optionList);
    }

    /**
     * @rbac ({title:'添加菜单'})
     * @throws \Exception
     */
    public function addAction()
    {
        $pid = Request::getInt('pid', false);
        $homeId = MenuService::homeId();
        if ($pid == $homeId) {
            return $this->error('首页不能添加子菜单');
        }
        if ($this->request->isPost()) {
            $data = Request::getData();
            $data['id'] = 0;
            return $this->saveData($this->model, $data);
        }
        return [
            'pid' => $pid,
            'menuList' => $this->options(),
        ];
    }

    private function saveData(SystemMenu $model, array $data)
    {

        Validate::check($data, [
            'pid|上级菜单' => 'require',
            'title|菜单名称' => 'require',
        ]);

        $data['type'] = MyData::getInt($data, 'type', 0);
        if (!empty($data['href'])) {
            if (SystemNode::KIND_MODULE == $data['type']) {
                if (Router::isMultipleModules($data['href'])) {
                    throw new \Exception('Module 链接地址不能以 /m/ 开头');
                }
            } else if (SystemNode::KIND_PROJECT == $data['type']) {
                if (Router::isAppProject($data['href'])) {
                    throw new \Exception('Project 链接地址不能以 /p/ 开头');
                }
            }
        } else {
            $data['kind'] = 0;
        }
        $model->assign($data, [
            'pid', 'title', 'href', 'icon', 'type', 'sort', 'remark', 'roles',
        ]);
        if ($model->save()) {
            EventService::updateMenu();
            return $this->success('保存成功');
        } else {
            return $this->error($model->getErrors());
        }
    }

    /**
     * @rbac ({title:'编辑菜单'})
     * @throws \Exception
     */
    public function editAction()
    {
        $id = Request::getQueryInt('id');
        $row = ($this->model)::mustFindFirst($id);
        if ($this->request->isPost()) {
            $data = Request::getData();
            return $this->saveData($row, $data);
        }

        return [
            'id' => $id,
            'menuList' => $this->options(),
            'row' => $row->toArray(),
        ];
    }

    public function userAction($userId)
    {
        if ($userId < 1) {
            throw new \Exception('user id 不能为空');
        }
        $user = SystemUser::findFirst($userId);
        $ms = new UserMenuService($user);
        return $ms->getMenuTree();
    }

    protected function deleteActionBefore(QueryBuilder $queryBuilder, array $ids)
    {
        $homeId = MenuService::homeId();
        if (in_array($homeId, $ids)) {
            throw new \Exception('不允许删除后台首页');
        }
    }

}