<?php

namespace App\Modules\tao\Helper;

use Phax\Utils\MyFileSystem;
use PHPUnit\Framework\TestCase;

class AppStructureTest extends TestCase
{
    /**
     * @throws \Exception
     */
    public function testModuleControllers()
    {

        $pathModule = PATH_APP_MODULES . 'demo/';

        // 模块下的控制器
        $files = AppStructure::getControllerFiles($pathModule . 'Controllers');

        $this->assertEquals([
            'controllers' => [
                'IndexController.php',
                'TodoController.php',
            ],
            'subDirs' => [
                'db' => ['TestController.php'],
            ]
        ], $files);

        // 模块下的子模块列表
        $a0s = MyFileSystem::findInDirs($pathModule . 'A0/', 'dir');
        $this->assertEquals(['db'], $a0s);

        $a0sFiles = AppStructure::getControllerFiles($pathModule . 'A0/db/Controllers');
        $this->assertEquals([
            'controllers' => [
                'TestController.php'
            ],
            'subDirs' => [
                'user' => [
                    'InfoController.php'
                ]
            ]
        ], $a0sFiles);

        // 模块下全部的控制器
        $files = AppStructure::findInModule('demo');
        $this->assertEquals([
            'controllers' => [
                "IndexController.php",
                "TodoController.php",
            ],
            'subDirs' => [
                'db' => [
                    "TestController.php",
                ]
            ],
            'a0' => [
                'db' => [
                    'controllers' => [
                        "TestController.php",
                    ],
                    'subDirs' => [
                        'user' => [
                            "InfoController.php"
                        ]
                    ]
                ]
            ]
        ], $files);

    }
}