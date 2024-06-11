<?php

namespace App\Modules\tao\Controllers\user;

use App\Modules\tao\BaseController;
use App\Modules\tao\Models\SystemQuick;
use Phax\Mvc\Model;
use Phax\Mvc\Request;
use Phax\Support\Validate;

/**
 * @property SystemQuick $model
 */
class QuickController extends BaseController
{
    protected array|string $userActions = '*';

    protected array $sort = [
        'sort' => 'desc',
        'id' => 'desc',
    ];

    public function initialize(): void
    {
        $this->model = new SystemQuick();
        parent::initialize();
    }

    protected array $allowModifyFields = [
        'sort', 'title', 'status', 'href', 'remark',
    ];

    /**
     * @throws \Exception
     */
    public function addAction()
    {
        if ($this->request->isPost()) {
            $post = $this->request->get();
            $post['user_id'] = $this->loginUser->userId();
            $this->model->assign($post, [
                'user_id', 'href',
                'title', 'icon', 'sort', 'remark'
            ]);
            Validate::check($post, [
                'href|链接地址' => 'require',
                'title|快捷名称' => 'require',
            ]);

            if ($this->model->create()) {
                return $this->success('保存成功');
            } else {
                return $this->error($this->model->getErrors());
            }
        }
        return [];
    }

    /**
     * @throws \Exception
     */
    public function editAction()
    {
        $id = Request::getQueryInt('id');
        /**
         * @var $model Model
         */
        $model = $this->model::findFirst(['id' => $id]);
        $this->checkModelActionAccess($model);

        if ($this->request->isPost()) {
            $model->assign($this->request->get(), [
                'href', 'title', 'icon', 'sort', 'remark'
            ]);
            return $model->save() ? $this->success('保存成功') : $this->error($model->getErrors());
        }
        $_POST = $model->toArray();
        return [];
    }
}