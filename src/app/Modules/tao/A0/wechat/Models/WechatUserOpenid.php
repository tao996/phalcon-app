<?php

namespace app\Modules\tao\A0\wechat\Models;

use app\Modules\tao\BaseModel;

class WechatUserOpenid extends BaseModel
{

    public string $app_id = ''; // 微信 appId
    public int $user_id = 0; // 绑定用户 id
    public string $openid = ''; // 微信用户 openid
    public string $unionid = '';
    public int $sub = 0; // 是否关注公众号（1：是；2；取消）
    public int $sub_at = 0; // 关注/取消关注时间

    public string $nickname = '';// 昵称
    public int $sex = 0; // 性别
    public string $language = '';
    public string $city = '';
    public string $province = '';
    public string $country = '';
    public string $headimgurl = ''; // 头像
}