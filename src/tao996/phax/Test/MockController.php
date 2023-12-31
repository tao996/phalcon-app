<?php

namespace Phax\Test;

use Phax\Foundation\Application;

readonly class MockController
{
    public readonly \Phalcon\Http\RequestInterface $request;
    public readonly \Phalcon\Http\Response $response;
    /**
     * 待测试的控制器
     * @var \Phalcon\Mvc\Controller
     */
    public readonly \Phalcon\Mvc\Controller $controller;

    public readonly \Phalcon\Session\Manager $session;

    public function __construct()
    {
        Application::addWebServices(Application::di());
        $this->request = new MockRequest();
        $this->response = new MockResponse();
        Application::di()->setShared('request', $this->request);
        Application::di()->setShared('response', $this->response);


        $this->session = new MockSession();
        Application::di()->setShared('session', $this->session);
    }

    public function create(string $controllerClass)
    {
        $controller = new $controllerClass();
        $this->controller = $controller;
        $controller->request = $this->request;
        if (method_exists($controller, 'initialize')) {
            $controller->initialize();
        }
        return $controller;
    }

    /**
     * 获取控制器的 protected/private 属性
     * @param string $name
     */
    public function getControllerProperty(string $name)
    {
        return MockObject::getProperty($this->controller, $name);
    }

    /**
     * 提交一份 ajax post 数据
     * @param array $data
     * @param bool $isAjax 是否通过 ajax 提交
     * @return void
     */
    public function setPostData(array $data, $isAjax = true)
    {
        $this->request->data['isAjax'] = $isAjax;
        $this->request->data['isPost'] = true;
        $this->request->data['getPost'] = $data;
    }

    /**
     * 返回内容
     * @param mixed $response
     * @return array ['code'=>number, 'msg'=>string', data=null]
     */
    public function getActionResponse(mixed $response)
    {
        return $response->getJsonContent();
    }

    public function mustException(string $action)
    {
        try {
            $this->controller->$action();
            return 'sorry,no exception run ~~~~';
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

}