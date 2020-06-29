<?php

namespace CubeTools\CubeCommonBundle\DataHandling\Logs;

use CubeTools\CubeCommonBundle\DataHandling\StringHelper;
use DataDog\AuditBundle\Entity\AuditLog;
use DataDog\AuditBundle\Entity\Association;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class handling data from DataDogAuditBundle.
 */
class DataDogAudit extends AbstractBaseAudit
{
    use LogsFunctionsTrait;

    const UNKNOWN_VERSION_CHANGE = 'unknown version';

    const KEY_MODIFY_NEW = self::KEY_ADD;
    const KEY_MODIFY_OLD = self::KEY_REMOVE;

    const REMOVE_PARAMETER_NAME = ' element deleted';
    const REMOVE_TEXT = 'element is deleted';

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
     * @var DataDogAudit\AuditQueries
     */
    protected $auditQueries;

    /**
     * @var \Symfony\Component\Translation\TranslatorInterface
     */
    protected $translator;

    public function __construct(ObjectManager $em, TranslatorInterface $translator)
    {
        $this->em = $em;
        $this->translator = $translator;
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
        $class = $this->em->getMetadataFactory()->getMetadataFor(get_class($entity))->getName();
        $id = $entity;

        $this->instanceCache = array(
            'id' => $id,
        ); // is only valid for one entity
        if ($class !== $this->cache['class']) {
            $this->cache = array('class' => $class);
        }

        $aQb = $this->auditQueries->createOwningSideQb($id, $class);

        $assocClasses = array();
        foreach ($aQb->getQuery()->getResult() as $infos) {
            if ($infos['class'] !== $class) {
                // avoid showing changes for parents
                $assocClasses[$infos['class']][] = $infos['fk'];
            }
        }

        $qb = $this->auditQueries->createAuditLogQb($id, $class);
        foreach ($assocClasses as $assocClass => $ids) {
            if ($ids) {
                $this->auditQueries->extendAuditLogWithAttributeQb($qb, $assocClass, $ids);
            }
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
        $outputArray = $this->auditLogToDiff($qb);

        return array_reverse($outputArray, true);
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
            if ($currentVersion->getTarget()) {
                $this->handleAssociateDissociate($currentVersion, $diffElement);
            } elseif ('remove' === $currentVersion->getAction()) {
                $this->removeThisEntity($currentVersion, $diffElement);
            } else {
                $this->logicWrong('Action '.$currentVersion->getAction().' is not supported here (with no diff).');
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
     * Update the $diffElement with the change of $currentVersion of an associated class.
     *
     * @param AuditLog $currentVersion AuditLog of associated class
     * @param array    $diffElement    where the result is written
     */
    protected function handleOtherAttributeSideChange(AuditLog $currentVersion, array &$diffElement)
    {
        $id = $currentVersion->getSource()->getFk();
        $assocClass = $currentVersion->getSource()->getClass();

        // handle oneToMany associations
        if ($currentVersion->getDiff() && isset($this->cache['oneToManyAssoc'][$assocClass])) {
            /**
             * @var function check if it the diff element is an association
             *               and if its class and foreign key match the current entity
             */
            $isThisEntity = function ($check) {
                return is_array($check) && $check['class'] === $this->cache['class'] && $check['fk'] === $this->instanceCache['id'];
            };
            foreach ($currentVersion->getDiff() as $fieldName => $fieldChange) {
                if ($isThisEntity($fieldChange['new'])) { //associate, set registered to 1
                    $assocAttrName = $this->getAttributeNameFor1toN($currentVersion, $fieldName);
                    $this->instanceCache['currentAttributes'][$assocClass][$id][$assocAttrName] = 1;
                }
                if ($isThisEntity($fieldChange['old'])) { //dissociate, delete registration (registered is always 1)
                    $assocAttrName = $this->getAttributeNameFor1toN($currentVersion, $fieldName);
                    unset($this->instanceCache['currentAttributes'][$assocClass][$id][$assocAttrName]);
                }
            }
        }

        if (empty($this->instanceCache['currentAttributes'][$assocClass][$id])) {
            return; // currently no attribute associated
        }

        $label = $this->getLabelForAssociation($currentVersion->getSource());
        $oldLabel = $this->getCachedAssociationValue($currentVersion->getSource(), false);
        if ($label !== $oldLabel) {
            // a real change
        } elseif (strlen($label) === 255) {
            // limitation: if the label is trucated, the change is maybe hidden
            // therefore currently show anyway
        } else {
            return; // change not visible to this entity
        }

        // update the label for all registered attributes
        foreach ($this->instanceCache['currentAttributes'][$assocClass][$id] as $attrName => $registered) {
            if ($registered <= 0) {
                continue; // currently not associated => next $attrName
            }
            $this->setCachedAssociationValue($currentVersion->getSource(), $label);
            $diffElement[$attrName][self::KEY_MODIFY_NEW][$id] = $label;
            $diffElement[$attrName][self::KEY_MODIFY_OLD][$id] = $oldLabel;
        }
    }

    /**
     * Update the $diffElement with the change of $currentVersion of an assiciation change.
     *
     * @param AuditLog $currentVersion AuditLog with associate / dissociate (source is this class)
     * @param array    $diffElement    where the result is written
     */
    protected function handleAssociateDissociate(AuditLog $currentVersion, array &$diffElement)
    {
        if ('associate' === $currentVersion->getAction()) {
            $id = $currentVersion->getTarget()->getFk();
            $assocClass = $currentVersion->getTarget()->getClass();
            $columnName = $this->getAttributeNameForNtoN($currentVersion);

            if (empty($this->instanceCache['currentAttributes'][$assocClass][$id][$columnName])) {
                // currently not yet registered => set as change (associate)
                $label = $this->getLabelForAssociation($currentVersion->getTarget());
                $diffElement[$columnName][self::KEY_ADD][$id] = $label;
                $this->instanceCache['currentAttributes'][$assocClass][$id][$columnName] = 1;
                $this->setCachedAssociationValue($currentVersion->getSource(), $label);
            } else {
                // already registered (because of associate before dissociate) => only update registered count
                ++$this->instanceCache['currentAttributes'][$assocClass][$id][$columnName];
            }
        } elseif ('dissociate' === $currentVersion->getAction()) {
            $id = $currentVersion->getTarget()->getFk();
            $assocClass = $currentVersion->getTarget()->getClass();
            $columnName = $this->getAttributeNameForNtoN($currentVersion);

            if (isset($this->instanceCache['currentAttributes'][$assocClass][$id][$columnName])) {
                $registered = --$this->instanceCache['currentAttributes'][$assocClass][$id][$columnName];
            } else { // auditLog was likely enabled after entity creation
                $this->instanceCache['currentAttributes'][$assocClass][$id][$columnName] = 0;
                $registered = 0;
            }
            if (0 === $registered) {
                // no more registered => set as change (dissociate)
                $oldLabel = $this->getLabelForAssociation($currentVersion->getTarget());
                $diffElement[$columnName][self::KEY_REMOVE][$id] = $oldLabel;

                if (isset($diffElement[$columnName][self::KEY_ADD][$id]) &&
                    $oldLabel === $diffElement[$columnName][self::KEY_ADD][$id] &&
                    empty($this->instanceCache['insertCalled'])
                ) { // likely associate + dissociate the same, when 1st association is missing
                    // write this once (as uncertainity), then go to a stable level
                    $this->instanceCache['currentAttributes'][$assocClass][$id][$columnName] = 1;
                }
            } elseif ($registered < 0) {
                $this->logicWrong('registered for "'.$columnName.'" may not be < 0. It is '.$registered);
                $this->instanceCache['currentAttributes'][$assocClass][$id][$columnName] = 0;
            }
        } else {
            $this->logicWrong('Action '.$currentVersion->getAction().' is not supported by this method.');
        }
    }

    /**
     * Note in the $diffElement the removal of the logged entity.
     *
     * @todo Select a good default behaviour, current one may be unhandy. Unstable functionality!
     * Default behaviour:
     *
     * Writes in the $diffElement that the value is deleted.
     * What is written instead of a parameter name can be configured by `$options['parameter_name']` (default is
     * `static::REMOVE_PARAMETER_NAME`). The text used instad of a parameter value is read from
     * $options['parameter_value']
     *
     * Removing other changes noted for the same time is disabled (enable by setting `$options['only_remove']` to true).
     *
     * Associations are not tracked any longer (to track on, set $options['track_attributes'] = true).
     *
     * Can be override for customization needs.
     *
     * @param AuditLog $currentVersion AuditLog with associate / dissociate (source is this class)
     * @param array    $diffElement    where the result is written
     * @param array    $options        empty normally, for simple adapting by callling from subclasses
     */
    protected function removeThisEntity(AuditLog $currentVersion, array &$diffElement, array $options = array())
    {
        if (isset($options['only_remove']) && false === $options['only_remove']) {
            $diffElement = array();
        }
        $key = isset($options['parameter_name']) ? $options['parameter_name'] : static::REMOVE_PARAMETER_NAME;
        $value = isset($options['parameter_value']) ? $options['parameter_value'] : static::REMOVE_TEXT;
        $diffElement[$key] = $value;

        if (!empty($options['track_attributes'])) {
            $this->instanceCache['currentAttributes'] = array();
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
        if ((empty($this->instanceCache['insertCalled']) && // insert not called &&
            isset($this->instanceCache['insertCalled'])) // value is false => data was added
        ) {
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
                    // call custom filter for values containing several elements (manyToMany associations)
                    $filteredProp = $this->filterMultiValueProperty($versionValue['changes'][$columnName], $columnName);
                    if (array() === $filteredProp) { // cleared
                        unset($versionValue['changes'][$columnName]);
                    } else { // still a value, may even change the type to scalar
                        $versionValue['changes'][$columnName] = $filteredProp;
                    }
                }
            }
            if ($versionValue['changes']) {
                // call custom filter for value of each property
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
     * @return string|null name
     */
    protected function getColumnNameForAssociation(AuditLog $currentVersion)
    {
        return null; // => use default name
    }

    protected function getAttributeNameForNtoN(AuditLog $currentVersion)
    {
        $name = $this->getColumnNameForAssociation($currentVersion);
        if (is_string($name)) {
            return $name;
        }

        $joinTableName = $currentVersion->getTbl();
        if (isset($this->cache['assocTable'][$joinTableName])) {
            return $this->cache['assocTable'][$joinTableName];
        }

        foreach ($this->getEntitiesClassMetaData()->getAssociationMappings() as $assMapping) {
            if (!empty($assMapping['joinTable'])) {
                $assJoinTable = $assMapping['joinTable']['name'];
                $this->cache['assocTable'][$assJoinTable] = $assMapping['fieldName'];
                if ($joinTableName === $assJoinTable) {
                    return $assMapping['fieldName'];
                }
            }
        }

        $this->logicWrong(sprintf('no association for %s found.', $joinTableName));

        return ' '.$currentVersion->getTbl(); // some fallback
    }

    protected function getAttributeNameFor1toN(AuditLog $currentVersion, $otherAttrName)
    {
        $name = $this->getColumnNameForAssociation($currentVersion);
        if (is_string($name)) {
            return $name;
        }
        $attrClass = $currentVersion->getSource()->getClass();

        return $this->cache['oneToManyAssoc'][$attrClass][$otherAttrName];
    }

    /**
     * Get the label of an association. Can be overridden for customization needs.
     *
     * @param Association $assoc
     *
     * @return string
     */
    protected function getLabelForAssociation(Association $assoc)
    {
        return StringHelper::indicateStrippedKeepSize($assoc->getLabel(), 255); // 255 is the labels column size
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
        $entClass = $this->em->getMetadataFactory()->getMetadataFor(get_class($entity))->getName();

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
        // register attribute name for lookup in {@see getAttributeNameFor1toN()}
        $this->cache['oneToManyAssoc'][$attrClass][$entInAttr] = $attribute;

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
     * Reports a non-fatal logic exception, only shown in debug mode.
     *
     * @param string $message
     */
    protected function logicWrong($message)
    {
        // to get exceptions in dev mode (while developping this class), just remove the @
        @trigger_error('potential LogicException: '.$message, E_USER_WARNING); // write to log, in dev only
        $dumpFn = 'dump';
        if (function_exists($dumpFn)) { // in dev only
            $dumpFn('potential LogicException: '.$message); // show it as dump, is not shown on ajax request
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
