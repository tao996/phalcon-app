<?php

namespace App\Modules\tao\A0\wechat\Logics;

use App\Modules\tao\A0\open\Logic\OpenUserLogic;
use App\Modules\tao\A0\open\Services\OpenUserService;

/**
 * 微信用户处理逻辑
 */
class WechatUserLogic
{

    /**
     * 创建公众号用户
     * @param \EasyWeChat\OfficialAccount\Application $application 微信公众号
     * @param string $openid 用户 openid
     * @return array
     */
    public static function officialUser(\EasyWeChat\OfficialAccount\Application $application, string $openid)
    {
        $appid = $application->getConfig()->get('app_id');
        // 查询当前用户是否已经记录
        $openidRecord = OpenUserService::getOpenidRecord($appid, $openid);
        if (!empty($openidRecord)) {
            return OpenUserLogic::responseData($openidRecord);
        }
        // https://developers.weixin.qq.com/doc/offiaccount/User_Management/Get_users_basic_information_UnionID.html#UinonId
        $data = $application->getClient()->get('/cgi-bin/user/info', ['openid' => $openid])->toArray();
        /*{
            "subscribe": 1, subscribe_time": 1382694957,
            "openid": "o6_bmjrPTlm6_2sgVt7hMZOPfL2M",
            "unionid": " o6_bmasdasdsad6_2sgVt7hMZOPfL",
        } */
        return OpenUserLogic::save($appid, $data, []);
    }

    /**
     * 取消订阅
     * @param array $data 接收微信发送的信息
     * @return void
     * @throws \Exception
     */
    public static function unsubscribe($data)
    {

        if ($record = OpenUserService::getOpenidRecord($data['ToUserName'], $data['FromUserName'])) {
            $record->sub = 0;
            $record->sub_at = time();
            if ($record->save() === false) {
                throw new \Exception($record->getErrors());
            }
        }
    }
}