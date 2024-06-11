<?php

namespace App\Modules\tao\A0\open\Services;

use App\Modules\tao\A0\open\Models\OpenUserOpenid;
use App\Modules\tao\A0\open\Models\OpenUserUnionid;
use App\Modules\tao\Models\SystemUser;
use App\Modules\tao\Services\UserService;
use Phax\Db\Db;

class OpenUserService
{
    public static function postUserInfo($userInfo): array
    {
        $rst = [
            'avatarUrl' => '', 'nickname' => '',
            'gender' => 0,
        ];

        if (isset($userInfo['avatarUrl']) || isset($userInfo['headimgurl'])) {
            $rst['avatarUrl'] = $userInfo['avatarUrl'] ?? $userInfo['headimgurl'] ?? '';
        }
        if (isset($userInfo['nickName']) || isset($userInfo['nickname'])) {
            $rst['nickname'] = $userInfo['nickName'] ?? $userInfo['nickname'] ?? '';
        }
        if (isset($userInfo['gender']) || isset($userInfo['sex'])) {
            $rst['gender'] = intval($userInfo['gender'] ?? $userInfo['sex'] ?? 0);
        }

        return array_merge($userInfo, $rst);
    }

    public static function bindUserInfo(
        SystemUser           $user,
        OpenUserOpenid       $openidRecord,
        OpenUserUnionid|null $unionidRecord,
        array                $userInfo, string $appid, array $data)
    {
        $user->nickname = $userInfo['nickname'];
        $user->avatar_url = $userInfo['avatarUrl'];

        $openidRecord->appid = $appid;
        $openidRecord->openid = $data['openid'];
        self::bindSubscribe($openidRecord, $data);

        $openidRecord->unionid = $data['unionid'];
        $openidRecord->avatar_url = $userInfo['avatarUrl'];
        $openidRecord->nickname = $userInfo['nickname'];

        if ($unionidRecord) {
            $unionidRecord->appid = $appid;
            $unionidRecord->unionid = $data['unionid'];
        }
    }

    public static function bindSubscribe(OpenUserOpenid $openidRecord, array $data): bool
    {
        $hasChange = false;
        if (isset($data['session_key'])) {
            $openidRecord->session_key = $data['session_key'];
            $hasChange = true;
        }
        if (isset($data['subscribe_time']) && $data['subscribe_time'] > 0) {
            $openidRecord->sub = 1;
            $openidRecord->sub_at = $data['subscribe_time'];
            $hasChange = true;
        }
        return $hasChange;
    }

    public static function createUser(
        SystemUser           $user,
        OpenUserOpenid       $openidRecord,
        OpenUserUnionid|null $unionidRecord = null,
    )
    {
        Db::transaction(function () use ($user, $openidRecord, $unionidRecord) {
            UserService::create($user);

            $openidRecord->user_id = $user->id;
            if (!$openidRecord->save()) {
                throw new \Exception($openidRecord->getFirstError());
            }

            if ($unionidRecord) {
                $unionidRecord->user_id = $user->id;
                if (!$unionidRecord->save()) {
                    throw new \Exception($unionidRecord->getFirstError());
                }
            }
        });
    }

    /**
     * 查询 OpenUserOpenid 记录
     * @param string $appID
     * @param string $openid
     * @return OpenUserOpenid|null
     * @throws \Exception
     */
    public static function getOpenidRecord(string $appID, string $openid): OpenUserOpenid|null
    {
        if (empty($appID) || empty($openid)) {
            throw new \Exception(' appID 或 openid 不能为空');
        }
        return OpenUserOpenid::findFirst([
            'conditions' => "appid='{$appID}' AND openid='{$openid}'",
        ]);
    }

    /**
     * 查询用户的的 openid，如果没有找到，则返回空字符串
     * @param string $appID
     * @param int $userId
     * @return string
     * @throws \Exception
     */
    public static function getOpenidByUserId(string $appID, int $userId): string
    {
        if (empty($appID) || empty($userId)) {
            throw new \Exception('appID 或 userId 不能为空');
        }
        if ($row = OpenUserOpenid::findFirst([
            'conditions' => "appid='{$appID}' AND user_id='{$userId}'",
            'columns' => 'openid'
        ])?->toArray()) {
            return $row['openid'];
        }
        return '';
    }

    /**
     * 查询 OpenUserUnionid 记录
     * @param string $unionid
     * @return OpenUserUnionid|null
     * @throws \Exception
     */
    public static function getUnionIDRecord(string $unionid): OpenUserUnionid|null
    {
        if (empty($unionid)) {
            throw new \Exception("unionID can't be empty");
        }
        return OpenUserUnionid::queryBuilder()
            ->string('unionid', $unionid)
            ->findFirst(false);
    }
}