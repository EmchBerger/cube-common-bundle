<?php

namespace Tests\CubeTools\CubeCommonBundle\Subscriptions\Conditions;

use CubeTools\CubeCommonBundle\Subscriptions\Conditions\Notifications;

class NotificationsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests for static method prepareFilterFormData.
     */
    public function testPrepareFilterFormData()
    {
        $this->assertEquals(
            array(
                'field1' => 'abc',
                'field2' => array(2, 3, 4),
            ),
            Notifications::prepareFilterFormData(
                array(
                    'field1' => 'abc',
                    'field2' => array(2, 3, 4),
                )
            )
        );

        $this->assertEquals(
            array(
                'field1' => 'abc',
                'field2' => array(2, 3, 4),
            ),
            Notifications::prepareFilterFormData(
                array(
                    'field1' => 'abc',
                    'field2' => array(
                        2 => 'value1',
                        3 => 'value3',
                        4 => 'value4',
                    ),
                )
            )
        );
    }
}
