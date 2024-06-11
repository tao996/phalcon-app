<?php

namespace App\Modules\tao\Controllers;

use App\Modules\tao\BaseController;
use App\Modules\tao\sdk\SdkHelper;
use App\Modules\tao\Services\LoginService;
use App\Modules\tao\Services\Oauth3Service;
use App\Modules\tao\Services\RedirectService;
use App\Modules\tao\Services\RegisterService;
use Hybridauth\Hybridauth;
use Phax\Support\Logger;
use Phax\Utils\MyUrl;

class Oauth3Controller extends BaseController
{
    protected array|string $openActions = '*';
    public bool $disableUpdateActions = true;

    public function initialize(): void
    {
        parent::initialize();
        SdkHelper::hybridauth();
    }

// https://hybridauth.github.io/introduction.html
    public function indexAction()
    {
        if (empty($_GET['d'])) {
            return $this->error('请求参数错误 d=driver');
        }
        if ($this->isLogin()) {
            RedirectService::read(true);
            return $this->error('请先退出登录');
        }
        if (!isset($_GET['state'])) {
            if ($redirect = $this->request->getQuery('_redirect')) {
                RedirectService::save($redirect);
            }
        }
        $driver = strtolower($_GET['d']);
        $config = [
            'callback' => MyUrl::getMultiURL('tao/oauth3', ['d' => $driver]),
            'providers' => [
                'Google' => RegisterService::getInstance()->googleProvider(),
            ]
        ];
        $provider = ucwords($driver);
//        dd($config,$provider);
        if (empty($config['providers'][$provider])) {
            return $this->error('匹配不到 Provider');
        }
        if (!$config['providers'][$provider]['enabled']) {
            return $this->error('未启用的授权 Provider');
        }

        try {
            $hy = new Hybridauth($config);
            $adapter = $hy->authenticate($provider);


            $userProfile = $adapter->getUserProfile();
            $adapter->disconnect();
        } catch (\Exception $e) {
            return $this->error(
                Logger::message('授权错误', $e->getMessage(), false),
            );
        }

        $user = Oauth3Service::addUserProfile($userProfile);
        LoginService::makeLogin($user);
        RedirectService::read();

        dd('登录成功：跳转到登录页'); // 不会执行到这里
    }
}