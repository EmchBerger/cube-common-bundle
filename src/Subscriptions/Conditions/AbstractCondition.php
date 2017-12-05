<?php
namespace CubeTools\CubeCommonBundle\Subscriptions\Conditions;

use Doctrine\Common\Persistence\ObjectManager;

/**
 * Abstract class for subscription conditions.
 */
abstract class AbstractCondition
{
    /**
     * Key with project value
     */
    const KEY_PROJECT = 'project';

    /**
     * key for element with elements (id or entity)
     */
    const KEY_ELEMENTS = 'elements';

    /**
     * @var \Doctrine\Common\Persistence\ObjectManager
     */
    protected $em;

    /**
     * @var array filter for specifying range of data, for which condition is checked
     */
    protected $filterData;

    /**
     * @var array data with output after condition checking
     */
    protected $outputData = array();

    public function __construct(ObjectManager $em)
    {
        $this->em = $em;
    }

    /**
     * Setter for filter data.
     * @param array $filterData filter for condition
     * @return \CubeTools\CubeCommonBundle\Subscriptions\Conditions\AbstractCondition object, on which method has been executed
     */
    public function setFilterData($filterData)
    {
        $this->filterData = $filterData;

        return $this;
    }

    /**
     * Method  checking, if condition is fulfilled.
     *
     * @return boolean true, if condition is fulfilled
     */
    abstract public function isConditionFulfilled();

    /**
     * Method returning data after condition checking.
     * @return array data gathered during isConditionFulfilled
     */
    public function getOutputData()
    {
        return $this->outputData;
    }
}
