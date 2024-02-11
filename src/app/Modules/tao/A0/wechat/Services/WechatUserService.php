<?php

namespace app\Modules\tao\A0\wechat\Services;

use app\Modules\tao\A0\wechat\Models\WechatUserOpenid;
use app\Modules\tao\A0\wechat\Models\WechatUserUnionid;

class WechatUserService
{
    /**
     * 在 WechatUserUnionId 表中中查询用户 ID
     * @param string $unionid
     * @return int
     * @throws \Exception
     */
    public static function findUserIdByUnionID(string $unionid): int
    {
        if (empty($unionid)) {
            throw new \Exception("unionID 不能为空");
        }
        if ($row = WechatUserUnionid::findFirst([
            'conditions' => "unionid='{$unionid}'",
            'columns' => 'user_id'
        ])?->toArray()) {
            return $row['user_id'];
        }
        return 0;
    }

    /**
     * @param string $unionid
     * @return WechatUserUnionid|null
     * @throws \Exception
     */
    public static function findByUnionID(string $unionid)
    {
        if (empty($unionid)) {
            throw new \Exception("unionID 不能为空");
        }
        return WechatUserUnionid::findFirst([
            'conditions' => "unionid='{$unionid}'",
            'columns' => 'user_id'
        ]);
    }

    /**
     * 在 WechatUserOpenId 表中查询用户 ID
     * @param string $appID
     * @param string $openid
     * @return int
     * @throws \Exception
     */
    public static function findUserIdByOpenid(string $appID, string $openid): int
    {
        if (empty($appID) || empty($openid)) {
            throw new \Exception('微信 appID 或 openid 不能为空');
        }
        if ($row = WechatUserOpenid::findFirst([
            'conditions' => "app_id='{$appID}' AND openid='{$openid}'",
            'columns' => 'user_id'
        ])?->toArray()) {
            return $row['user_id'];
        }
        return 0;
    }

    /**
     * @param string $appID
     * @param string $openid
     * @return WechatUserOpenid|null
     * @throws \Exception
     */
    public static function findByOpenid(string $appID, string $openid)
    {
        if (empty($appID) || empty($openid)) {
            throw new \Exception('微信 appID 或 openid 不能为空');
        }
        return WechatUserOpenid::findFirst([
            'conditions' => "app_id='{$appID}' AND openid='{$openid}'",
        ]);
    }

    /**
     * 查询用户的 openid
     * @param string $appID
     * @param int $userId
     * @return mixed|string
     * @throws \Exception
     */
    public static function findOpenidByUserId(string $appID, int $userId)
    {
        if (empty($appID) || empty($userId)) {
            throw new \Exception('微信 appID 或 userId 不能为空');
        }
        if ($row = WechatUserOpenid::findFirst([
            'conditions' => "app_id='{$appID}' AND user_id='{$userId}'",
            'columns' => 'openid'
        ])?->toArray()) {
            return $row['openid'];
        }
        return '';
    }

    /**
     * 更新订阅状态
     * @param WechatUserOpenid $record
     * @return void
     * @throws \Exception
     */
    public static function saveSubStatus(WechatUserOpenid $record): void
    {
        $record->sub_at = time();
        if ($record->save() === false) {
            throw new \Exception($record->getErrors());
        }
    }

}