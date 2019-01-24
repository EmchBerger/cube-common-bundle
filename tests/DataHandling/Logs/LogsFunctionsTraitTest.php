<?php

namespace Tests\CubeTools\CubeCommonBundle\DataHandling\Logs;

use CubeTools\CubeCommonBundle\DataHandling\Logs\LogsFunctionsTrait;
use PHPUnit\Framework\TestCase;

class LogsFunctionsTraitTest extends TestCase
{
    /**
     * Tests method for processing flags.
     */
    public function testProcessDiffFlags()
    {
        $this->assertThisAttributes(
            array('field1' => 'diff1', 'field2' => 'Yes'),
            LogsFunctionsTrait::processDiffFlags(
                array('field1' => 'diff1', 'field2' => true),
                array('field2')
            )
        );

        $this->assertThisAttributes(
            array('field2' => 'No'),
            LogsFunctionsTrait::processDiffFlags(
                array('field2' => false),
                array('field2')
            )
        );

        $this->assertThisAttributes(
            array('field1' => 'diff1', 'field2' => 'Perfect'),
            LogsFunctionsTrait::processDiffFlags(
                array('field1' => 'diff1', 'field2' => true),
                array('field2'),
                'Perfect',
                'Bad'
            )
        );

        $this->assertThisAttributes(
            array('field1' => 'diff1', 'field2' => 'Bad'),
            LogsFunctionsTrait::processDiffFlags(
                array('field1' => 'diff1', 'field2' => false),
                array('field2'),
                'Perfect',
                'Bad'
            )
        );

        $this->assertThisAttributes(
            array('field1' => 'diff1', 'field2' => true),
            LogsFunctionsTrait::processDiffFlags(
                array('field1' => 'diff1', 'field2' => true),
                array('field3')
            )
        );
    }

    /**
     * Tests method for processing nulls.
     */
    public function testProcessDiffNulls()
    {
        $this->assertThisAttributes(
            array('field1' => 'diff1', 'field2' => '-'),
            LogsFunctionsTrait::processDiffNulls(
                array('field1' => 'diff1', 'field2' => null)
            )
        );

        $this->assertThisAttributes(
            array('field1' => 'diff1', 'field2' => 'No data'),
            LogsFunctionsTrait::processDiffNulls(
                array('field1' => 'diff1', 'field2' => null),
                'No data'
            )
        );
    }

    /**
     * Tests testCalculateAttributesAt().
     */
    public function testCalculateAttributesAt()
    {
        $changes = array();
        $stopAt = false;
        $ret = LogsFunctionsTrait::calculateAttributesAt($changes, $stopAt);
        $this->assertNoAttributes($ret);

        $changes = array(
            '14_1' => $this->getOneChange('2018-02-01 11:06:26', 'u7', array('c' => 5, 'o' => 'Oo')),
            '12_7' => $this->getOneChange('2018-01-28 08:44:23', 'u3', array('c' => 9, 'r' => '')),
            '9_' => $this->getOneChange('2018-01-22 16:26:58', '', array('r' => 'lfks')),
        );

        $ret = LogsFunctionsTrait::calculateAttributesAt($changes, $stopAt);
        $expected1 = array(
            'c' => 5,
            'r' => '',
            'o' => 'Oo',
        );
        $this->assertThisAttributes($expected1, $ret);

        $stopAt = -1;
        $ret = LogsFunctionsTrait::calculateAttributesAt($changes, $stopAt);
        $this->assertNoAttributes($ret);

        $stopAt = 0;
        $ret = LogsFunctionsTrait::calculateAttributesAt($changes, $stopAt);
        $expected2 = array(
            'r' => 'lfks',
        );
        $this->assertThisAttributes($expected2, $ret);

        $stopAt = 1;
        $ret = LogsFunctionsTrait::calculateAttributesAt($changes, $stopAt);
        $expected3 = array(
            'c' => 9,
            'r' => '',
        );
        $this->assertThisAttributes($expected3, $ret);

        $stopAt = 5;
        $ret = LogsFunctionsTrait::calculateAttributesAt($changes, $stopAt);
        $this->assertThisAttributes($expected1, $ret);

        // too early change
        $stopAt = new \DateTime('2017-11-30 13:39:02');
        $ret = LogsFunctionsTrait::calculateAttributesAt($changes, $stopAt);
        $this->assertNoAttributes($ret);

        // after last change => all
        $stopAt = new \DateTime('2018-02-01 12:33:17');
        $ret = LogsFunctionsTrait::calculateAttributesAt($changes, $stopAt);
        $this->assertThisAttributes($expected1, $ret);

        // between changes
        $stopAt = new \DateTime('2018-01-29 17:42:36');
        $ret = LogsFunctionsTrait::calculateAttributesAt($changes, $stopAt);
        $this->assertThisAttributes($expected3, $ret);

        // exactly on a change date
        $stopAt = new \DateTime('2018-01-22 16:26:58');
        $ret = LogsFunctionsTrait::calculateAttributesAt($changes, $stopAt);
        $this->assertThisAttributes($expected2, $ret);

        // invalid
        $stopAt = true;
        $this->expectException(\InvalidArgumentException::class);
        LogsFunctionsTrait::calculateAttributesAt($changes, $stopAt);
    }

    private function getOneChange($savedAt, $savedBy, array $changes)
    {
        if (!($savedAt instanceof \DateTimeInterface)) {
            $savedAt = new \DateTime($savedAt);
        }

        return array('savedAt' => $savedAt, 'savedBy' => $savedBy, 'changes' => $changes);
    }

    private function assertThisAttributes(array $expected, array $values, $msg = null)
    {
        $this->assertEquals($expected, $values, $msg); // do not care for order => assertEquals
    }

    private function assertNoAttributes(array $values, $msg = null)
    {
        $this->assertSame(array(), $values, $msg);
    }
}
