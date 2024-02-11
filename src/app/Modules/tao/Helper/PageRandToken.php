<?php

namespace app\Modules\tao\Helper;

use Phax\Support\Facades\Helper;

class PageRandToken
{
    public bool $skip = false;
    public string $defaultToken = '1000';

    public function __construct(private readonly string $name)
    {
    }

    public function create(): string
    {
        if ($this->skip) {
            return $this->defaultToken;
        }
        $token = Helper::random();
        session()->set($this->name, $token);
        return $token;
    }

    public function get()
    {
        if ($this->skip) {
            return $this->defaultToken;
        }
        return session()->get($this->name);
    }

    /**
     * 比较提交的 token
     * @param string $token 用户提交过来的数据
     * @return bool
     * @throws \Exception
     */
    public function compare($token): bool
    {

        if ($this->skip) {
            if ($token != $this->defaultToken) {
                throw new \Exception('表单 token 不匹配，请刷新后再试');
            }
            return true;
        }
        $v = session()->get($this->name, '-1', false);
        $match = $v == $token;
        if (!$match) {
            throw new \Exception('表单 token 不匹配，请刷新后再试');
        }
        return true;
    }

    public function remove()
    {
        if ($this->skip) {
            return;
        }
        session()->remove($this->name);
    }


}