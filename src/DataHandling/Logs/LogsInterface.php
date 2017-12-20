<?php
namespace CubeTools\CubeCommonBundle\DataHandling\Logs;

interface LogsInterface
{
    /**
     * Method for getting diff array for given entity for all versions (changes between subsequent versions).
     *
     * @param object $entity entity for which we want to get the log
     *
     * @return array subsequent elements are diff for each version
     */
    public function getAllVersionsDiffArray($entity);
}
