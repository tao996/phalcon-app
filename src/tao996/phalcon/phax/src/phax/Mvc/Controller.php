<?php

namespace Phax\Mvc;


class Controller extends \Phalcon\Mvc\Controller
{
    /**
     * @var bool 是否启用自动响应
     */
    protected bool $autoResponse = true;
    /**
     * @var string 设置页面标题（只对 view 渲染有效）
     */
    protected string $pageTitle = '';
    /**
     * 是否以 json 方式返回
     * @var bool
     */
    protected bool $jsonResponse = false;


    /**
     * 禁用控制器布局 views/layouts/controller.phtml
     * @return void
     */
    protected function disabledControllerLayout(): void
    {
        view()->disableLevel(\Phalcon\Mvc\View::LEVEL_LAYOUT);
    }

    /**
     * 禁用主布局 views/index.phtml
     * @return void
     */
    protected function disabledMainLayout(): void
    {

        view()->disableLevel(\Phalcon\Mvc\View::LEVEL_MAIN_LAYOUT);
    }

    /**
     * @param mixed $data 控制器返回的数据
     * @return bool 默认返回 true，表示后续继续处理自动响应
     */
    protected function beforeJsonResponse(mixed &$data): bool
    {
        return true;
    }

    /**
     * @param mixed $data 控制器返回的数据
     * @return bool 默认返回 true，表示后续继续处理自动响应
     */
    protected function beforeViewResponse(mixed &$data): bool
    {
        return true;
    }

    protected function doResponse(bool $isApi, mixed $data)
    {
        if ($isApi) {
            return \json($data);
        } else {
            if ($this->pageTitle) {
                $data['pageTitle'] = $this->pageTitle;
            }
            return \view($data);
        }
    }

    /**
     * 处理响应，如果有自己的格式要求，则可以重写这部分的内容
     * @param \Phalcon\Mvc\Dispatcher $dispatcher
     * @return void
     */
    public function afterExecuteRoute(\Phalcon\Mvc\Dispatcher $dispatcher): void
    {
        if ($this->response->isSent()) {
            return;
        }
        if ($this->autoResponse) {
            $data = $dispatcher->getReturnedValue() ?: [];
            // 获取控制器响应内容，并根据请求样式判断数据响应方式
            if (Request::isApiRequest() || $this->jsonResponse) {
                if ($this->beforeJsonResponse($data)) {
                    $this->doResponse(true, $data);
                }
            } else {
                // 必须存在模板才会有输出内容
                if ($this->beforeViewResponse($data)) {
                    $this->doResponse(false, $data);
                }
            }
        }
    }
}