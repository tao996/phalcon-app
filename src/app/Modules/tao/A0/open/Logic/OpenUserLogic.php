<?php

namespace App\Modules\tao\A0\open\Logic;

use App\Modules\tao\A0\open\Models\OpenApp;
use App\Modules\tao\A0\open\Models\OpenUserOpenid;
use App\Modules\tao\A0\open\Models\OpenUserUnionid;
use App\Modules\tao\A0\open\Services\OpenAppService;
use App\Modules\tao\A0\open\Services\OpenUserService;
use App\Modules\tao\Models\SystemUser;

class OpenUserLogic
{
    public static function responseData(OpenUserOpenid $openidRecord)
    {
        return [
            'user_id' => $openidRecord->user_id,
            'nickname' => $openidRecord->nickname,
            'avatar_url' => $openidRecord->avatar_url,
            'openid' => $openidRecord->openid,
        ];
    }

    /**
     * 检查并保存用户信息
     * @param string $appid 应用的 appid
     * @param array $data 包含用户关键信息 [openid, unionid, session_key, subscribe, subscribe_time]
     * @param array $userBaseInfo 用户的基本信息
     * @return void
     */
    public static function save(string $appid, array $data, array $userBaseInfo)
    {
        $app = OpenAppService::getWithAppid($appid);
        $userBind = OpenApp::newUserBind($app);
        $userInfo = OpenUserService::postUserInfo($userBaseInfo);

        // 检查 unionid 是否存在
        if (!empty($data['unionid'])) {
            $unionidRecord = OpenUserService::getUnionIDRecord($data['unionid']);
            // 如果存在，则 userOpenid 必然存在
            if ($unionidRecord) {
                // 不再处理，可直接返回数据
                $responseData = OpenUserOpenid::queryBuilder()
                    ->string('appid', $appid)
                    ->string('openid', $data['openid'])
                    ->columns(['id', 'user_id', 'nickname', 'avatar_url', 'openid'])
                    ->findFirst();
            } else { // 用户没有注册过
                $responseData = OpenUserOpenid::queryBuilder()
                    ->string('appid', $appid)
                    ->string('openid', $data['openid'])
                    ->columns(['id', 'user_id', 'nickname', 'avatar_url', 'openid'])
                    ->findFirst();
                if ($responseData) {// 统一应用后，现在有了 unionid
                    $unionidRecord = new OpenUserUnionid();
                    $unionidRecord->platform = $app['platform'];
                    $unionidRecord->user_id = $responseData['user_id'];
                    $unionidRecord->appid = $appid;
                    if (!$unionidRecord->save()) {
                        throw new \Exception($unionidRecord->getFirstError());
                    }
                } else {
                    // Openid 和 Unionid 都没有，需要注册用户
                    $user = new SystemUser();
                    $user->addBinds($userBind);

                    $openidRecord = new OpenUserOpenid();
                    $openidRecord->platform = $app['platform'];

                    $unionidRecord = new OpenUserUnionid();
                    $unionidRecord->platform = $app['platform'];

                    OpenUserService::bindUserInfo(
                        $user, $openidRecord, $unionidRecord,
                        $userInfo, $appid, $data
                    );

                    OpenUserService::createUser(
                        $user, $openidRecord, $unionidRecord);

                    $responseData = [
                        'user_id' => $user->id,
                        'nickname' => $openidRecord->nickname,
                        'avatar_url' => $openidRecord->avatar_url,
                        'openid' => $data['openid']
                    ];
                }
            }
        } else {
            // 1. 检查 openid 记录是否存在
            /**
             * @var OpenUserOpenid $openidRecord
             */
            $openidRecord = OpenUserOpenid::queryBuilder()
                ->string('appid', $appid)
                ->string('openid', $data['openid'])
                ->columns(['id', 'user_id', 'nickname', 'avatar_url', 'openid'])
                ->findFirst(false);
            // 如果不存在，则需要注册
            if (empty($openidRecord)) {

                $user = new SystemUser();
                $user->addBinds($userBind);

                $openidRecord = new OpenUserOpenid();
                $openidRecord->platform = $app['platform'];

                OpenUserService::bindUserInfo(
                    $user, $openidRecord, null,
                    $userInfo, $appid, $data
                );

                OpenUserService::createUser(
                    $user, $openidRecord,
                );


                $responseData = [
                    'user_id' => $user->id,
                    'nickname' => $openidRecord->nickname,
                    'avatar_url' => $openidRecord->avatar_url,
                    'openid' => $data['openid']
                ];
            } else {
                // 如果存在
                if (OpenUserService::bindSubscribe($openidRecord, $data)) {
                    $openidRecord->save();
                }
                $responseData = $openidRecord->toArray([
                    'user_id', 'nickname', 'avatar_url', 'openid'
                ]);
            }
        }
        return $responseData;
    }
}