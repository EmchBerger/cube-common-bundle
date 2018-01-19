<?php

namespace CubeTools\CubeCommonBundle\DataHandling\Logs;

use DataDog\AuditBundle\Entity\AuditLog;
use DataDog\AuditBundle\Entity\Association;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\QueryBuilder;

/**
 * Class handling data from DataDogAuditBundle.
 */
class DataDogAudit implements LogsInterface
{
    const TEMP_KEY_READD = 'temp_readd';
    const TEMP_KEY_OLDVAL = 'temp_oldval';

    /**
     * @var ObjectManager
     */
    protected $em;

    /**
     * @var mixed[] cached data for current entity type
     */
    protected $cache;

    /**
     * @var mixed[] cached data for current entity
     */
    protected $instanceCache = array();

    public function __construct(ObjectManager $em)
    {
        $this->em = $em;
    }

    /**
     * Method for getting AuditLog of all versions of given entity.
     *
     * Result is ordered with oldest changes first.
     *
     * @param object $entity entity for which we want to get all versions
     *
     * @return QueryBuilder
     */
    protected function getAllVersionsQb($entity)
    {
        $class = get_class($entity);
        $id = $entity;

        $this->instanceCache = array(); // is only valid for one entity
        if ($class !== $this->cache['class']) {
            $this->cache = array('class' => $class);
        }

        return $this->em->getRepository(AuditLog::class)
            ->createQueryBuilder('a')
            ->join('a.source', 's')
            ->where('s.fk = :entity')->setParameter('entity', $id)
            ->andWhere('s.class = :class')->setParameter('class', $class)
            ->orderBy('a.id', 'ASC')
        ;
    }

    /**
     * @deprecated since version 1.0.19
     *
     * @param object $entity
     *
     * @return mixed[]
     */
    public function getAllVersionsDiffArray($entity)
    {
        @trigger_error('getAllVersionsDiffArray() is deprecated since version 1.0.19. Use getAllVersionsDiff instead.', E_USER_DEPRECATED);
        $versions = array();
        foreach ($this->getAllVersionsDiff($entity) as $verKey => $value) {
            $versions[$verKey] = $value['changes'];
        }

        return $versions;
    }

    public function getAllVersionsDiff($entity)
    {
        // doc is in interface

        $qb = $this->getAllVersionsQb($entity);

        return $this->auditLogToDiff($qb);
    }

