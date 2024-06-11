<?php

namespace App\Modules\tao\Services;

use App\Modules\tao\Config\Data;
use App\Modules\tao\Models\SystemUser;
use Phax\Support\Logger;

class Oauth3Service
{
    public static function addUserProfile(\Hybridauth\User\Profile $profile): SystemUser
    {

        // 准备注册账号
        $qb = SystemUser::queryBuilder();
        if ($profile->email) {
            $qb->where(['email' => $profile->email, 'email_valid' => 1]);
        }
        if ($user = $qb->findFirst(false)) {
            return $user;
        }
        if ($profile->phone) {
            $qb->where(['phone' => $profile->phone, 'phone_valid' => 1]);
        }
        if ($user = $qb->findFirst(false)) {
            return $user;
        }
        // 注册
        $user = new SystemUser();
        if ($profile->email) {
            $user->email = $profile->email;
            $user->email_valid = 1;
        }
        if ($profile->phone) {
            $user->phone = $profile->phone;
            $user->phone_valid = 1;
        }
        $user->head_img = $profile->photoURL;
        $user->nickname = $profile->displayName;
        $user->addBinds(Data::Gmail);

        if ($user->save()) {
            return $user;
        } else {
            Logger::message('注册账号失败', $user->getErrors());
        }
    }
}