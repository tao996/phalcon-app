<?php

namespace App\Modules\tao\Models;

use App\Modules\tao\BaseModel;
use App\Modules\tao\Config\Config;
use App\Modules\tao\Services\MessageService;
use Phax\Support\Validate;

/**
 * 验证码
 */
class SystemSmsCode extends BaseModel
{

    const ReceiverKindPhone = 1;
    const ReceiverKindEmail = 2;

    const SendStatusToSend = 0;
    const SendStatusSuccess = 1;
    const SendStatusFailed = 2;

    // 验证码校验状态
    const StatusNew = 1;
    const StatusDone = 2;

    public int $user_id = 0; // 用户 ID
    // 注意 kind + receiver 是复合索引
    public string $kind = ''; // 短信/邮件类型
    public int $status = 0; // 校验状态
    public int $num = 0; // 错误次数
    public string $send_engine = ''; // 发送引擎
    public int $send_status = 0; // 发送状态
    public int $send_at = 0; // 发送时间
    public string $receiver = ''; // 手机号/邮箱
    public int $receiver_kind = 0;
    public string $code = ''; // 验证码
    public string $data = ''; // 额外信息（最多150个字符）
    public string $ip = ''; // ip 地址


    public function isActive(int $seconds = 0)
    {
        return $this->created_at > 0
            && $this->created_at + ($seconds > 0 ? $seconds : Config::VerifyCodeActiveSeconds) > time()
            && $this->num <= Config::VerifyCodeMaxErrorNum
            && $this->send_status == self::SendStatusSuccess
            && $this->status == self::StatusNew;
    }

    public static function insertOne(array $condition, MessageService $mSer)
    {
        if (empty($condition['kind']) || empty($condition['receiver'])) {
            throw new \Exception('必须指定 kind 和 receiver');
        }
        $verifyCode = rand(1000, 9999);
        $code = new SystemSmsCode();
        $isEmail = Validate::isEmail($condition['receiver']);

        $code->assign(array_merge($condition, [
            'status' => SystemSmsCode::StatusNew,
            'send_at' => time(),
            'code' => (string)$verifyCode,
            'ip' => request()->getClientAddress(),
            'receiver_kind' => $isEmail
                ? SystemSmsCode::ReceiverKindEmail
                : SystemSmsCode::ReceiverKindPhone,
        ]));
        if ($code->create() === false) {
            throw new \Exception($code->getFirstError());
        }
        return $code;
    }
}