    /**
     * Creates the diff format from related AuditLogs.
     *
     * @param AuditLog[]|\Iterable|QueryBuilder $entityVersions
     *
     * @return mixed[] {@see getAllVersionsDiff()}
     */
    protected function auditLogToDiff($entityVersions)
    {
        $diffArray = array();
        if ($entityVersions instanceof QueryBuilder) {
            $entityVersions = $entityVersions->getQuery()->getResult();
        }

        /** @var AuditLog $currentVersion */
        foreach ($entityVersions as $currentVersion) {
            $versionKey = $this->getVersionKey($currentVersion, $diffArray);
            if (!isset($diffArray[$versionKey])) {
                $diffArray[$versionKey] = array(
                    'changes' => array(),
                    'savedBy' => $currentVersion->getBlame() ? $currentVersion->getBlame()->getLabel() : '',
                    'savedAt' => $currentVersion->getLoggedAt(),
                );
            }
            $changes = $this->getCurrentVersionElement($currentVersion, $diffArray[$versionKey]['changes']);
            if ($changes) {
                $diffArray[$versionKey]['changes'] = $changes;
            } else {
                unset($diffArray[$versionKey]);
            }
        }

        return $this->removeColumnIfOnlyUnchanged($diffArray);
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
        if (empty($this->cache['entityTableName'])) {
            $this->cache['entityTableName'] = $this->getEntitiesClassMetaData()->getTableName();
        }
        $entityTable = $this->cache['entityTableName'];
        if ($currentVersion->getTbl() !== $entityTable && $currentVersion->getSource()->getTbl() !== $entityTable) {
            // owning side of ManyToMany association, the side is not this entity
            $columnName = $this->getColumnNameForAssociation($currentVersion);
            $label = $this->getLabelForAssociation($currentVersion->getSource());
            if ('insert' === $currentVersion->getAction()) {
                $diffElement[$columnName][self::KEY_ADD][$currentVersion->getSource()->getFk()] = $label;
                $this->setCachedAssociationValue($currentVersion->getSource(), $label);
            } elseif ('remove' === $currentVersion->getAction()) {
                $label = $this->getCachedAssociationValue($currentVersion->getSource(), true);
                $diffElement[$columnName][self::KEY_REMOVE][$currentVersion->getSource()->getFk()] = $label;
            } else { // update, (associate, dissociate)
                $diffElement[$columnName][self::KEY_ADD][$currentVersion->getSource()->getFk()] = $label;
                $oldLabel = $this->getCachedAssociationValue($currentVersion->getSource(), true);
                $this->setCachedAssociationValue($currentVersion->getSource(), $label);
                $diffElement[$columnName][self::KEY_REMOVE][$currentVersion->getSource()->getFk()] = $oldLabel;
            }
        } elseif ($currentVersion->getAction() === 'associate') {
            $columnName = $this->getColumnNameForAssociation($currentVersion);
            $label = $this->getLabelForAssociation($currentVersion->getTarget());
            $oldLabel = $this->getCachedAssociationValue($currentVersion->getTarget(), false);
            if ($oldLabel === $label) { // same value is added, save to skip removing later
                $diffElement[$columnName][self::TEMP_KEY_READD][$currentVersion->getTarget()->getFk()] = $label;
            } else { // value has changed, save old value for the coming remove
                $diffElement[$columnName][self::TEMP_KEY_OLDVAL][$currentVersion->getTarget()->getFk()] = $oldLabel;
                $diffElement[$columnName][self::KEY_ADD][$currentVersion->getTarget()->getFk()] = $label;
                $this->setCachedAssociationValue($currentVersion->getTarget(), $label);
            }
        } elseif ($currentVersion->getAction() === 'dissociate') {
            $columnName = $this->getColumnNameForAssociation($currentVersion);
            $label = $this->getLabelForAssociation($currentVersion->getTarget());
            if (!isset($diffElement[$columnName][self::TEMP_KEY_READD][$currentVersion->getTarget()->getFk()])) {
                $oldLabel = $label;
                if (isset($diffElement[$columnName][self::TEMP_KEY_OLDVAL][$currentVersion->getTarget()->getFk()])) {
                    // get old label because dissociate got the new one
                    $oldLabel = $diffElement[$columnName][self::TEMP_KEY_OLDVAL][$currentVersion->getTarget()->getFk()];
                    unset($diffElement[$columnName][self::TEMP_KEY_OLDVAL][$currentVersion->getTarget()->getFk()]);
                }
                $diffElement[$columnName][self::KEY_REMOVE][$currentVersion->getTarget()->getFk()] = $oldLabel;
                $this->getCachedAssociationValue($currentVersion->getTarget(), true /*delete*/);
            } else {    // when dissociate and associate on same element that means, that it was before
                unset($diffElement[$columnName][self::TEMP_KEY_READD][$currentVersion->getTarget()->getFk()]);
                $diffElement[$columnName][self::KEY_UNCHANGED][$currentVersion->getTarget()->getFk()] = $label;
            }
        } else {
            foreach ($currentVersion->getDiff() as $columnName => $diffValue) {
                if (isset($diffValue['new']['label'])) {
                    // ManyToOne relation has changed
                    $diffElement[$columnName] = $diffValue['new']['label'];
                } else {
                    $diffElement[$columnName] = $diffValue['new'];
                }
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
            foreach (array_keys($versionValue['changes']) as $columnName) {
                $currentElement = $diffArray[$versionKey]['changes'][$columnName];
                if (is_array($currentElement)
                        && isset($currentElement[self::KEY_UNCHANGED])
                        && !isset($currentElement[self::KEY_ADD])
                        && !isset($currentElement[self::KEY_REMOVE])
                ) {
                    unset($diffArray[$versionKey]['changes'][$columnName]);
                } elseif (is_array($currentElement)) {
                    unset(
                        $diffArray[$versionKey]['changes'][$columnName][self::TEMP_KEY_READD],
                        $diffArray[$versionKey]['changes'][$columnName][self::TEMP_KEY_OLDVAL]
                    );
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
        $joinTableName = $currentVersion->getTbl();
        if (isset($this->cache['assocTable'][$joinTableName])) {
            return $this->cache['assocTable'][$joinTableName];
        }

        foreach ($this->getEntitiesClassMetaData()->getAssociationMappings() as $assMapping) {
            if (isset($assMapping['joinTable'])) {
                $assJoinTable = $assMapping['joinTable']['name'];
                $this->cache['assocTable'][$assJoinTable] = $assMapping['fieldName'];
                if ($joinTableName === $assJoinTable) {
                    return $assMapping['fieldName'];
                }
            } elseif (is_null($currentVersion->getTarget())
                && $currentVersion->getSource()->getClass() === $assMapping['targetEntity']
            ) { // ManyToOne mapping
                $this->cache['assocTable'][$joinTableName] = $assMapping['fieldName'];

                return $assMapping['fieldName'];
            }
        }

        throw new \LogicException(sprintf('no association for %s found.', $joinTableName));
    }

    protected function getLabelForAssociation(Association $ass)
    {
        return $ass->getLabel();
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

    /**
     * Returns doctrines metadata for the main entity.
     *
     * @return \Doctrine\ORM\Mapping\ClassMetadata
     */
    private function getEntitiesClassMetaData()
    {
        return $this->em->getClassMetadata($this->cache['class']);
    }

    /**
     * Caches a value as current.
     *
     * @param Association $ass
     * @param string      $value
     */
    private function setCachedAssociationValue(Association $ass, $value)
    {
        $this->instanceCache['assocCurrentValues'][$ass->getTbl()][$ass->getFk()] = $value;
    }

    /**
     * Gets a cached current value, and optionally deletes it.
     *
     * @param Association $ass
     * @param bool        $delete
     *
     * @return string the cached current value
     */
    private function getCachedAssociationValue(Association $ass, $delete)
    {
        if (!isset($this->instanceCache['assocCurrentValues'][$ass->getTbl()][$ass->getFk()])) {
            return;
        }
        $value = $this->instanceCache['assocCurrentValues'][$ass->getTbl()][$ass->getFk()];
        if ($delete) {
            unset($this->instanceCache['assocCurrentValues'][$ass->getTbl()][$ass->getFk()]);
        }

        return $value;
    }
}
