<?php

namespace App\Modules\tao\Console;

use App\Modules\tao\Services\MigrationService;
use PHPUnit\Framework\TestCase;

class MigrateTest extends TestCase
{
    public function testDb()
    {
        $version = 'tao.test.'.time();
        $rst = MigrationService::upgrade($version, 'init the tao db', function (\PDO $db) {
            // tao.test.sql 中包含 DDL 语句，会自动触发事务
            $sql = file_get_contents(dirname(__DIR__) . '/data/tao.test.sql');
            $db->exec($sql);
        });

//        $this->assertTrue($rst);
    }
}