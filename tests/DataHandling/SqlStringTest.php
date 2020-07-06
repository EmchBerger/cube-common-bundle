<?php

namespace Tests\CubeTools\CubeCommonBundle\DataHandling;

use CubeTools\CubeCommonBundle\DataHandling\SqlString;
use PHPUnit\Framework\TestCase;

class SqlStringTest extends TestCase
{
    private static $toLike = [
        // forSql, forUser, [forSql expected fromLikeString]
        ['%9876%', '9876'],
        ['%i3,dz', '*i3,dz'],
        ['zu6%jr2', 'zu6*jr2'],
        ['pjTe%', 'pjTe*'],
        ['%43jt%', '*43jt*', '43jt'],
        ['%9g3%hwks%', '*9g3*hwks*'],
        ['rwqk_je', 'rwqk?je'],
        ['tex\_xu', 'tex_xu'], // would be better for [0]: '%tex\_xu%' instead of 'tex\_xu'
        ['us\%98', 'us%98'], // would be better for [0]: '%us\%98%' instead of 'us\%98'
        ['%oZ\_%', '*oZ_*'], // would be better for [2]: 'oZ\_' (instead of same as [0])
        ['_\_yIU', '?_yIU'],
        ['Q%i\%', 'Q*i%'],
    ];

    public function testToLikeString()
    {
        $toLike = self::$toLike;

        foreach ($toLike as $toTest) {
            $expected = $toTest[0];
            $fromUser = $toTest[1];
            $forSql = SqlString::toLikeString($fromUser);
            $this->assertSame($expected, $forSql);
        }
    }

    public function testFromLikeString()
    {
        $toLike = self::$toLike;

        foreach ($toLike as $toTest) {
            $expected = isset($toTest[2]) ? $toTest[2] : $toTest[1];
            $forSql = $toTest[0];
            $this->assertSame($expected, SqlString::fromLikeString($forSql));
        }
    }
}
