<?php

namespace app\Modules\tao\A0\wechat\Logics;

use app\Modules\tao\Config\Config;
use app\Modules\tao\A0\wechat\Models\WechatUserOpenid;
use app\Modules\tao\A0\wechat\Models\WechatUserUnionid;
use app\Modules\tao\A0\wechat\Services\WechatUserService;
use app\Modules\tao\Models\SystemUser;

use Phax\Db\Db;
use Phax\Support\Logger;

/**
 * 微信用户处理逻辑
 */
class WechatUserLogic
{

    /**
     * 创建公众号用户
     * @param \EasyWeChat\OfficialAccount\Application $app 微信公众号
     * @param string $openid 用户 openid
     * @return WechatUserOpenid
     */
    public static function officialUser(\EasyWeChat\OfficialAccount\Application $app, string $openid)
    {
        $appID = $app->getConfig()->get('app_id');
        // 查询当前用户是否已经记录
        $userOpenId = WechatUserService::findByOpenid($appID, $openid);
        if (!empty($userOpenId)) {
            return $userOpenId;
        }

        // https://developers.weixin.qq.com/doc/offiaccount/User_Management/Get_users_basic_information_UnionID.html#UinonId
        $api = $app->getClient();
        $response = $api->get('/cgi-bin/user/info', ['openid' => $openid]);
        $userInfo = $response->toArray();


        $user = new SystemUser();
        $user->assign([
            'head_img' => $userInfo['headimgurl'],
            'status' => Config::STATUS_ACTIVE,
        ]);
        $user->addBinds('official');

        $userOpenId = new WechatUserOpenid();
        $userOpenId->assign([
            'app_id' => $appID,
            'user_id' => 0,
            'openid' => $openid,
            'unionid' => $userInfo['unionid'] ?? '',
            'nickname' => $userInfo['nickname'] ?? '',
            'sex' => intval($userInfo['sex'] ?? 0),
            'language' => $userInfo['language'] ?? '',
            'city' => $userInfo['city'] ?? '',
            'province' => $userInfo['province'] ?? '',
            'country' => $userInfo['country'] ?? '',
            'headimgurl' => $userInfo['headimgurl'] ?? '',
        ]);
        if (isset($userInfo['subscribe_time']) && $userInfo['subscribe_time'] > 0) {
            $userOpenId->sub = Config::STATUS_ACTIVE;
            $userOpenId->sub_at = $userInfo['subscribe_time'];
        }

        $saveUnionId = false;
        $userUnionId = new WechatUserUnionid();
        $userUnionId->assign([
            'user_id' => 0,
            'app_id' => $appID,
            'unionid' => $userInfo['unionid']
        ]);
        if (!empty($userInfo['unionid'])) {
            if (!WechatUserService::findByUnionID($userInfo['unionid'])) {
                $saveUnionId = true;
            }
        }


        Db::transaction(function () use ($user, $userOpenId, $userUnionId, $saveUnionId) {
            if ($user->save() === false) {
                Logger::message('创建新用户失败', $user->getErrors());
            }
            $userOpenId->user_id = $user->id;
            if ($userOpenId->save() === false) {
                Logger::message('保存微信用户 OpenID 信息失败', $userOpenId->getErrors());
            }
            if ($saveUnionId) {
                $userUnionId->user_id = $user->id;
                if ($userUnionId->save() === false) {
                    Logger::message('保存微信用户 UnionID 信息失败', $userUnionId->getErrors());
                }
            }
        });

        return $userOpenId;
    }

    /**
     * 取消订阅
     * @param array $data 接收微信发送的信息
     * @return void
     * @throws \Exception
     */
    public static function unsubscribe($data)
    {

        if ($record = WechatUserService::findByOpenid($data['ToUserName'], $data['FromUserName'])) {
            $record->sub = 0;
            WechatUserService::saveSubStatus($record);
        }
    }
}