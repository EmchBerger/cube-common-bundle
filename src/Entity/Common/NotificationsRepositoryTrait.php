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
     * Method returns watched entities (from container parameter).
     *
     * @return array string[] subsequent elements are full class names of entities, which are watched
     */
    abstract public function setWatchedEntities();

    /**
     * Method checks, if there are set notifications for this entity.
     *
     * @param object $entity examined entity
     *
     * @return array notifications valid for given entity
     */
    public function getNotificationsForEntity($entity = null)
    {
        $this->setWatchedEntities();
        $notificationsOutput = array();
        $entityClassName = $this->_em->getMetadataFactory()->getMetadataFor(get_class($entity))->getName();

        if (in_array($entityClassName, $this->watchedEntities)) { // entity is watched - now check id of entity
            $qb = $this->createQueryBuilder('n')
                ->where('n.entityClass = :entityClass')
                ->andWhere('n.entityId = :entityId OR n.entityId IS NULL')
                ->setParameter('entityClass', $entityClassName)
                ->setParameter('entityId', (is_null($entity) ? -1 : $entity->getId()));
            ;
            $notificationsOutput = $qb->getQuery()->getResult();
        }

        return $notificationsOutput;
    }
}
