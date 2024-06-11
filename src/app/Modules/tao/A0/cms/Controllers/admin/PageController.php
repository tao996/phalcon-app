<?php

namespace App\Modules\tao\A0\cms\Controllers\admin;

use App\Modules\tao\A0\cms\Models\CmsContent;
use App\Modules\tao\A0\cms\Models\CmsPage;
use App\Modules\tao\A0\cms\Services\CmsContentService;
use App\Modules\tao\BaseController;
use Phax\Db\Db;
use Phax\Db\QueryBuilder;
use Phax\Mvc\Request;
use Phax\Support\Config;
use Phax\Utils\MyData;

/**
 * @rbac ({title:'单页管理'})
 * @property CmsPage $model
 */
class PageController extends BaseController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->model = new CmsPage();
    }

    protected $indexQueryColumns = ['id', 'tag', 'name', 'title', 'sort', 'status'];
    protected array $allowModifyFields = ['sort', 'status'];

    protected function indexActionQueryBuilder(QueryBuilder $queryBuilder): void
    {
        $queryBuilder->int('status', $this->request->getQuery('status', 'int', 0));
        $queryBuilder->int('tag', $this->request->getQuery('tag'));
    }

    /**
     * @rbac ({title:'添加单页'})
     */
    public function addAction()
    {
        if ($this->request->isPost()) {
            $data = Request::getData();

            $keys = ['tag', 'sort', 'title', 'name', 'content'];
            MyData::mustHasSet($data, $keys, ['sort', 'tag']);

            $model = $this->model;
            $model->assign($data, $keys);

            if ($model->isRepeat()) {
                return $this->error('重复的 tag+name');
            }

            Db::transaction(function () use ($data, $model) {
                $cc = new CmsContent();
                $cc->content = $data['content'];
                if ($cc->create()) {
                    $model->content_id = $cc->id;
                    if ($model->create() === false) {
                        throw new \Exception('save page failed:' . $model->getFirstError());
                    }
                } else {
                    throw new \Exception('save content failed:' . $cc->getFirstError());
                }
            });

            return $this->success('添加成功');
        }
        return [];
    }

    /**
     * @rbac ({title:'编辑单页'})
     */
    public function editAction()
    {
        $id = Request::getQueryInt('id');
        if (!$page = CmsPage::findFirst($id)) {
            return $this->error('没有找到指定记录');
        }
        if ($this->request->isPost()) {
            $data = Request::getData();

            $keys = ['tag', 'sort', 'title', 'name', 'content'];
            MyData::mustHasSet($data, $keys, ['sort', 'tag']);
            $page->assign($data, ['tag', 'sort', 'title', 'name']);

            $cc = CmsContentService::getById($page->content_id) ?: new CmsContentService();
            $cc->content = $data['content'];

            Db::transaction(function () use ($page, $cc) {
                if (!$cc->save()) {
                    throw new \Exception('save content failed:' . $cc->getFirstError());
                }
                $page->content_id = $cc->id;
                if (!$page->save()) {
                    throw new \Exception('save page failed:' . $page->getFirstError());
                }
            });
            return $this->success('保存成功');
        }
        $this->htmlTitle = '编辑单页';
        $data = $page->toArray();
        $data['content'] = CmsContentService::getContentById($page->content_id);
        return $data;
    }
}