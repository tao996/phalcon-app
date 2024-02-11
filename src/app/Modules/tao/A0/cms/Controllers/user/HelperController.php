<?php

namespace app\Modules\tao\A0\cms\Controllers\user;

use app\Modules\tao\BaseController;
use app\Modules\tao\Models\SystemUploadfile;
use Phax\Mvc\Request;
use Phax\Utils\Data;

class HelperController extends BaseController
{
    protected array|string $userActions = '*';
    public array $enableActions = ['select', 'edit'];

    /**
     * @rbac ({title:'图集图片选择'})
     */
    public function selectAction()
    {
        return [];
    }

    /**
     * @rbac ({title:'图集图片修改'})
     */
    public function editAction()
    {
        $id = Request::getQueryInt('id');
        $this->model = SystemUploadfile::mustFindFirst('id=' . $id . ' AND user_id=' . $this->loginUser->userId());
        if ($this->request->isPost()) {
            $data = Request::getData();
            Data::mustHasSet($data, ['summary']);
            $this->model->assign($data, ['summary']);
            return $this->model->save() ? $this->success('保存成功', $this->model->toArray()) : $this->error('保存失败');
        }
        return $this->model->toArray();
    }
}