<?php

namespace app\Modules\tao\A0\wechat\Controllers\admin;

use app\Modules\tao\BaseController;
use app\Modules\tao\A0\wechat\Models\WechatApp;
use Phax\Utils\Data;


class AppController extends BaseController
{
    protected string $pageTitle = '微信应用列表';
    protected array|string $superAdminActions = '*';

    public function initialize(): void
    {
        $this->model = new WechatApp();
        parent::initialize();
    }

    protected $indexQueryColumns = ['id', 'created_at', 'status', 'sort', 'online', 'kind', 'title', 'app_id', 'crop_id', 'remark'];
    protected string $indexOrder = 'sort desc,id desc';
    protected array $allowModifyFields = ['status', 'sort', 'online'];
    protected array $saveWhiteList = [
        'kind', 'title', 'app_id', 'secret',
        'token', 'enc_method', 'aes_key', 'crop_id',
        'remark', 'sort'
    ];
    protected function beforeModelSaveAssign($data)
    {
        Data::mustHasSet($data, ['kind', 'title', 'app_id', 'secret']);
        return $data;
    }
}