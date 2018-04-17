<?php

namespace CubeTools\CubeCommonBundle\Entity\Common;

/**
 * To be used in entities for notifications to be send.
 */
trait NotificationsToSendTrait
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var int id of notification, for which this information is created
     *
     * @ORM\Column(name="notificationId", type="integer")
     */
    private $notificationId;

    /**
     * @var string content of message to be send to user
     *
     * @ORM\Column(name="message", type="string")
     */
    private $message;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="dateOfExecution", type="date", nullable=false)
     */
    private $dateOfExecution;

    /**
     * @var string content of message to be send to user
     *
     * @ORM\Column(name="isExecuted", type="boolean")
     */
    private $isExecuted;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     *
     * @param int $notificationId
     *
     * @return $this
     */
    public function setNotificationId($notificationId)
    {
        $this->notificationId = $notificationId;

        return $this;
    }

    /**
     * @return int
     */
    public function getNotificationId()
    {
        return $this->notificationId;
    }

    /**
     *
     * @param string $message
     *
     * @return $this
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     *
     * @param \DateTime $dateOfExecution
     *
     * @return $this
     */
    public function setDateOfExecution($dateOfExecution)
    {
        $this->dateOfExecution = $dateOfExecution;

        return $this;
    }

    /**
     * @return string
     */
    public function getDateOfExecution()
    {
        return $this->dateOfExecution;
    }

    /**
     *
     * @param bool $isExecuted
     *
     * @return $this
     */
    public function setIsExecuted($isExecuted)
    {
        $this->isExecuted = $isExecuted;

        return $this;
    }

    /**
     * @return bool
     */
    public function getIsExecuted()
    {
        return $this->isExecuted;
    }
}
