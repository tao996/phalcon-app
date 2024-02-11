<?php

namespace app\Modules\tao\A0\wechat;

use app\Modules\tao\A0\wechat\Helper\WechatHelper;
use app\Modules\tao\sdk\SdkHelper;
use Phax\Mvc\Controller;

class BaseWechatServeController extends Controller
{
    protected bool $autoResponse = false;

    public function initialize(): void
    {
        $this->view->disable();
        SdkHelper::easyWechat();
    }

    public function afterExecuteRoute(\Phalcon\Mvc\Dispatcher $dispatcher): void
    {
        if ($this->response->isSent()) {
            return;
        }
        $data = $dispatcher->getReturnedValue();


        if ($data instanceof \Psr\Http\Message\ResponseInterface) {
            WechatHelper::response($data);
        }

    }

    public function tryLogin(){

    }
}