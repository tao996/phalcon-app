<?php

namespace Phax\Utils;

use PHPUnit\Framework\TestCase;

class DataTest extends TestCase
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
            $ans = Data::getIntsWith($data, $key);
            $this->assertEquals($rst, $ans);
        }

    }

    public function testFindByKeys()
    {
        $data = ['a' => 1, 'b' => 2, 'c' => 'hello'];
        $rst = Data::findByKeys($data, ['a', 'c']);
        $this->assertEquals(['a' => 1, 'c' => 'hello'], $rst);
    }

    public function testFormatName()
    {
        foreach ([
                     'refreshNode',
                     'refresh-node',
                     'refresh_node',
                     'RefreshNode',
                 ] as $item) {
            $this->assertEquals('refreshNode', Data::formatName($item));
        }
    }
}