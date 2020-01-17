<?php

namespace Tests\CubeTools\CubeCommonBundle\Filter;

use CubeTools\CubeCommonBundle\Filter\FilterQueryCondition;
use CubeTools\CubeCommonBundle\Filter\FilterConstants;
use PHPUnit\Framework\TestCase;

class FilterQueryConditionTest extends TestCase
{
    /**
     * Tests some basic functionality.
     */
    public function testBasics()
    {
        $filter = new FilterQueryCondition(array(
            't' => 75,
            'U' => 'kd',
            'v' => 1,
            'w' => '',
            'x' => 0,
            'y' => null,
            'z' => false,
            'a' => array(),
            'b' => new FilterQueryCondition(array()),
        ));

        $this->assertSame(75, $filter['t']);
        $this->assertCount(9, $filter);

        $this->assertNotEmpty($filter['v'], 'isset');   // 1 seems to be returned, so not assertTrue
        $this->assertEmpty($filter['d'], 'isset');
        $this->assertEmpty($filter['v']['e'], 'isset subelement');

        $this->assertTrue($filter->isActive('U'), 'isActive int');
        $this->assertFalse($filter->isActive('f'), 'isActive unset');
        $this->assertFalse($filter->isActive('w'), 'isActive ""');
        $this->assertTrue($filter->isActive('x'), 'isActive 0');
        $this->assertFalse($filter->isActive('y'), 'isActive null');
        $this->assertTrue($filter->isActive('z'), 'isActive false');
        $this->assertFalse($filter->isActive('a'), 'isActive array()');
        $this->assertFalse($filter->isActive('b'), 'isActive count=0'); // count is 0

        $this->assertTrue($filter->anyActive(), 'anyActive');

        $asP = $filter->getAsParameters();
        $this->assertCount(5, $asP);
        $this->assertArrayHasKey('x', $asP);
        $asPf = $filter->getAsParameters(array('x', 'g', 'z', 'b'));
        $this->assertCount(3, $asPf);
        $this->assertArrayNotHasKey('x', $asPf);

        // no error when condition not met:
        $filter->andWhereEqual('dbTable', 'notSet'); // not set
        $filter->andWhereIn('dbTbl', 'y'); // null
        $filter->andWhereLike('dbT', 'y', 'dbCol'); // null
        $this->assertTrue(true, 'no exception thrown');
    }

    public function testAndWhereFunctions()
    {
        $noWhereCalls = 9;
        $mQb = $this->getMockedQueryBuilder(array('andWhere', 'setParameter'));
        $mQb->expects($this->exactly($noWhereCalls))->method('andWhere')->will($this->returnSelf());
        $mQb->expects($this->exactly($noWhereCalls - 3 + 3))->method('setParameter')->will($this->returnSelf());

        $filter = new FilterQueryCondition(array(
            'f' => 'sae',
            'g' => array('from' => 1, 'to' => null),
            'h' => array('from' => 9, 'to' => 11),
            'i' => FilterConstants::WHERE_IS_NOT_SET,
            'j' => FilterConstants::WHERE_IS_SET,
        ));
        $filter->setQuerybuilder($mQb);

        $filter->andWhereEqual('dbTable', 'f');
        $filter->andWhereLike('table', 'f', 'dbColmn'); // 2 calls on setParameters (2 variants)
        $filter->andWhereIn('dbTbl', 'f');
        $filter->andWhereIsSetIsNotSet('dbT', 'i'); // 0 calls on setParameter
        $filter->andWhereIsSetIsNotSet('dbT', 'j', 'dbC'); // 0 calls on setParameter
        $filter->andWhereCheckedValue('dTbl', 'f', 'dbCol'); // 0 calls on setParameter
        $filter->andWhereDaterange('tbl', 'g');
        $filter->andWhereDaterange('tblNam', 'h'); // 2 Calls on both

        $filter->setFilterParameter('f');
        $filter->setFilterParameter('g', 'parInDb');
    }

    public function testQueryBuilderFunctions()
    {
        $returnGetParameter = rand();
        $mQb = $this->getMockedQueryBuilder(array('orWhere', 'join', 'getParameter'));
        $mQb->expects($this->once())->method('orWhere');
        $mQb->expects($this->once())->method('join')->will($this->returnSelf());
        $mQb->expects($this->once())->method('getParameter')->willReturn($returnGetParameter);

        $filter = new FilterQueryCondition(array(
            'g' => 23,
        ));
        $filter->setQuerybuilder($mQb);

        $filter->orWhere('x.y = 3');
        $this->assertSame($filter, $filter->join('tbl.extRef', 'e'));
        $this->assertSame($returnGetParameter, $filter->getParameter('aPar'));

        $this->expectException('BadMethodCallException');
        $filter->nonExistingMethod('jdfklsa');
    }

    private function getMockedQueryBuilder(array $methods)
    {
        return $this->getMockBuilder('dummy\QueryBuilder')
            ->disableAutoload()
            ->setMethods($methods)
            ->getMock();
    }
}
