<?php

namespace App\Modules\tao\Services;

use App\Modules\tao\Config\Config;
use App\Modules\tao\Models\SystemSmsCode;
use App\Modules\tao\Models\SystemUser;
use App\Modules\tao\sdk\EmailDriverInterface;
use App\Modules\tao\sdk\SmsDriverInterface;
use Phax\Support\Logger;
use Phax\Support\Validate;

class SmsCodeService
{
    public static function smsTemplateCode($kind): string
    {
        static $code = [
            // 用户注册验证码： 验证码${code}，您正在注册成为新用户，感谢您的支持！
            'register' => 'SMS_177005595',
            // 登录确认验证码： 验证码${code}，您正在登录，若非本人操作，请勿泄露。
            'login' => 'SMS_177005597',
            // 修改密码验证码： 验证码${code}，您正在尝试修改登录密码，请妥善保管账户信息
            'password' => 'SMS_177005594',
            // 信息变更验证码:  验证码${code}，您正在尝试变更重要信息，请妥善保管账户信息。
            'change_account' => 'SMS_177005593',
        ];
        if (isset($code[$kind])) {
            return $code[$kind];
        } else {
            throw new \Exception('没有找到符合条件的短信模板:' . $kind);
        }
    }

    /**
     * 获取最近的发送
     * @param array $conditions
     * @return SystemSmsCode|null
     * @throws \Exception
     */
    public static function getLast(array $conditions): SystemSmsCode|null
    {
        return SystemSmsCode::queryBuilder()
            ->where($conditions)
            ->order('id desc')
            ->findFirst(false);
    }

    /**
     * 统计当天发送的数量
     * @param array $conditions
     * @return int
     * @throws \Exception
     */
    public static function todayCount(array $conditions): int
    {
        $beginOfDay = strtotime('today', time());
        return SystemSmsCode::queryBuilder()
            ->where($conditions)
            ->and('created_at >= ' . $beginOfDay, true)
            ->and('created_at <= ' . strtotime('tomorrow', $beginOfDay), true)
            ->count();
    }

    /**
     * @param EmailDriverInterface|SmsDriverInterface $engine
     * @param SystemSmsCode $code
     * @param $rst
     * @return bool
     * @throws \Phalcon\Logger\Exception
     */
    public static function updateSendStatus(SmsDriverInterface|EmailDriverInterface $engine, SystemSmsCode $code, $rst)
    {
        $code->send_engine = $engine->engine();
        if ($engine->isSendSuccess($rst)) {
            $code->send_status = SystemSmsCode::SendStatusSuccess;
            if ($code->save() === false) {
                Logger::message('更新验证码发送成功状态错误:' . $code->id, $code->getErrors());
            }
            return true;
        } else {
            $code->send_status = SystemSmsCode::SendStatusFailed;
            if ($code->save() === false) {
                Logger::message('更新验证码发送失败状态错误:' . $code->id, $code->getErrors());
            }
            return false;
        }
    }

    public static function compare(SystemSmsCode $code, $vercode)
    {
        if (empty($vercode)) {
            throw new \Exception('必须填写验证码');
        }
        if ($code->code != $vercode) {
            $code->num += 1;
            if ($code->save() === false) {
                Logger::message('更新验证码错误次数失败:' . $code->id, $code->getMessages());
            }
            return false;
        }
        return true;
    }

    /**
     * 检查是否是一个合法的接收账号
     * @param string $receiver 接收账号
     * @return bool 是否为 email账号
     * @throws \Exception 如果不是手机号或者电子邮箱
     */
    public static function mustReceiver(string $receiver): bool
    {
        if (empty($receiver)) {
            throw new \Exception('接收账号不能为空');
        }
        $isPhone = Validate::isPhone($receiver);
        $isEmail = Validate::isEmail($receiver);
        if (!$isPhone && !$isEmail) {
            throw new \Exception('只支持手机号或电子邮箱');
        }
        return $isEmail;
    }


    /**
     * 检验注册验证码
     * @param string $account
     * @param string $vercode
     * @return SystemSmsCode
     * @throws \Exception
     */
    public static function checkRegisterCode(string $account, string $vercode)
    {
        if (empty($vercode)) {
            throw new \Exception('必须填写验证码');
        }
        $isEmail = self::mustReceiver($account);
        $condition = [
            'user_id' => 0, 'kind' => 'register', 'receiver' => $account,
        ];
        $code = self::getLast($condition);
        if (!$code || !$code->isActive()) {
            throw new \Exception('验证码不存在或者已经过期了');
        }
        if (!self::compare($code, $vercode)) {
            throw new \Exception('验证码错误');
        }
        return $code;
    }

