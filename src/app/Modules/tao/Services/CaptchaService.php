<?php

namespace app\Modules\tao\Services;

use app\Modules\tao\sdk\captcha\ImageCaptcha;
use Phax\Traits\Singleton;
/**
 * 验证码
 * @link https://www.google.com/recaptcha/about/ 谷歌验证码
 * @link https://forum.phalcon.io/discussion/10631/image-verification-code
 */
class CaptchaService
{
    use Singleton;

    private $engine;
    private $key = 'captcha';

    public function __construct()
    {
        $this->engine = session();
    }

    public function output()
    {
        $captcha = new ImageCaptcha();
        $captcha->create();
        session()->set($this->key, strtolower($captcha->getText()));
        $captcha->output();
        // exit;
    }

    public function compare($code)
    {
        if (empty($code)) {
            throw new \Exception('必須填寫驗證碼', 200);
        }
        $actual = $this->engine->get($this->key);
        if ($actual != strtolower($code)) {
            throw new \Exception('驗證碼錯誤', 200);
        }
    }

    public function destory()
    {
        $this->engine->remove($this->key);
    }
}