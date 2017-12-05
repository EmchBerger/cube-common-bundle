<?php
namespace CubeTools\CubeCommonBundle\Subscriptions;

class MailSubscription
{
    /**
     * @var \CubeTools\CubeCommonBundle\Subscriptions\Conditions\AbstractCondition
     */
    protected $condition;

    /**
     * @var array array of \CubeTools\CubeCommonBundle\Subscriptions\ReportInterface
     */
    protected $reports;

    /**
     * @var \CubeTools\CubeCommonBundle\Subscriptions\Message\ContentGenerator
     */
    protected $messageContentGenerator;

    /**
     * @var \CubeTools\CubeCommonBundle\Subscriptions\Message\ParticipantsGenerator
     */
    protected $messageParticipantsGenerator;

    /**
     * @var \Swift_Message instance of message object
     */
    protected $messageObject;

    /**
     * @var \Swift_Mailer
     */
    protected $swiftMailer;

    /**
     * @var integer id of currently set subscription (default: 0)
     */
    protected $subscriptionId = 0;

    public function __construct(\Swift_Mailer $swiftMailer)
    {
        $this->swiftMailer = $swiftMailer;
    }

    /**
     * Method setting condition. If this condition is fulfilled, then sending mail with subscripted data take place.
     * @param \CubeTools\CubeCommonBundle\Subscriptions\Conditions\AbstractCondition $condition condition for subscription
     * @return CubeTools\CubeCommonBundle\Subscriptions\MailSubscription object, on which this method was executed
     */
    public function setCondition($condition)
    {
        $this->condition = $condition;
        return $this;
    }

    /**
     * Method adding report (can be more than one). Those reports are only generated, when condition is fulfilled.
     * @param \CubeTools\CubeCommonBundle\Subscriptions\Reports\AbstractReport $report object handling report
     * @param boolean $overwrite if true, previous reports are overwritten with given (by default false)
     * @return CubeTools\CubeCommonBundle\Subscriptions\MailSubscription object, on which this method was executed
     */
    public function addReport($report, $overwrite = false)
    {
        if ($overwrite) {
            $this->reports = array($report);
        } else {
            $this->reports[] = $report;
        }

        return $this;
    }

    /**
     * Setter for object creating message content.
     * @param \CubeTools\CubeCommonBundle\Subscriptions\Message\ContentGenerator $messageContentGenerator object for generating message content
     * @return CubeTools\CubeCommonBundle\Subscriptions\MailSubscription object, on which this method was executed
     */
    public function setMessageContentGenerator($messageContentGenerator)
    {
        $this->messageContentGenerator = $messageContentGenerator;

        return $this;
    }

    /**
     * Setter for object injecting message recipients to existing message object.
     * @param \CubeTools\CubeCommonBundle\Subscriptions\Message\ParticipantsGenerator $messageParticipantsGenerator object for inserting message recipients
     * @return CubeTools\CubeCommonBundle\Subscriptions\MailSubscription object, on which this method was executed
     */
    public function setMessageParticipantsGenerator($messageParticipantsGenerator)
    {
        $this->messageParticipantsGenerator = $messageParticipantsGenerator;

        return $this;
    }

    /**
     * Setter for object responsible for creating email.
     * @param \Swift_Message $messageObject instance of message object
     */
    public function setMessageObject($messageObject = null)
    {
        if (is_null($messageObject)) {
            $messageObject = new \Swift_Message();
        }

        $this->messageObject = $messageObject;
    }

    /**
     * Setter for id of current subscription.
     * @param integer $subscriptionId id of subscription
     */
    public function setSubscriptionId($subscriptionId)
    {
        $this->subscriptionId = $subscriptionId;
    }

    /**
     * Getter for id of current subscription
     * @return integer id of subscription
     */
    public function getSubscriptionId()
    {
        return $this->subscriptionId;
    }

    /**
     * Method returning object for creating message.
     * @return \Swift_Message instance of message object
     */
    public function getMessageObject()
    {
        if (!isset($this->messageObject)) {
            $this->messageObject = new \Swift_Message();
        }

        return $this->messageObject;
    }

    /**
     * Processing subscription. Steps:
     * 1. Check if condition is fulfilled - if yes, prepare output data
     * 2. From condition output data prepare array with reports
     * 3. From array with reports compose mail content
     * 4. Add participants to mail
     * 5. Send email
     */
    public function processSubscription()
    {
        if ($this->condition->isConditionFulfilled()) {
            $reportsArray = array();

            foreach ($this->reports as $report) {
                $reportsArray = array_merge($reportsArray,
                    $report->getReportArray(
                        $this->condition->getOutputData()
                    )
                );
            }

            $this->messageContentGenerator->setMessageObject(
                    $this->getMessageObject()
            );
            $this->messageContentGenerator->setReports($reportsArray);
            $this->messageContentGenerator->setSubject();
            $this->messageContentGenerator->setBody();
            $this->messageContentGenerator->setAttachments();

            $this->messageParticipantsGenerator->setMessageObject(
                    $this->getMessageObject()
            );
            $this->messageParticipantsGenerator->setParticipants($this);
            $messages = $this->messageParticipantsGenerator->createMessagesForRecipients();

            foreach ($messages as $message) {
                $this->swiftMailer->send($message);
            }

            $this->messageContentGenerator->deleteAttachments();
        }
    }
}
