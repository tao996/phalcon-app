<?php

namespace App\Modules\tao\Services;

use Phax\Mvc\Response;
use Phax\Support\Exception\BlankException;

class RedirectService
{
    public static string $keyRedirect = '_redirect';


    public static function save(string $redirect, array $drivers = ['session']): bool
    {
        if (!empty($redirect)) {
            if (in_array('cookie', $drivers)) {
                cookies()->set(self::$keyRedirect, $redirect);
//                cookies()->send(); 你需要自己调用
                return true;
            } elseif (in_array('session', $drivers)) {
                session()->set(self::$keyRedirect, $redirect);
                return true;
            }
        }
        return false;
    }

    public static function query(string $defaultValue = ''): string
    {
        return request()->getQuery(self::$keyRedirect,null,$defaultValue);
    }

    /**
     * 回调地址
     * @param bool $response 是否直接跳转
     * @return string
     */
    public static function read(bool $response = true, array $drivers = ['session']): string
    {
        $redirect = request()->getQuery(self::$keyRedirect);

        if (empty($redirect) && in_array('cookie', $drivers)) {
            if (cookies()->has(self::$keyRedirect)) {
                $redirect = cookies()->get(self::$keyRedirect)->getValue();
                cookies()->delete(self::$keyRedirect);
            }
        }
        if (empty($redirect) && in_array('session', $drivers)) {
            if (session()->has(self::$keyRedirect)) {
                $redirect = session()->get(self::$keyRedirect, '', true);
            }
        }

        $href = $redirect ? urldecode($redirect) : url('tao/index/index');
        if ($response) {
            Response::redirect($href);
            throw new BlankException();
        }
        return $href;
    }
}