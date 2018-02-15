<?php

namespace Tests\CubeTools\CubeCommonBundle\DataHandling;

use CubeTools\CubeCommonBundle\DataHandling\StringHelper;
use PHPUnit\Framework\TestCase;

class StringHelperTest extends TestCase
{
    public function testContains()
    {
        $needle = '5tg';
        $this->assertTrue(StringHelper::contains($needle, 'hjhek5tgfsjk'), $needle);
        $needle = 'j4kh';
        $this->assertTrue(StringHelper::contains($needle, 'j4khejku3o'), $needle);
        $needle = 'iZ';
        $this->assertFalse(StringHelper::contains($needle, 'uIzizIZ3g'), $needle);
        $needle = 'uPT6';
        $this->assertTrue(StringHelper::contains($needle, 't6jUpT6kls', true), $needle);
    }

    public function testStartsWith()
    {
        $needle = 'zuas';
        $this->assertTrue(StringHelper::startsWith('zuasuehkf', $needle), $needle);
        $needle = 'uas';
        $this->assertFalse(StringHelper::startsWith('duashsl', $needle), $needle);
        $needle = 'bIjKl';
        $this->assertTrue(StringHelper::startsWith('Bijklst', $needle, true), $needle);
    }

    public function testEndsWith()
    {
        $needle = 'xfrw';
        $this->assertTrue(StringHelper::endsWith('iwashxfrw', $needle), $needle);
        $needle = 'dls';
        $this->assertFalse(StringHelper::endsWith('dklsjdlsisi', $needle), $needle);
        $needle = 'iT';
        $this->assertTrue(StringHelper::endsWith('djJKLskdIt', $needle, true), $needle);
    }

    public function testRemoveSurroundingText()
    {
        $testText = '<p>remains <p>!</p></p>';
        $this->assertEquals('remains <p>!</p>', StringHelper::removeSurroundingText($testText, '<p>', '</p>'));
        $testText = '<b>all</b> same';
        $this->assertEquals($testText, StringHelper::removeSurroundingText($testText, '<b>', '</b>'));
        $testText = 'XsameAsWellYy';
        $this->assertEquals($testText, StringHelper::removeSurroundingText($testText, 'X', 'Y'));
        $testText = 'AAsDkflaOoOoOo';
        $this->assertEquals('AsDkflaOoOo', StringHelper::removeSurroundingText($testText, 'A', 'Oo'));
    }

    public function testSanitizeFilenameSimpleCases()
    {
        // sanitizeFilename($fileName, $defaultIfEmpty = 'default', $separator = '_', $lowerCase = false)
        $testName = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789. _';
        $this->assertEquals(StringHelper::sanitizeFilename($testName), $testName);
        $testName = 'not/good';
        $this->assertNotEquals(StringHelper::sanitizeFilename($testName), $testName);
        $testName = '/\\?';
        $san      = StringHelper::sanitizeFilename($testName);
        $this->assertNotEquals($san, $testName);
        $this->assertNotEmpty($san, $testName);
    }

    public function testSanizeFilenameReplaced()
    {
        $illegalChars = array('ä', "\01", '/'); // TODO add more chars
        foreach ($illegalChars as $c) {
            $testName = chr(rand(0x40, 0x49)).$c.chr(rand(0x40, 0x49));
            $this->assertNotContains($c, StringHelper::sanitizeFilename($testName));
        }
    }

    public function testIndicateStrippedKeepSize()
    {
        $testText = function ($expected, $text, $length, $hint) {
            $this->assertSame($expected.'|', StringHelper::indicateStrippedKeepSize($text, $length).'|', $hint.'?');
        };
        $testText('1234', '1234', 6, 'too short');
        $testText('a2cd5…', 'a2cd5e78', 8, 'looks stripped');
        $testText('ä3… ', 'ä3£67', 7, 'stripped, with multi byte chars');
        $testText('àc4e67h9j …  ', 'àc4e67h9j 2mno6', 16, 'stripped, to word boundry');
        $testText('1bc4è 8ij1.…   ', '1bc4è 8ij1.m4õq8', 18, 'stripped, to word boundry');
        $testText('abc efghijk mnopqrstuvwzxzabcdefghijklmn… ', 'abc efghijk mnopqrstuvwzxzabcdefghijklmnôpq', 44, 'stripped, word too long');
    }
}
