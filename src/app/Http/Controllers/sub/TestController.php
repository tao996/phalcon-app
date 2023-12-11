<?php

namespace App\Http\Controllers\sub;

use Phalcon\Mvc\Controller;

/**
 * @rbac ({title:'Test1'})
 */
class TestController extends Controller
{
    /**
     * @rbac ({title:'ABC'})
     */
    public function abcAction()
    {
        $this->view->setVars([
            'data' => 'ABC',
        ]);
    }
}