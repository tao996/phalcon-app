<?php

namespace App\Modules\tao;

use Phax\Db\QueryBuilder;
use Phax\Foundation\Router;
use Phax\Mvc\Model;
use Phax\Mvc\Request;
use Phax\Support\Validate;

use App\Modules\tao\Config\Config;
use App\Modules\tao\Services\LogService;
use Phax\Utils\MyData;

/**
 * 业务逻辑控制器，用于简便处理 index/add/edit/delete/modify 等操作
 */
class BaseController extends BaseRbacController
{
    /**
     * 允许修改的模型属性名称，需要傳入參數為 [id,field,value]
     * @var array|string[]
     */
    protected array $allowModifyFields = ['status', 'sort', 'remark'];
    protected array $appendModifyFields = [];
    /**
     * 字段保存白名单
     * @var array
     */
    protected array $saveWhiteList = [];
    /**
     * 当前控制器所使用的模型（用于增删改查）
     * @var Model|null
     */
    protected Model|null $model = null;


    /**
     * @throws \Exception
     */
    public function initialize(): void
    {
        parent::initialize();
        parent::rbacInitialize();
        $this->prepareInitialize();
    }

    /**
     * 复制另一个控制器的配置信息，通常用在聚合 api/view 之中 <br>
     * 复制 isDemo, pageTokenName, token, loginUser 属性
     * @param BaseController $controller
     * @return  static
     */
    public static function copyWith(BaseController $controller): static
    {
        $cc = new static();
        $cc->isDemo = $controller->isDemo;
        $cc->loginUser = $controller->loginUser;
        return $cc;
    }

    /**
     * 列表搜索字段
     * @var string|array
     */
    protected $indexQueryColumns = '';
    /**
     * 列表搜索隐藏的字段
     * @var string|array
     */
    protected $indexHiddenColumns = '';
    /**
     * 列表搜索条件
     * @var string
     */
    protected string $indexOrder = 'id desc';

    /**
     * @var string 設置 HTML 頁面標題
     */
    protected string $htmlTitle = '';


    /**
     * 处理查询语句，通常用来补充查询条件
     * @param QueryBuilder $queryBuilder
     * @return void
     */
    protected function indexActionQueryBuilder(QueryBuilder $queryBuilder): void
    {
        if ($this->isResetSearch()) {
            return;
        }
        if (!$this->request->hasQuery('status') && property_exists($this->model, 'status')) {
            $queryBuilder->int('status', $this->request->getQuery('status', 'int', 0));
        }
    }

    /**
     * 处理搜索的结果，已经在 indexAction 中自动对 $count>0 作出判断
     * @param int $count 记录总数
     * @param \Phax\Db\QueryBuilder $queryBuilder
     * @return array
     */
    protected function indexActionGetResult(int $count, \Phax\Db\QueryBuilder $queryBuilder): array
    {
        if ($this->indexQueryColumns) {
            $queryBuilder->columns($this->indexQueryColumns);
        } elseif ($this->indexHiddenColumns) {
            $columns = metadata()->getAttributes($this->model);
            $queryBuilder->columns(array_diff($columns,
                is_array($this->indexHiddenColumns)
                    ? $this->indexHiddenColumns
                    : explode(',', $this->indexHiddenColumns)));
        }
        if ($this->indexOrder) {
            $queryBuilder->order($this->indexOrder);
        }
        return $queryBuilder->find();
    }

    /**
     * @rbac ({title:"数据列表"})
     */
    public function indexAction()
    {
        if (Request::isApiRequest()) {
            $this->autoResponse = false;
            $queryBuilder = QueryBuilder::with($this->model);


            if ($this->isUserAction()) {
                if (property_exists($this->model, 'user_id')) {
                    $queryBuilder->int('user_id', $this->loginUser->userId());
                }
            }


            $this->indexActionQueryBuilder($queryBuilder);
            $count = $queryBuilder->count();
            $rows = [];
            if ($count > 0) {
                $this->pagination($queryBuilder);
                $rows = $this->indexActionGetResult($count, $queryBuilder);
            }
            return $this->successPagination($count, $rows);
        }
        $this->updateHtmlTitle('列表', false);
        return [];
    }


    private function updateHtmlTitle(string $action, bool $prefix = true): void
    {
        if ($this->isViewRequest()) {
            if ($this->htmlTitle) {
                $this->addViewData('title', $this->htmlTitle);
            }
        }
    }

    protected function beforeViewResponse(mixed &$data): void
    {
        if ($this->htmlTitle && !$this->view->getVar('title')) {
            $this->addViewData('title', $this->htmlTitle);
        }
        parent::beforeViewResponse($data);
    }

    /**
     * @rbac ({title:'添加记录'})
     */
    public function addAction()
    {
        if ($this->request->isPost()) {
            $data = Request::getData();

            if (property_exists($this->model, 'user_id')) {
                $this->model->user_id = $this->loginUser->userId();
            }

            $success = $this->save($data);
            if ($success) {
                $this->afterModelChange('add');
            }
            return $this->saveModelResponse($success, true);
        }
        $this->updateHtmlTitle('添加');
        return [];
    }

