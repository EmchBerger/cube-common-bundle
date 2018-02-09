<?php

namespace CubeTools\CubeCommonBundle\DataHandling\Logs;

use DataDog\AuditBundle\Entity\AuditLog;
use DataDog\AuditBundle\Entity\Association;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\QueryBuilder;

/**
 * Class handling data from DataDogAuditBundle.
 */
class DataDogAudit extends AbstractBaseAudit
{
    use LogsFunctionsTrait;

    const UNKNOWN_VERSION_CHANGE = 'unknown version';

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

    /**
     *
     * @var DataDogAudit\AuditQueries
     */
    protected $auditQueries;

    public function __construct(ObjectManager $em)
    {
        $this->em = $em;
        $this->auditQueries = new DataDogAudit\AuditQueries($em);
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

        $aQb = $this->auditQueries->createOwningSideQb($id, $class);

        $assocClasses = array();
        foreach ($aQb->getQuery()->getResult() as $infos) {
            $assocClasses[$infos['class']][] = $infos['fk'];
        }

        $qb = $this->auditQueries->createAuditLogQb($id, $class);
        foreach ($assocClasses as $assocClass => $ids) {
            $this->auditQueries->extendAuditLogWithAttributeQb($qb, $assocClass, $ids);
        }

        return $qb;
    }

    public function getLastBlame($entity)
    {
        // doc is in interface

        $qb = $this->getAllVersionsQb($entity);
        $currentVersion = $qb->limit(1)->getQuery()->getResult();

        return array(
            'savedBy' => $currentVersion->getBlame() ? $currentVersion->getBlame()->getLabel() : '',
            'savedAt' => $currentVersion->getLoggedAt(),
        );
    }

