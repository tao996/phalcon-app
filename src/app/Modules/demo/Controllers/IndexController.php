<?php

namespace App\Modules\demo\Controllers;

use Phax\Mvc\Controller;

class IndexController extends Controller
{
    /**
     * 模型关联
     * @link http://localhost:8071/api/m/demo
     * @return array
     */
    public function indexAction()
    {
        return [];
    }

    public function helloAction($name = 'phalcon')
    {
        return ['name' => $name];
    }
}