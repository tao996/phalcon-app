<?php

namespace Phax\Support\I18n;

use Phax\Foundation\Router;
use Phax\Utils\MyHelper;

/**
 * @link https://docs.phalcon.io/5.0/en/request#i18n
 */
class Lang
{
    public static function locale()
    {
        return \Locale::acceptFromHttp($_SERVER['HTTP_ACCEPT_LANGUAGE']);
    }

// https://docs.phalcon.io/5.0/en/i18n
    public static function message(string $lang, string $pattern, array $value)
    {
        $formatter = new \MessageFormatter($lang, $pattern);
        return $formatter->format($value);
    }

    /**
     * 对文本进行替换
     * @link https://docs.phalcon.io/5.0/en/support-helper#interpolate
     * @param string $message ':date (YYYY-MM-DD)'
     * @param array $placeholders ['date'  => '2020-09-09']
     * @param string $leftToken 左分割符，为了保持跟 Laravel 之类的兼容，默认使用 : 号
     * @param string $rightToken
     * @return string '2020-09-09 (YYYY-MM-DD)'
     */
    public static function interpolate(string $message, array $placeholders = [], string $leftToken = ":", string $rightToken = ""): string
    {
        return MyHelper::interpolate($message, $placeholders, $leftToken, $rightToken);
    }

    /**
     * 获取当前设置的的语言
     * @return string
     */
    public static function getBaseLanguage(): string
    {
        if ($language = request()->getQuery('language')) {
            return $language;
        }
        if ($language = Router::getMatchLanguage()) { // 网址中设置的语言
            return $language;
        }
        return dispatcher()->getParam('language') ?: config('app.locale', 'en');
    }


}