<?php
namespace CubeTools\CubeCommonBundle\DataHandling\Logs;

interface LogsInterface
{
    /**
     * Method for getting all versions of entity.
     * @param type $entity
     * @return array subsequent elements are entities
     */
    public function getAllVersions($entity);

    /**
     * Method for getting diff array for given entity for all versions (changes between subsequent versions).
     * @param Object $entity object with entity
     * @return array subsequent elements are diff for each version
     */
    public function getAllVersionsDiffArray($entity);
}
