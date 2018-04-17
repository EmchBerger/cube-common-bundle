<?php

namespace CubeTools\CubeCommonBundle\Entity\Common;

/**
 * To be used in entities for notifications to be send.
 */
trait NotificationsToSendRepositoryTrait
{
    /**
     * Method gets all notifications, which dateOfExecution has already passed and they are marked as not executed.
     *
     * @return array
     */
    public function getNotificationsToSend()
    {
        $qb = $this->createQueryBuilder('ns')
            ->where('ns.dateOfExecution < CURRENT_TIMESTAMP()')
            ->andWhere('ns.isExecuted = :isExecuted')
            ->setParameter('isExecuted', false)
        ;

        return $qb->getQuery()->getResult();
    }
}
