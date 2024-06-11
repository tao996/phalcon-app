<?php

namespace App\Http\A0\Sub\Controllers\sub1;

use Phalcon\Mvc\Controller;

/**
 * @rbac ({})
 */
class BBQController extends Controller
{
    /**
     * @rbac ({})
     * @return void
     */
    public function sayAction()
    {
        dd('just for test RBAC');
    }
}