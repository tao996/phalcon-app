<?php

namespace app\Modules\tao\Config;

class Config
{
    /**
     * 授权方式，默认为空，只有超级管理员才能访问
     */
    const ACCESS = '';
    /**
     * 超级管理员用户 ID 列表，不受权限控制；
     * 注意：写在前面的 user_id 可以修改写在后面的 user_id 的记录；
     * 比如 [1,2] 同样的超级管理员；但 1 可以修改 2 的记录，2 不能修改 1 的记录；
     * 如果是 [2,1] 是 2 可以修改 1 的记录，1 不能修改 2 的记录
     */
    const SUPER_ADMIN_IDS = [1];
    /**
     * 首页的 PID
     */
    const HOME_PID = 99999999;
    /**
     * 数据表前缀
     */
    const TABLE_PREFIX = 'tao_';

    /**
     * 数据表字段统一的常量（不要使用 0，前端可能传输0来表示查询全部状态）
     */
    const STATUS_ACTIVE = 1;
    const STATUS_DISABLED = 2; // 禁用

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