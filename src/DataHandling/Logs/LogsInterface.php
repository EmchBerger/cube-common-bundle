<?php

namespace CubeTools\CubeCommonBundle\DataHandling\Logs;

interface LogsInterface
{
    /**
     * key for subtable with deleted elements.
     */
    const KEY_REMOVE = 'removed';

    /**
     * key for subtable with added elements.
     */
    const KEY_ADD = 'added';

    /**
     * key for subtable with elements, which has not changed.
     */
    const KEY_UNCHANGED = 'unchanged';

    /**
     * Method for getting diff array for given entity for all versions (changes between subsequent versions).
     *
     * return value:
     * [
     *     $editId1 => [
     *         $attrName => $anyValue
     *     ],
     *     $editId2 => ...
     * ]
     * the oldest change is listed first.
     *
     * with $anyValue:
     *   new value for simple types (string, date, ...)
     *   for array based types:
     *   [
     *      KEY_REMOVED   => [$removed, $elements, ...],
     *      KEY_ADDED     => [$added, $elements, ...],
     *      KEY_UNCHANGED => [$unchanged, $elements, ...],
     *    ]
     *
     * @param object $entity entity for which we want to get the log
     *
     * @return array subsequent elements are diff for each version
     */
    public function getAllVersionsDiffArray($entity);
}
