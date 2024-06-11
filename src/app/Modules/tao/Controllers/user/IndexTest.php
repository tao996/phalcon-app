<?php

namespace App\Modules\tao\Controllers\user;

use App\Modules\tao\Models\SystemSmsCode;
use App\Modules\tao\Models\SystemUser;
use App\Modules\tao\Services\LoginUser;
use Phax\Test\MockController;
use PHPUnit\Framework\TestCase;

class IndexTest extends TestCase
{

    public function testIndexAction()
    {

        $mock = new MockController();
        // indexController 需要预先登录
        LoginUser::getInstance()->loadUserInfo(1);
        /**
         * @var $cc IndexController
         */
        $cc = $mock->create(IndexController::class);
        /**
         * @var $loginUser LoginUser
         */
        $loginUser = $mock->getControllerProperty('loginUser');
        $this->assertTrue($loginUser->user()->id > 0);

        $rst = $cc->indexAction();
        $this->assertEquals('修改资料', $rst['pageTitle']);

        $postData = [
            'head_img' => 'http://a.com/b.jpg',
            'signature' => '您好,晚安'
        ];
        $mock->request->data['isAjax'] = true;
        $mock->request->data['isPost'] = true;
        $mock->request->data['getPost'] = $postData;
        $rst = $cc->indexAction()->getJsonContent();
        $this->assertEquals('保存成功', $rst['msg']);

        $this->assertEquals($postData['head_img'], $loginUser->user()->head_img);

    }

    public function testPhoneCodeAction()
    {
        $mock = new MockController();
        LoginUser::getInstance()->loadUserInfo(1);
        /**
         * @var $cc IndexController
         */
        $cc = $mock->create(IndexController::class);
        /**
         * @var $loginUser LoginUser
         */
        $loginUser = $mock->getControllerProperty('loginUser');

        $postData = [
            'phone' => 'abc',
        ];
        $mock->setPostData($postData);
        $this->assertStringContainsString('不匹配',
            $mock->mustException('phoneCodeAction')
        );

        // 2.
        $loginUser->user()->phone_at = time(); // 最近修改
        $mock->setPostData($postData);
        $this->assertStringContainsString('每年只能',
            $mock->mustException('phoneCodeAction')
        );

        // 3. 不是一个有效的电话号码
        $loginUser->user()->phone_at = 0;
        $this->assertStringContainsString('abc',
            $mock->mustException('phoneCodeAction')
        );

        // 清空
        SystemSmsCode::truncate(true);

        // 发送成功
        $postData['phone'] = '13412345678';
        $mock->setPostData($postData);
        $rst = $mock->getActionResponse($cc->phoneCodeAction());
        $this->assertStringContainsString('已发送', $rst['msg']);

        // 检查是否存在
        $sms = SystemSmsCode::queryBuilder()
            ->where(['user_id' => $loginUser->userId(),
                'receiver' => $postData['phone'],
                'status' => SystemSmsCode::StatusNew,
            ])->findFirst();
        $this->assertTrue($sms['id'] > 0);

        // 再次发送，不会重复发送
        $mock->setPostData($postData);
        $rst = $mock->getActionResponse($cc->phoneCodeAction());
        $this->assertStringContainsString('已发送', $rst['msg']);

        $smsCount = SystemSmsCode::queryBuilder()
            ->where(['user_id' => $loginUser->userId(),
                'receiver' => $postData['phone'],
                'status' => SystemSmsCode::StatusNew,
            ])->count();
        $this->assertEquals(1, $smsCount);

    }

    public function testChangePhoneAction()
    {
        $mock = new MockController();
        LoginUser::getInstance()->loadUserInfo(1);
        /**
         * @var $cc IndexController
         */
        $cc = $mock->create(IndexController::class);
        /**
         * @var $loginUser LoginUser
         */
        $loginUser = $mock->getControllerProperty('loginUser');

        $newPhone = '13445678901';
        if ($loginUser->user()->phone == $newPhone) {
            $newPhone = '13445678910';
        }
        $loginUser->user()->phone_at = 0; // 避免被1年修改1次限制
        // 为手机成功
        $postData['phone'] = $newPhone;

        $mock->setPostData($postData);
        $rst = $mock->getActionResponse($cc->phoneCodeAction());
        $this->assertStringContainsString('已发送', $rst['msg']);

        sleep(1);
        $sms = SystemSmsCode::queryBuilder()
            ->where(['user_id' => $loginUser->userId(),
                'receiver' => $newPhone,
                'status' => SystemSmsCode::StatusNew,
            ])->findFirst();
        $this->assertTrue($sms['id'] > 0);

        $postData = [
            'phone' => $newPhone,
            'vercode' => $sms['code'] . '1', // 错误的验证码
        ];
        $mock->setPostData($postData);

        $msg = $mock->mustException('changePhoneAction');
        $this->assertStringContainsString('验证码', $msg);

        // 验证码错误次数需要+1
        $sms2 = SystemSmsCode::queryBuilder()
            ->where(['id' => $sms['id']])
            ->findFirst();
        $this->assertEquals($sms['num'] + 1, $sms2['num']);

        $postData = [
            'phone' => $newPhone,
            'vercode' => $sms['code'],
        ];
        $mock->setPostData($postData);
        $rst = $mock->getActionResponse($cc->changePhoneAction());
        $this->assertStringContainsString('成功', $rst['msg']);
        // user phone change
        $user = SystemUser::queryBuilder()
            ->where(['id' => $loginUser->userId()])
            ->findFirst();
        $this->assertEquals($newPhone, $user['phone']);
        $this->assertTrue($user['phone_at'] > 0);
        // code status use
        $sms3 = SystemSmsCode::queryBuilder()
            ->where(['id' => $sms['id'], 'receiver' => $newPhone])
            ->findFirst();
        $this->assertEquals(SystemSmsCode::StatusDone, $sms3['status']);

    }

    public function testPasswordAction()
    {
        $mock = new MockController();
        LoginUser::getInstance()->loadUserInfo(1);
        /**
         * @var $cc IndexController
         */
        $cc = $mock->create(IndexController::class);
        $postData = [
            'password' => '1234567',
        ];

        $mock->setPostData($postData);
        $rst = $mock->getActionResponse($cc->passwordAction());
        $this->assertStringContainsString('成功', $rst['msg']);


    }
}