    /**
     * @rbac ({title:'编辑记录'})
     */
    public function editAction()
    {
        $id = Request::getQueryInt('id');
        $this->model = $this->model::mustFindFirst($id);
        if ($this->request->isPost()) {
            $data = Request::getData();
            $success = $this->save($data);
            if ($success) {
                $this->afterModelChange('edit');
            }
            return $this->saveModelResponse($success, false);
        }
        $this->updateHtmlTitle('编辑');
        return $this->model->toArray();
    }

    protected function beforeModelSaveAssign($data)
    {
        return $data;
    }

    protected function save($data): bool
    {
        $data = $this->beforeModelSaveAssign($data);
        if ($this->saveWhiteList) {
            $this->model->assign($data, $this->saveWhiteList);
        } else {
            $this->model->assign($data);
        }
        return $this->model->save();
    }

    /**
     * 检查修改参数
     * @param array $data
     * @return void
     */
    protected function modifyActionCheckPostData(array $data)
    {

    }

    /**
     * 注意：并未判断 user_id
     * @rbac ({title:"属性快捷修改"})
     * @throws \Exception
     */
    public function modifyAction()
    {
        Request::mustPost();
        $post = $this->request->getPost();
        MyData::mustHasSet($post, ['id', 'field', 'value'], ['value']);
        $rules = [
            'id|ID' => 'int',
            'field|字段' => 'require',
        ];
        Validate::check($post, $rules);
        $rows = array_merge($this->allowModifyFields, $this->appendModifyFields);

        if (!in_array($post['field'], $rows)) {
            return $this->error('该字段不允许修改');
        }
        if (empty($this->model)) {
            return $this->error('控制器 this.model 不能为空');
        }
        if (!property_exists($this->model, $post['field'])) {
            return $this->error('当前模型不存在此属性');
        }
        $this->modifyActionCheckPostData($post);

        /**
         * @var $model BaseModel
         */
        $model = ($this->model)::mustFindFirst($post['id']);
        $this->checkModelActionAccess($model);

        $model->assign([
            $post['field'] => $post['value']
        ]);
        if ($model->save()) {
            LogService::insert($model->tableTitle(), 'modify');
            $this->afterModelChange('modify');
            return $this->success('保存成功');
        } else {
            return $this->error($model->getErrors());
        }
    }

    /**
     * 删除指定记录前执行，通常用于检查是否能够删除
     * @param QueryBuilder $queryBuilder
     * @param array $ids
     * @return void
     */
    protected function deleteActionBefore(\Phax\Db\QueryBuilder $queryBuilder, array $ids)
    {
    }

    /**
     * 删除成功之后执行
     * @param array $ids
     * @return void
     */
    protected function deleteActionAfterSuccess(array $ids)
    {

    }

    /**
     * @rbac ({title:"删除记录"})
     * @throws \Exception
     */
    public function deleteAction()
    {
        Request::mustPost();
        $ids = Request::tryGetInts('id');

        if (empty($ids)) {
            return $this->error('待删除记录 ID 不能为空');
        }
        $qb = QueryBuilder::with($this->model)->inInt('id', $ids);

        if ($this->isUserAction()) {
            if (property_exists($this->model, 'user_id')) {
                $qb->int('user_id', $this->loginUser->userId());
            }
        }

        $this->deleteActionBefore($qb, $ids);
        if ($qb->delete()) {
            $this->deleteActionAfterSuccess($ids);
            LogService::insert($this->model->tableTitle(), 'delete');
            $this->afterModelChange('delete');
            return $this->success('删除成功');
        } else {
            return $this->error('删除失败');
        }
    }

    /**
     * 检查用户修改记录的权限
     * @param Model|null $model
     */
    protected function checkModelActionAccess(Model|null $model): void
    {
        if (empty($model)) {
            throw new \Exception('记录不存在');
        }
        if (property_exists($model, 'user_id')) {
            if ($this->isUserAction()) {
                $model->user_id = $this->loginUser->userId();
            } elseif ($this->isSuperAdminAction()) {
                if ($model->user_id != $this->loginUser->userId()) { // 修改别人的记录
                    $superAdminIds = Config::superAdminIds();
                    if (in_array($model->user_id, $superAdminIds)) {
                        if (in_array($this->loginUser->userId(), $superAdminIds)) {
                            $recordUserIndex = array_search($model->user_id, $superAdminIds);
                            $userIndex = array_search($this->loginUser->userId(), $superAdminIds);
                            if ($userIndex > $recordUserIndex) {
                                throw new \Exception('不能修改更高级别的超级管理员');
                            }
                            return;
                        }
                    }
                    throw new \Exception('没有修改超级管理员记录的权限');
                }
            } else {
                // 检查是否有修改节点的权限
                if (!$this->loginUser->getAuth()->access(Router::getNode())) {
                    throw new \Exception('没有修改记录的权限');
                }
            }
        }

    }

    protected function saveModelResponse(bool $success, bool $isCreate = true)
    {
        $text = $isCreate ? '创建' : '保存';
        return $success
            ? $this->success($text . '成功', $this->model?->toArray())
            : $this->error($text . '失败:' . $this->model?->getFirstError());
    }

    /**
     * 在模型修改成功之后调用
     * @param string $action add|edit|delete|modify
     * @return void
     */
    protected function afterModelChange(string $action)
    {

    }
}