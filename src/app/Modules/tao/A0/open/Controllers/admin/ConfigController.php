<?php

namespace App\Modules\tao\A0\open\Controllers\admin;

use App\Modules\tao\A0\open\Models\OpenConfig;
use App\Modules\tao\A0\open\Services\OpenConfigService;
use App\Modules\tao\BaseController;

/**
 * @rbac ({title:'开放平台配置'})
 */
class ConfigController extends BaseController
{

    /**
     * @rbac ({title:'公共配置'})
     */
    public function indexAction()
    {
        $rows = OpenConfigService::rows();
        // 更新配置信息
        if ($this->request->isPost()) {
            $model = new OpenConfig();
            $hasChange = false;
            foreach ($this->request->getPost() as $key => $value) {
                if (key_exists($key, $rows) && $rows[$key] != $value) {
                    $model->updateValue($key, $value);
                    $hasChange = true;
                }
            }
            if ($hasChange) {
                OpenConfigService::cache();
            }
            return $this->success('更新开放平台配置成功');
        }

        return $rows;
    }
}