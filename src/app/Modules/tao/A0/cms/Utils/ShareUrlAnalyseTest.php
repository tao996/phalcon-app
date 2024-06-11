<?php

namespace App\Modules\tao\A0\cms\Utils;

use PHPUnit\Framework\TestCase;

class ShareUrlAnalyseTest extends TestCase
{
    public function testMatchYouTubeLink()
    {
        $link = 'https://youtu.be/JTxsNm9IdYU?si=cNkeJX-rOkRJMQqr';
        $id = ShareUrlAnalyse::matchYouTubeLink($link);
        $this->assertEquals('JTxsNm9IdYU',$id);

        $link = 'https://www.youtube.com/watch?v=JTxsNm9IdYU';
        $id = ShareUrlAnalyse::matchYouTubeLink($link);
        $this->assertEquals('JTxsNm9IdYU',$id);
    }
}