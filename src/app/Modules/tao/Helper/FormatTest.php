<?php

namespace App\Modules\tao\Helper;

use PHPUnit\Framework\TestCase;

class FormatTest extends TestCase
{
    public function testHumanFileSize()
    {
        $tests = [
            [1024, 0, '1K'],
            [1024, 2, '1.00K'],
        ];
        foreach ($tests as $i) {
            $rst = Format::humanFileSize($i[0], $i[1]);
            $this->assertEquals($i[2], $rst, 'error with:' . json_encode($i));
        }
    }

    public function testGetBytesFromSize(){
        $tests = [
            ['1k',1024],
            ['2M',1024 * 1024 * 2]
        ];
        foreach ($tests as $item){
            $rst = Format::getBytesFromSize($item[0]);
            $this->assertEquals($item[1],$rst);
        }
    }

    public function testSplitFileSize()
    {
        $tests = [
            ['5kb', [5, 'K']],
            ['5.0k', [5.0, 'K']],
            ['5KB', [5, 'K']],
            [5, [5, 'B']],
            [5.01, [5.01, 'B']]
        ];
        foreach ($tests as $item) {
            $rst = Format::splitFileSize($item[0]);
            $this->assertEquals($item[1], $rst);
        }
    }
}