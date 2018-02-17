<?php

namespace CubeTools\CubeCommonBundle\UserSettings;

use Symfony\Component\EventDispatcher\Event;

/**
 * Event for reporting or getting a value.
 */
class ValueEvent extends Event
{
    private $type;
    private $settingId;
    private $value;

    /**
     * @param string $type
     * @param string $settingId
     * @param mixed  $value
     */
    public function __construct($type, $settingId, $value = null)
    {
        $this->type = $type;
        $this->settingId = $settingId;
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getSettingId()
    {
        return $this->settingId;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     *
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }
}
