<?php

namespace App\Modules\tao\Services;

use PHPUnit\Framework\TestCase;

class NodeServiceTest extends TestCase
{
    public function testCompare()
    {
        $dbNodes = array(
            // 修改的
            array('id' => 1, 'module' => '', 'node' => 'auth', 'title' => '注册登录', 'type' => 2, 'is_auth' => 2,),
            // 不变的
            array('id' => 3, 'module' => 'tao', 'node' => 'tao', 'title' => '系统管理模块', 'type' => 1, 'is_auth' => 1,),
            array('id' => 4, 'module' => 'tao', 'node' => 'tao/admin.config', 'title' => '系统配置管理', 'type' => 2, 'is_auth' => 1,),
            array('id' => 5, 'module' => 'tao', 'node' => 'tao/admin.config/save', 'title' => '配置保存', 'type' => 3, 'is_auth' => 1,),
            // 删除的
            array('id' => 9, 'module' => 'tao', 'node' => 'tao/admin.config/delete', 'title' => '删除记录', 'type' => 3, 'is_auth' => 1,),
        );

        $newNodes = array(
            // 修改的
            array('id' => 1, 'module' => '', 'node' => 'auth', 'title' => '注册登录996', 'type' => 2, 'is_auth' => 1,),

            // 不变的
            array('id' => 3, 'module' => 'tao', 'node' => 'tao', 'title' => '系统管理模块', 'type' => 1, 'is_auth' => 1,),
            array('id' => 4, 'module' => 'tao', 'node' => 'tao/admin.config', 'title' => '系统配置管理', 'type' => 2, 'is_auth' => 1,),
            array('id' => 5, 'module' => 'tao', 'node' => 'tao/admin.config/save', 'title' => '配置保存', 'type' => 3, 'is_auth' => 1,),

            // 新添加的
            array('module' => 'tao', 'node' => 'tao/admin.menu', 'title' => '后台菜单管理', 'type' => 2, 'is_auth' => 1,),
            array('module' => 'tao', 'node' => 'tao/admin.menu/add', 'title' => '添加菜单', 'type' => 3, 'is_auth' => 1,)
        );

        $rows = NodeService::compare($dbNodes,$newNodes);
        $this->assertEquals(1,count($rows['delete']));
        $this->assertEquals(1,count($rows['update']));
        $this->assertEquals(2,count($rows['append']));
    }
}