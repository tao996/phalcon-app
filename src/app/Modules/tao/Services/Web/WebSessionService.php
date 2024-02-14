<?php

namespace app\Modules\tao\Services\Web;

use Phax\Mvc\Request;

class WebSessionService
{
    public static function save($key, $data): void
    {
        if (!Request::isApiRequest()) {
            session()->set($key, $data);
        }
    }
}