<?php

namespace App\Modules\tao\A0\open\Controllers;

use App\Modules\tao\BaseResponseController;

class OpenController extends BaseResponseController
{
    public function indexAction()
    {
        throw new \Exception('not support');
    }

    /**
     * 网页授权登录（登录成功之后，设置 token）
     */
    public function authAction()
    {
        $kind = $this->request->getQuery('js', 'string'); // tt/wx
        return [];
    }

    /**
     * 隐私政策
     * @link http://localhost:8071/m/tao.mini/open/terms
     */
    public function termsAction()
    {
        $tag = request()->getQuery('tag', 'string', 'boyu');
        $page = \App\Modules\tao\A0\cms\Services\CmsPageService::findFirst($tag, 'terms');
        if (empty($page)) {
            throw new \Exception('隐私政策暂未设置');
        }
        return [
            'id' => $page['id'],
            'title' => $page['title'],
            'content' => $page['content']
        ];
    }

    /**
     * 投稿需知
     * @link http://localhost:8071/m/tao.mini/open/post
     */
    public function postAction()
    {
        return [];
    }
}