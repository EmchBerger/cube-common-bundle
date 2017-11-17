<?php
namespace CubeTools\CubeCommonBundle\Subscriptions\Message;

use Doctrine\Common\Persistence\ObjectManager;

/**
 * Object providing participants for subscriptions. Extension of class provide specific method to get participants.
 */
abstract class AbstractParticipantsProvider
{
    /**
     * @var \Doctrine\Common\Persistence\ObjectManager
     */
    protected $em;

    public function __construct(ObjectManager $em)
    {
        $this->em = $em;
    }

    abstract public function getParticipants($subscriptionId);
}
