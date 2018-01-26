<?php

namespace CubeTools\CubeCommonBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface as User;

/**
 * UserSettings.
 *
 * @ORM\Entity
 * @ORM\Table(
 *      name = "ccbUserSettings",
 *      uniqueConstraints={@ORM\UniqueConstraint(name="ccbOneUserSetting", columns={"relatedUser_id", "type", "settingId"})}
 * )
 */
class UserSettings
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
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Symfony\Component\Security\Core\User\UserInterface")
     * @ORM\JoinColumn(nullable=false)
     */
    private $relatedUser;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=64)
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=128)
     */
    private $settingId;

    /**
     * @var mixed
     *
     * @ORM\Column(type="object")
     */
    private $value;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set type.
     *
     * @param string $type
     *
     * @return UserSettings
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set settingId.
     *
     * @param string $settingId
     *
     * @return UserSettings
     */
    public function setSettingId($settingId)
    {
        $this->settingId = $settingId;

        return $this;
    }

    /**
     * Get settingId.
     *
     * @return string
     */
    public function getSettingId()
    {
        return $this->settingId;
    }

    /**
     * Set value.
     *
     * @param mixed $value
     *
     * @return UserSettings
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value.
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set relatedUser.
     *
     * @param null|User $relatedUser
     *
     * @return UserSettings
     */
    public function setRelatedUser(User $relatedUser = null)
    {
        $this->relatedUser = $relatedUser;

        return $this;
    }

    /**
     * Get relatedUser.
     *
     * @return User
     */
    public function getRelatedUser()
    {
        return $this->relatedUser;
    }
}