    /**
     * 发送注册验证码
     * @param string $account 账号
     * @return bool
     * @throws \Phalcon\Logger\Exception
     */
    public static function sendRegisterCode(string $account)
    {
        $isEmail = self::mustReceiver($account);
        // 检查发送
        $condition = [
            'user_id' => 0, 'kind' => 'register', 'receiver' => $account,
        ];
        // 从库中检查最近发送
        $history = self::getLast($condition);
        if ($history && $history->isActive()) {
            return true;
        }

        $count = self::todayCount($condition);
        if ($count > Config::MaxRegisterCodeNum) {
            throw new \Exception('每天至多发送' . Config::MaxRegisterCodeNum . '次注册验证码');
        }

        $mSer = MessageService::getInstance();
        $code = SystemSmsCode::insertOne($condition, $mSer);

        if ($isEmail) {
            $email = $mSer->email();
            $rst = $email->useSingleSendMailRequest()
                ->setSubject('账号注册验证码')
                ->setAddress($account)
                ->setHtmlBody('您好，当前账号注册验证码为：' . $code->code)
                ->send();
            return self::updateSendStatus($email, $code, $rst);
        } else {
            $sms = $mSer->sms();
            $rst = $sms->addTemplateCode(self::smsTemplateCode('register'))
                ->addPhoneNumber($account)
                ->addTemplateParams(['code' => $code->code])
                ->send();
            return self::updateSendStatus($sms, $code, $rst);
        }
    }

    /**
     * 发送修改账号验证码
     * @param int $userId 用户 ID
     * @param string $account 新的账号
     * @return bool
     * @throws \Phalcon\Logger\Exception
     */
    public static function sendChangeAccountCode(int $userId, string $account)
    {
        $isEmail = self::mustReceiver($account);

        // 从库中检查最近发送
        $condition = [
            'user_id' => $userId, 'kind' => 'change-account',// 'receiver' => $account,
        ];
        $history = self::getLast($condition);
        if ($history && $history->isActive()) {
            return true;
        }

        $count = self::todayCount($condition);
        if ($count >= Config::MaxChangeAccountCodeNum) {
            throw new \Exception('每天至多发送' . Config::MaxChangeAccountCodeNum . '次账号修改验证码');
        }
        $condition['receiver'] = $account;
        $mSer = MessageService::getInstance();
        $code = SystemSmsCode::insertOne($condition, $mSer);

        if ($isEmail) {
            $email = $mSer->email();
            $rst = $email->useSingleSendMailRequest()
                ->setSubject('账号修改验证码')
                ->setAddress($account)
                ->setHtmlBody('您好，当前修改邮箱账号验证码为：' . $code->code)
                ->send();
            return self::updateSendStatus($email, $code, $rst);
        } else {
            $sms = $mSer->sms();
            $rst = $sms->addTemplateCode(self::smsTemplateCode('change_account'))
                ->addPhoneNumber($account)
                ->addTemplateParams(['code' => $code->code])
                ->send();

            return self::updateSendStatus($sms, $code, $rst);
        }
    }

    /**
     * 修改账号验证码校验
     * @param int $userId
     * @param string $account
     * @param string $vercode
     * @return SystemSmsCode
     * @throws \Exception
     */
    public static function checkChangeAccountCode(int $userId, string $account, string $vercode): SystemSmsCode
    {
        if (empty($vercode)) {
            throw new \Exception('必须填写验证码');
        }
        $condition = [
            'user_id' => $userId,
            'kind' => 'change-account',
            'receiver' => $account,
        ];
        $code = self::getLast($condition);
        if (!$code || !$code->isActive()) {
            throw new \Exception('验证码不存在或者已经过期');
        }
        if (!self::compare($code, $vercode)) {
            throw new \Exception('验证码错误');
        }
        return $code;
    }

    /**
     * 将验证码标记为已使用
     * @param SystemSmsCode $code
     * @return void
     * @throws \Exception
     */
    public static function done(SystemSmsCode $code)
    {
        $code->status = SystemSmsCode::StatusDone;
        if ($code->save() === false) {
            Logger::message('更新验证码状态错误', $code->getErrors());
        }
    }

