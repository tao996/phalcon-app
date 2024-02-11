<?php

namespace app\Modules\tao\Controllers\user;

use app\Modules\tao\BaseController;
use app\Modules\tao\Helper\FileUpload;
use app\Modules\tao\Models\SystemUploadfile;
use Phax\Db\QueryBuilder;
use Phax\Mvc\Request;

class FileController extends BaseController
{
    protected array|string $userActions = '*';

    protected array $allowModifyFields = ['summary'];

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new SystemUploadfile();
    }

    /**
     * 文件列表
     * @throws \Exception
     */
    public function indexAction()
    {
        if (Request::isApiRequest()) {

            $model = new SystemUploadfile();
            $b = QueryBuilder::with($model)
                ->like('summary', $this->request->get('keyword','string',''))
                ->int('status', $this->request->get('status', 'int', 0));
            $count = $b->count();
            Request::pagination($b);
            $rows = $b->and('user_id=' . $this->loginUser->userId(), true)
                ->columns('id,url,summary,created_at,width,height')
                ->order('id desc')->find();
            return $this->successPagination($count, $rows);
        }
        $this->disabledMainLayout();
        return [];
    }

    /**
     * 添加图片
     */
    public function saveAction()
    {
        $fp = new FileUpload();
        $sf = $fp->fromRequest()->validate()->save();
        $sf->user_id = $this->loginUser->userId();
        if ($sf->save()) {
            return $this->success('上传成功', $sf->url);
        } else {
            return $this->error($sf->getErrors());
        }
    }

    /**
     * 通过编辑器上传图片
     * @throws \Exception
     */
    public function editorAction()
    {
        $fp = new FileUpload();
        $sf = $fp->fromRequest()->validate()->save();
        $sf->user_id = $this->loginUser->userId();
        if ($sf->save()) {
            return \json([
                'error' => ['message' => '上传成功', 'number' => 201],
                'filename' => '',
                'uploaded' => 1,
                'url' => $sf->url,
            ]);
        } else {
            return $this->error($sf->getErrors());
        }
    }
}