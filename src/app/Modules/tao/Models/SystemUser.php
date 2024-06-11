<?php

namespace App\Modules\tao\Models;

use App\Modules\tao\BaseModel;
use App\Modules\tao\Config\Config;
use App\Modules\tao\Config\Data;
use App\Modules\tao\Services\UserService;
use Phax\Db\QueryBuilder;
use Phax\Support\Validate;
use Phax\Traits\SoftDelete;
use Phax\Utils\MyHelper;

class SystemUser extends BaseModel
{
    use SoftDelete;

    public string $role_ids = ''; // 角色权限ID
    public string $seed = ''; // 随机数

    public string $password = ''; // 登录密码
    public string $email = ''; // 登录邮箱（唯一）
    public int $email_at = 0; // 修改登录邮箱时间
    public int $email_valid = 0; // 电子邮箱是否验证
    public string $phone = ''; // 手机号（唯一）
    public int $phone_at = 0;
    public int $phone_valid = 0; // 手机号是否验证

    public string $nickname = ''; // 用户昵称
    public string $head_img = ''; // 头像
    public string $signature = ''; // 签名
    public string $binds = '[]'; // 第三方账号绑定
    public int $status = 1; // 状态:1启用

    const STATUS_DELETE = 100; // 账号被禁用/删除

    public function checkStatus()
    {
        if ($this->status == self::STATUS_DELETE) {
            throw new \Exception('当前账号已经被禁止登录');
        }
    }

    public function tableTitle(): string
    {
        return '用户';
    }

    public function roleIds(): array
    {
        return $this->role_ids ? explode(',', $this->role_ids) : [];
    }

    /**
     * 待注册的手机号是否合法
     * @throws \Exception
     */
    public function mustUniquePhone(string $phone, bool $assign = false): void
    {
        if ($this->phone == $phone) {
            return;
        }
        if (empty($phone)) {
            throw new \Exception('待检测的手机号不能为空');
        }
        Validate::mustPhone($phone);
        if (QueryBuilder::with($this)
            ->string('phone', $phone)
            ->notEqual("id", $this->id)
            ->exits()) {
            throw new \Exception('手机号重复');
        }
        if ($assign) {
            $this->phone = $phone;
        }
    }


    public function newAccount($account): void
    {
        if (Validate::isEmail($account)) {
            $this->email = $account;
            $this->email_at = time();
            $this->email_valid = 1;
        } elseif (Validate::isPhone($account)) {
            $this->phone = $account;
            $this->phone_at = time();
            $this->phone_valid = 1;
        }
    }

    public function newPassword($password): void
    {
        UserService::mustPassword($password);
        $this->password = security()->hash($password);
    }

    public function checkPassword(string $password, bool $must = true)
    {
        if (empty($password) && $must) {
            throw new \Exception('密码不能为空');
        }
        if (!security()->checkHash($password, $this->password)) {
            throw new \Exception('密码错误');
        }
    }

    /**
     * 检查待注册的电子邮箱是否合法
     * @throws \Exception
     */
    public function mustUniqueEmail(string $email, bool $assign = false): void
    {
        if ($this->email == $email) {
            return;
        }
        Validate::mustEmail($email);
        if (QueryBuilder::with($this)->string('email', $email)
            ->notEqual('id', $this->id)->exits()) {
            throw new \Exception('邮箱地址重复');
        }
        if ($assign) {
            $this->email = $email;
        }
    }

    public function isSuperAdmin(): bool
    {
        return in_array($this->id, Config::superAdminIds());
    }

    public function hasLoginAccount(): bool
    {
        if ($this->phone_valid == 1 && !empty($this->phone)) {
            return true;
        }
        if ($this->email_valid == 1 && !empty($this->email)) {
            return true;
        }
        return false;
    }

    /**
     * 能否修改账号
     * @param string $kind 类型 account|phone|email
     * @param string $account 待检查的账号
     */
    public function mustChangeAccount(string $kind, string $account): void
    {
        if (empty($account)) {
            throw new \Exception('账号不能为空');
        }
        switch ($kind) {
            case 'phone':
                if (!self::enableChangePhoneAt($this->phone_at)) {
                    throw new \Exception('每 30天 才能修改一次电话号码');
                }
                $this->mustUniquePhone($account);
                break;
            case 'email':
                if (!self::enableChangeEmailAt($this->email_at)) {
                    throw new \Exception('每 30天 才能修改一次电子邮箱');
                }
                $this->mustUniqueEmail($account);
                break;
            default:
                throw new \Exception('不支持修改的账号类型');
        }
    }

    public static function enableChangePhoneAt(int $phoneAt): bool
    {
        return $phoneAt == 0 || $phoneAt + 60 * 60 * 24 * 30 < time();
    }

    public static function enableChangeEmailAt(int $emailAt): bool
    {
        return $emailAt == 0 || $emailAt + 60 * 60 * 24 * 30 < time();
    }

    public function getRolesAttr()
    {
        if ($this->role_ids) {
            $ids = explode(',', $this->role_ids);
            $rows = SystemRole::queryBuilder()->inInt('id', $ids)
                ->findColumn(['id', 'title']);
            return array_column($rows, 'title', 'id');
        }
        return [];
    }

    public function addBinds($bind)
    {
        if (!in_array($bind, array_keys(Data::MapBinds))) {
            throw new \Exception('不支持绑定类型');
        }
        if (empty($this->binds)) {
            $this->binds = json_encode([$bind]);
        } else {
            $binds = json_decode($this->binds, true);
            if (!in_array($bind, $binds)) {
                $binds[] = $bind;
            }
            $this->binds = json_encode($binds);
        }
    }

    public function beforeCreate()
    {
        // https://docs.phalcon.io/5.0/en/support-helper#random
        if (empty($this->seed)) {
            $this->seed = MyHelper::random(); // 默认 字母数字，长度8
        }
    }

}