    public static function sendLoginCode(string $account)
    {
        $isEmail = self::mustReceiver($account);
        // 检查发送
        $condition = [
            'user_id' => 0, 'kind' => 'login', 'receiver' => $account,
        ];
        // 检查最新发送
        $history = self::getLast($condition);
        if ($history && $history->isActive()) {
            return true;
        }

        $count = self::todayCount($condition);
        if ($count > Config::MaxSigninCodeNum) {
            throw new \Exception('每天至多发送' . Config::MaxSigninCodeNum . '次登录验证码');
        }

        $mSer = MessageService::getInstance();
        $code = SystemSmsCode::insertOne($condition, $mSer);

        if ($isEmail) {
            $email = $mSer->email();
            $rst = $email->useSingleSendMailRequest()
                ->setSubject('账号登录验证码')
                ->setAddress($account)
                ->setHtmlBody('您好，您的登录验证码为：' . $code->code)
                ->send();
            return self::updateSendStatus($email, $code, $rst);
        } else {
            $sms = $mSer->sms();
            $rst = $sms->addTemplateCode(self::smsTemplateCode('login'))
                ->addPhoneNumber($account)
                ->addTemplateParams(['code' => $code->code])
                ->send();
            return self::updateSendStatus($sms, $code, $rst);
        }
    }

    public static function checkLoginCode(string $account, string $vercode)
    {
        if (empty($vercode)) {
            throw new \Exception('必须填写验证码');
        }
        $condition = [
            'user_id' => 0, 'kind' => 'login', 'receiver' => $account,
        ];
        $code = self::getLast($condition);
        if (!$code || !$code->isActive()) {
            throw new \Exception('验证码不存在或者已经过期');
        }
        if (!self::compare($code, $vercode)) {
            throw new \Exception('验证码错误');
        }
        return $code;
    }

    public static function sendForgotPasswordEmail($email)
    {
        if (!Validate::isEmail($email)) {
            throw new \Exception('不是有一个有效电子邮箱地址');
        }
        if ($row = SystemUser::queryBuilder()
            ->where(['email' => $email, 'email_valid' => 1])
            ->columns(['id', 'status'])->findFirst(true)) {

            $condition = ['user_id' => $row['id'], 'kind' => 'forgot', 'receiver' => $email];
            // 检查最新发送
            $history = self::getLast($condition);
            if ($history && $history->isActive(3600 * 2)) {
                return true;
            }

            $count = self::todayCount($condition);
            if ($count > Config::MaxResetPasswordCodeNum) {
                throw new \Exception('每天至多发送' . Config::MaxResetPasswordCodeNum . '次重置密码邮件');
            }

            $mSer = MessageService::getInstance();
            $code = SystemSmsCode::insertOne($condition, $mSer);

            $link = \Phax\Utils\MyUrl::getMultiURL('tao/auth/password', [
                'type' => 'forgot',
                'sign' => md5($code->code . $row['id']),
                'id' => $code->id,
            ]);

            $body = <<<HTML
<p>您申请了重置登录密码！请在2小时内点击此链接以完成重置。</p>
<p><a href="{$link}" target="_blank">{$link}</a></p>
HTML;

            $engine = $mSer->email();
            $rst = $engine->useSingleSendMailRequest()
                ->setSubject('重置密码')
                ->setAddress($email)
                ->setHtmlBody($body)
                ->send();
            return self::updateSendStatus($engine, $code, $rst);
        } else {
            throw new \Exception('没有找到符合条件的账号');
        }
    }

    /**
     * @param int $id 记录 ID
     * @param string $sign 签名
     * @return SystemSmsCode
     * @throws \Exception
     */
    public static function checkForgotPasswordEmail(int $id, string $sign)
    {
        if ($id < 1) {
            throw new \Exception('验证码 ID 不能为空');
        }
        /**
         * @var $code SystemSmsCode
         */
        $code = SystemSmsCode::findFirst($id);
        if (!$code || !$code->isActive(3600 * 2)) {
            throw new \Exception('重置密码验证码不存在或过期');
        }

        if ($sign != md5($code->code . $code->user_id)) {
            throw new \Exception('签名参数不匹配');
        }
        if ($code->user_id < 1){
            throw new \Exception('验证码所绑定用户丢失');
        }
        return $code;
    }
}