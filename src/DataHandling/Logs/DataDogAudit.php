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
     * @var \Doctrine\Common\Persistence\ObjectManager
     */
    protected $em;

    public function __construct(ObjectManager $em)
    {
        $this->em = $em;
    }

    /**
     * Method for getting all versions of given entity.
     * @param Object $entity entity for which we want to get all versions
     * @return array versions of entity
     */
    public function getAllVersions($entity)
    {
        return $this->em->getRepository(AuditLog::class)->findBy(array('source' => $this->getAssociations($entity)), array('id' => 'ASC'));
    }

    /**
     * Method for getting diff array for given entity for all versions (changes between subsequent versions).
     * @param Object $entity object with entity
     * @return array subsequent elements are diff for each version
     */
    public function getAllVersionsDiffArray($entity)
    {
        $entityVersions = $this->getAllVersions($entity);
        $diffArray = array();

        foreach ($entityVersions as $currentVersion) {
            /* @var $currentVersion \DataDog\AuditBundle\Entity\AuditLog */

            $versionKey = $this->getVersionKey($currentVersion);
            if (!isset($diffArray[$versionKey])) {
                $diffArray[$versionKey] = array();
            }
            $diffArray[$versionKey] = $this->getCurrentVersionElement($currentVersion, $diffArray[$versionKey]);                    
        }

        return $diffArray;
    }

    /**
     * Method for getting associations for given entity.
     * @param Object $entity object with entity
     * @return array entities
     */
    protected function getAssociations($entity)
    {
        $associations = $this->em->getRepository(Association::class)->findBy(array('class' => get_class($entity), 'fk' => $entity->getId()));
        return $associations;
    }

    /**
     * Method creating version key (to group table entries associated with the same user action).
     * @param \DataDog\AuditBundle\Entity\AuditLog $currentVersion entity for which version key is created
     * @return string version key (timestamp and user id)
     */
    protected function getVersionKey($currentVersion)
    {
        $versionTimestamp = $currentVersion->getLoggedAt()->getTimestamp();
        $versionUser = $currentVersion->getBlame()->getFk();
        $versionKey = sprintf('%d_%d', $versionTimestamp, $versionUser); // having user and time prevent from logging in the same place simoultaneus changes from more then one user
        return $versionKey;
    }

    /**
     * Method creating diff for given log entry.
     * @param \DataDog\AuditBundle\Entity\AuditLog $currentVersion
     * @param array $diffElement current state of diff for this version (one version can consist of more then one log entry)
     * @return array diff element for given version
     */
    protected function getCurrentVersionElement($currentVersion, $diffElement)
    {
        if ($currentVersion->getAction()=='associate') {
            $diffElement[$this->getColumnNameForAssociation($currentVersion)][$currentVersion->getTarget()->getFk()] = $currentVersion->getTarget()->getLabel();
        } else if ($currentVersion->getAction()=='dissociate') {
            $columnName = $this->getColumnNameForAssociation($currentVersion);
            if (!isset($diffElement[$columnName][$currentVersion->getTarget()->getFk()])) {
                $diffElement[$columnName][$currentVersion->getTarget()->getFk()] = $currentVersion->getTarget()->getLabel() . ' (removed)'; // TODO: make removed as translation key
            } else {    // when dissociate and associate on same element that means, that it was before
                unset($diffElement[$columnName][$currentVersion->getTarget()->getFk()]);
            }
        } else {
            foreach ($currentVersion->getDiff() as $columnName => $diffValue) {
                $diffElement[$columnName] = $diffValue['new'];
            }
        }

        return $this->filterVersionElement($currentVersion, $diffElement);
    }

    /**
     * Method for getting key storing information about given on input column name with association. Can be override for customization needs.
     * @param \DataDog\AuditBundle\Entity\AuditLog $currentVersion
     * @return string name 
     */
    protected function getColumnNameForAssociation($currentVersion)
    {
        return $currentVersion->getTarget()->getTbl();
    }

    /**
     * Method for filtering diff element. Can be override for customization needs.
     * @param \DataDog\AuditBundle\Entity\AuditLog $currentVersion
     * @param array $diffElement
     * @return array filtered diff element
     */
    protected function filterVersionElement($currentVersion, $diffElement)
    {
        return $diffElement;
    }
}
