<?php

namespace Phax\Mvc;


class Controller extends \Phalcon\Mvc\Controller
{
    /**
     * @var bool 是否启用自动响应
     */
    protected bool $autoResponse = true;
    /**
     * 是否以 json 方式返回
     * @var bool
     */
    protected bool $jsonResponse = false;

    /**
     * 绑定到视图的数据（不会在 api 中返回）
     * @var array
     */
    protected array $viewData = [];
    /**
     * 接口中返回的数据将会绑定到此 ['row'=>actionApiReturn]
     * @var string
     */
    protected string $actionResponseDataName = 'row';

    public function addViewData($name, $value): self
    {
        $this->viewData[$name] = $value;
        return $this;
    }

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
            $this->view->setVars($this->viewData, true);
            if (is_scalar($data)) {
                $this->view->setVar('message', $data);
            } else {
                $this->view->setVar($this->actionResponseDataName, $data);
            }
            return \view();
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
            $data = $dispatcher->getReturnedValue() ?: []; // 接口返回的数据
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


    protected function isViewRequest(): bool
    {
        return !Request::isApiRequest();
    }

    public function isApiRequest(): bool
    {
        return Request::isApiRequest();
    }
}