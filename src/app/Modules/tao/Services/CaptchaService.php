<?php

namespace App\Modules\tao\Services;

use App\Modules\tao\sdk\captcha\ImageCaptcha;
use Phax\Traits\Singleton;

/**
 * 验证码
 * @link https://www.google.com/recaptcha/about/ 谷歌验证码
 * @link https://forum.phalcon.io/discussion/10631/image-verification-code
 */
class CaptchaService
{
    use Singleton;

    private string $key = 'captcha';

    public function __construct()
    {
    }

    private function secret($text): string
    {
        return substr(md5(strtolower($text) . $this->key), 10, 10);
    }

    public function output()
    {
        $captcha = new ImageCaptcha();
        $captcha->create();
        session()->set($this->key, $this->secret($captcha->getText()));
        $captcha->output();
        // exit;
    }

    public function compare($code)
    {
        if (empty($code)) {
            throw new \Exception('必須填寫驗證碼', 200);
        }
        $actual = session()->get($this->key);
        if (empty($actual) || strlen($actual) < 4) {
            throw new \Exception('验证码不存在');
        }
        $expect = $this->secret($code);
        if ($actual != $expect) {
            throw new \Exception('驗證碼錯誤', 200);
        }
    }

    public function destory()
    {
        session()->remove($this->key);
    }
}