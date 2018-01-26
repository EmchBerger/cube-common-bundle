<?php

namespace CubeTools\CubeCommonBundle\DataHandling\Logs;

abstract class AbstractBaseAudit implements LogsInterface
{
    /**
     * Method for filtering the diff of a change. Can be override for customization needs.
     *
     * Returning an empty array deletes this diff.
     *
     * Is called once for each change.
     *
     * @param array $versionDiff
     *
     * @return mixed[]
     */
    protected function filterVersionChange(array $versionDiff)
    {
        return $versionDiff;
    }

    /**
     * Method for filtering a property with KEY_ADD, .... Can be override for customization needs.
     *
     * Returning an empty array deletes the propertys diff. Other values are the new property value.
     *
     * Is called once for each property of each change.
     *
     * @param string[][] $propertyDiff
     * @param string     $propertyName
     *
     * @return string[][]|string
     */
    protected function filterMultiValueProperty(array $propertyDiff, $propertyName)
    {
        return $propertyDiff;
    }
}
