<?php

namespace Phax\Support\I18n;

use PHPUnit\Framework\TestCase;

class LangTest extends TestCase
{
    public function testInterpolate()
    {
        $rst = Lang::interpolate(':date (YYYY-MM-DD)', ['date' => '2020-09-09']);
        $this->assertEquals('2020-09-09 (YYYY-MM-DD)',$rst);


    }
}