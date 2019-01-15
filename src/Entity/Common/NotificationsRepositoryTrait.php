<?php

namespace CubeTools\CubeCommonBundle\Entity\Common;

/**
 * To be used as repository for notifications system.
 */
trait NotificationsRepositoryTrait
{
    /**
     * @var array string[] - subsequent element are entity classes
     */
    protected $watchedEntities = array();

    /**
     * Method gets watched entities (for example from container parameter) and set them in internal variable.
     *
     * @return array string[] subsequent elements are full class names of entities, which are watched
     */
    abstract public function setWatchedEntities();

    /**
     * Method checks, if there are set notifications for this entity.
     *
     * @param object $entity                examined entity
     * @param bool   $searchForNullEntityId if true, search for records with null entityId enabled (default: true)
     * @param bool   $noConditions          if true, only notifications with all conditions set to null are taken (default: false)
     *
     * @return array notifications valid for given entity
     */
    public function getNotificationsForEntityBase($entity, $searchForNullEntityId, $noConditions = false)
    {
        $notificationsOutput = array();
        if (null !== $entity) {
            $this->setWatchedEntities();
            $entityClassName = $this->_em->getMetadataFactory()->getMetadataFor(get_class($entity))->getName();

            if (in_array($entityClassName, $this->watchedEntities)) { // entity is watched - now check id of entity
                $qb = $this->createQueryBuilder('n')
                    ->andWhere('n.entityClass = :entityClass')
                ;

                if ($searchForNullEntityId) {
                    $qb->andWhere('n.entityId = :entityId OR n.entityId IS NULL');
                } else {
                    $qb->andWhere('n.entityId = :entityId');
                }

                if ($noConditions) {
                    $qb->andWhere('n.triggerChangedColumns IS NULL AND n.filterBefore IS NULL AND n.filterAfter IS NULL');
                }

                $qb->setParameter('entityClass', $entityClassName)
                    ->setParameter('entityId', (is_null($entity->getId()) ? -1 : $entity->getId()))
                ;
                $notificationsOutput = $qb->getQuery()->getResult();
            }
        }

        return $notificationsOutput;
    }

    /**
     * Method checks, if there are set notifications for this entity.
     *
     * @param object $entity                examined entity
     * @param bool   $searchForNullEntityId if true, search for records with null entityId enabled (default: true)
     *
     * @return array notifications valid for given entity
     */
    public function getNotificationsForEntity($entity, $searchForNullEntityId = true)
    {
        return $this->getNotificationsForEntityBase($entity, $searchForNullEntityId);
    }

    /**
     * Method checks, if there are set notifications for this entity without any conditions.
     *
     * @param object $entity                examined entity
     * @param bool   $searchForNullEntityId if true, search for records with null entityId enabled (default: true)
     *
     * @return array notifications valid for given entity without any conditions
     */
    public function getNotificationsForEntityNoConditions($entity, $searchForNullEntityId = true)
    {
        return $this->getNotificationsForEntityBase($entity, $searchForNullEntityId, true);
    }
}
