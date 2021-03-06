<?php

namespace Tests\CubeTools\CubeCommonBundle\Filter;

use CubeTools\CubeCommonBundle\Filter\FilterEntityQueryBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2018-05-03 at 12:29:04.
 */
class FilterEntityQueryBuilderTest extends TestCase
{
    /**
     * @var \CubeTools\CubeCommonBundle\Filter\FilterEntityQueryBuilder
     */
    protected $object;

    /**
     * @var object entity, against which filter condition are compared
     */
    protected $analysedEntity;

    /**
     * Setting mock objects for tests.
     *
     * @before
     */
    protected function setUpMock()
    {
        $this->object = new FilterEntityQueryBuilder();

        $this->analysedEntity = $this->getMockBuilder('MockNotExistingEntity')
            ->setMethods(array('getTitle', 'getPosition', 'getZeroValue', 'getNullValue', 'getFalseValue', 'getActualDate', 'getOneWeekBeforeDate', 'getContainsCollection', 'getNotContainsCollection', 'getRelatedEntity', 'getNotRelatedEntity'))
            ->getMock();
        $this->analysedEntity->expects($this->any())
            ->method('getTitle')
            ->will($this->returnValue('This is test title.'))
        ;
        $this->analysedEntity->expects($this->any())
            ->method('getPosition')
            ->will($this->returnValue(100))
        ;
        $this->analysedEntity->expects($this->any())
            ->method('getZeroValue')
            ->will($this->returnValue(0))
        ;
        $this->analysedEntity->expects($this->any())
            ->method('getNullValue')
            ->will($this->returnValue(null))
        ;
        $this->analysedEntity->expects($this->any())
            ->method('getFalseValue')
            ->will($this->returnValue(false))
        ;
        $actualDateTime = new \DateTime();
        $this->analysedEntity->expects($this->any())
            ->method('getActualDate')
            ->will($this->returnValue($actualDateTime))
        ;
        $beforeDate = clone $actualDateTime;
        $beforeDate = $beforeDate->modify('-1 week');
        $this->analysedEntity->expects($this->any())
            ->method('getOneWeekBeforeDate')
            ->will($this->returnValue($beforeDate))
        ;

        // setting mock collection:
        $mockContainsCollection = $this->getMockBuilder('MockExistingCollection')
            ->setMethods(array('contains'))
            ->getMock()
        ;
        $mockContainsCollection->expects($this->any())
            ->method('contains')
            ->will($this->returnValue(true))
        ;
        $this->analysedEntity->expects($this->any())
            ->method('getContainsCollection')
            ->will($this->returnValue($mockContainsCollection))
        ;
        $mockNotContainsCollection = $this->getMockBuilder('MockNotExistingCollection')
            ->setMethods(array('contains'))
            ->getMock()
        ;
        $mockNotContainsCollection->expects($this->any())
            ->method('contains')
            ->will($this->returnValue(false))
        ;
        $this->analysedEntity->expects($this->any())
            ->method('getNotContainsCollection')
            ->will($this->returnValue($mockNotContainsCollection))
        ;

        $relatedEntity = $this->getMockBuilder('relatedEntity')
            ->setMethods(array('getId'))
            ->getMock()
        ;
        $relatedEntity->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(2))
        ;
        $this->analysedEntity->expects($this->any())
            ->method('getRelatedEntity')
            ->will($this->returnValue($relatedEntity))
        ;
        $notRelatedEntity = $this->getMockBuilder('notRelatedEntity')
            ->setMethods(array('getId'))
            ->getMock()
        ;
        $notRelatedEntity->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(3))
        ;
        $this->analysedEntity->expects($this->any())
            ->method('getNotRelatedEntity')
            ->will($this->returnValue($notRelatedEntity))
        ;

        $this->object->setAnalysedEntity($this->analysedEntity);
    }

    /**
     * @covers CubeTools\CubeCommonBundle\Filter\FilterEntityQueryBuilder::evaluateExpression
     */
    public function testEvaluateExpression()
    {
        $this->assertTrue(
            $this->object->evaluateExpression(FilterEntityQueryBuilder::EXPRESSION_LIKE, $this->analysedEntity->getTitle(), 'is test')
        );
        $this->assertFalse(
            $this->object->evaluateExpression(FilterEntityQueryBuilder::EXPRESSION_LIKE, $this->analysedEntity->getTitle(), 'is not test')
        );
        $this->assertTrue(
            $this->object->evaluateExpression(FilterEntityQueryBuilder::EXPRESSION_LIKE, $this->analysedEntity->getTitle(), 'title.')
        );
        $this->assertTrue(
            $this->object->evaluateExpression(FilterEntityQueryBuilder::EXPRESSION_LIKE, $this->analysedEntity->getTitle(), 'This')
        );
        $this->assertTrue(
            $this->object->evaluateExpression(FilterEntityQueryBuilder::EXPRESSION_LIKE, $this->analysedEntity->getTitle(), '%This')
        );
        $this->assertTrue(
            $this->object->evaluateExpression(FilterEntityQueryBuilder::EXPRESSION_LIKE, $this->analysedEntity->getTitle(), 'title%')
        );
        $this->assertTrue(
            $this->object->evaluateExpression(FilterEntityQueryBuilder::EXPRESSION_LIKE, $this->analysedEntity->getTitle(), '%is%')
        );

        $this->assertTrue(
            $this->object->evaluateExpression(FilterEntityQueryBuilder::EXPRESSION_EQUAL, $this->analysedEntity->getTitle(), 'This is test title.')
        );
        $this->assertFalse(
            $this->object->evaluateExpression(FilterEntityQueryBuilder::EXPRESSION_EQUAL, $this->analysedEntity->getTitle(), 'This is simply not test title.')
        );

        $this->assertTrue(
            $this->object->evaluateExpression(FilterEntityQueryBuilder::EXPRESSION_NOT_ZERO, $this->analysedEntity->getPosition())
        );
        $this->assertFalse(
            $this->object->evaluateExpression(FilterEntityQueryBuilder::EXPRESSION_NOT_ZERO, $this->analysedEntity->getZeroValue())
        );

        $this->assertTrue(
            $this->object->evaluateExpression(FilterEntityQueryBuilder::EXPRESSION_NOT_NULL, $this->analysedEntity->getPosition())
        );
        $this->assertFalse(
            $this->object->evaluateExpression(FilterEntityQueryBuilder::EXPRESSION_NOT_NULL, $this->analysedEntity->getNullValue())
        );

        $this->assertTrue(
            $this->object->evaluateExpression(FilterEntityQueryBuilder::EXPRESSION_NULL, $this->analysedEntity->getNullValue())
        );
        $this->assertFalse(
            $this->object->evaluateExpression(FilterEntityQueryBuilder::EXPRESSION_NULL, $this->analysedEntity->getPosition())
        );

        $this->assertTrue(
            $this->object->evaluateExpression(FilterEntityQueryBuilder::EXPRESSION_ZERO_OR_NULL, $this->analysedEntity->getNullValue())
        );
        $this->assertTrue(
            $this->object->evaluateExpression(FilterEntityQueryBuilder::EXPRESSION_ZERO_OR_NULL, $this->analysedEntity->getZeroValue())
        );
        $this->assertTrue(
            $this->object->evaluateExpression(FilterEntityQueryBuilder::EXPRESSION_ZERO_OR_NULL, $this->analysedEntity->getFalseValue())
        );
        $this->assertFalse(
            $this->object->evaluateExpression(FilterEntityQueryBuilder::EXPRESSION_ZERO_OR_NULL, $this->analysedEntity->getPosition())
        );

        // 100 >= 50
        $this->assertTrue(
            $this->object->evaluateExpression(FilterEntityQueryBuilder::EXPRESSION_MORE_OR_EQUAL, $this->analysedEntity->getPosition(), 50)
        );
        // 100 >= 100
        $this->assertTrue(
            $this->object->evaluateExpression(FilterEntityQueryBuilder::EXPRESSION_MORE_OR_EQUAL, $this->analysedEntity->getPosition(), 100)
        );
        // 0 >= -1
        $this->assertTrue(
            $this->object->evaluateExpression(FilterEntityQueryBuilder::EXPRESSION_MORE_OR_EQUAL, $this->analysedEntity->getZeroValue(), -1)
        );
        // 1 >= -1
        $this->assertTrue(
            $this->object->evaluateExpression(FilterEntityQueryBuilder::EXPRESSION_MORE_OR_EQUAL, $this->analysedEntity->getActualDate(), $this->analysedEntity->getOneWeekBeforeDate())
        );
        $this->assertTrue(
            $this->object->evaluateExpression(FilterEntityQueryBuilder::EXPRESSION_MORE_OR_EQUAL, $this->analysedEntity->getActualDate(), $this->analysedEntity->getActualDate())
        );
        // 0 >= 1
        $this->assertFalse(
            $this->object->evaluateExpression(FilterEntityQueryBuilder::EXPRESSION_MORE_OR_EQUAL, $this->analysedEntity->getZeroValue(), 1)
        );
        // 100 >= 110
        $this->assertFalse(
            $this->object->evaluateExpression(FilterEntityQueryBuilder::EXPRESSION_MORE_OR_EQUAL, $this->analysedEntity->getPosition(), 110)
        );
        $this->assertFalse(
            $this->object->evaluateExpression(FilterEntityQueryBuilder::EXPRESSION_MORE_OR_EQUAL, $this->analysedEntity->getOneWeekBeforeDate(), $this->analysedEntity->getActualDate())
        );

        // 0 < 1
        $this->assertTrue(
            $this->object->evaluateExpression(FilterEntityQueryBuilder::EXPRESSION_DATE_RANGE_TO, $this->analysedEntity->getOneWeekBeforeDate(), $this->analysedEntity->getActualDate())
        );
        $this->assertTrue(
            $this->object->evaluateExpression(FilterEntityQueryBuilder::EXPRESSION_DATE_RANGE_TO, $this->analysedEntity->getActualDate(), $this->analysedEntity->getActualDate())
        );
        $this->assertFalse(
            $this->object->evaluateExpression(FilterEntityQueryBuilder::EXPRESSION_DATE_RANGE_TO, $this->analysedEntity->getActualDate(), $this->analysedEntity->getOneWeekBeforeDate())
        );

        $this->assertTrue(
            $this->object->evaluateExpression(FilterEntityQueryBuilder::EXPRESSION_IN, $this->analysedEntity->getContainsCollection(), array(new \stdClass()))
        );
        $this->assertFalse(
            $this->object->evaluateExpression(FilterEntityQueryBuilder::EXPRESSION_IN, $this->analysedEntity->getNotContainsCollection(), array(new \stdClass()))
        );
        $this->assertTrue(
            $this->object->evaluateExpression(FilterEntityQueryBuilder::EXPRESSION_IN, $this->analysedEntity->getRelatedEntity(), array($this->analysedEntity->getRelatedEntity()))
        );
        $this->assertFalse(
            $this->object->evaluateExpression(FilterEntityQueryBuilder::EXPRESSION_IN, $this->analysedEntity->getRelatedEntity(), array($this->analysedEntity->getNotRelatedEntity()))
        );
        $this->assertTrue(
            $this->object->evaluateExpression(FilterEntityQueryBuilder::EXPRESSION_IN, $this->analysedEntity->getRelatedEntity(), array($this->analysedEntity->getNotRelatedEntity(), $this->analysedEntity->getRelatedEntity()))
        );
        $this->assertTrue(
            $this->object->evaluateExpression(FilterEntityQueryBuilder::EXPRESSION_IN, $this->analysedEntity->getRelatedEntity(), array($this->analysedEntity->getRelatedEntity()), $this->analysedEntity->getNotRelatedEntity())
        );
        $this->assertTrue(
            $this->object->evaluateExpression(FilterEntityQueryBuilder::EXPRESSION_IN, $this->analysedEntity->getContainsCollection(), $this->analysedEntity->getRelatedEntity())
        );
        $this->assertFalse(
            $this->object->evaluateExpression(FilterEntityQueryBuilder::EXPRESSION_IN, $this->analysedEntity->getNotContainsCollection(), $this->analysedEntity->getNotRelatedEntity())
        );
    }

    /**
     * @covers CubeTools\CubeCommonBundle\Filter\FilterEntityQueryBuilder::executeCondition
     * @todo   Implement testExecuteCondition().
     */
    public function testExecuteCondition()
    {
        $this->object->resetObject();
        $this->object->setParameter('title', 'This is test title.');
        $this->object->executeCondition('s.title LIKE :title');
        $this->assertTrue(
            boolval(count($this->object->getResult()))
        );

        $this->object->resetObject();
        $this->object->setParameter('title', 'This is NOT test title.');
        $this->object->executeCondition('s.title = :title');
        $this->assertFalse(
            boolval(count($this->object->getResult()))
        );
    }

    /**
     * @covers CubeTools\CubeCommonBundle\Filter\FilterEntityQueryBuilder::andWhere
     */
    public function testAndWhere()
    {
        $this->object->resetObject();
        $this->object->setParameter('position', 50);
        $this->object->andWhere('s.position >= :position');
        $this->assertTrue(
            boolval(count($this->object->getQuery()->getResult()))
        );

        $this->object->resetObject();
        $this->object->setParameter('position', 200);
        $this->object->andWhere('s.position >= :position');
        $this->assertFalse(
            boolval(count($this->object->getQuery()->getResult()))
        );

        $this->object->resetObject();
        $this->object->andWhere('s.position = 0 OR s.position IS NULL');
        $this->assertFalse(
            boolval(count($this->object->getQuery()->getResult()))
        );

        $this->object->resetObject();
        $this->object->andWhere('s.position IS NULL');
        $this->assertFalse(
            boolval(count($this->object->getQuery()->getResult()))
        );

        $this->object->resetObject();
        $this->object->andWhere('s.position IS NOT NULL');
        $this->assertTrue(
            boolval(count($this->object->getQuery()->getResult()))
        );
    }

    /**
     * @covers CubeTools\CubeCommonBundle\Filter\FilterEntityQueryBuilder::andWhereIn
     */
    public function testWhereIn()
    {
        $this->object->resetObject();
        $this->object->setParameter('sccIds', array(1, 2, 3));
        $this->object->leftJoin('s.containsCollection', 'scc');
        $this->object->andWhereIn('scc.id IN (:sccIds)');
        $this->assertTrue(
            boolval(count($this->object->getQuery()->getResult()))
        );

        $this->object->resetObject();
        $this->object->setParameter('sccIds', array(1, 2, 3));
        $this->object->leftJoin('s.notContainsCollection', 'scc');
        $this->object->andWhereIn('scc.id IN (:sccIds)');
        $this->assertFalse(
            boolval(count($this->object->getQuery()->getResult()))
        );

        $this->object->resetObject();
        $this->object->setParameter('sccIds', array(1, 2, 3));
        $this->object->leftJoin('s.containsCollection', 'scc');
        $this->object->addGetterProvider('getContainsCollection', array('getNotContainsCollection'));
        $this->object->andWhereIn('scc.id IN (:sccIds)');
        $this->assertFalse(
            boolval(count($this->object->getQuery()->getResult()))
        );

        $this->object->resetObject();
        $this->object->setParameter('sccIds', array(1, 2, 3));
        $this->object->leftJoin('s.notContainsCollection', 'scc');
        $this->object->addGetterProvider('getNotContainsCollection', array('getContainsCollection'));
        $this->object->andWhereIn('scc.id IN (:sccIds)');
        $this->assertTrue(
            boolval(count($this->object->getQuery()->getResult()))
        );

        $this->object->resetObject();
        $this->object->setParameter('sccIds', array(1, 2, 3));
        $this->object->leftJoin('s.containsCollection', 'scc');
        $this->object->addGetterProvider('getContainsCollection', array('getNotContainsCollection'));
        $this->object->addGetterProvider('getContainsCollection', array('getContainsCollection'));
        $this->object->andWhereIn('scc.id IN (:sccIds)');
        $this->assertTrue(
            boolval(count($this->object->getQuery()->getResult()))
        );

        $this->object->resetObject();
        $this->object->setParameter('sccIds', array(1, 2, 3));
        $this->object->leftJoin('s.containsCollection', 'scc');
        $this->object->addGetterProvider('getContainsCollection', array('getNotContainsCollection'));
        $this->object->addGetterProvider('getContainsCollection', array('getNotContainsCollection'));
        $this->object->andWhereIn('scc.id IN (:sccIds)');
        $this->assertFalse(
            boolval(count($this->object->getQuery()->getResult()))
        );
    }
}
