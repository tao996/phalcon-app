<?php

namespace App\Modules\tao\A0\open\Controllers;

use App\Modules\tao\A0\open\BaseDeveloperOpenController;
use App\Modules\tao\A0\open\Logic\OpenUserLogic;
use App\Modules\tao\A0\open\Services\MiniAppServer;
use App\Modules\tao\A0\open\Services\OpenAppService;
use App\Modules\tao\Services\Auth\LoginAppAuth;
use Phax\Mvc\Request;
use Phax\Support\Validate;

class MiniServerController extends BaseDeveloperOpenController
{
    protected array|string $openActions = '*';

    /**
     * 获取授权用户的 session_key 和 openid
     * @link https://demo.fushuilu.com/api/m/tao.open/mini-server/code2session
     * @method POST
     * @query {appid:小程序appid}
     * @body {code:login接口返回的登录凭证,userInfo:{avatarUrl:头像,nickName:昵称}} 其它参数如 encryptedData,iv,rawData,signature 不是必须的
     */
    public function code2SessionAction()
    {
        Request::mustPost();
        // 用户传上来的资料信息
        $requestData = $this->requestMiniData();
        Validate::check($requestData, ['code' => 'required']);

        $code = $requestData['code'];
        if (empty($code)) {
            throw new \Exception('code 参数不能为空');
        }
        $appid = $this->getAppid();
        $app = OpenAppService::getWithAppid($appid); // 应用配置信息
        $data = MiniAppServer::code2Session($app, $code); // session_key, openid, unionid
        $baseInfo = OpenUserLogic::save($appid, $data, $responseData['userInfo'] ?? []);

        // token-secret
        $responseData['ts'] = (new LoginAppAuth())->saveUser(['id' => $baseInfo['user_id']]);
        return $responseData;
    }
}