<?php

namespace app\Modules\tao\A0\cms\Controllers\admin;

use app\Modules\tao\A0\cms\Models\CmsAlbum;
use app\Modules\tao\A0\cms\Services\CmsContentService;
use app\Modules\tao\BaseController;
use app\Modules\tao\Services\UploadfileService;
use Phax\Db\Db;
use Phax\Mvc\Request;
use Phax\Utils\Data;

/**
 * @property CmsAlbum $model
 */
class AlbumController extends BaseController
{
    protected array|string $superAdminActions = '*';
    protected array $appendModifyFields = ['tag'];

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new CmsAlbum();
    }

    protected array $saveWhiteList = [
        'cover', 'title', 'tag', 'summary','image_ids'
    ];

    /**
     * @rbac ({title:'修改图集'})
     */
    public function editAction()
    {

        if ($this->request->isPost()){
            return parent::editAction();
        }

        $id = Request::getInt('id');
        $this->model = CmsAlbum::mustFindFirst($id);
        $row = $this->model->toArray();
        $row['images'] = UploadfileService::getImages($this->model->image_ids);
        return $row;
    }

    /**
     * @rbac ({title:'图集预览'})
     */
    public function previewAction()
    {
        return $this->editAction();
    }
}