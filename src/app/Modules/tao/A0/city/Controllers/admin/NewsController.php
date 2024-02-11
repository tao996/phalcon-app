<?php

namespace app\Modules\tao\A0\city\Controllers\admin;

use app\Modules\tao\A0\city\Models\CityNews;
use app\Modules\tao\A0\city\Services\CityNewsService;
use app\Modules\tao\A0\cms\Services\CmsContentService;
use app\Modules\tao\common\BaseProjectController;
use app\Modules\tao\sdk\phaxui\Layui\LayuiData;
use Phax\Db\Db;
use Phax\Db\QueryBuilder;
use Phax\Utils\Data;

/**
 * @property CityNews $model
 */
class NewsController extends BaseProjectController
{
    protected array|string $superAdminActions = '*';
    protected bool $console = true;

    public function initialize(): void
    {
        $this->model = new CityNews();
        parent::initialize();
    }

    protected array $saveWhiteList = [
        'kind', 'title', 'summary', 'list', 'banner',
        'address', 'lng', 'lat', 'dt1', 'dt2',
        'warning', 'tag', 'metadata',
        'image_ids', 'live_ids', 'video_ids', 'near_ids'
    ];
    protected $indexQueryColumns = ['id', 'kind', 'status', 'title', 'summary',
        'list', 'banner', 'address', 'lng', 'lat', 'dt1', 'dt2',
        'tag', 'image_ids', 'live_ids', 'video_ids', 'near_ids', 'ad_id',
        'hot'];

    protected function beforeModelSaveAssign($data)
    {
        LayuiData::timestamp($data, ['dt1', 'dt2']);
        $metadata = $data['metadata'];
        unset($data['metadata']);
        switch ($data['kind']) {
            case CityNews::KindGame:
                $data['metadata'] = json_encode(Data::getByKeys($metadata, CityNews::KeysGame));
                break;
        }
        return $data;
    }

    protected function indexActionQueryBuilder(QueryBuilder $queryBuilder): void
    {
        parent::indexActionQueryBuilder($queryBuilder);
        $queryBuilder->int('kind', $this->request->getQuery('kind', 'int', 0));
    }

    protected function save($data): bool
    {
        $data = $this->beforeModelSaveAssign($data);
//dd($data);
        Db::transaction(function () use ($data) {
            if (!empty($data['content']) || $this->model->content_id > 0) {
                $cc = CmsContentService::saveContentDataById($this->model->content_id, Data::getString($data, 'content'));
                if ($this->model->content_id < 1) {
                    $this->model->content_id = $cc->id;
                }
            }

            $this->model->assign($data, $this->saveWhiteList);
            return $this->model->save();
        });
        return true;
    }

    public function editAction()
    {
        $row = parent::editAction();
        if ($this->request->isPost()){
            return $row;
        }
        $row['metadata'] = $row['metadata']
            ? json_decode($row['metadata'],true)
            : null;
        CityNewsService::appendInfo($row);
        return $row;
    }

    /**
     * @rbac ({title:'预览'})
     */
    public function previewAction()
    {
        return self::editAction();
    }
}