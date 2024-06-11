<?php

namespace App\Modules\tao\Config;

class Config
{

    public static function superAdminIds(): array
    {
        return config('app.superAdmin')->toArray();
    }


    /**
     * 数据表前缀
     */
    const TABLE_PREFIX = 'tao_';

    /**
     * 验证码配置
     */
    const VerifyCodeActiveSeconds = 900; // 15 分钟内有效
    const VerifyCodeMaxErrorNum = 3; // 输入错误3次即失效
    const MaxChangeAccountCodeNum = 3; // 用户修改手机号+电子邮件每天允许发送验证码数量
    const MaxRegisterCodeNum = 3; // 每个注册账号每天允许发送的验证码数量
    const MaxSigninCodeNum = 3; // 验证码登录的次数
    const MaxResetPasswordCodeNum = 3;
    /**
     * 登录后台后默认显示的界面
     */
    const IndexWelcome = 'tao/index/welcome';
}