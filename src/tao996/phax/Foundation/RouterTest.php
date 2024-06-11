<?php
declare(strict_types=1);

use \Phax\Foundation\Router;

class RouterTest extends \PHPUnit\Framework\TestCase
{
    public function testAnalysisRoute()
    {
        // 多模块
        // 多模块默认全部放在 app/Modules 目录下，子模块放在 app/Modules/多模块/A0/子模块 目录下
        $expect = [
            'pattern' => '/m/',
            'paths' => ['module' => 'index', 'controller' => 'index', 'action' => 'index'],
            'pathsname' => ['module' => 'index', 'controller' => 'index', 'action' => 'index'],
            'namespace' => "App\Modules\index\Controllers",
            'viewpath' => "/var/www/app/Modules/index/views",
            'module' => '/var/www/app/Modules/index/Module.php',
            'name' => 'index'
        ];

        $rst = Router::analysisRoutePath('/m/');
        $this->assertEquals($expect, $rst);
        $rst = Router::analysisRoutePath('/cn/m/');
        $expect['pattern'] = Router::$languageRule . $expect['pattern'];
        $this->assertEquals($expect, $rst);


        $node = Router::getNode($rst);
        $this->assertEquals('index/index/index', $node);

        $expect = [
            'pattern' => '/m/:module',
            'paths' => ['module' => 1, 'controller' => 'index', 'action' => 'index'],
            'pathsname' => ['module' => 'm1', 'controller' => 'index', 'action' => 'index'],
            'namespace' => "App\Modules\m1\Controllers",
            'viewpath' => "/var/www/app/Modules/m1/views",
            'module' => '/var/www/app/Modules/m1/Module.php',
            'name' => 'm1',
        ];
        $rst = Router::analysisRoutePath('/m/m1');
        $this->assertEquals($expect, $rst);

        $rst = Router::analysisRoutePath('/m/M1'); // 大写
        $this->assertEquals($expect, $rst);

        $rst = Router::analysisRoutePath('/en/m/m1');
        $expect['pattern'] = Router::$languageRule . $expect['pattern'];
        $expect['paths']['module'] = 2;
        $this->assertEquals($expect, $rst);

        $node = Router::getNode($rst);
        $this->assertEquals('m1/index/index', $node);

        $expect = [
            'pattern' => '/m/:module/:controller',
            'paths' => ['module' => 1, 'controller' => 2, 'action' => 'index'],
            'pathsname' => ['module' => "m1", 'controller' => "c", 'action' => 'index'],
            'namespace' => "App\Modules\m1\Controllers",
            'viewpath' => "/var/www/app/Modules/m1/views",
            'module' => '/var/www/app/Modules/m1/Module.php',
            'name' => 'm1',
        ];
        $rst = Router::analysisRoutePath('/m/m1/c');
        $this->assertEquals($expect, $rst);

        $rst = Router::analysisRoutePath('/en/m/m1/c');
        $expect['pattern'] = Router::$languageRule . $expect['pattern'];
        $expect['paths']['module'] = 2;
        $expect['paths']['controller'] = 3;
        $this->assertEquals($expect, $rst);

        $node = Router::getNode($rst);
        $this->assertEquals('m1/c/index', $node);

        $expect = [
            'pattern' => '/m/:module/:controller/:action',
            'paths' => ['module' => 1, 'controller' => 2, 'action' => 3],
            'pathsname' => ['module' => 'm1', 'controller' => 'c1', 'action' => 'a1'],
            'namespace' => "App\Modules\m1\Controllers",
            'viewpath' => "/var/www/app/Modules/m1/views",
            'module' => '/var/www/app/Modules/m1/Module.php',
            'name' => 'm1',
        ];
        $rst = Router::analysisRoutePath('/m/m1/c1/a1');
        $this->assertEquals($expect, $rst);
        $rst = Router::analysisRoutePath('/en/m/m1/c1/a1');
        $expect['pattern'] = Router::$languageRule . $expect['pattern'];
        $expect['paths']['module'] = 2;
        $expect['paths']['controller'] = 3;
        $expect['paths']['action'] = 4;
        $this->assertEquals($expect, $rst);

        $node = Router::getNode($rst);
        $this->assertEquals('m1/c1/a1', $node);


        $expect = [
            'pattern' => '/m/:module/:controller/:action/:params',
            'paths' => ['module' => 1, 'controller' => 2, 'action' => 3, 'params' => 4],
            'pathsname' => ['module' => 'm2', 'controller' => 'c2', 'action' => 'a2'],
            'namespace' => "App\Modules\m2\Controllers",
            'viewpath' => "/var/www/app/Modules/m2/views",
            'module' => '/var/www/app/Modules/m2/Module.php',
            'name' => 'm2',
        ];
        $rst = Router::analysisRoutePath('/m/m2/c2/a2/p');
        $this->assertEquals($expect, $rst);

        $rst = Router::analysisRoutePath('/en/m/m2/c2/a2/p1/p2/p3');
        $expect['pattern'] = Router::$languageRule . $expect['pattern'];
        $expect['paths']['module'] = 2;
        $expect['paths']['controller'] = 3;
        $expect['paths']['action'] = 4;
        $expect['paths']['params'] = 5;
        $this->assertEquals($expect, $rst);

        $node = Router::getNode($rst);
        $this->assertEquals('m2/c2/a2', $node);

        // 多模块：子模块
        $expect = [
            'pattern' => '/m/:module\.wechat',
            'pathsname' => ['module' => 'tao', 'controller' => 'index', 'action' => 'index'],
            'namespace' => 'App\Modules\tao\A0\wechat\Controllers',
            'viewpath' => '/var/www/app/Modules/tao/A0/wechat/views',
            'route' => '/m/:module\.wechat',
        ];
        $keys = ['pattern', 'pathsname', 'namespace', 'viewpath', 'route'];
        $rst = Router::analysisWithURL('/m/tao.wechat');
        $this->assertEquals($expect, \Phax\Utils\MyData::getByKeys($rst, $keys));

        $rst = Router::analysisWithURL('/cn/m/tao.wechat');
        $expect['pattern'] = Router::$languageRule . $expect['pattern'];
        $expect['route'] = Router::$languageRule . $expect['route'];
        $this->assertEquals($expect, \Phax\Utils\MyData::getByKeys($rst, $keys));

        $rst1 = Router::analysisWithURL('/m/m1.m11/');
        $rst2 = Router::analysisWithURL('/m/m1.m11');
        $this->assertEquals($rst1, $rst2);

        $rst = Router::analysisRoutePath('/m/m1.m11/c1');
        $this->assertEquals([
            'pattern' => '/m/:module/:controller',
            'paths' => ['module' => 1, 'controller' => 2, 'action' => 'index'],
            'pathsname' => ['module' => 'm1', 'controller' => 'c1', 'action' => 'index'],
            'namespace' => "App\Modules\m1\A0\\m11\Controllers",
            'viewpath' => "/var/www/app/Modules/m1/A0/m11/views",
            'module' => '/var/www/app/Modules/m1/Module.php',
            'name' => 'm1',
            'subm' => 'm11'
        ], $rst);

        $rst = Router::analysisWithURL('/m/m1.m11/c1');
        $this->assertEquals([
            'registerModules' => [
                'm1' => [
                    'path' => '/var/www/tao996/phalcon/phax/src/phax/Mvc/Module.php',
                    'className' => 'Phax\Mvc\Module'
                ]
            ],
            'route' => '/m/:module\.m11/:controller'
        ], [
            'registerModules' => $rst['registerModules'],
            'route' => $rst['route']
        ]);


        $node = Router::getNode($rst);
        $this->assertEquals('m1.m11/c1/index', $node);

        // 多模块：子模块+子目录
        $rst = Router::analysisRoutePath('/m/m1.m11/sub1.c2');
        $this->assertEquals([
            'pattern' => '/m/:module/:controller',
            'paths' => ['module' => 1, 'controller' => 2, 'action' => 'index'],
            'pathsname' => ['module' => 'm1', 'controller' => 'c2', 'action' => 'index'],
            'namespace' => "App\Modules\m1\A0\\m11\Controllers\sub1",
            'viewpath' => "/var/www/app/Modules/m1/A0/m11/views",
            'module' => '/var/www/app/Modules/m1/Module.php',
            'name' => 'm1',
            'subc' => 'sub1',
            'subm' => 'm11'
        ], $rst);
        $node = Router::getNode($rst);
        $this->assertEquals('m1.m11/sub1.c2/index', $node);

        // 多模块子目录
        $rst = Router::analysisRoutePath('/m/m1/sub1.c1');
        $this->assertEquals([
            'pattern' => '/m/:module/:controller',
            'paths' => ['module' => 1, 'controller' => 2, 'action' => 'index'],
            'pathsname' => ['module' => 'm1', 'controller' => 'c1', 'action' => 'index'],
            'namespace' => "App\Modules\m1\Controllers\sub1",
            'viewpath' => "/var/www/app/Modules/m1/views",
            'module' => '/var/www/app/Modules/m1/Module.php',
            'name' => 'm1',
            'subc' => 'sub1',
        ], $rst);
        $node = Router::getNode($rst);
        $this->assertEquals('m1/sub1.c1/index', $node);

        $rst = Router::analysisRoutePath('/m/m1/sub.c1/a1');
        $this->assertEquals([
            'pattern' => '/m/:module/:controller/:action',
            'paths' => ['module' => 1, 'controller' => 2, 'action' => 3],
            'pathsname' => ['module' => 'm1', 'controller' => 'c1', 'action' => 'a1'],
            'namespace' => "App\Modules\m1\Controllers\sub",
            'viewpath' => "/var/www/app/Modules/m1/views",
            'module' => '/var/www/app/Modules/m1/Module.php',
            'name' => 'm1', 'subc' => 'sub',
        ], $rst);
        $node = Router::getNode($rst);
        $this->assertEquals('m1/sub.c1/a1', $node);

        $rst = Router::analysisRoutePath('/m/m1/sub.c1/a1/p1');
        $this->assertEquals([
            'pattern' => '/m/:module/:controller/:action/:params',
            'paths' => ['module' => 1, 'controller' => 2, 'action' => 3, 'params' => 4],
            'pathsname' => ['module' => 'm1', 'controller' => 'c1', 'action' => 'a1'],
            'namespace' => "App\Modules\m1\Controllers\sub",
            'viewpath' => "/var/www/app/Modules/m1/views",
            'module' => '/var/www/app/Modules/m1/Module.php',
            'name' => 'm1', 'subc' => 'sub',
        ], $rst);
        $node = Router::getNode($rst);
        $this->assertEquals('m1/sub.c1/a1', $node);

        // 单应用,路由设计
        // 默认的
        $expect = [
            'pattern' => '/',
            'paths' => ['controller' => 'index', 'action' => 'index'],
            'pathsname' => ['controller' => 'index', 'action' => 'index'],
            'namespace' => "App\Http\Controllers",
            'viewpath' => "/var/www/app/Http/views"
        ];
        $rst = Router::analysisRoutePath('');
        $this->assertEquals($expect, $rst);
        $node = Router::getNode($rst);
        $this->assertEquals('index/index', $node);

        $rst = Router::analysisRoutePath('/');
        $this->assertEquals($expect, $rst);
        $node = Router::getNode($rst);
        $this->assertEquals('index/index', $node);

        $rst1 = Router::analysisRoutePath('/cn/');
        $expect['pattern'] = Router::$languageRule . $expect['pattern'];
        $this->assertEquals($expect, $rst1);


        $rst = Router::analysisRoutePath('/c1');
        $this->assertEquals([
            'pattern' => '/:controller',
            'paths' => ['controller' => 1, 'action' => 'index'],
            'pathsname' => ['controller' => 'c1', 'action' => 'index'],
            'namespace' => "App\Http\Controllers",
            'viewpath' => "/var/www/app/Http/views"
        ], $rst);
        $node = Router::getNode($rst);
        $this->assertEquals('c1/index', $node);

        $rst = Router::analysisRoutePath('/c2/a2');
        $this->assertEquals([
            'pattern' => '/:controller/:action',
            'paths' => ['controller' => 1, 'action' => 2],
            'pathsname' => ['controller' => 'c2', 'action' => 'a2'],
            'namespace' => "App\Http\Controllers",
            'viewpath' => "/var/www/app/Http/views"
        ], $rst);
        $node = Router::getNode($rst);
        $this->assertEquals('c2/a2', $node);

        $rst = Router::analysisRoutePath('/c2/a2/p');
        $this->assertEquals([
            'pattern' => '/:controller/:action/:params',
            'paths' => ['controller' => 1, 'action' => 2, 'params' => 3],
            'pathsname' => ['controller' => 'c2', 'action' => 'a2'],
            'namespace' => "App\Http\Controllers",
            'viewpath' => "/var/www/app/Http/views"
        ], $rst);
        $node = Router::getNode($rst);
        $this->assertEquals('c2/a2', $node);

        $rst = Router::analysisRoutePath('/c2/a2/p1/p2');
        $this->assertEquals([
            'pattern' => '/:controller/:action/:params',
            'paths' => ['controller' => 1, 'action' => 2, 'params' => 3],
            'pathsname' => ['controller' => 'c2', 'action' => 'a2'],
            'namespace' => "App\Http\Controllers",
            'viewpath' => "/var/www/app/Http/views"
        ], $rst);
        $node = Router::getNode($rst);
        $this->assertEquals('c2/a2', $node);

        $rst = Router::analysisRoutePath('/c2/a2/p1/p2/p3');
        $this->assertEquals([
            'pattern' => '/:controller/:action/:params',
            'paths' => ['controller' => 1, 'action' => 2, 'params' => 3],
            'pathsname' => ['controller' => 'c2', 'action' => 'a2'],
            'namespace' => "App\Http\Controllers",
            'viewpath' => "/var/www/app/Http/views"
        ], $rst);
        $node = Router::getNode($rst);
        $this->assertEquals('c2/a2', $node);

        // 普通 c1/a1/p1，单应用没有子模块（module/controller/action）因为跟普通路径冲突，并且文件目录没得放

        // 单应用子目录 sub.c1 或者 sub.c1/a1 或者 sub.c1/a1/p1
        $rst = Router::analysisRoutePath('/sub.c1');
        $this->assertEquals([
            'pattern' => '/:controller',
            'paths' => ['controller' => 1, 'action' => 'index'],
            'pathsname' => ['controller' => 'c1', 'action' => 'index'],
            'namespace' => "App\Http\Controllers\sub",
            'viewpath' => "/var/www/app/Http/views",
            'subc' => 'sub',
        ], $rst);
        $node = Router::getNode($rst);
        $this->assertEquals('sub.c1/index', $node);

        $rst = Router::analysisRoutePath('/sub.c2/a2');
        $this->assertEquals([
            'pattern' => '/:controller/:action',
            'paths' => ['controller' => 1, 'action' => 2],
            'pathsname' => ['controller' => 'c2', 'action' => 'a2'],
            'namespace' => "App\Http\Controllers\sub",
            'viewpath' => "/var/www/app/Http/views",
            'subc' => 'sub',
        ], $rst);
        $node = Router::getNode($rst);
        $this->assertEquals('sub.c2/a2', $node);

        $rst = Router::analysisRoutePath('/sub.c2/a2/p1');
        $this->assertEquals([
            'pattern' => '/:controller/:action/:params',
            'paths' => ['controller' => 1, 'action' => 2, 'params' => 3],
            'pathsname' => ['controller' => 'c2', 'action' => 'a2'],
            'namespace' => "App\Http\Controllers\sub",
            'viewpath' => "/var/www/app/Http/views",
            'subc' => 'sub',
        ], $rst);
        $node = Router::getNode($rst);
        $this->assertEquals('sub.c2/a2', $node);

        $rst = Router::analysisRoutePath('/sub.c2/a2/p1/p2');
        $this->assertEquals([
            'pattern' => '/:controller/:action/:params',
            'paths' => ['controller' => 1, 'action' => 2, 'params' => 3],
            'pathsname' => ['controller' => 'c2', 'action' => 'a2'],
            'namespace' => "App\Http\Controllers\sub",
            'viewpath' => "/var/www/app/Http/views",
            'subc' => 'sub',
        ], $rst);
        $node = Router::getNode($rst);
        $this->assertEquals('sub.c2/a2', $node);


        // 单应用子模块+子目录 m1/sub.c1/a1 或者 m1/sub.c1/a1/p1
        $rst = Router::analysisRoutePath('/m1/sub.c1/a1');
        $this->assertEquals([
            'pattern' => '/:controller/:action',
            'paths' => ['controller' => 1, 'action' => 2,],
            'pathsname' => ['controller' => 'c1', 'action' => 'a1'],
            'namespace' => "App\Http\A0\m1\Controllers\sub",
            'viewpath' => "/var/www/app/Http/A0/m1/views",
            'subm' => 'm1',
            'subc' => 'sub'
        ], $rst);
        $node = Router::getNode($rst);
        $this->assertEquals('m1/sub.c1/a1', $node);


        $rst = Router::analysisRoutePath('/m1/sub.c1/a1/p1');
        $this->assertEquals([
            'pattern' => '/:controller/:action/:params',
            'paths' => ['controller' => 1, 'action' => 2, 'params' => 3],
            'pathsname' => ['controller' => 'c1', 'action' => 'a1'],
            'namespace' => "App\Http\A0\m1\Controllers\sub",
            'viewpath' => "/var/www/app/Http/A0/m1/views",
            'subm' => 'm1',
            'subc' => 'sub'
        ], $rst);
        $node = Router::getNode($rst);
        $this->assertEquals('m1/sub.c1/a1', $node);

        $rst = Router::analysisRoutePath('/m1/sub.c1/a1/p1/p2');
        $this->assertEquals([
            'pattern' => '/:controller/:action/:params',
            'paths' => ['controller' => 1, 'action' => 2, 'params' => 3],
            'pathsname' => ['controller' => 'c1', 'action' => 'a1'],
            'namespace' => "App\Http\A0\m1\Controllers\sub",
            'viewpath' => "/var/www/app/Http/A0/m1/views",
            'subm' => 'm1',
            'subc' => 'sub'
        ], $rst);
        $node = Router::getNode($rst);
        $this->assertEquals('m1/sub.c1/a1', $node);

        // 单应用，非标准应用
        $rst = Router::analysisWithURL('/admin.simpleRent', ['project' => 'city']);
//        dd($rst);
        $this->assertEquals(['controller' => 'simpleRent', 'action' => 'index'], $rst['pathsname']);
        $this->assertEquals('App\Http\Projects\city\Controllers\admin', $rst['namespace']);
        $this->assertEquals('/var/www/app/Http/Projects/city/views', $rst['viewpath']);

    }

