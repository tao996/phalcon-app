<?php

namespace app\Modules\tao\Controllers;

use app\Modules\tao\BaseController;
use app\Modules\tao\Services\CaptchaService;

class CaptchaController extends BaseController
{
    protected array|string $openActions = '*';

    /**
     * 生成一个验证码
     */
    public function indexAction()
    {
        CaptchaService::getInstance()->output();
        exit;
    }
}