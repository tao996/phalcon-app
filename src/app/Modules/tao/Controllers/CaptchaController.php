<?php

namespace App\Modules\tao\Controllers;

use App\Modules\tao\BaseController;
use App\Modules\tao\Services\CaptchaService;

class CaptchaController extends BaseController
{
    protected array|string $openActions = '*';

    /**
     * 生成一个验证码
     * http://localhost:8071/m/tao/captcha
     */
    public function indexAction()
    {
        CaptchaService::getInstance()->output();
        exit;
    }
}