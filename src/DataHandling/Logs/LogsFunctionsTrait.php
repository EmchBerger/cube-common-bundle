<?php

namespace CubeTools\CubeCommonBundle\DataHandling\Logs;

trait LogsFunctionsTrait
{
    /**
     * Filters out the changes of one property, suitable for LogsInterface::getVersionsOfProperty().
     *
     * @param mixed[] $allVersionChanges data formatted as returned by LogsInterface::getAllVersionsDiff()
     * @param string  $columnName        name of column to get property for
     *
     * @return mixed[]
     */
    protected function filterLogForOneProperty($allVersionChanges, $columnName)
    {
        foreach ($allVersionChanges as $versionKey => $value) {
            $changes = $value['changes'];
            if (isset($changes[$columnName])) {
                $value['changes'] = $changes[$columnName];
                yield $versionKey => $value;
            }
        }
    }
}
