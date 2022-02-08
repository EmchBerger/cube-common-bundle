<?php
namespace CubeTools\CubeCommonBundle\Subscriptions\Message;

use CubeTools\CubeCommonBundle\Subscriptions\MailSubscription;
use Symfony\Component\Mime\Message;

class ParticipantsGenerator
{
    /**
     * @var Message instance of message object
     */
    protected $message;

    /**
     * @var array key is user name (or numeric), value - email
     */
    protected $participants;

    /**
     * @var \CubeTools\CubeCommonBundle\Subscriptions\Message\AbstractParticipantsProvider object providing participants for subscriptions
     */
    protected $participantsProvider;

    /**
     * Setter for object responsible for creating message. Object can be changed by class methods.
     * @param Message $message instance of message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * Setter for participants provider.
     * @param \CubeTools\CubeCommonBundle\Subscriptions\Message\AbstractParticipantsProvider $participantsProvider object providing participants for subscriptions
     */
    public function setParticipantsProvider($participantsProvider)
    {
        $this->participantsProvider = $participantsProvider;
    }

    /**
     * Method setting message recipients.
     * @param \CubeTools\CubeCommonBundle\Subscriptions\MailSubscription $subscription object
     * @return array each element is message object
     */
    public function setParticipants(MailSubscription $subscription)
    {
        $this->participants = $this->participantsProvider->getParticipants(
                $subscription->getSubscriptionId()
        );
    }

    /**
     * Method creating separate message for each recipient.
     * @return array each element is Message instance
     */
    public function createMessagesForRecipients()
    {
        $messageObjectArray = array();

        foreach ($this->participants as $participantName => $participantEmail) {
            $newMessageObject = clone $this->message;

            if (!is_numeric($participantName)) {
                $name = $participantName;
            } else {
                $name = null;
            }

            $newMessageObject->addTo($participantEmail, $name);
            $messageObjectArray[] = $newMessageObject;
        }

        return $messageObjectArray;
    }
}
