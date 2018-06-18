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
     *
     * @return array notifications valid for given entity
     */
    public function getNotificationsForEntity($entity = null, $searchForNullEntityId = true)
    {
        $this->setWatchedEntities();
        $notificationsOutput = array();
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

            $qb->setParameter('entityClass', $entityClassName)
                ->setParameter('entityId', (is_null($entity) ? -1 : $entity->getId()))
            ;
            $notificationsOutput = $qb->getQuery()->getResult();
        }

        return $notificationsOutput;
    }
}
