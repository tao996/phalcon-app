<?php

namespace App\Http\A0\Sub\Controllers\sub1;

use Phalcon\Mvc\Controller;

class MeController extends Controller
{
    public function sayAction()
    {
        $this->view->setVars([
            'name' => 'ME~~~~'
        ]);
    }
}