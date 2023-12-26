<?php

namespace demo1\Controllers;

use Phax\Mvc\Controller;

class HomeController extends Controller
{
    public function indexAction()
    {
        return ['title' => 'demo1 home.index'];
    }
}