<?php

namespace App\Http\Controllers;

use Phalcon\Mvc\Controller;

/**
 * @rbac ({title:'Home',close:1})
 */
class IndexController extends Controller
{
    /**
     * @rbac ({title:'Index'})
     */
    public function indexAction()
    {
//        return 'welcome to phalcon';
    }

    public function aboutAction(string $name = 'Phalcon', int $age = 0)
    {

        view()->setVars([
            'name' => $name,
            'age' => $age
        ]);
    }
}