    public function testAnalysisWithURL()
    {
        $data = Router::analysisRoutePath('/about/us', ['project' => 'city']);
        $this->assertEquals([
            'namespace' => 'App\Http\Projects\city\Controllers',
            'viewpath' => '/var/www/app/Http/Projects/city/views'
        ], \Phax\Utils\MyData::getByKeys($data, ['namespace', 'viewpath']));

        $data = Router::analysisRoutePath('/', ['project' => 'city']);
        $this->assertEquals([
            'namespace' => 'App\Http\Projects\city\Controllers',
            'viewpath' => '/var/www/app/Http/Projects/city/views'
        ], \Phax\Utils\MyData::getByKeys($data, ['namespace', 'viewpath']));

        $data = Router::analysisRoutePath('/auth', ['project' => 'city']);
        $this->assertEquals([
            'namespace' => 'App\Http\Projects\city\Controllers',
            'viewpath' => '/var/www/app/Http/Projects/city/views'
        ], \Phax\Utils\MyData::getByKeys($data, ['namespace', 'viewpath']));
        $this->assertEquals('auth', $data['pathsname']['controller']);

        $url = '/sub/sub1.me/say?key=1';
        $data = Router::analysisWithURL($url);
        $this->assertEquals('/sub/sub1\.([a-zA-Z0-9\_\-]+)/:action', $data['route']);
        $this->assertFalse($data['isApi']);
        $this->assertEquals('sub1/me/say', $data['pickview']);


        $url = '/api/sub/sub1.me/say';
        $data = Router::analysisWithURL($url);
        $this->assertTrue($data['isApi']);
        $this->assertEquals('/api/sub/sub1\.([a-zA-Z0-9\_\-]+)/:action', $data['route']);

//        preg_match_all('#^/m/([\w0-9\_\-]+)\.wechat/([\w0-9\_\-]+)/([\w0-9\_\-]+)$#u','/m/tao.wechat/menu/edit',$matches);

    }

