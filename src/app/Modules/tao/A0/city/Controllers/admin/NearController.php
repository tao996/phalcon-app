<?php

namespace app\Modules\tao\A0\city\Controllers\admin;

use app\Modules\tao\A0\city\Models\CityNear;
use app\Modules\tao\A0\city\Services\CityVideoService;
use app\Modules\tao\common\BaseProjectController;
use app\Modules\tao\sdk\phaxui\Layui\LayuiData;
use app\Modules\tao\Services\UploadfileService;
use Phax\Db\QueryBuilder;
use Phax\Mvc\Request;
use Phax\Support\Validate;

/**
 * @property \app\Modules\tao\A0\city\Models\CityNear $model
 */
class NearController extends BaseProjectController
{

    protected array|string $superAdminActions = '*';
    protected bool $console = true;
    protected array $saveWhiteList = [
        'kind', 'title', 'address', 'lng', 'lat',
        'banner', 'list', 'summary', 'sort',
        'tag', 'hot', 'top', 'video_ids', 'image_ids'
    ];
    protected array $appendModifyFields = ['hot', 'top', 'tag'];

    public function initialize(): void
    {
        $this->model = new CityNear();
        parent::initialize();
    }

    protected function beforeModelSaveAssign($data)
    {
        Validate::check($data, [
            'kind|类型' => 'required',
            'title|名称' => 'required',
            'address|地址' => 'required'
        ]);
        LayuiData::boolData($data, ['hot', 'top']);
        return $data;
    }

    /**
     * @rbac ({title:'修改地址'})
     */
    public function addressAction()
    {
        $id = Request::getQueryInt('id');
        $this->model = CityNear::mustFindFirst($id);
        if ($this->request->isPost()) {
            $data = Request::getData();
            $this->model->assign($data, ['address', 'lng', 'lat']);
            return $this->saveModelResponse($this->model->save(), false);
        }
        return $this->model->toArray();
    }

    /**
     * @rbac ({title:'编辑周边'})
     */
    public function editAction()
    {
        $rst = parent::editAction();
        if ($this->request->isPost()) {
            return $rst;
        }
        $rst['images'] = UploadfileService::getImages($this->model->image_ids);
        $rst['videos'] = CityVideoService::find($this->model->video_ids);
        return $rst;
    }

    /**
     * @rbac ({title:'预览周边记录'})
     */
    public function previewAction()
    {
        return $this->editAction();
    }

    protected bool $isSelect = false;

    protected function indexActionQueryBuilder(QueryBuilder $queryBuilder): void
    {
        if ($this->isSelect) {
            $queryBuilder->int('status', 1);
        }
        parent::indexActionQueryBuilder($queryBuilder);
        $queryBuilder
            ->int('kind', $this->request->getQuery('kind', 'int', 0))
            ->int('user_id', $this->request->getQuery('user_id', 'int', 0))
            ->like('title', $this->request->getQuery('keyword', 'string', ''));
    }

    /**
     * @rbac ({title:'选择周边'})
     */
    public function selectAction()
    {
        $this->disabledMainLayout();
        $this->isSelect = true;
        $this->indexQueryColumns = ['id', 'kind','list', 'title', 'address'];
        return $this->indexAction();
    }
}