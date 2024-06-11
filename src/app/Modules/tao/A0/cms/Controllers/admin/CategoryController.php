<?php

namespace App\Modules\tao\A0\cms\Controllers\admin;

use App\Modules\tao\A0\cms\Models\CmsCategory;
use App\Modules\tao\A0\cms\Services\CmsCategoryService;
use App\Modules\tao\A0\cms\Services\CmsContentService;
use App\Modules\tao\BaseController;
use App\Modules\tao\sdk\phaxui\Layui\LayuiData;
use App\Modules\tao\Services\UploadfileService;
use Phax\Db\Db;
use Phax\Db\QueryBuilder;
use Phax\Mvc\Request;
use Phax\Support\Validate;
use Phax\Utils\MyData;

/**
 * @property CmsCategory $model
 * @rbac ({title:'栏目管理'})
 */
class CategoryController extends BaseController
{
    protected array $appendModifyFields = ['navbar', 'name', 'tag'];

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new CmsCategory();
    }

    protected function indexActionGetResult(int $count, QueryBuilder $queryBuilder): array
    {
        $rows = $queryBuilder->order('pid asc, sort desc, id asc')
            ->disabledPagination()->excludeColumns([
                'created_at', 'updated_at', 'deleted_at', ''
            ])
            ->find();
        return LayuiData::treeTable($rows);
    }

    public function getPidCategoryList()
    {
        return array_merge([[
            'id' => 0, 'pid' => 0, 'title' => '一级栏目'
        ]], CmsCategoryService::options());
    }

    /**
     * @rbac ({title:'添加栏目'})
     */
    public function addAction()
    {
        $pid = Request::getInt('pid', false);

        if ($this->request->isPost()) {
            $data = Request::getData();
            $this->save($data);
            return $this->success('创建栏目成功');
        }
        $this->htmlTitle = '添加分类';
        return [
            'pid' => $pid,
            'categoryList' => $this->getPidCategoryList(),
        ];
    }

    /**
     * @rbac ({title:'修改栏目'})
     */
    public function editAction()
    {
        $id = Request::getInt('id');
        $this->model = CmsCategory::mustFindFirst($id);

        if ($this->request->isPost()) {
            $data = Request::getData();
            $this->save($data);
            return $this->success('更新栏目成功');
        }
        $row = $this->model->toArray();
        $row['content'] = CmsContentService::getContentById($this->model->content_id);
        $row['images'] = UploadfileService::getImages($this->model->image_ids);

        return [
            'row' => $row,
            'categoryList' => $this->getPidCategoryList(),
        ];
    }

    protected function save($data): bool
    {
        Validate::check($data, [
            'title|栏目名称' => 'required',
            'kind|栏目类型' => 'required'
        ]);

        $this->model->kind = intval($data['kind']);
        if (!in_array($this->model->kind, array_keys(CmsCategory::mapKind()))) {
            throw new \Exception('暂不支持的栏目类型');
        }

        $this->model->assign($data, [
            'pid', 'title', 'name', 'tag', 'summary', 'cover',
            'tag', 'navbar', 'sort', 'status', 'other', 'image_ids'
        ]);
        if ($this->model->pid > 0) {
            if ($parentCategory = CmsCategory::findFirst($this->model->pid, function (\Phalcon\Mvc\Model\Query\Builder $builder) {
                $builder->columns('id,pids');
            })) {
                $pids = $parentCategory->pids ? explode(',', $parentCategory->pids) : [];
                $pids[] = $this->model->pid;
                $this->model->pids = join(',', $pids);
            }
        }

        Db::transaction(function () use ($data) {
            if ($this->model->kind == CmsCategory::KindList) {
                if (!empty($data['content']) || $this->model->content_id > 0) {
                    $cc1 = CmsContentService::saveContentDataById($this->model->content_id, MyData::getString($data, 'content', ''));
                    $this->model->content_id = $cc1->id;
                }
            }
            if (!$this->model->save()) {
                throw new \Exception('保存栏目信息错误:' . $this->model->getFirstError());
            }
        });
        return true;
    }
}