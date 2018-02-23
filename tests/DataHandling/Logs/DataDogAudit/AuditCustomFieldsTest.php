<?php

namespace Tests\CubeTools\CubeCommonBundle\DataHandling\Logs\DataDogAudit;

use CubeTools\CubeCommonBundle\DataHandling\Logs\DataDogAudit\AuditCustomFields;
use PHPUnit\Framework\TestCase;

class AuditCustomFieldsTest extends TestCase
{
    public function testSetCustomFieldLabelPrefix()
    {
        $cfSrv = $this->getMockBuilder(AuditCustomFields::class)->disableOriginalConstructor()->setMethods(null)->getMock();

        $cfSrv->setCustomFieldLabelPrefix('');
        $cfSrv->setCustomFieldLabelPrefix('1 jie ä');
        $cfSrv->setCustomFieldLabelPrefix(7);

        $phpError = \PHPUnit\Framework\Error::class;
        if (!class_exists($phpError)) {
            $phpError = str_replace('\\', '_', $phpError);
        }
        $this->expectException($phpError);
        $cfSrv->setCustomFieldLabelPrefix(array());
    }
}
