<?php

namespace app\Modules\tao\A0\tiktok\Controllers\admin;

use app\Modules\tao\A0\tiktok\Models\TiktokConfig;
use app\Modules\tao\A0\tiktok\Services\TiktokConfigService;
use app\Modules\tao\BaseController;

/**
 * @rbac ({title:'抖音配置'})
 */
class ConfigController extends BaseController
{
    protected array|string $superAdminActions = '*';
    public array $enableActions = ['index'];

    /**
     * @rbac ({title:'公共配置'})
     */
    public function indexAction()
    {
        $rows = TiktokConfigService::rows();
        // 更新配置信息
        if ($this->request->isPost()) {
            $model = new TiktokConfig();
            $hasChange = false;
            foreach ($this->request->getPost() as $key => $value) {
                if (key_exists($key, $rows) && $rows[$key] != $value) {
                    $model->updateValue($key, $value);
                    $hasChange = true;
                }
            }
            if ($hasChange) {
                TiktokConfigService::forceCache();
            }
            return $this->success('update tiktok.config success');
        }

        return $rows;
    }
}