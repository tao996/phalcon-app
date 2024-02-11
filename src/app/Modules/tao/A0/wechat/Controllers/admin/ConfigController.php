<?php

namespace app\Modules\tao\A0\wechat\Controllers\admin;

use app\Modules\tao\A0\wechat\Models\WechatConfig;
use app\Modules\tao\A0\wechat\Services\WechatConfigService;
use app\Modules\tao\BaseController;
use Phax\Mvc\Request;


class ConfigController extends BaseController
{
    protected array|string $superAdminActions = '*';

    /**
     * @rbac ({title:'公共配置'})
     */
    public function indexAction()
    {
        $rows = WechatConfigService::rows();
        // 更新配置信息
        if ($this->request->isPost()) {
            $model = new WechatConfig();
            $hasChange = false;
            foreach ($this->request->getPost() as $key => $value) {
                if (key_exists($key, $rows) && $rows[$key] != $value) {
                    $model->updateValue($key, $value);
                    $hasChange = true;
                }
            }
            if ($hasChange) {
                WechatConfigService::forceCache();
            }
            return $this->success('update wechat.config success');
        }

        return $rows;
    }
}