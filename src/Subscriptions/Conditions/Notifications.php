<?php

namespace CubeTools\CubeCommonBundle\Subscriptions\Conditions;

/**
 * Class for checking, if analyzed entity meet requirements to be a subject of subscription.
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

    protected function setChangedColumns()
    {
        $changedColumns = array_keys($this->filterData[self::KEY_CHANGESET]);
        $triggerChangedColumns = array(); // columns, which are changed and trigger notification

        foreach ($changedColumns as $columnName) {
            if (empty($this->filterData[self::KEY_TRIGGER_CHANGED_COLUMNS]) || in_array($columnName, $this->filterData[self::KEY_TRIGGER_CHANGED_COLUMNS])) {
                $triggerChangedColumns[] = $columnName;
            }
        }
        $this->outputData[self::KEY_TRIGGER_CHANGED_COLUMNS] = $triggerChangedColumns;
    }

    /**
     * Method checks, if entity meets conditions for notification to be made.
     *
     * @return bool true if condition is fulfilled
     */
    public function isConditionFulfilled()
    {
        $this->setChangedColumns();

        return !empty($this->outputData[self::KEY_TRIGGER_CHANGED_COLUMNS]);
    }
}
