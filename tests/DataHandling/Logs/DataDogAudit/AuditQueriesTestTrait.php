<?php

namespace Tests\CubeTools\CubeCommonBundle\DataHandling\Logs\DataDogAudit;

use CubeTools\CubeCommonBundle\DataHandling\Logs\DataDogAudit\AuditQueries;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Querybuilder;

trait AuditQueriesTestTrait
{
    /**
     * Test if methods handling QueryBuilder modify some parts correctly.
     */
    public function testQueryBuilder()
    {
        $checkConditions = function (Querybuilder $qb) {
            $whereCond = $qb->getDQLPart('where');
            if ($whereCond instanceof Expr\Orx) {
                $whereCondCount = $whereCond->count();
            } elseif ($whereCond instanceof Expr) {
                $whereCondCount = 1;
            } else {
                $whereCondCount = 0;
            }

            return array('whereCount' => $whereCondCount);
        };

        $auditQ = $this->getTestObject();
        $entity = 11;

        // getAllVersionsQb
        $qb = $auditQ->createOwningSideQb($entity, 'Entity1');
        $checkBase = $checkConditions($qb);

        $auditQ->extendAuditLogWithAttributeQb($qb, 'OwningSide', array(4, 6));
        $checkOwning = $checkConditions($qb);

        // extendQbWithInverseSideAttribute
        $auditQ->extendAuditLogWithAttributeQb($qb, 'OtherSide', array(9, 11, 17));
        $checkOther = $checkConditions($qb);

        $this->assertGreaterThan($checkBase['whereCount'], $checkOwning['whereCount'], 'new where condition missing (owning)');
        $this->assertGreaterThan($checkOwning['whereCount'], $checkOther['whereCount'], 'new where condition missing (other)');
    }

    /**
     * @return AuditQueries
     */
    protected function getTestObject()
    {
        return new AuditQueries($this->getEntityManagerInterface());
    }

    /**
     * @return \Doctrine\ORM\EntityManagerInterface
     */
    abstract protected function getEntityManagerInterface();
}
