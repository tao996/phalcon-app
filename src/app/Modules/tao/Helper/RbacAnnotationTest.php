<?php

namespace App\Modules\tao\Helper;

use App\Modules\demo\Controllers\TodoController;
use App\Modules\tao\Models\SystemNode;
use PHPUnit\Framework\TestCase;

class RbacAnnotationTest extends TestCase
{

    /**
     * @throws \ReflectionException
     */
    public function testModuleRbac()
    {
        $baseInfo = RbacAnnotation::moduleBaseInfo('demo');
//        dd('baseInfo',$baseInfo);
        /*
        Array (6) (
          [name] => String (4) "demo"
          [namespace] => String (16) "App\Modules\demo"
          [path] => String (25) "/var/www/app/Modules/demo"
          [module] => Boolean (TRUE)
          [title] => String (10) "Demo模块"
          [structure] => Array (3) (
            [controllers] => Array (2) (
              [0] => String (19) "IndexController.php"
              [1] => String (18) "TodoController.php"
            )
            [subDirs] => Array (1) (
              [db] => Array (1) (
                [0] => String (18) "TestController.php"
              )
            )
            [a0] => Array (1) (
              [db] => Array (2) (
                [controllers] => Array (1) (
                  [0] => String (18) "TestController.php"
                )
                [subDirs] => Array (1) (
                  [user] => Array (1) (
                    [0] => String (18) "InfoController.php"
                  )
                )
              )
            )
          )
        )
         */
        $nodes = RbacAnnotation::getNodes($baseInfo);
//        dd('~~~~nodes:', $nodes);
    }

    public function testProjectRbac()
    {
        $baseInfo = RbacAnnotation::projectBaseInfo(''); // 默认 app/Http
        /*
        Array (5) (
          [name] => String (0) ""
          [namespace] => String (8) "App\Http"
          [path] => String (17) "/var/www/app/Http"
          [module] => Boolean (FALSE)
          [structure] => Array (3) (
            [controllers] => Array (1) (
              [0] => String (19) "IndexController.php"
            )
            [subDirs] => Array (1) (
              [sub] => Array (1) (
                [0] => String (18) "TestController.php"
              )
            )
            [a0] => Array (1) (
              [sub] => Array (2) (
                [controllers] => Array (0) (
                )
                [subDirs] => Array (1) (
                  [sub1] => Array (2) (
                    [0] => String (17) "BBQController.php"
                    [1] => String (16) "MeController.php"
                  )
                )
              )
            )
          )
        )
         */
        $defNodes = RbacAnnotation::getNodes($baseInfo);
//        dd($defNodes);
/*
Array (7) (
  [0] => Array (6) (
    [kind] => Integer (1)
    [module] => String (0) ""
    [node] => String (0) ""
    [title] => String (0) ""
    [type] => Integer (1)
    [is_auth] => Integer (1)
  )
  [1] => Array (6) (
    [kind] => Integer (1)
    [module] => String (0) ""
    [node] => String (6) "/index"
    [title] => String (4) "Home"
    [type] => Integer (2)
    [is_auth] => Integer (0)
  )
  [2] => Array (6) (
    [kind] => Integer (1)
    [module] => String (0) ""
    [node] => String (12) "/index/index"
    [title] => String (5) "Index"
    [type] => Integer (3)
    [is_auth] => Integer (0)
  )
  [3] => Array (6) (
    [kind] => Integer (1)
    [module] => String (0) ""
    [node] => String (9) "/sub.test"
    [title] => String (5) "Test1"
    [type] => Integer (2)
    [is_auth] => Integer (1)
  )
  [4] => Array (6) (
    [kind] => Integer (1)
    [module] => String (0) ""
    [node] => String (13) "/sub.test/abc"
    [title] => String (3) "ABC"
    [type] => Integer (3)
    [is_auth] => Integer (1)
  )
  [5] => Array (6) (
    [kind] => Integer (1)
    [module] => String (0) ""
    [node] => String (13) "/sub/sub1.bBQ"
    [title] => String (3) "BBQ"
    [type] => Integer (2)
    [is_auth] => Integer (1)
  )
  [6] => Array (6) (
    [kind] => Integer (1)
    [module] => String (0) ""
    [node] => String (17) "/sub/sub1.bBQ/say"
    [title] => String (3) "say"
    [type] => Integer (3)
    [is_auth] => Integer (1)
  )
)
 */
        $expectNodes = [
            '/index/index' => 0, '/sub.test/abc' => 0, '/sub/sub1.bBQ/say' => 0
        ];
        foreach ($defNodes as $node) {
            if ($node['type'] == SystemNode::TYPE_ACTION) {
                $expectNodes[$node['node']] = 1;
            }
        }
        foreach ($expectNodes as $n => $v) {
            $this->assertEquals(1, $v, $n);
        }

        $baseInfo = RbacAnnotation::projectBaseInfo('boyu');
    }

    /**
     * 测试指定的控制器
     * @throws \ReflectionException
     */
    public function testController()
    {
        $info = RbacAnnotation::getControllerInfo(TodoController::class);
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