    public function testFormatName()
    {
        foreach ([
                     'refreshNodeAction',
                     'refreshNodeController',
                     'refreshNode',
                     'refresh-node',
                     'refresh_node',
                     'refresh node',
                     'RefreshNode'
                 ] as $v) {
            $rst = Router::formatNodeName($v);
            $this->assertEquals('refreshNode',
                $rst,
                $v . ' format failed'
            );
        }
    }

    public function testLanguage()
    {
        $items = [
            ['', false],
            ['/', false],
            ['/a', false], ['a', false],
            ['/a/', false], ['a/', false],
            ['/ab', false], ['ab', false],
            ['/abc', false], ['abc', false],
            ['/abc/', false], ['abc/', false],
            ['/cn/', true], ['cn/', false],
            ['/cn/index', true], ['cn/index', false],
        ];
        foreach ($items as $item) {
            $rst = Router::hasLanguage($item[0]);
            $this->assertEquals($item[1], $rst);
        }

        $items = [
            ['/cn/', '/'],
            ['/cn/index', '/index']
        ];
        foreach ($items as $item) {
            $rst = Router::filterIfLanguage($item[0]);
            $this->assertEquals($item[1], $rst);
        }
    }

    public function testCliRoute()
    {
        $blankOption = ['project' => '', 'm' => ''];
        $rst = Router::analysisWithCLI('/', $blankOption);
        $expect = [
            'task' => 'main', 'action' => 'index', 'namespace' => 'App\Console'
        ];
        $this->assertEquals($expect, $rst);
        $rst1 = Router::analysisWithCLI('', $blankOption);
        $this->assertEquals($rst, $rst1);

        $rst = Router::analysisWithCLI('/main/test', $blankOption);
        $this->assertEquals(['task' => 'main', 'action' => 'test', 'namespace' => 'App\Console'], $rst);

        $rst = Router::analysisWithCLI('/main/test', ['project' => 'city']);
        $expect = ['task' => 'main', 'action' => 'test',
            'namespace' => 'App\Http\Projects\city\Console'];
        $this->assertEquals($expect, $rst);

        $rst = Router::analysisWithCLI('/', ['project' => 'city']);
        $expect = ['task' => 'main', 'action' => 'index',
            'namespace' => 'App\Http\Projects\city\Console'];
        $this->assertEquals($expect, $rst);

// 多模块
        $rst1 = Router::analysisWithCLI('/m/tao', ['m' => 'm', 'project' => 'city']);
        $rst = Router::analysisWithCLI('m/tao', ['m' => 'm']);
        $this->assertEquals($rst, $rst1);
        $expect = [
            'task' => 'main', 'action' => 'index',
            'namespace' => 'App\Modules\tao\Console',
            'module' => 'tao',
            'modules' => [
                'tao' => [
                    'path' => '/var/www/app/Modules/tao/Module.php',
                    'className' => 'App\Modules\tao\Module',
                ]
            ]
        ];
        $this->assertEquals($expect, $rst);


        $rst = Router::analysisWithCLI('m/tao/migrate', ['m' => 'm']);
        $expect['task'] = 'migrate';
        $this->assertEquals($expect, $rst);

        // 多模块子模块
        $rst = Router::analysisWithCLI('m/tao.wechat/migrate', ['m' => 'm']);
        $expect['namespace'] = 'App\Modules\tao\A0\wechat\Console';
        $this->assertEquals($expect, $rst);


    }
}