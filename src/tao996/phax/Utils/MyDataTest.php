<?php

namespace Phax\Utils;

use PHPUnit\Framework\TestCase;

class MyDataTest extends TestCase
{
    public function testGetInts()
    {
        $data = [
            'id1' => ["1", "2", "3"],
            'ids' => '1,2,3',
            'ida' => [1 => 'on', 2 => 'on', 3 => 'on'],
            'id2' => [1, 2, 3]
        ];
        $rst = [1, 2, 3];
        foreach (['ids', 'ida', 'id2'] as $key) {
            $ans = MyData::getIntsWith($data, $key);
            $this->assertEquals($rst, $ans);
        }

    }

    public function testGetByKeys()
    {
        $data = ['a' => 1, 'b' => 2, 'c' => 'hello'];
        $rst = MyData::getByKeys($data, ['a', 'c']);
        $this->assertEquals(['a' => 1, 'c' => 'hello'], $rst);
    }

    public function testFindByKeys()
    {
        $data = [
            ['a' => 1, 'b' => 2, 'd' => 5],
            ['a' => 0, 'c' => 5]
        ];
        $rst = MyData::findByKeys($data, ['a', 'b', 'c'], ['a']);
        $this->assertEquals(['a' => 1, 'b' => 2], $rst[0]);

        $rst = MyData::findByKeys($data, ['a', 'b', 'c']);
        $this->assertEquals([
            ['a' => 1, 'b' => 2],
            ['a' => 0, 'b' => 5]
        ], $rst);
    }

    public function testFormatName()
    {
        foreach ([
                     'refreshNode',
                     'refresh-node',
                     'refresh_node',
                     'RefreshNode',
                 ] as $item) {
            $this->assertEquals('refreshNode', MyData::formatName($item));
        }
    }
}