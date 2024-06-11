<?php

namespace App\Modules\tao\Controllers\user;

use App\Modules\tao\BaseController;
use App\Modules\tao\Models\SystemUser;
use App\Modules\tao\Services\LoginUser;
use App\Modules\tao\Services\UserMenuService;
use App\Modules\tao\Services\SecretService;
use App\Modules\tao\Services\SmsCodeService;

use Phax\Db\Db;
use Phax\Mvc\Request;
use Phax\Support\Logger;
use Phax\Utils\MyData;

/**
 * 用户控制器
 */
class IndexController extends BaseController
{
    protected array $allowModifyFields = ['status', 'nickname', 'head_img', 'signature'];
    protected array|string $userActions = '*';

    /**
     * 基本资料
     */
    public function indexAction()
    {
        $user = $this->loginUser->user();

        if ($this->request->isPost()) {
            $data = Request::getData();
            $user->head_img = SecretService::innerURL(MyData::getString($data, 'head_img'));
            $user->signature = MyData::getString($data, 'signature');
            if ($user->save()) {
                LoginUser::getInstance()->updateUserInfo([
                    'head_img' => $user->head_img,
                    'signature' => $user->signature,
                ]);
                return $this->success('保存成功');
            } else {
                return $this->error($user->getErrors());
            }
        }
        return [
            'roles' => $user->roles,
        ];
    }

    /**
     * 修改手机号
     */
    public function changePhoneAction()
    {
        if ($this->request->isPost()) {
            $data = Request::getData();
            Request::mustHasSet($data, ['phone', 'vercode']);

            $code = SmsCodeService::checkChangeAccountCode($this->loginUser->userId(),
                $data['phone'], $data['vercode']);

            $user = $this->loginUser->user();
            Db::transaction(function () use ($user, $data, $code) {
                $user->phone = $data['phone'];
                $user->phone_at = time();
                $user->phone_valid = 1;
                if ($user->save() === false) {
                    Logger::message('修改手机号失败', $user->getErrors());
                }
                SmsCodeService::done($code);
            });
            $this->loginUser->updateUserInfo($user->toArray());
            return $this->success('修改手机号成功');
        }

        return [
        ];
    }

    /**
     * 发送手机验证码
     * @throws \Exception
     */
    public function phoneCodeAction()
    {
        $data = Request::getData();
        Request::mustHasSet($data, ['phone']);
        $this->loginUser->user()->mustChangeAccount('phone', $data['phone']);

        if (SmsCodeService::sendChangeAccountCode(
            $this->loginUser->userId(),
            $data['phone']
        )) {
            return $this->success('验证码已发送');
        }

        return $this->error('发送失败，请稍后再试');
    }

    /**
     * 修改登录邮箱
     * @throws \Phalcon\Logger\Exception
     */
    public function changeEmailAction()
    {
        if ($this->request->isPost()) {
            $data = Request::getData();
            Request::mustHasSet($data, ['email', 'vercode']);

            $code = SmsCodeService::checkChangeAccountCode($this->loginUser->userId(),
                $data['email'], $data['vercode']);

            $user = $this->loginUser->user();
            Db::transaction(function () use ($user, $data, $code) {
                $user->email = $data['email'];
                $user->email_at = time();
                $user->email_valid = 1;
                if ($user->save() === false) {
                    Logger::message('修改邮箱失败', $user->getErrors());
                }
                SmsCodeService::done($code);
            });
            $this->loginUser->updateUserInfo($user->toArray());
            return $this->success('修改邮箱成功');

        }

        return [
        ];
    }

    /**
     * 发送邮箱验证码
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface|null
     * @throws \Phalcon\Logger\Exception
     */
    public function emailCodeAction()
    {
        $data = Request::getData();
        Request::mustHasSet($data, ['email']);
        $this->loginUser->user()->mustChangeAccount('email', $data['email']);

        if (SmsCodeService::sendChangeAccountCode($this->loginUser->userId(),
            $data['email'])) {
            return $this->success('验证码已发送');
        }
        return $this->error('发送失败，请稍后再试');
    }

    /**
     * 用户菜单
     */
    public function menuAction()
    {
        $ms = new UserMenuService($this->loginUser->user());
        $data = [
            'logoInfo' => [
                'title' => config('app.name'),
                'image' => config('app.logo'),
                'href' => url('tao')
            ],
            'homeInfo' => $ms->getHomeInfo(),
            'menuInfo' => $ms->getMenuTree(),
        ];

        return \json($data);
    }

    /**
     * 修改密码
     */
    public function passwordAction()
    {
        $user = SystemUser::findFirst($this->loginUser->userId());
        if ($this->request->isPost()) {
            $password = $this->request->getPost('password');
            $user->newPassword($password);
            if ($user->save()) {
                return $this->success('修改密码成功');
            } else {
                return $this->error('修改密码失败');
            }
        }
        $this->htmlTitle = '修改密码';
        return [
        ];
    }

    /**
     * 清除个人缓存
     */
    public function clearAction()
    {
        $this->loginUser->clearCache();
        return $this->success('清除缓存成功');
    }

    /**
     * 退出登录
     */
    public function logoutAction()
    {
        $this->loginUser->logout();
        return $this->success('退出登录成功', '/');
    }


}