    public function getFirstBlame($entity)
    {
        // doc is in interface

        $qb = $this->getAllVersionsQb($entity);
        $currentVersion = $qb->orderBy('a.id', 'DESC')->limit(1)->getQuery()->getResult();

        return array(
            'savedBy' => $currentVersion->getBlame() ? $currentVersion->getBlame()->getLabel() : '',
            'savedAt' => $currentVersion->getLoggedAt(),
        );
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
     * @param AuditLog[]|iterable|QueryBuilder $entityVersions
     *
     * @return mixed[] {@see getAllVersionsDiff()}
     */
    protected function auditLogToDiff($entityVersions)
    {
        $diffArray = array(self::UNKNOWN_VERSION_CHANGE => array());
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

        return $this->filterFinalResult($diffArray);
    }

    public function getVersionsOfProperty($entity, $columnName)
    {
        // doc is in interface

        $allVer = $this->getAllVersionsDiff($entity);
        /*
         * Could filter in db, but some properties are in diff, some are in an association.
         * And only for associations: $columnName = $this->getColumnNameForAssociation($propertyName);
         */

        return $this->filterLogForOneProperty($allVer, $columnName);
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
            // other side of association
            $this->handleOtherAttributeSideChange($currentVersion, $diffElement);
        } elseif (!$currentVersion->getDiff()) {
            $this->handleAssociateDissociate($currentVersion, $diffElement);
        } else {
            if ('insert' === $currentVersion->getAction()) {
                if (!isset($this->instanceCache['insertCalled'])) {
                    $this->instanceCache['insertCalled'] = true;
                }
            } elseif (!isset($this->instanceCache['insertCalled'])) { // update/... before insert
                $this->instanceCache['insertCalled'] = false;
            }
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

    protected function handleOtherAttributeSideChange(AuditLog $currentVersion, array &$diffElement)
    {
        $id = $currentVersion->getSource()->getFk();
        $assocClass = $currentVersion->getSource()->getClass();

        if (empty($this->instanceCache['currentAttributes'][$assocClass][$id])) {
            return; // not associated
        }

        foreach ($this->instanceCache['currentAttributes'][$assocClass][$id] as $attrName => $registered) {
            if ($registered <= 0) {
                continue; // not associated => next $attrName
            }
            $label = $this->getLabelForAssociation($currentVersion->getSource());
            $oldLabel = $this->getCachedAssociationValue($currentVersion->getSource(), false);
            $this->setCachedAssociationValue($currentVersion->getSource(), $label);
            $diffElement[$attrName][self::KEY_ADD][$id] = $label;
            $diffElement[$attrName][self::KEY_REMOVE][$id] = $oldLabel;
        }
    }

    protected function handleAssociateDissociate(AuditLog $currentVersion, array &$diffElement)
    {
        if ('associate' === $currentVersion->getAction()) {
            $id = $currentVersion->getTarget()->getFk();
            $assocClass = $currentVersion->getTarget()->getClass();
            $columnName = $this->getColumnNameForAssociation($currentVersion);

            if (empty($this->instanceCache['currentAttributes'][$assocClass][$id][$columnName])) {
                $label = $this->getLabelForAssociation($currentVersion->getTarget());
                $diffElement[$columnName][self::KEY_ADD][$id] = $label;
                $this->instanceCache['currentAttributes'][$assocClass][$id][$columnName] = 1;
                $this->setCachedAssociationValue($currentVersion->getSource(), $label);
            } else {
                ++$this->instanceCache['currentAttributes'][$assocClass][$id][$columnName];
            }
        } elseif ('dissociate' === $currentVersion->getAction()) {
            $id = $currentVersion->getTarget()->getFk();
            $assocClass = $currentVersion->getTarget()->getClass();
            $columnName = $this->getColumnNameForAssociation($currentVersion);

            --$this->instanceCache['currentAttributes'][$assocClass][$id][$columnName];
            if (0 === $this->instanceCache['currentAttributes'][$assocClass][$id][$columnName]) {
                $oldLabel = $this->getLabelForAssociation($currentVersion->getTarget());
                $diffElement[$columnName][self::KEY_REMOVE][$id] = $oldLabel;
            }
        } else {
            throw new \LogicException('Action '.$currentVersion->getAction().' is not supported by this method.');
        }
    }

    /**
     * Filter the result before returning.
     *
     * @param array $diffArray subsequent elements are diff for each version
     *
     * @return array subsequent elements are diff for each version, filtered
     */
    protected function filterFinalResult(array $diffArray)
    {
        if (empty($this->instanceCache['insertCalled'])) {
            // insert call missing, incomplete log => update placeholder
            $diffArray[self::UNKNOWN_VERSION_CHANGE] = array(
            'changes' => array('  ' => 'unknown document versions before'),
            'savedBy' => 'UNKNOWN USER',
            'savedAt' => new \DateTime('1970-01-01 00:00'),
            );
        } else {
            unset($diffArray[self::UNKNOWN_VERSION_CHANGE]); // complete log, remove placeholder
        }
        foreach ($diffArray as $versionKey => $versionValue) {
            foreach (array_keys($versionValue['changes']) as $columnName) {
                $currentElement = $diffArray[$versionKey]['changes'][$columnName];
                if (is_array($currentElement)) {
                    $filteredProp = $this->filterMultiValueProperty($versionValue['changes'][$columnName], $columnName);
                    if (array() === $filteredProp) {
                        unset($versionValue['changes'][$columnName]);
                    } else {
                        $versionValue['changes'][$columnName] = $filteredProp;
                    }
                }
            }
            if ($versionValue['changes']) {
                $filtered = $this->filterVersionChange($versionValue['changes']);
            } else {
                $filtered = null; // mark for remove
            }
            if (!$filtered) {
                unset($diffArray[$versionKey]);
            } else {
                $diffArray[$versionKey]['changes'] = $filtered;
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

    protected function getLabelForAssociation(Association $assoc)
    {
        return $assoc->getLabel();
    }

    /**
     * Method for filtering diff element. Can be override for customization needs.
     *
     * May be called multiple times for one change.
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
     * Get data for attributes the entity is not the owning side.
     *
     * @param object    $entity               doctrine entity object
     * @param string    $attribute            name of attribute to get
     * @param mixed[][] $additionalConditions array(array('attr' => attrName, 'value' => value), ...)
     *
     * @return array array('class'=> classOfAttribute, 'ids' => array(id1, id2, ...))
     */
    protected function getInverseSideAttributeIds($entity, $attribute, $additionalConditions = array())
    {
        $entId = $entity->getId();
        $entClass = get_class($entity);

        $entMeta = $this->em->getClassMetadata($entClass);
        if (isset($entMeta->getAssociationMapping($attribute)['joinTable'])) {
            throw new \LogicException(sprintf(
                'ManyToMany associations (for attribute %s) are not supported by this method',
                $attribute
            ));
        }
        $attrClass = $entMeta->getAssociationMapping($attribute)['targetEntity'];
        $entInAttr = $entMeta->getAssociationMapping($attribute)['mappedBy'];

        $qb = $this->auditQueries->create1toNAssociationQb($attrClass, $entInAttr, $entClass, $entId);
        $this->auditQueries->extend1toNAssociationQb($qb, $additionalConditions);

        $candidates = $qb->getQuery()->getResult();
        /// checking real values
        $matchingIds = array();
        /** @var AuditLog $candidate */
        foreach ($candidates as $candidate) {
            $diff = $candidate->getDiff();
            if ($diff[$entInAttr]['new']['class'] !== $entClass || $diff[$entInAttr]['new']['fk'] != $entId) {
                continue; // wrong class/id => next candidate (id may have type string or int)
            }
            foreach ($additionalConditions as $condition) {
                if ($condition['value'] !== $diff[$condition['attr']]['new']) {
                    continue 2; // condition not fulfilled => next candidate
                }
            }
            $matchingIds[] = $candidate->getSource()->getFk();
        }

        return array('class' => $attrClass, 'ids' => $matchingIds);
    }

    /**
     * Extends the QueryBuilder from getAllVersionsQb with logs of attributes the entity is not the owning side.
     *
     * @param QueryBuilder $qb
     * @param string       $attributeClass probably ->getInverseSideAttributeIds()['class']
     * @param int[]        $ids            probably ->getInverseSideAttributeIds()['ids']
     */
    protected function extendQbWithInverseSideAttribute(QueryBuilder $qb, $attributeClass, $ids)
    {
        if ($ids) {
            $this->auditQueries->extendAuditLogWithAttributeQb($qb, $attributeClass, $ids);
        }
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
     * @param Association $assoc
     * @param string      $value
     */
    private function setCachedAssociationValue(Association $assoc, $value)
    {
        $this->instanceCache['assocCurrentValues'][$assoc->getTbl()][$assoc->getFk()] = $value;
    }

    /**
     * Gets a cached current value, and optionally deletes it.
     *
     * @param Association $assoc
     * @param bool        $delete
     *
     * @return string the cached current value
     */
    private function getCachedAssociationValue(Association $assoc, $delete)
    {
        if (!isset($this->instanceCache['assocCurrentValues'][$assoc->getTbl()][$assoc->getFk()])) {
            return;
        }
        $value = $this->instanceCache['assocCurrentValues'][$assoc->getTbl()][$assoc->getFk()];
        if ($delete) {
            unset($this->instanceCache['assocCurrentValues'][$assoc->getTbl()][$assoc->getFk()]);
        }

        return $value;
    }
}
