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
}
