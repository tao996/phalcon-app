<?php

namespace App\Modules\tao\Services;

use App\Modules\tao\Models\SystemUser;
use Phax\Support\Validate;

class UserService
{

    public static function mustAccountString(string $account)
    {
        if (empty($account)) {
            throw new \Exception('账号不能为空');
        }
        if (Validate::isEmail($account) || Validate::isPhone($account)) {
            return;
        }
        throw new \Exception('不是一个合法的账号');
    }

    public static function mustCanRegister(string $account)
    {
        $isEmail = SmsCodeService::mustReceiver($account);

        if ($row = SystemUser::queryBuilder()
            ->where($isEmail ? 'email' : 'phone', $account)
            ->columns('id,email_valid,phone_valid,status')
            ->findFirst()
        ) {
            if ($isEmail) {
                if ($row['email_valid'] == 1) {
                    throw new \Exception('邮箱已经被占用');
                }
            } else {
                if ($row['phone_valid'] == 1) {
                    throw new \Exception('手机号已经被占用');
                }
            }
        }
    }

    public static function mustCanLogin(mixed $account)
    {
        $isEmail = SmsCodeService::mustReceiver($account);

        if ($row = SystemUser::queryBuilder()
            ->where($isEmail ? 'email' : 'phone', $account)
            ->columns('id,email_valid,phone_valid,status')
            ->findFirst()
        ) {
            if ($row[$isEmail ? 'email_valid' : 'phone_valid'] != 1) {
                throw new \Exception('账号不存在或未激活');
            }

        } else {
            throw new \Exception('账号不存在');
        }
    }

    public static function mustPassword($password)
    {
        if (strlen($password) < 6) {
            throw new \Exception('密码最少为6位');
        }
    }

    /**
     * @param array $condition
     * @return SystemUser
     * @throws \Exception
     */
    public static function mustGetUser(array $condition)
    {
        $qb = SystemUser::queryBuilder()
            ->where($condition);

        if ($user = $qb->findFirst(false)) {
            return $user;
        }
        throw new \Exception('没有找到符合条件的用户');
    }

    public static function create(SystemUser $user)
    {
        if (!$user->save()){
            throw new \Exception('注册账号失败');
        }
    }

}