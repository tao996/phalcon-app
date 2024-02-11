<?php

namespace app\Modules\tao\Controllers\admin;

use app\Modules\tao\BaseController;
use app\Modules\tao\Config\Config;
use app\Modules\tao\Models\SystemMenu;
use app\Modules\tao\sdk\phaxui\Layui\LayuiData;
use app\Modules\tao\Services\EventService;
use Phax\Db\QueryBuilder;
use Phax\Mvc\Request;
use Phax\Support\Validate;
use Phax\Utils\Data;

/**
 * @rbac ({title:'后台菜单管理'})
 * @property SystemMenu $model
 */
class MenuController extends BaseController
{
    protected string $pageTitle = '菜单';

    protected array $allowModifyFields = ['sort', 'status', 'tag', 'remark', 'href', 'href', 'params', 'remark'];

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new SystemMenu();
    }

    protected function indexActionGetResult(int $count, QueryBuilder $queryBuilder): array
    {
        $rows = $queryBuilder->order('pid asc, sort desc, id asc')
            ->disabledPagination()->find();

        foreach ($rows as $index => $item) {
            if ($item['multi'] == 1 && $item['href']) {
                $rows[$index]['href'] = url($item['href']);
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
            ->int('status', Config::STATUS_ACTIVE)
            ->notEqual('pid', Config::HOME_PID)
            ->columns('id,pid,title')
            ->find();
        $optionList = LayuiData::selectOptions(0,$list);
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
        $homeId = $this->model::queryBuilder()->int('pid', Config::HOME_PID)
            ->value('id');
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

        if ($msg = Validate::check($data, ['pid|上级菜单' => 'require',
            'title|菜单名称' => 'require',
        ])) {
            return $this->error($msg);
        }
        if ($remark = Data::getString($data, 'remark')) {
            if (SystemMenu::queryBuilder()
                ->string('remark', $remark)->notEqual('id', $model->id)
                ->exits()) {
                throw new \Exception('当前标识符已经被占用');
            }
        }
        $data['multi'] = Data::getInt($data, 'multi', 0);
        $model->assign($data, [
            'pid', 'title', 'href', 'icon', 'multi', 'sort', 'remark', 'tag',
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

}