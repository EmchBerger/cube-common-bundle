<?php

namespace CubeTools\CubeCommonBundle\Entity\Common;

/**
 * To be used in entities for notification intervals system.
 */
trait NotificationsIntervalsTrait
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
     * @var string crontab value of execution (like '* * * * *')
     *
     * @ORM\Column(name="cronExpressionExecutionTime", type="string")
     */
    private $cronExpressionExecutionTime;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     *
     * @param string $cronExpressionExecutionTime
     *
     * @return $this
     */
    public function setCronExpressionExecutionTime($cronExpressionExecutionTime)
    {
        $this->cronExpressionExecutionTime = $cronExpressionExecutionTime;

        return $this;
    }

    /**
     * @return string
     */
    public function getCronExpressionExecutionTime()
    {
        return $this->cronExpressionExecutionTime;
    }
}
