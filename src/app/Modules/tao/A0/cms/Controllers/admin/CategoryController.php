<?php

namespace app\Modules\tao\A0\cms\Controllers\admin;

use app\Modules\tao\A0\cms\Models\CmsCategory;
use app\Modules\tao\A0\cms\Services\CmsCategoryService;
use app\Modules\tao\A0\cms\Services\CmsContentService;
use app\Modules\tao\BaseController;
use app\Modules\tao\sdk\phaxui\Layui\LayuiData;
use app\Modules\tao\Services\UploadfileService;
use Phax\Db\Db;
use Phax\Db\QueryBuilder;
use Phax\Mvc\Request;
use Phax\Support\Validate;
use Phax\Utils\Data;

/**
 * @property CmsCategory $model
 */
class CategoryController extends BaseController
{
    protected array $appendModifyFields = ['navbar'];

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

    public function addAction()
    {
        $pid = Request::getInt('pid', false);

        if ($this->request->isPost()) {
            $data = Request::getData();
            $this->save($data);
            return $this->success('创建栏目成功');
        }
        $this->pageTitle = '添加分类';
        return [
            'pid' => $pid,
            'categoryList' => $this->getPidCategoryList(),
        ];
    }

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
            'pid', 'title', 'tag', 'summary', 'cover',
            'tag', 'navbar', 'sort', 'status', 'other','image_ids'
        ]);

        Db::transaction(function () use ($data) {
            if ($this->model->kind == CmsCategory::KindList) {
                if (!empty($data['content']) || $this->model->content_id > 0) {
                    $cc1 = CmsContentService::saveContentDataById($this->model->content_id, Data::getString($data, 'content', ''));
                    $this->model->content_id = $cc1->id;
                }
            }
            if (!$this->model->save()) {
                throw new \Exception('保存栏目信息错误:' . $this->model->getErrors(true));
            }
        });
        return true;
    }
}