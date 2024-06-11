<?php

namespace App\Modules\tao\A0\open;

use App\Modules\tao\A0\open\Data\Config;
use App\Modules\tao\A0\open\Helper\WechatHelper;
use App\Modules\tao\BaseController;
use App\Modules\tao\Services\Auth\LoginAppAuth;
use App\Modules\tao\Services\LoginService;

class BaseDeveloperOpenController extends BaseController
{
    public function initialize(): void
    {
        // 注意：如果不需要登录，需要设置 openAction='*'; 其它可选 enableActions=['index']
        LoginService::setAuthAdapter(new LoginAppAuth());
        $this->view->disable();
        parent::initialize();
    }

    protected function getAppid(): string
    {
        $appid = $this->request->getQuery('appid', 'string', '');
        if (empty($appid)) {
            throw new \Exception('必须指定 appid');
        }
        return $appid;
    }

    /**
     * @return int
     * @throws \Exception
     */
    protected function mustGetPlatform(): int
    {
        $pl = $this->request->getQuery('platform', 'string', '');
        switch ($pl) {
            case 'tt':
            case 'tiktok':
                return Config::Tiktok;
            case 'weapp':
            case 'wechat':
                return Config::Wechat;
        }
        throw new \Exception('could not find platform from query');
    }

    /**
     * 小程序提交的数据
     * @return array
     */
    protected function requestMiniData(): array
    {
        return $this->request->getJsonRawBody(true);
    }

    public function afterExecuteRoute(\Phalcon\Mvc\Dispatcher $dispatcher): void
    {
        if ($this->response->isSent()) {
            return;
        }
        $data = $dispatcher->getReturnedValue();


        if ($data instanceof \Psr\Http\Message\ResponseInterface) {
            WechatHelper::response($data);
            return;
        }
        parent::afterExecuteRoute($dispatcher);
    }

}