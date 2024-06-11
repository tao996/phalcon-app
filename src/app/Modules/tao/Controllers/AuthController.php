<?php

namespace App\Modules\tao\Controllers;

use App\Modules\tao\BaseController;
use App\Modules\tao\Models\SystemUser;
use App\Modules\tao\Services\CaptchaService;
use App\Modules\tao\Services\LoginService;
use App\Modules\tao\Services\RedirectService;
use App\Modules\tao\Services\SmsCodeService;
use App\Modules\tao\Services\UserService;

use Phax\Db\Db;
use Phax\Mvc\Request;
use Phax\Support\Logger;
use Phax\Utils\MyData;

class AuthController extends BaseController
{
    protected array|string $openActions = '*';
    protected string $htmlTitle = '注册登录';

    public function initialize(): void
    {
        parent::prepareInitialize();
        if ($this->isLogin()) {
            RedirectService::read();
        }
    }

    /**
     * 用户密码登录
     */
    public function indexAction()
    {
        if ($this->request->isPost()) {
            $data = Request::getData();

            Request::mustHasSet($data, ['account', 'password', 'captcha']);
            CaptchaService::getInstance()->compare($data['captcha']);

            /**
             * @var $user SystemUser
             */
            $user = null;
            if ($this->isDemo && $data['account'] == 'admin' && $data['password'] == '123456') {
                $user = SystemUser::findFirst(1);
            }
            if (!$user) {
                $isEmail = SmsCodeService::mustReceiver($data['account']);
                $qb = SystemUser::queryBuilder()
                    ->where($isEmail
                        ? ['email' => $data['account'], 'email_valid' => 1]
                        : ['phone' => $data['account'], 'phone_valid' => 1]);

                if ($user = $qb->findFirst(false)) {
                    $user->checkPassword($data['password']);
                    $user->checkStatus();
                } else {
                    return $this->error('请检查您的账号和密码是否正确..');
                }
            }


            $authResp = LoginService::makeLogin($user);
            CaptchaService::getInstance()->destory();
            return $this->success('登录成功', $authResp);
        }
        return [
        ];
    }

    /**
     * 验证码登录
     */
    public function signinAction()
    {
        if ($this->request->isPost()) {
            $data = Request::getData();
            MyData::mustHasSet($data, ['account', 'vercode']);
            $isEmail = SmsCodeService::mustReceiver($data['account']);
            SmsCodeService::checkLoginCode($data['account'], $data['vercode']);

            // 查询用户
            if ($user = SystemUser::queryBuilder()
                ->where([
                    $isEmail ? 'email' : 'phone' => $data['account'],
                    $isEmail ? 'email_valid' : 'phone_valid' => 1
                ])->findFirst(false)) {
                $token = LoginService::makeLogin($user);
            } else {
                return $this->error('没有找到符合条件的账号');
            }

            CaptchaService::getInstance()->destory();
            return $this->success('登录成功', $token);
        }
        return [
        ];
    }

    /**
     * 发送登录验证码
     */
    public function signinCodeAction()
    {
        Request::mustPost();
        $data = Request::getData();
        MyData::mustHasSet($data, ['captcha', 'account']);

        SmsCodeService::mustReceiver($data['account']);
        CaptchaService::getInstance()->compare($data['captcha']);

        // 账号检测
        try {
            UserService::mustCanLogin($data['account']);
        } catch (\Exception $e) {
            Logger::message('登录验证码已发送，请注意查收.', [
                $e->getMessage(), '登录账号检查异常:' . $data['account'],
            ]);
        }

        // 发送验证码
        if (!SmsCodeService::sendLoginCode($data['account'])) {
            return $this->error('发送失败，请稍后再试');
        }
        CaptchaService::getInstance()->destory();
        return $this->success('登录验证码已发送，请注意查收');

    }

    /**
     * 账号注册
     */
    public function signupAction()
    {
        if ($this->request->isPost()) {
            $data = Request::getData();
            MyData::mustHasSet($data, ['account', 'vercode', 'password']);

            UserService::mustAccountString($data['account']);
            UserService::mustCanRegister($data['account']);
            $code = SmsCodeService::checkRegisterCode($data['account'], $data['vercode']);

            // 账号注册
            Db::transaction(function () use ($data, $code) {
                $user = new SystemUser();
                $user->newPassword($data['password']);
                $user->newAccount($data['account']);
                if ($user->save() === false) {
                    Logger::message('账号注册失败，请稍后再试', $user->getErrors());
                }

                SmsCodeService::done($code);
            });

            return $this->success('账号注册成功');
        }
        return [
        ];
    }

    /**
     * 发送账号注册验证码
     */
    public function signupCodeAction()
    {
        Request::mustPost();
        $data = Request::getData();
        MyData::mustHasSet($data, ['captcha', 'account']);

        UserService::mustAccountString($data['account']);
        CaptchaService::getInstance()->compare($data['captcha']);

        // TODO : ip 地址检查注册

        try {
            UserService::mustCanRegister($data['account']);
        } catch (\Exception $e) {
            Logger::message('注册验证码已发送，请注意查收', [
                $e->getMessage(), '注册账号检查:' . $data['account'],
            ]);
        }

        // 发送验证码
        if (!SmsCodeService::sendRegisterCode($data['account'])) {
            return $this->error('发送失败，请稍后再试');
        }

        CaptchaService::getInstance()->destory();
        return $this->success('验证码已发送，请注意查收');
    }

    /**
     * 重置密码
     */
    public function forgotAction()
    {
        if ($this->request->isPost()) {
            $data = Request::getData();
            MyData::mustHasSet($data, ['account', 'captcha']);

            CaptchaService::getInstance()->compare($data['captcha']);
            SmsCodeService::sendForgotPasswordEmail($data['account']);

            return $this->success('重置密码邮件已发送，请注意查收');
        }
        return [
        ];
    }

    /**
     * 邮件重置密码
     * @throws \Exception
     */
    public function passwordAction()
    {
        $data = $this->request->getQuery();
        MyData::mustHasSet($data, ['type', 'sign', 'id']);
        if ('forgot' != $data['type']) {
            throw new \Exception('参数错误');
        }
        $code = SmsCodeService::checkForgotPasswordEmail($data['id'], $data['sign']);

        if ($this->request->isPost()) {
            $d2 = $this->request->getPost();
            MyData::mustHasSet($d2, ['password']);
            UserService::mustPassword($d2['password']);
            $user = UserService::mustGetUser(['id' => $code->user_id]);
            $user->newPassword($d2['password']);
            if ($user->save() === false) {
                return $this->error('重置密码失败');
            }

            SmsCodeService::done($code);
            return $this->success('重置密码成功');
        }

        return [
        ];
    }


}