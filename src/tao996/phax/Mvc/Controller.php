<?php

namespace Phax\Mvc;


use Phax\Support\Exception\BlankException;

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
     * @var string 语言
     */
    protected string $language = 'en';
    /**
     * 接口中返回的数据将会绑定到此 ['api'=>actionApiReturn]
     * @var string
     */
    protected string $actionResponseDataName = 'api';

    public function initialize(): void
    {
        $this->language = Request::getBestLanguage();
    }

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
     * 对 api 接口返回数据进行处理
     * @param mixed $data 控制器返回的数据
     */
    protected function beforeJsonResponse(mixed &$data): void
    {
    }

    /**
     * 对视图数据进行处理
     * @param mixed $data 控制器返回的数据
     */
    protected function beforeViewResponse(mixed &$data): void
    {
    }

    /**
     * 对响应数据进行处理
     * @param bool $isApi
     * @param mixed $data
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface|\Phalcon\Mvc\View
     */
    protected function doResponse(bool $isApi, mixed $data)
    {
        if ($isApi) {
            return \json($data);
        } else {
            $this->view->setVars($this->viewData, true);
            $this->view->setVar('language', $this->language);
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
                $this->beforeJsonResponse($data);
                $this->doResponse(true, $data);
            } else {
                $this->beforeViewResponse($data);
                $this->doResponse(false, $data);
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