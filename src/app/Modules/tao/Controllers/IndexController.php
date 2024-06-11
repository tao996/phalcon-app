<?php

namespace App\Modules\tao\Controllers;

use App\Modules\tao\BaseController;
use App\Modules\tao\Services\UserMenuService;
use Phax\Mvc\Request;

/**
 * 后台框架
 */
class IndexController extends BaseController
{
    public bool $disableUpdateActions = true;
    protected array|string $userActions = '*';

    public function indexAction()
    {
        $this->disabledMainLayout();
        $ms = new UserMenuService($this->loginUser->user());
        $data = [
            'menuTree' => $ms->getMenuTree()
        ];
        return Request::isApiRequest() ? $this->success('', $data) : $data;
    }

    /**
     * 后台首页：欢迎界面
     * @return array
     */
    public function welcomeAction()
    {
        return [];
    }
}