<?php

namespace app\Modules\tao\Services\Auth;

use app\Modules\tao\Models\SystemUser;
use app\Modules\tao\Services\JwtService;
use Phax\Utils\Data;

class LoginJwtAuth implements LoginAuth
{
    private array $config = [
        'key' => 'Authorization',
        'audience' => 'app'
    ];

    private function jwt(): JwtService
    {
        static $jwt = null;
        if (is_null($jwt)) {
            $jwt = new JwtService();
            $jwt->setAudience($this->config['audience']);
        }
        return $jwt;
    }

    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->config, $config);
    }

    public function hasLoginKey(): bool
    {
        return request()->hasHeader($this->config['key']);
    }

    public function getUser(): SystemUser|null
    {
        if ($this->hasLoginKey()) {
            $data = $this->jwt()->parser(request()->getHeader($this->config['key']));
            if ($id = Data::getInt($data, 'id')) {
                return SystemUser::findFirst($id);
            }
        }
        return null;
    }

    public function saveUser(array $user): string
    {
        if (!isset($user['id']) || $user['id'] < 1) {
            throw new \Exception('用户 id 不能为空');
        }
        return $this->jwt()->getToken(['id' => $user['id']]);
    }

    public function destroy(): void
    {
    }
}