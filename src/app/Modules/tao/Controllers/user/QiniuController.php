<?php

namespace App\Modules\tao\Controllers\user;

use App\Modules\tao\BaseController;
use App\Modules\tao\sdk\qiniu\QiniuDriver;
use App\Modules\tao\Services\ConfigService;

class QiniuController extends BaseController
{
    protected array|string $userActions = '*';

    /**
     * 生成客户端上传凭证
     * @return array
     */
    public function indexAction()
    {
        ConfigService::forceCache();
        $qiniu = new QiniuDriver(ConfigService::groupRows('upload'));

        return [
            'token' => $qiniu->imageToken(),
            'expire' => time() + 7100,
            'domain' => $qiniu->getDomain(),
        ];
    }
}