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

    const TEMP_KEY_READD = 'temp_readd';
    const TEMP_KEY_OLDVAL = 'temp_oldval';
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
                if (!isset($diffElement[$columnName][self::KEY_ADD][$currentVersion->getTarget()->getFk()])) {
                    $this->getCachedAssociationValue($currentVersion->getTarget(), true /*delete*/);
                } // else log started in the middle, incomplete
            } else {    // when dissociate and associate on same element that means, that it was before
                unset($diffElement[$columnName][self::TEMP_KEY_READD][$currentVersion->getTarget()->getFk()]);
                $diffElement[$columnName][self::KEY_UNCHANGED][$currentVersion->getTarget()->getFk()] = $label;
            }
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
                if (is_array($currentElement)
                        && isset($currentElement[self::KEY_UNCHANGED])
                        && !isset($currentElement[self::KEY_ADD])
                        && !isset($currentElement[self::KEY_REMOVE])
                ) { // removes columns, for which changes stays only in unchanged key
                    unset($versionValue['changes'][$columnName]);
                } elseif (is_array($currentElement)) { // remove temp results
                    unset(
                        $versionValue['changes'][$columnName][self::TEMP_KEY_READD],
                        $versionValue['changes'][$columnName][self::TEMP_KEY_OLDVAL]
                    );
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
        $entClassJson = json_encode($entClass);

        $qb = $this->em->getRepository(AuditLog::class)->createQueryBuilder('al')
            ->join('al.source', 's')
            ->where('s.class = :attrClass')->setParameter('attrClass', $attrClass)
            ->andWhere("al.action = 'insert'")
            ->andWhere("al.diff LIKE :diffLikeClsS ESCAPE '°'")
            ->setParameter('diffLikeClsS', '%"'.$entInAttr.'":%"class":'.$entClassJson.'%,"fk":"'.$entId.'",%') // string fk
        ;
        $i = 1;
        foreach ($additionalConditions as $condition) {
            $condValue = json_encode($condition['value']);
            $qb->andWhere('al.diff LIKE :diffLike'.$i)->setParameter('diffLike'.$i, '%"'.$condition['attr'].'"%"new":'.$condValue.'%');
            ++$i;
        }
        $candidates = $qb->getQuery()->getResult();
        /// checking real values
        $matchingIds = array();
        /** @var AuditLog $candidate */
        foreach ($candidates as $candidate) {
            $diff = $candidate->getDiff();
            if ($diff[$entInAttr]['new']['fk'] != $entId) {
                continue; // wrong id => next candidate
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
    protected function extendQbWithInverseSideAttribute($qb, $attributeClass, $ids)
    {
        $unique = count($qb->getParameters()).substr($attributeClass, (strrpos($attributeClass, '\\') ?: -1) + 1);
        $qb->orWhere('s.fk IN (:idsAttr'.$unique.') AND s.class = :classAttr'.$unique)
            ->setParameter('classAttr'.$unique, $attributeClass)
            ->setParameter('idsAttr'.$unique, $ids)
        ;
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
