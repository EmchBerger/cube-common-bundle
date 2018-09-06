<?php

namespace CubeTools\CubeCommonBundle\Subscriptions\Conditions;

use CubeTools\CubeCommonBundle\Filter\FilterEntityQueryBuilder;
use CubeTools\CubeCommonBundle\Filter\FilterQueryCondition;

/**
 * Class for checking, if analysed entity meet requirements to be a subject of subscription.
 * Method checks, if specified columns where changed. Later filter are checked.
 */
class Notifications extends AbstractCondition
{
    /**
     * Key with changeset from entity
     */
    const KEY_CHANGESET = 'changeset';

    /**
     * Key with columns, which if changed, trigger a notification (in output data shows changed columns from those, whichc trigger notification)
     */
    const KEY_TRIGGER_CHANGED_COLUMNS = 'triggerChangedColumns';

    /**
     * Key with filter for entity before change (to be implemented later)
     */
    const KEY_FILTER_BEFORE = 'filterBefore';

    /**
     * Key with filter for entity after change (to be implemented later)
     */
    const KEY_FILTER_AFTER = 'filterAfter';

    /**
     * Key with updated entity
     */
    const KEY_ENTITY = 'entity';

    /**
     * Key with filterform before change
     */
    const KEY_FILTERFORM_BEFORE = 'form_before';

    /**
     * Key with filterform after change
     */
    const KEY_FILTERFORM_AFTER = 'form_after';

    /**
     * @var \CubeTools\CubeCommonBundle\Filter\FilterEntityQueryBuilder
     */
    protected $filterEntityQueryBuilder;

    /**
     * @var object object having method for creating query builder
     */
    protected $filterBuilderObject;

    /**
     * @var string name of method responsible for creating query builder on $this->filterBuilderObject
     */
    protected $filterBuilderMethodName;

    /**
     * Method setting entity query builder.
     *
     * @param \CubeTools\CubeCommonBundle\Filter\FilterEntityQueryBuilder $filterEntityQueryBuilder
     *
     * @return $this
     */
    public function setFilterEntityQueryBuilder(FilterEntityQueryBuilder $filterEntityQueryBuilder)
    {
        $this->filterEntityQueryBuilder = $filterEntityQueryBuilder;

        return $this;
    }

    /**
     * Method set object and method name for query builder.
     *
     * @param object $filterBuilderObject     object having method for creating query builder
     * @param string $filterBuilderMethodName name of method responsible for creating query builder (default: createQueryBuilder)
     *
     * @return $this
     */
    public function setFilterBuilderProvider($filterBuilderObject, $filterBuilderMethodName = 'createQueryBuilder')
    {
        $this->filterBuilderObject = $filterBuilderObject;
        $this->filterBuilderMethodName = $filterBuilderMethodName;

        return $this;
    }

    /**
     * Method checks, if entity meets conditions for notification to be made.
     *
     * @return bool true if condition is fulfilled
     */
    public function isConditionFulfilled()
    {
        if ($this->isFilterBeforeSet() || $this->isFilterAfterSet()) {
            // at least before or after filter is set
            $conditionFulfilled = $this->executeFilters();
        } else {
            $this->setChangedColumns();
            $conditionFulfilled = !empty($this->outputData[self::KEY_TRIGGER_CHANGED_COLUMNS]);
        }

        return $conditionFulfilled;
    }

    /**
     * Method setting information, which columns which were changed, were subject to trigger.
     */
    protected function setChangedColumns()
    {
        $changedColumns = array_keys($this->filterData[self::KEY_CHANGESET]);
        $triggerChangedColumns = array(); // columns, which are changed and trigger notification

        foreach ($changedColumns as $columnName) {
            if ((empty($this->filterData[self::KEY_TRIGGER_CHANGED_COLUMNS]) || empty($this->filterData[self::KEY_TRIGGER_CHANGED_COLUMNS][0])) || in_array($columnName, $this->filterData[self::KEY_TRIGGER_CHANGED_COLUMNS])) {
                $triggerChangedColumns[] = $columnName;
            }
        }
        $this->outputData[self::KEY_TRIGGER_CHANGED_COLUMNS] = $triggerChangedColumns;
    }

    /**
     * Method checks, if filter for entity before change is set.
     *
     * @return bool true if filter before change set
     */
    public function isFilterBeforeSet()
    {
        return (isset($this->filterData[self::KEY_FILTER_BEFORE]) && !is_null($this->filterData[self::KEY_FILTER_BEFORE]));
    }

    /**
     * Method checks, if filter for entity after change is set.
     *
     * @return bool true if filter after change set
     */
    public function isFilterAfterSet()
    {
        return (isset($this->filterData[self::KEY_FILTER_AFTER]) && !is_null($this->filterData[self::KEY_FILTER_AFTER]));
    }

    /**
     * Method building query on entity.
     *
     * @param \CubeTools\CubeCommonBundle\Filter\FilterQueryCondition $filter set Filter
     * @param \CubeTools\CubeCommonBundle\Form\Type\AbstractFilterType $filterform valid filterform
     * @param bool $useEntityQuery if false, filtering for entity is directly made on database (by default true)
     *
     * @return \Doctrine\ORM\QueryBuilder|\CubeTools\CubeCommonBundle\Filter\FilterEntityQueryBuilder type of return depends on value for $useEntityQuery flag
     */
    protected function buildQuery($filter, $filterform, $useEntityQuery = true)
    {
        if ($useEntityQuery) {
            $this->filterEntityQueryBuilder->resetObject();
            $this->filterEntityQueryBuilder->setAnalysedEntity($this->filterData[self::KEY_ENTITY]);
            $this->filterBuilderObject->{$this->filterBuilderMethodName}($filter, $filterform, $this->filterEntityQueryBuilder);
            $outputQb = $this->filterEntityQueryBuilder;
        } else {
            $qb = $this->filterBuilderObject->{$this->filterBuilderMethodName}($filter, $filterform);
            $qb->andWhere($qb->getRootAliases()[0].'.id = :entityId');
            $qb->setParameter('entityId', $this->filterData[self::KEY_ENTITY]->getId());
            $outputQb = $qb;
        }

        return $outputQb;
    }

    /**
     * Method checking analysed entity against filters.
     *
     * @return bool true if entity filters matches
     */
    protected function executeFilters()
    {
        if ($this->isFilterBeforeSet()) {
            $filterform = $this->filterData[self::KEY_FILTERFORM_BEFORE];
            $filter = new FilterQueryCondition($filterform->getData());
            $qb = $this->buildQuery($filter, $filterform, false);
            $filterBeforeFulfilled = boolval(count($qb->getQuery()->getResult()));
        } else {
            $filterBeforeFulfilled = true;
        }

        if ($this->isFilterAfterSet()) {
            $filterform = $this->filterData[self::KEY_FILTERFORM_AFTER];
            $filter = new FilterQueryCondition($filterform->getData());
            $qb = $this->buildQuery($filter, $filterform);
            $filterAfterFulfilled = boolval(count($qb->getQuery()->getResult()));
        } else {
            $filterAfterFulfilled = true;
        }

        return $filterBeforeFulfilled && $filterAfterFulfilled;
    }
}
