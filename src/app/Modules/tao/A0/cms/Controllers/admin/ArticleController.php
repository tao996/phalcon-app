<?php

namespace App\Modules\tao\A0\cms\Controllers\admin;

use App\Modules\tao\A0\cms\Models\CmsArticle;
use App\Modules\tao\A0\cms\Services\CmsCategoryService;
use App\Modules\tao\A0\cms\Services\CmsContentService;
use App\Modules\tao\BaseController;
use App\Modules\tao\Services\UploadfileService;
use Phax\Db\Db;
use Phax\Db\QueryBuilder;
use Phax\Mvc\Request;
use Phax\Support\Validate;
use Phax\Utils\MyData;

/**
 * @property CmsArticle $model
 * @rbac ({title:'文章管理'})
 */
class ArticleController extends BaseController
{
    protected array $cateOptions = [];
    protected array $appendModifyFields = ['top'];


    public function initialize(): void
    {
        parent::initialize();
        $this->model = new CmsArticle();
        $this->cateOptions = CmsCategoryService::options();
        if ($this->loginUser->isSuperAdmin()) {
            $this->appendModifyFields = array_merge($this->appendModifyFields, ['hits', 'hot', 'cstatus']);
        }
    }

    protected function indexActionGetResult(int $count, QueryBuilder $queryBuilder): array
    {
        $rows = parent::indexActionGetResult($count, $queryBuilder);
        $cate = array_column($this->cateOptions, 'otitle', 'id');
        foreach ($rows as $index => $row) {
            if ($row['cate_id'] > 0) {
                $rows[$index]['cate_title'] = MyData::getString($cate, $row['cate_id']);
            }
        }
        return $rows;
    }

    /**
     * @rbac ({title:'文章列表'})
     */
    public function indexAction()
    {
        if (!Request::isApiRequest()) {
            $this->view->setVar('options', $this->cateOptions);
        }
        return parent::indexAction();
    }

    /**
     * @rbac ({title:'添加文章'})
     */
    public function addAction()
    {
        if ($this->request->isPost()) {
            $data = Request::getData();

            Validate::check($data, [
                'cate_id|栏目' => 'required',
                'title|标题' => 'required', 'content|内容' => 'required'
            ]);
            $row = CmsCategoryService::getRecord($data['cate_id'], ['kind']); // 查询栏目类型

            $this->model->assign([
                'user_id' => $this->loginUser->userId(),
                'ip' => $this->request->getClientAddress(),
                'kind' => $row['kind'],
            ]);

            if (empty($data['author'])) {
                $data['author'] = '管理员';
            }

            $this->save($data);

            return $this->success('添加文章成功');
        }
        return [
            'options' => $this->cateOptions
        ];
    }

    /**
     * @rbac ({title:'编辑文章'})
     */
    public function editAction()
    {
        $id = Request::getInt('id');
        $this->model = CmsArticle::mustFindFirst($id);

        if ($this->request->isPost()) {
            $data = Request::getData();
            $this->save($data);
            return $this->success('保存文章成功');
        }

        $row = $this->model->toArray();
        $row['images'] = UploadfileService::getImages($this->model->image_ids);
        $row['content'] = CmsContentService::getContentById($this->model->content_id);

        return [
            'options' => $this->cateOptions,
            'row' => $row,
        ];
    }

    protected function save($data): bool
    {
        $keys = ['cate_id', 'cover', 'title', 'keywords', 'summary', 'author', 'hits', 'image_ids'];
        $this->model->assign($data, $keys);

        Db::transaction(function () use ($data) {
            if (isset($data['content']) || $this->model->content_id > 0) {
                $cc1 = CmsContentService::saveContentDataById($this->model->content_id, $data['content']);
                $this->model->content_id = $cc1->id;
            }

            if (!$this->model->save()) {
                throw new \Exception('添加文章失败:' . $this->model->getFirstError());
            }
        });
        return true;
    }

    /**
     * @rbac ({title:'文章审核'})
     */
    public function cstatusAction()
    {
        $data = Request::getData();
        Validate::check($data, [
            'id' => 'required|int',
            'cstatus' => 'in:' . join(',', array_keys(CmsArticle::mapCheckStatus())),
            'cmessage' => 'required'
        ]);
        if ($data['cstatus'] == CmsArticle::CheckStatusDeny) {
            if (empty($data['cmessage'])) {
                return $this->error('请填写不通过的原因');
            }
        }
        $this->model = CmsArticle::mustFindFirst($data['id']);
        $this->model->cstatus = $data['cstatus'];
        $this->model->cmessage = $data['cmessage'];
        $this->model->cuser_id = $this->loginUser->userId();
        return $this->model->save()
            ? $this->success('修改成功')
            : $this->error('修改失败:' . $this->model->getFirstError());
    }

    /**
     * @rbac ({title:'文章预览'})
     */
    public function previewAction()
    {
        $id = Request::getInt('id');
        $this->model = CmsArticle::mustFindFirst($id);
        $row = $this->model->toArray();
        $row['images'] = UploadfileService::getImages($this->model->image_ids);
        $row['content'] = CmsContentService::getContentById($this->model->content_id);
        return $row;
    }
}