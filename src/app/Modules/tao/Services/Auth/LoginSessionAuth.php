<?php

namespace app\Modules\tao\Services\Auth;

use app\Modules\tao\Models\SystemUser;
use Phalcon\Session\Bag;
use Phax\Foundation\Application;

class LoginSessionAuth implements LoginAuth
{

    private function session(): Bag
    {
        static $session = null;
        if (is_null($session)) {
            $session = new \Phalcon\Session\Bag(session(), __CLASS__);
            $session->setDI(Application::di());
        }
        return $session;
    }

    public function __construct(public string $key = 'user')
    {

    }

    public function getUser(): SystemUser|null
    {
        if ($this->hasLoginKey()) {
            $data = $this->session()->get($this->key);
            $user = new SystemUser();
            $user->assign($data);
            return $user;
        }
        return null;
    }

    public function saveUser(array $user): mixed
    {
        if (!empty($user)) {
            unset($user['password'], $user['deleted_at']);
            $this->session()->set($this->key, $user);
            return true;
        }
        return false;
    }

    public function destroy(): void
    {
        $this->session()->clear();
        session()->destroy();
    }

    public function hasLoginKey(): bool
    {
        return $this->session()->has($this->key);
    }
}