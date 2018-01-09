<?php

namespace CubeTools\CubeCommonBundle\DataHandling\Logs;

use DataDog\AuditBundle\Entity\AuditLog;
use DataDog\AuditBundle\Entity\Association;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class handling data from DataDogAuditBundle.
 */
class DataDogAudit implements LogsInterface
{
    /**
     * @var ObjectManager
     */
    protected $em;

    public function __construct(ObjectManager $em)
    {
        $this->em = $em;
    }

    /**
     * Method for getting AuditLog of all versions of given entity.
     *
     * @param object $entity entity for which we want to get all versions
     *
     * @return AuditLog[] versions of entity
     */
    public function getAllVersions($entity)
    {
        return $this->em->getRepository(AuditLog::class)->findBy(array('source' => $this->getAssociations($entity)), array('id' => 'ASC'));
    }

    public function getAllVersionsDiffArray($entity)
    {
        // doc is in interface

        $entityVersions = $this->getAllVersions($entity);
        $diffArray = array();

        /** @var AuditLog $currentVersion */
        foreach ($entityVersions as $currentVersion) {
            $versionKey = $this->getVersionKey($currentVersion, $diffArray);
            if (!isset($diffArray[$versionKey])) {
                $diffArray[$versionKey] = array();
            }
            $diffArray[$versionKey] = $this->getCurrentVersionElement($currentVersion, $diffArray[$versionKey]);
        }

        return $this->removeColumnIfOnlyUnchanged($diffArray);
    }

    /**
     * Method for getting associations for given entity.
     *
     * @param object $entity object with entity
     *
     * @return array entities
     */
    protected function getAssociations($entity)
    {
        $associations = $this->em->getRepository(Association::class)->findBy(array('class' => get_class($entity), 'fk' => $entity->getId()));

        return $associations;
    }

    /**
     * Method creating version key (to group table entries associated with the same user action).
     *
     * @param AuditLog $currentVersion entity for which version key is created
     * @param array    $diffArray      subsequent elements are diff for each version
     *
     * @return string version key (timestamp and user id)
     */
    protected function getVersionKey(AuditLog $currentVersion, array $diffArray)
    {
        $versionTimestamp = $currentVersion->getLoggedAt()->getTimestamp();

        $versionUser = $currentVersion->getBlame() ? $currentVersion->getBlame()->getFk() : -1;
        $versionKeyNormal = sprintf('%d_%d', $versionTimestamp, $versionUser); // having user and time prevent from logging in the same place simoultaneus changes from more then one user
        $versionKeyBefore = sprintf('%d_%d', $versionTimestamp - 1, $versionUser); // 1 second before by the same user
        $versionKeyAfter = sprintf('%d_%d', $versionTimestamp + 1, $versionUser); // 1 second after by the same user

        if (isset($diffArray[$versionKeyBefore])) {
            $versionKey = $versionKeyBefore;
        } elseif (isset($diffArray[$versionKeyAfter])) {
            $versionKey = $versionKeyAfter;
        } else {
            $versionKey = $versionKeyNormal;
        }

        return $versionKey;
    }

    /**
     * Method creating diff for given log entry.
     *
     * @param AuditLog $currentVersion
     * @param array    $diffElement    current state of diff for this version (one version can consist of more then one log entry)
     *
     * @return array diff element for given version
     */
    protected function getCurrentVersionElement(AuditLog $currentVersion, array $diffElement)
    {
        if ($currentVersion->getAction() === 'associate') {
            $diffElement[$this->getColumnNameForAssociation($currentVersion)][self::KEY_ADD][$currentVersion->getTarget()->getFk()] = $currentVersion->getTarget()->getLabel();
        } elseif ($currentVersion->getAction() === 'dissociate') {
            $columnName = $this->getColumnNameForAssociation($currentVersion);
            if (!isset($diffElement[$columnName][self::KEY_ADD][$currentVersion->getTarget()->getFk()])) {
                $diffElement[$columnName][self::KEY_REMOVE][$currentVersion->getTarget()->getFk()] = $currentVersion->getTarget()->getLabel();
            } else {    // when dissociate and associate on same element that means, that it was before
                unset($diffElement[$columnName][self::KEY_ADD][$currentVersion->getTarget()->getFk()]);
                $diffElement[$columnName][self::KEY_UNCHANGED][$currentVersion->getTarget()->getFk()] = $currentVersion->getTarget()->getLabel();
                if (empty($diffElement[$columnName][self::KEY_ADD])) {
                    unset($diffElement[$columnName][self::KEY_ADD]);
                }
            }
        } else {
            foreach ($currentVersion->getDiff() as $columnName => $diffValue) {
                $diffElement[$columnName] = $diffValue['new'];
            }
        }

        return $this->filterVersionElement($currentVersion, $diffElement);
    }

    /**
     * Method removes columns, for which changes stays only in unchanged key.
     *
     * @param array $diffArray subsequent elements are diff for each version
     *
     * @return array subsequent elements are diff for each version, filtered
     */
    protected function removeColumnIfOnlyUnchanged(array $diffArray)
    {
        foreach ($diffArray as $versionKey => $versionValue) {
            foreach (array_keys($versionValue) as $columnName) {
                $currentElement = $diffArray[$versionKey][$columnName];
                if (is_array($currentElement)
                        && isset($currentElement[self::KEY_UNCHANGED])
                        && !isset($currentElement[self::KEY_ADD])
                        && !isset($currentElement[self::KEY_REMOVE])
                ) {
                    unset($diffArray[$versionKey][$columnName]);
                }
            }
        }

        return $diffArray;
    }

    /**
     * Method for getting key storing information about given on input column name with association. Can be override for customization needs.
     *
     * @param AuditLog $currentVersion
     *
     * @return string name
     */
    protected function getColumnNameForAssociation(AuditLog $currentVersion)
    {
        return $currentVersion->getTarget()->getTbl();
    }

    /**
     * Method for filtering diff element. Can be override for customization needs.
     *
     * @param AuditLog $currentVersion
     * @param array    $diffElement
     *
     * @return array filtered diff element
     */
    protected function filterVersionElement(AuditLog $currentVersion, array $diffElement)
    {
        return $diffElement;
    }
}
