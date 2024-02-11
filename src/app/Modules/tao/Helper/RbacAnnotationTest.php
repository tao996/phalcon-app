<?php

namespace app\Modules\tao\Helper;

use app\Modules\demo\Controllers\TodoController;
use PHPUnit\Framework\TestCase;

class RbacAnnotationTest extends TestCase
{

    public function testDefaultApp()
    {
        $aa = new RbacAnnotation();
        $app = $aa->getSingleAppInfos();
        $this->assertEquals('app\Http', $app['namespace']);

    }

    public function testProjectApp()
    {
        $aa = new RbacAnnotation();
        $app = $aa->getSingleAppInfos(config('app.project'));
        $this->assertEquals('app\Http\Projects\city', $app['namespace']);
    }

    public function testLoadRbacInfo()
    {

        $aa = new RbacAnnotation();
        // 单应用
        $rows = $aa->loadRbacInfo([
            'namespace' => 'app\Http',
            'controllers' => [
                'IndexController.php',
            ],
        ]);
        $this->assertEquals(['module' => '', 'node' => 'index', 'title' => 'Home', 'type' => 2, 'is_auth' => 0], $rows[0]);
        $this->assertEquals(['module' => '', 'node' => 'index/index', 'title' => 'Index', 'type' => 3, 'is_auth' => 0], $rows[1]);

        // 单应用子目录
        $rows = $aa->loadRbacInfo([
            'namespace' => 'app\Http',
            'controllers' => [
                ['sub', 'TestController.php'],
            ],
        ]);
        $this->assertEquals(['module' => '', 'node' => 'sub.test', 'title' => 'Test1', 'type' => 2, 'is_auth' => 1], $rows[0]);
        $this->assertEquals(['module' => '', 'node' => 'sub.test/abc', 'title' => 'ABC', 'type' => 3, 'is_auth' => 1], $rows[1]);

        // 单应用：子模块+子目录
        $rows = $aa->loadRbacInfo([
            'namespace' => 'app\Http',
            'path' => '/var/www/app/Http',
            'extends' => [
                'Sub' => [
                    ['sub1', 'BBQController.php']
                ],
            ]
        ]);
        $this->assertEquals(['module' => '', 'node' => 'sub/sub1.bBQ', 'title' => 'BBQ', 'type' => 2, 'is_auth' => 1], $rows[0]);
        $this->assertEquals(['module' => '', 'node' => 'sub/sub1.bBQ/say', 'title' => 'say', 'type' => 3, 'is_auth' => 1], $rows[1]);

        // 单应用非默认 project
        $rows = $aa->loadRbacInfo([
            'namespace' => 'app\Http\Projects\city',
            'controllers' => [
                "AuthController.php"
            ]
        ]);
        $this->assertEquals(['module' => '', 'node' => 'auth',
            'title' => '注册登录', 'type' => 2, 'is_auth' => 0], $rows[0]);
        $this->assertEquals(['module' => '', 'node' => 'auth/index',
            'title' => '登录', 'type' => 3, 'is_auth' => 0], $rows[1]);

    }


    public function testModule()
    {
        $aa = new RbacAnnotation();

        $modules = $aa->getMultiModulesInfos();
        $info = $aa->loadRbacInfo($modules['Demo']);
//        dd($modules['Demo'],$info);

        $this->assertTrue(in_array('Demo', array_keys($modules)));

        $info = $aa->scanModule('Demo');
        $this->assertTrue(in_array('IndexController.php', (array)$info['controllers']));
        $this->assertTrue(in_array(['db', 'TestController.php'], (array)$info['controllers']));
        $this->assertTrue(isset($info['extends']['Db']));
        $this->assertTrue(in_array(['user', 'InfoController.php'], (array)$info['extends']['Db']));

        // 多模块
        $rows = $aa->loadRbacInfo([
            'name' => 'Demo',
            'title' => 'Demo 测试',
            'namespace' => 'app\Modules\demo',
            'controllers' => [
                "TodoController.php"
            ]
        ]);
        $this->assertEquals(['module' => 'demo', 'node' => 'demo',
            'title' => 'Demo 测试', 'type' => 1, 'is_auth' => 1], $rows[0]);
        $this->assertEquals(['module' => 'demo', 'node' => 'demo/todo', 'title' => '表单', 'type' => 2, 'is_auth' => 1], $rows[1]);
        $this->assertEquals(['module' => 'demo', 'node' => 'demo/todo/list', 'title' => 'list1', 'type' => 3, 'is_auth' => 1], $rows[2]);
        $this->assertEquals(['module' => 'demo', 'node' => 'demo/todo/test2', 'title' => 'test2', 'type' => 3, 'is_auth' => 0], $rows[3]);
        // 多模块+子目录
        $rows = $aa->loadRbacInfo([
            'name' => 'Demo',
            'title' => 'Demo 测试',
            'namespace' => 'app\Modules\demo',
            'controllers' => [
                ["db", "TestController.php"]
            ]
        ]);
        $this->assertEquals(['module' => 'demo', 'node' => 'demo/db.test/hello', 'title' => 'HELLO', 'type' => 3, 'is_auth' => 1], $rows[2]);
        $this->assertEquals(['module' => 'demo', 'node' => 'demo/db.test/trans', 'title' => 'RBAC事务', 'type' => 3, 'is_auth' => 1], $rows[3]);

        // 多模块+子模块
        $rows = $aa->loadRbacInfo([
            'name' => 'Demo',
            'title' => 'Demo 测试',
            'namespace' => 'app\Modules\demo',
            'extends' => [
                'Db' => [
                    "TestController.php"
                ]
            ], // demo.db/test
        ]);
        $this->assertEquals(['module' => 'demo', 'node' => 'demo.db/test', 'title' => 'DbTest', 'type' => 2, 'is_auth' => 1], $rows[1]);
        $this->assertEquals(['module' => 'demo', 'node' => 'demo.db/test/insert', 'title' => '时间戳和添加记录', 'type' => 3, 'is_auth' => 1], $rows[2]);
        // 多模块+子模块+子目录
        $rows = $aa->loadRbacInfo([
            'name' => 'Demo',
            'title' => 'Demo 测试',
            'namespace' => 'app\Modules\demo',
            'extends' => [
                'Db' => [
                    [
                        "user", "InfoController.php"
                    ]
                ]
            ], // demo.db/test
        ]);

        $this->assertEquals(['module' => 'demo', 'node' => 'demo.db/user.info', 'title' => 'Info', 'type' => 2, 'is_auth' => 1], $rows[1]);
        $this->assertEquals(['module' => 'demo', 'node' => 'demo.db/user.info/name', 'title' => 'name', 'type' => 3, 'is_auth' => 0], $rows[2]);
    }

    public function testController()
    {
        $aa = new RbacAnnotation();
        $info = $aa->getControllerInfo(TodoController::class);
        /*
        Array (2) (
          [title] => String (6) "表单"
          [actions] => Array (2) (
            [list] => Array (1) (
              [title] => String (5) "list1"
            )
            [test2] => Array (2) (
              [title] => String (5) "test2"
              [close] => Integer (1)
            )
          )
        )
         */
        $this->assertEquals('表单', $info['title']);
        $this->assertEquals(['list', 'test2'], array_keys($info['actions']));
        $this->assertEquals(1, $info['actions']['test2']['close']);
    }

}

