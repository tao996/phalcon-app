<?php

namespace App\Modules\tao\A0\wechat\Controllers;

use App\Modules\tao\A0\open\Helper\WechatHelper;
use App\Modules\tao\A0\open\Services\OpenAppService;
use App\Modules\tao\A0\open\Services\WechatApplicationService;
use App\Modules\tao\Services\LoginService;
use App\Modules\tao\Services\RedirectService;
use Phax\Mvc\Controller;
use Phax\Mvc\Request;
use Phax\Support\Exception\BlankException;

class AuthController extends Controller
{

    /**
     * 公众号授权
     * @link https://easywechat.com/6.x/oauth.html
     */
    public function indexAction()
    {
        if ($this->request->getQuery('user')) { // 只是需要检查用户是否登录
            if (LoginService::tryLogin()) {
                header('Location:' . RedirectService::query('/'));
            }
        }


        $appid = Request::getString('appid');
        $scope = $this->request->getQuery('scope', null, 'snsapi_base');
        $target = $this->request->getQuery('target', null, '/');

        if (!WechatHelper::isMicroMessengerBrowser()) {
            $url = WechatHelper::url('tao.wechat/auth', ['appid' => $appid]);
            WechatHelper::renderQrcode($url);
        }
        $app = WechatApplicationService::getOfficialApplication($appid);
        $oauth = $app->getOAuth();


        if (in_array($scope, ['snsapi_base', 'snsapi_userinfo'])) {
            if (!OpenAppService::kindCompare($appid, 'gzh')) {
                throw new \Exception('appid is not kind of "gzh"');
            }
        } elseif ('snsapi_login' == $scope) {
            if (!OpenAppService::kindCompare($appid, 'web')) {
                throw new \Exception('appid is not kind of "web"');
            }
        }
        $oauth->scopes([$scope]);

        $absURL = WechatHelper::url('tao.wechat/auth/code', [
            'appid' => $appid,
            'target' => $target, // 授权后跳转到此地址
        ]);
        $redirectURL = $oauth->redirect($absURL);
        header("Location:{$redirectURL}");
        throw new BlankException();
    }

    public function codeAction()
    {
        $appid = Request::getString('appid');
        $code = Request::getString('code');
        $app = WechatApplicationService::getOfficialApplication($appid);
        $oauth = $app->getOAuth();

        $user = $oauth->userFromCode($code);
        $info = $user->toArray();
//        dd('info', $info, $_SERVER);
        $redirect = $this->request->getQuery('target', null, '/');
        $redirectURL = WechatHelper::url($redirect, ['openid' => $info['id'], 'appid' => $appid], false); // 跳转到回调地址
        header("Location:{$redirectURL}");
        throw new BlankException();
    }
}